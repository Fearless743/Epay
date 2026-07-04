<?php
namespace lib;

class Cache {
	private $useRedis = false;
	private $redis = null;
	private $mysqlFallback = false;
	// 进程内静态缓存：避免每个 PHP-FPM worker 启动时第一次访问触发 pre_config 全表扫描
	// 单进程内多请求复用，Opcache 共享内存层（APCu）跨进程复用
	private static $processCache = [];
	private static $apcuAvailable = null;

	private static function isApcuAvailable(){
		if(self::$apcuAvailable === null){
			self::$apcuAvailable = function_exists('apcu_enabled') && apcu_enabled();
		}
		return self::$apcuAvailable;
	}

	private static function apcuGet($key){
		if(!self::isApcuAvailable()) return false;
		$success = false;
		$val = apcu_fetch($key, $success);
		return $success ? $val : false;
	}

	private static function apcuSet($key, $val, $ttl=0){
		if(!self::isApcuAvailable()) return false;
		return apcu_store($key, $val, $ttl);
	}

	public function __construct() {
		global $redisconfig;
		$this->useRedis = (!empty($redisconfig['host']) && class_exists('Redis'));
	}

	private function redisConnect() {
		if ($this->redis !== null) {
			return true;
		}
		if (!$this->useRedis) {
			return false;
		}
		global $redisconfig;
		try {
			$redis = new \Redis();
			$redis->connect($redisconfig['host'], $redisconfig['port'], 2);
			if (!empty($redisconfig['auth'])) {
				$redis->auth($redisconfig['auth']);
			}
			if (!empty($redisconfig['database'])) {
				$redis->select((int)$redisconfig['database']);
			}
			$this->redis = $redis;
			return true;
		} catch (\Exception $e) {
			$this->redis = null;
			$this->useRedis = false;
			return false;
		}
	}

	public function get($key) {
		global $_CACHE;
		if ($this->useRedis && !$this->mysqlFallback) {
			if ($this->redisConnect()) {
				try {
					$val = $this->redis->get($key);
					if ($val !== false && $val !== null) {
						return @unserialize($val);
					}
				} catch (\Exception $e) {
					$this->mysqlFallback = true;
				}
			}
		}
		return $_CACHE[$key] ?? null;
	}

	// read() 返回 pre_cache.v 的原始值，与 MySQL 保持一致
	public function read($key = 'config') {
		if ($this->useRedis && !$this->mysqlFallback) {
			if ($this->redisConnect()) {
				try {
					$val = $this->redis->get($key);
					if ($val !== false && $val !== null) {
						return $val;
					}
				} catch (\Exception $e) {
					$this->mysqlFallback = true;
				}
			}
		}
		global $DB;
		return $DB->getColumn("SELECT v FROM pre_cache WHERE k=:key LIMIT 1", [':key'=>$key]);
	}

	// save() 存原始序列化串到 Redis，保持与 MySQL REPLACE INTO 一致的存储格式
	public function save($key, $value, $expire=0) {
		global $DB, $_CACHE;
		$serialized = is_array($value) ? serialize($value) : $value;

		if ($this->useRedis && !$this->mysqlFallback) {
			if ($this->redisConnect()) {
				try {
					$this->redis->set($key, $serialized);
					if ($expire) {
						$this->redis->expire($key, $expire);
					}
					if ($key === 'config') {
						$_CACHE[$key] = $value;
					}
					return true;
				} catch (\Exception $e) {
					$this->mysqlFallback = true;
				}
			}
		}

		if($expire) $expire = time() + $expire;
		return $DB->exec("REPLACE INTO pre_cache VALUES (:key, :value, :expire)", [':key'=>$key, ':value'=>$serialized, ':expire'=>$expire]);
	}

	public function pre_fetch(){
		global $_CACHE;
		$_CACHE = array();

		// 1. 进程内静态缓存（最快）
		if(isset(self::$processCache['config']) && is_array(self::$processCache['config'])){
			$_CACHE = self::$processCache['config'];
			return $_CACHE;
		}

		// 2. APCu 共享内存（跨进程复用，避免每个 FPM worker 都查 DB）
		$apcu_val = self::apcuGet('epay_config');
		if($apcu_val !== false && is_array($apcu_val)){
			$_CACHE = $apcu_val;
			self::$processCache['config'] = $apcu_val;
			return $_CACHE;
		}

		if ($this->useRedis && !$this->mysqlFallback) {
			if ($this->redisConnect()) {
				try {
					$val = $this->redis->get('config');
					if ($val !== false && $val !== null) {
						$_CACHE = @unserialize($val);
					}
				} catch (\Exception $e) {
					$this->mysqlFallback = true;
				}
			}
		}

		if (empty($_CACHE['version'])) {
			global $DB;
			$cache = array();
			$result = $DB->getAll("SELECT k, v FROM pre_config");
			foreach($result as $row){
				$cache[ $row['k'] ] = $row['v'];
			}
			if (!empty($cache)) {
				$_CACHE = $cache;
			}
		}
		// 写入各级缓存
		self::$processCache['config'] = $_CACHE;
		self::apcuSet('epay_config', $_CACHE, 60);
		return $_CACHE;
	}

	public function update() {
		global $DB;
		$cache = array();
		$result = $DB->getAll("SELECT k, v FROM pre_config");
		foreach($result as $row){
			$cache[ $row['k'] ] = $row['v'];
		}
		$this->save('config', $cache);
		self::$processCache['config'] = $cache;
		self::apcuSet('epay_config', $cache, 60);
		return $cache;
	}

	public function clear($key = 'config') {
		global $_CACHE;
		if ($this->useRedis && !$this->mysqlFallback) {
			if ($this->redisConnect()) {
				try {
					$this->redis->del($key);
					unset($_CACHE[$key]);
					unset(self::$processCache[$key]);
					self::apcuSet('epay_'.$key, false, 1);
					return true;
				} catch (\Exception $e) {
					$this->mysqlFallback = true;
				}
			}
		}
		unset(self::$processCache[$key]);
		self::apcuSet('epay_config', false, 1);
		global $DB;
		return $DB->exec("UPDATE pre_cache SET v='' WHERE k=:key", [':key'=>$key]);
	}

	public function delete($key) {
		global $_CACHE;
		if ($this->useRedis && !$this->mysqlFallback) {
			if ($this->redisConnect()) {
				try {
					$this->redis->del($key);
					unset($_CACHE[$key]);
					unset(self::$processCache[$key]);
					return true;
				} catch (\Exception $e) {
					$this->mysqlFallback = true;
				}
			}
		}
		unset(self::$processCache[$key]);
		global $DB;
		return $DB->exec("DELETE FROM pre_cache WHERE k=:key", [':key'=>$key]);
	}

	public function clean() {
		if ($this->useRedis) {
			return true;
		}
		global $DB;
		$DB->exec("DELETE FROM pre_cache WHERE expire>0 AND expire<'".time()."'");
		return true;
	}
}
