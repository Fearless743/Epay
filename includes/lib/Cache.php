<?php
namespace lib;

class Cache {
	private $useRedis = false;
	private $redis = null;
	private $mysqlFallback = false;

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

	public function read($key = 'config') {
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
		global $DB;
		$value = $DB->getColumn("SELECT v FROM pre_cache WHERE k=:key LIMIT 1", [':key'=>$key]);
		return $value;
	}

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
			$result = $DB->getAll("SELECT * FROM pre_config");
			foreach($result as $row){
				$cache[ $row['k'] ] = $row['v'];
			}
			if (!empty($cache)) {
				$_CACHE = $cache;
			}
		}
		return $_CACHE;
	}

	public function update() {
		global $DB;
		$cache = array();
		$result = $DB->getAll("SELECT * FROM pre_config");
		foreach($result as $row){
			$cache[ $row['k'] ] = $row['v'];
		}
		$this->save('config', $cache);
		return $cache;
	}

	public function clear($key = 'config') {
		global $_CACHE;
		if ($this->useRedis && !$this->mysqlFallback) {
			if ($this->redisConnect()) {
				try {
					$this->redis->del($key);
					unset($_CACHE[$key]);
					return true;
				} catch (\Exception $e) {
					$this->mysqlFallback = true;
				}
			}
		}
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
					return true;
				} catch (\Exception $e) {
					$this->mysqlFallback = true;
				}
			}
		}
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
