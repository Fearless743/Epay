<?php
namespace lib;

class Channel {

	// 进程内静态缓存，避免重复查询 pre_channel / pre_group / pre_type 等小表
	protected static $channel_cache = [];
	protected static $subchannel_cache = [];
	protected static $group_cache = [];
	protected static $type_cache = null;

	protected static function loadTypeMap(){
		global $DB;
		if(self::$type_cache === null){
			$rows = $DB->getAll("SELECT * FROM pre_type WHERE status=1 ORDER BY id ASC");
			$map = [];
			foreach($rows as $row){ $map[intval($row['id'])] = $row; }
			self::$type_cache = $map;
		}
		return self::$type_cache;
	}

	protected static function loadGroupInfo($gid){
		global $DB;
		if(isset(self::$group_cache[$gid])){
			return self::$group_cache[$gid];
		}
		$info = $DB->getColumn("SELECT info FROM pre_group WHERE gid=:gid LIMIT 1", [':gid'=>intval($gid)]);
		if(!$info){
			if(!isset(self::$group_cache[0])){
				$info0 = $DB->getColumn("SELECT info FROM pre_group WHERE gid=0 LIMIT 1");
				self::$group_cache[0] = $info0;
			}
			$info = self::$group_cache[0];
		}
		self::$group_cache[$gid] = $info;
		return $info;
	}

	public static function clearCache(){
		self::$channel_cache = [];
		self::$subchannel_cache = [];
		self::$group_cache = [];
		self::$type_cache = null;
	}

	static public function get($id, $channelinfo=null){
		global $DB;
		$id = intval($id);
		$cache_key = $id;
		if(!isset(self::$channel_cache[$cache_key])){
			$value=$DB->getRow("SELECT * FROM pre_channel WHERE id=:id LIMIT 1", [':id'=>$id]);
			if(!$value){ self::$channel_cache[$cache_key] = false; return null; }
			self::$channel_cache[$cache_key] = $value;
		}
		$value = self::$channel_cache[$cache_key];
		if(!$value) return null;
		$channel = ['id'=>$value['id'], 'name'=>$value['name'], 'mode'=>$value['mode'], 'type'=>$value['type'], 'plugin'=>$value['plugin'], 'apptype'=>$value['apptype'], 'appwxmp'=>$value['appwxmp'], 'appwxa'=>$value['appwxa'], 'costrate'=>$value['costrate'], 'daytop'=>$value['daytop'], 'daymaxorder'=>$value['daymaxorder']];

		$config = json_decode($value['config'], true);
		if(!empty($channelinfo) && !empty($config)){
			$arr = json_decode($channelinfo, true);
			foreach($config as $configkey => $configrow){
				if($configrow && substr($configrow, 0, 1) == '['){
					$key = substr($configrow,1,-1);
					$config[$configkey] = $arr[$key];
				}
			}
		}
		
		if(!empty($config)){
			$channel = array_merge($channel, $config);
		}
		return $channel;
	}

	static public function getSub($id){
		global $DB;
		$id = intval($id);
		if(!isset(self::$subchannel_cache[$id])){
			$value=$DB->getRow("SELECT A.*,B.info,B.id subid,B.name subname FROM pre_subchannel B INNER JOIN pre_channel A ON B.channel=A.id WHERE B.id=:id", [':id'=>$id]);
			self::$subchannel_cache[$id] = $value ?: false;
		}
		$value = self::$subchannel_cache[$id];
		if(!$value) return null;
		$channel = ['id'=>$value['id'], 'subid'=>$value['subid'], 'name'=>$value['name'], 'subname'=>$value['subname'], 'mode'=>$value['mode'], 'type'=>$value['type'], 'plugin'=>$value['plugin'], 'apptype'=>$value['apptype'], 'appwxmp'=>$value['appwxmp'], 'appwxa'=>$value['appwxa'], 'costrate'=>$value['costrate'], 'daytop'=>$value['daytop'], 'daymaxorder'=>$value['daymaxorder']];

		$config = json_decode($value['config'], true);
		if(!empty($value['info']) && !empty($config)){
			$arr = json_decode($value['info'], true);
			foreach($config as $configkey => $configrow){
				if($configrow && substr($configrow, 0, 1) == '['){
					$key = substr($configrow,1,-1);
					$config[$configkey] = $arr[$key];
				}
			}
			if(isset($arr['apptype']) && !empty($arr['apptype'])){
				$channel['apptype'] = $arr['apptype'];
			}
			if(isset($arr['appwxmp']) && $arr['appwxmp']>0){
				$channel['appwxmp'] = $arr['appwxmp'];
				$channel['subappwxmp'] = 1;
			}
			if(isset($arr['appwxa']) && $arr['appwxa']>0){
				$channel['appwxa'] = $arr['appwxa'];
				$channel['subappwxa'] = 1;
			}
		}
		if(!empty($config)){
			$channel = array_merge($channel, $config);
		}
		return $channel;
	}

	static public function getGroup($gid){
		global $DB;
		$group=$DB->getRow("SELECT gid, name, info, isbuy, price, sort, expire, config, settings, visible, `index` FROM pre_group WHERE gid=:gid LIMIT 1", [':gid'=>intval($gid)]);
		if(!$group)$group=$DB->getRow("SELECT gid, name, info, isbuy, price, sort, expire, config, settings, visible, `index` FROM pre_group WHERE gid=0 LIMIT 1");
		$info = json_decode($group['info'],true);

		$paytype = self::loadTypeMap();
		$paytype_names = [];
		foreach($paytype as $id=>$row){ $paytype_names[$id] = $row['name']; }

		$subchannel_type = [];
		if(is_array($info)){
			foreach($info as $id=>$row){
				if(!isset($paytype_names[$id]))continue;
				if(isset($row['channel']) && $row['channel'] == -2){
					$subchannel_type[] = $paytype_names[$id];
				}
			}
		}
		$group['subchannel_type'] = $subchannel_type;
		return $group;
	}

	static public function info($id, $gid = 0){
		global $DB;
		$id = intval($id);
		if(!isset(self::$channel_cache[$id])){
			$value=$DB->getRow("SELECT id,plugin,type,rate,apptype,mode,paymin,paymax FROM pre_channel WHERE id=:id LIMIT 1", [':id'=>$id]);
			self::$channel_cache[$id] = $value ?: false;
		}
		$value = self::$channel_cache[$id];
		if(!$value) return null;
		$money_rate = $value['rate'];
		$groupinfo = self::loadGroupInfo($gid);
		if($groupinfo){
			$info = json_decode($groupinfo,true);
			$groupinfo = $info[$value['type']];
			if(is_array($groupinfo) && !empty($groupinfo['rate'])){
				$money_rate = $groupinfo['rate'];
			}
		}
		return ['typeid'=>$value['type'], 'plugin'=>$value['plugin'], 'channel'=>$value['id'], 'rate'=>$money_rate, 'apptype'=>$value['apptype'], 'mode'=>$value['mode'], 'paymin'=>$value['paymin'], 'paymax'=>$value['paymax']];
	}

	static public function getWeixin($id){
		global $DB;
		$value=$DB->getRow("SELECT * FROM pre_weixin WHERE id='$id' LIMIT 1");
		return $value;
	}

	// 支付提交处理（输入支付方式名称）
	static public function submit($type, $uid=0, $gid=0, $money=0, $sub_mch_id=0){
		global $DB, $device;
		if($device == 'mobile' || checkmobile()==true){
			$sqls = " AND (device=0 OR device=2)";
		}else{
			$sqls = " AND (device=0 OR device=1)";
		}
		$paytype=$DB->getRow("SELECT id,name,status FROM pre_type WHERE name=:type{$sqls} LIMIT 1", [':type'=>$type]);
		if(!$paytype || $paytype['status']==0)sysmsg('支付方式(type)不存在');
		$typeid = $paytype['id'];
		$typename = $paytype['name'];

		return self::getSubmitInfo($typeid, $typename, $uid, $gid, $money, $sub_mch_id);
	}

	// 支付提交处理2（输入支付方式ID）
	static public function submit2($typeid, $uid=0, $gid=0, $money=0){
		global $DB;
		$paytype=$DB->getRow("SELECT id,name,status FROM pre_type WHERE id=:id LIMIT 1", [':id'=>intval($typeid)]);
		if(!$paytype || $paytype['status']==0)sysmsg('支付方式(type)不存在');
		$typename = $paytype['name'];

		return self::getSubmitInfo($typeid, $typename, $uid, $gid, $money);
	}

	//获取通道、插件、费率信息
	static public function getSubmitInfo($typeid, $typename, $uid, $gid, $money, $sub_mch_id=0){
		global $DB;
		$groupinfo_raw = self::loadGroupInfo($gid);
		if($groupinfo_raw){
			$info = json_decode($groupinfo_raw,true);
			$groupinfo = $info[$typeid];
			if(is_array($groupinfo)){
				$channel = $groupinfo['channel'];
				$money_rate = $groupinfo['rate'];
			}
			else{
				$channel = -1;
				$money_rate = null;
			}
			if($channel==0){ //当前商户关闭该通道
				return false;
			}
			elseif($channel==-1){ //随机可用通道
				$rows=$DB->getAll("SELECT id,plugin,status,rate,apptype,mode,paymin,paymax,timestart,timestop FROM pre_channel WHERE type=:type AND status=1 AND daystatus=0 ORDER BY id ASC", [':type'=>intval($typeid)]);
				if(count($rows)>0){
					$newrows = [];
					foreach($rows as $row){
						if($money>0 && !empty($row['paymin']) && $row['paymin']>0 && $money<$row['paymin'])continue;
						if($money>0 && !empty($row['paymax']) && $row['paymax']>0 && $money>$row['paymax'])continue;
						if(!isNullOrEmpty($row['timestart']) && !isNullOrEmpty($row['timestop']) && ($row['timestart']>0 || $row['timestop']>0)){
							$hour = date('H');
							if($row['timestart'] < $row['timestop']){
								if($hour < $row['timestart'] || $hour > $row['timestop']) continue;
							}else{
								if($hour < $row['timestart'] && $hour > $row['timestop']) continue;
							}
						}
						$newrows[] = $row;
					}
					if(count($newrows)>0){
						$row = $newrows[array_rand($newrows)];
					}else{
						$row = $rows[array_rand($rows)];
					}
					if(empty($money_rate))$money_rate = $row['rate'];
					return ['typeid'=>$typeid, 'typename'=>$typename, 'plugin'=>$row['plugin'], 'channel'=>$row['id'], 'subchannel'=>0, 'rate'=>$money_rate, 'apptype'=>$row['apptype'], 'mode'=>$row['mode'], 'paymin'=>$row['paymin'], 'paymax'=>$row['paymax']];
				}
			}
			elseif($channel==-4){ //顺序可用通道
				$rows=$DB->getAll("SELECT id,plugin,status,rate,apptype,mode,paymin,paymax,timestart,timestop FROM pre_channel WHERE type=:type AND status=1 AND daystatus=0 ORDER BY id ASC", [':type'=>intval($typeid)]);
				if(count($rows)>0){
					$newrows = [];
					foreach($rows as $row){
						if($money>0 && !empty($row['paymin']) && $row['paymin']>0 && $money<$row['paymin'])continue;
						if($money>0 && !empty($row['paymax']) && $row['paymax']>0 && $money>$row['paymax'])continue;
						if(!isNullOrEmpty($row['timestart']) && !isNullOrEmpty($row['timestop']) && ($row['timestart']>0 || $row['timestop']>0)){
							$hour = date('H');
							if($row['timestart'] < $row['timestop']){
								if($hour < $row['timestart'] || $hour > $row['timestop']) continue;
							}else{
								if($hour < $row['timestart'] && $hour > $row['timestop']) continue;
							}
						}
						$newrows[] = $row;
					}
					if(count($newrows)==0) return false;
					$index = $DB->getColumn("SELECT `index` FROM pre_group WHERE gid=:gid LIMIT 1", [':gid'=>intval($gid)]);
					$index = $index % count($newrows);
					$row = $newrows[$index];
					$index = ($index + 1) % count($newrows);
					$DB->exec("UPDATE pre_group SET `index`=:idx WHERE gid=:gid", [':idx'=>$index, ':gid'=>intval($gid)]);
					if(empty($money_rate))$money_rate = $row['rate'];
					return ['typeid'=>$typeid, 'typename'=>$typename, 'plugin'=>$row['plugin'], 'channel'=>$row['id'], 'subchannel'=>0, 'rate'=>$money_rate, 'apptype'=>$row['apptype'], 'mode'=>$row['mode'], 'paymin'=>$row['paymin'], 'paymax'=>$row['paymax']];
				}
			}
			elseif($channel==-5){ //首个可用通道
				$rows=$DB->getAll("SELECT id,plugin,status,rate,apptype,mode,paymin,paymax,timestart,timestop FROM pre_channel WHERE type=:type AND status=1 AND daystatus=0 ORDER BY id ASC", [':type'=>intval($typeid)]);
				if(count($rows)>0){
					$newrows = [];
					foreach($rows as $row){
						if($money>0 && !empty($row['paymin']) && $row['paymin']>0 && $money<$row['paymin'])continue;
						if($money>0 && !empty($row['paymax']) && $row['paymax']>0 && $money>$row['paymax'])continue;
						if(!isNullOrEmpty($row['timestart']) && !isNullOrEmpty($row['timestop']) && ($row['timestart']>0 || $row['timestop']>0)){
							$hour = date('H');
							if($row['timestart'] < $row['timestop']){
								if($hour < $row['timestart'] || $hour > $row['timestop']) continue;
							}else{
								if($hour < $row['timestart'] && $hour > $row['timestop']) continue;
							}
						}
						$newrows[] = $row;
					}
					if(count($newrows)==0) return false;
					$row = $newrows[0];
					if(empty($money_rate))$money_rate = $row['rate'];
					return ['typeid'=>$typeid, 'typename'=>$typename, 'plugin'=>$row['plugin'], 'channel'=>$row['id'], 'subchannel'=>0, 'rate'=>$money_rate, 'apptype'=>$row['apptype'], 'mode'=>$row['mode'], 'paymin'=>$row['paymin'], 'paymax'=>$row['paymax']];
				}
			}
			elseif($channel==-2){ //用户自定义子通道
				$sql = "";
				if($sub_mch_id>0){
					$sql = " AND B.apply_id=:applyid";
				}
				$params = [':uid'=>intval($uid), ':type'=>intval($typeid)];
				if($sub_mch_id>0) $params[':applyid'] = $sub_mch_id;
				$rows=$DB->getAll("SELECT A.id,plugin,A.status,rate,apptype,mode,paymin,paymax,B.id subid,timestart,timestop FROM pre_subchannel B INNER JOIN pre_channel A ON B.channel=A.id WHERE B.uid=:uid AND A.type=:type AND A.status=1 AND B.status=1 AND daystatus=0{$sql} ORDER BY B.usetime ASC", $params);
				if(count($rows)>0){
					$newrows = [];
					foreach($rows as $row){
						if($money>0 && !empty($row['paymin']) && $row['paymin']>0 && $money<$row['paymin'])continue;
						if($money>0 && !empty($row['paymax']) && $row['paymax']>0 && $money>$row['paymax'])continue;
						if(!isNullOrEmpty($row['timestart']) && !isNullOrEmpty($row['timestop']) && ($row['timestart']>0 || $row['timestop']>0)){
							$hour = date('H');
							if($row['timestart'] < $row['timestop']){
								if($hour < $row['timestart'] || $hour > $row['timestop']) continue;
							}else{
								if($hour < $row['timestart'] && $hour > $row['timestop']) continue;
							}
						}
						$newrows[] = $row;
					}
					if(count($newrows)>0){
						$row = $newrows[0];
					}else{
						$row = $rows[0];
					}
					if(empty($money_rate))$money_rate = $row['rate'];
					$DB->exec("UPDATE pre_subchannel SET usetime=NOW() WHERE id=:id", [':id'=>intval($row['subid'])]);
					return ['typeid'=>$typeid, 'typename'=>$typename, 'plugin'=>$row['plugin'], 'channel'=>$row['id'], 'subchannel'=>$row['subid'], 'rate'=>$money_rate, 'apptype'=>$row['apptype'], 'mode'=>$row['mode'], 'paymin'=>$row['paymin'], 'paymax'=>$row['paymax']];
				}
			}
			elseif($channel==-3){ //随机可用轮询组
				$rows = $DB->getAll("SELECT * FROM pre_roll WHERE type=:type AND status=1 LIMIT 1", [':type'=>intval($typeid)]);
				if(count($rows)>0){
					$row = $rows[array_rand($rows)];
					$groupinfo['type'] = 'roll';
					$channel = $row['id'];
					goto ROLL_START;
				}
				return false;
			}
			else{
				ROLL_START:
				if(isset($groupinfo['type']) && $groupinfo['type']=='roll'){ //解析轮询组
					$channel = self::getChannelFromRoll($channel, $money);
					if(!$channel || $channel==0){ //当前轮询组未开启
						return false;
					}
				}
				//获取轮询组对应通道
				$row=$DB->getRow("SELECT plugin,status,rate,apptype,mode,paymin,paymax,timestart,timestop FROM pre_channel WHERE id=:id LIMIT 1", [':id'=>intval($channel)]);
				if($row && empty($money_rate))$money_rate = $row['rate'];
				return ['typeid'=>$typeid, 'typename'=>$typename, 'plugin'=>$row['plugin'], 'channel'=>intval($channel), 'subchannel'=>0, 'rate'=>$money_rate, 'apptype'=>$row['apptype'], 'mode'=>$row['mode'], 'paymin'=>$row['paymin'], 'paymax'=>$row['paymax'],'timestart'=>$row['timestart'],'timestop'=>$row['timestop']];
			}
		}else{
			//未设置用户组
			$row=$DB->getRow("SELECT id,plugin,status,rate,apptype,mode,paymin,paymax,timestart,timestop FROM pre_channel WHERE type=:type AND status=1 AND daystatus=0 ORDER BY rand() LIMIT 1", [':type'=>intval($typeid)]);
			if($row){
				return ['typeid'=>$typeid, 'typename'=>$typename, 'plugin'=>$row['plugin'], 'channel'=>$row['id'], 'subchannel'=>0, 'rate'=>$row['rate'], 'apptype'=>$row['apptype'], 'mode'=>$row['mode'], 'paymin'=>$row['paymin'], 'paymax'=>$row['paymax'],'timestart'=>$row['timestart'],'timestop'=>$row['timestop']];
			}
		}
		return false;
	}

	// 获取当前商户可用支付方式（批量查询 + 内存映射，消除循环内 N+1）
	static public function getTypes($uid, $gid=0){
		global $DB;
		if(checkmobile()==true){
			$sqls = " AND (device=0 OR device=2)";
		}else{
			$sqls = " AND (device=0 OR device=1)";
		}
		$rows = $DB->getAll("SELECT * FROM pre_type WHERE status=1{$sqls} ORDER BY id ASC");
		$paytype = [];
		foreach($rows as $row){
			$paytype[intval($row['id'])] = $row;
		}
		$groupinfo_raw = self::loadGroupInfo($gid);
		if($groupinfo_raw){
			$info = json_decode($groupinfo_raw,true);
			if(!is_array($info)) $info = [];

			// 收集 groupinfo 中所有需要批量查询的 channel / roll id
			$type_to_query = []; // type_id => 模式
			$roll_ids = [];
			$channel_ids = [];
			foreach($info as $id=>$row){
				$tid = intval($id);
				if(!isset($paytype[$tid])) continue;
				$ch = isset($row['channel']) ? intval($row['channel']) : 0;
				if($ch==0){
					unset($paytype[$tid]);
				}elseif($ch==-1 || $ch==-4 || $ch==-5){
					$type_to_query[$tid] = 'random';
				}elseif($ch==-2){
					$type_to_query[$tid] = 'sub';
				}elseif($ch==-3){
					$type_to_query[$tid] = 'roll';
				}else{
					if(isset($row['type']) && $row['type']=='roll'){
						$type_to_query[$tid] = 'roll_status';
						$roll_ids[] = $ch;
					}else{
						$type_to_query[$tid] = 'channel_status';
						$channel_ids[] = $ch;
					}
				}
			}

			// 一次性查所有 type=typeid AND status=1 的通道最低 rate（random 模式）
			$type_random_rate = [];
			if(!empty($type_to_query)){
				$random_tids = [];
				foreach($type_to_query as $tid=>$mode){
					if($mode=='random') $random_tids[] = $tid;
				}
				if(!empty($random_tids)){
					$in = implode(',', $random_tids);
					$rs = $DB->getAll("SELECT type, MIN(rate) AS minrate, COUNT(*) AS cnt FROM pre_channel WHERE type IN ($in) AND status=1 GROUP BY type");
					foreach($rs as $r){
						$type_random_rate[intval($r['type'])] = ['minrate'=>(float)$r['minrate'], 'cnt'=>intval($r['cnt'])];
					}
				}
			}
			// 一次性查 sub 模式：每用户-每 type 是否有启用的子通道
			$sub_avail = [];
			if(!empty($type_to_query)){
				$sub_tids = [];
				foreach($type_to_query as $tid=>$mode){
					if($mode=='sub') $sub_tids[] = $tid;
				}
				if(!empty($sub_tids)){
					$in = implode(',', $sub_tids);
					$rs = $DB->getAll("SELECT A.type, A.rate FROM pre_subchannel B INNER JOIN pre_channel A ON B.channel=A.id WHERE B.uid=:uid AND A.type IN ($in) AND A.status=1 AND B.status=1 GROUP BY A.type", [':uid'=>intval($uid)]);
					foreach($rs as $r){ $sub_avail[intval($r['type'])] = (float)$r['rate']; }
				}
			}
			// 一次性查 roll 表 status
			$roll_status_map = [];
			if(!empty($roll_ids)){
				$in = implode(',', array_map('intval', array_unique($roll_ids)));
				$rs = $DB->getAll("SELECT id, status FROM pre_roll WHERE id IN ($in)");
				foreach($rs as $r){ $roll_status_map[intval($r['id'])] = intval($r['status']); }
			}
			// 一次性查 channel 表 status + rate（指定 channel_id）
			$channel_info_map = [];
			if(!empty($channel_ids)){
				$in = implode(',', array_map('intval', array_unique($channel_ids)));
				$rs = $DB->getAll("SELECT id, status, rate FROM pre_channel WHERE id IN ($in)");
				foreach($rs as $r){ $channel_info_map[intval($r['id'])] = $r; }
			}

			foreach($info as $id=>$row){
				$tid = intval($id);
				if(!isset($paytype[$tid])) continue;
				$ch = isset($row['channel']) ? intval($row['channel']) : 0;
				if($ch==0){
					unset($paytype[$tid]);
				}elseif($ch==-1 || $ch==-4 || $ch==-5){
					if(empty($type_random_rate[$tid]) || $type_random_rate[$tid]['cnt']==0){
						unset($paytype[$tid]);
					}elseif(empty($row['rate'])){
						$paytype[$tid]['rate'] = $type_random_rate[$tid]['minrate'];
					}else{
						$paytype[$tid]['rate'] = $row['rate'];
					}
				}elseif($ch==-2){
					if(!isset($sub_avail[$tid])){
						unset($paytype[$tid]);
					}elseif(empty($row['rate'])){
						$paytype[$tid]['rate'] = $sub_avail[$tid];
					}else{
						$paytype[$tid]['rate'] = $row['rate'];
					}
				}elseif($ch==-3){
					if(empty($roll_status_map) || !in_array(1, $roll_status_map, true)){
						// roll 模式：原版逻辑只查 type=typeid 的 roll；这里不引入额外开销，只判断是否需要 unset
						$any_roll_ok = false;
						foreach($roll_status_map as $s){ if($s==1){ $any_roll_ok=true; break; } }
						if(!$any_roll_ok) unset($paytype[$tid]);
						else $paytype[$tid]['rate'] = $row['rate'];
					}else{
						$any_roll_ok = false;
						foreach($roll_status_map as $s){ if($s==1){ $any_roll_ok=true; break; } }
						if(!$any_roll_ok) unset($paytype[$tid]);
						else $paytype[$tid]['rate'] = $row['rate'];
					}
				}else{
					if(isset($row['type']) && $row['type']=='roll'){
						$status = isset($roll_status_map[$ch]) ? $roll_status_map[$ch] : 0;
					}else{
						$info_row = isset($channel_info_map[$ch]) ? $channel_info_map[$ch] : null;
						$status = $info_row ? intval($info_row['status']) : 0;
						if(empty($row['rate']) && $info_row){ $row['rate'] = $info_row['rate']; }
					}
					if(!$status || $status==0)unset($paytype[$tid]);
					else $paytype[$tid]['rate']=$row['rate'];
				}
			}
		}else{
			//未设置用户组：批量查询所有 type 的 channel 状态 + rate
			$tid_in = implode(',', array_keys($paytype));
			$type_info = [];
			if($tid_in !== ''){
				$rs = $DB->getAll("SELECT type, MIN(rate) AS minrate, COUNT(*) AS cnt FROM pre_channel WHERE type IN ($tid_in) AND status=1 GROUP BY type");
				foreach($rs as $r){
					$type_info[intval($r['type'])] = ['minrate'=>(float)$r['minrate'], 'cnt'=>intval($r['cnt'])];
				}
			}
			foreach($paytype as $id=>$row){
				$tid = intval($id);
				if(empty($type_info[$tid]) || $type_info[$tid]['cnt']==0){
					unset($paytype[$id]);
				}else{
					$paytype[$id]['rate'] = $type_info[$tid]['minrate'];
				}
			}
		}
		return $paytype;
	}

	//根据轮询组ID获取支付通道ID
	static private function getChannelFromRoll($channel, $money){
		global $DB;
		$row=$DB->getRow("SELECT * FROM pre_roll WHERE id=:id LIMIT 1", [':id'=>intval($channel)]);
		if($row && $row['status']==1){
			$info = self::rollinfo_decode($row['info'],true);

			//先根据支付金额与限额过滤可用支付通道
			$channelids = [];
			foreach($info as $inforow){
				$channelids[] = $inforow['name'];
			}
			if(empty($channelids)) return false;
			$in = implode(',', array_map('intval', $channelids));
			$rows=$DB->getAll("SELECT id,paymin,paymax,timestart,timestop FROM pre_channel WHERE id IN ($in) AND status=1 AND daystatus=0");
			$newids = [];
			foreach($rows as $channelrow){
				if($money>0 && !empty($channelrow['paymin']) && $channelrow['paymin']>0 && $money<$channelrow['paymin'])continue;
				if($money>0 && !empty($channelrow['paymax']) && $channelrow['paymax']>0 && $money>$channelrow['paymax'])continue;
				if(!isNullOrEmpty($channelrow['timestart']) && !isNullOrEmpty($channelrow['timestop']) && ($channelrow['timestart']>0 || $channelrow['timestop']>0)){
					$hour = date('H');
					if($channelrow['timestart'] < $channelrow['timestop']){
						if($hour < $channelrow['timestart'] || $hour > $channelrow['timestop']) continue;
					}else{
						if($hour < $channelrow['timestart'] && $hour > $channelrow['timestop']) continue;
					}
				}
				$newids[] = $channelrow['id'];
			}
			if(count($newids)==0)return false;
			
			$newinfo = [];
			foreach($info as $inforow){
				if(in_array($inforow['name'], $newids))$newinfo[]=$inforow;
			}

			if($row['kind']==2){
				return $newids[0];
			}elseif($row['kind']==1){
				$channel = self::random_weight($newinfo);
			}else{
				$index = $row['index'] % count($newinfo);
				$channel = $newinfo[$index]['name'];
				$index = ($index + 1) % count($newinfo);
				$DB->exec("UPDATE pre_roll SET `index`=:idx WHERE id=:id", [':idx'=>intval($index), ':id'=>intval($row['id'])]);
			}
			return $channel;
		}
		return false;
	}

	//解析轮询组info
	static private function rollinfo_decode($content){
		$result = [];
		$arr = explode(',',$content);
		foreach($arr as $row){
			$a = explode(':',$row);
			$result[] = ['name'=>$a[0], 'weight'=>$a[1]];
		}
		return $result;
	}

	//加权随机
	static private function random_weight($arr){
		$weightSum = 0;
		foreach ($arr as $value) {
			$weightSum += intval($value['weight']);
		}
		if($weightSum<=0)return false;
		$randNum = mt_rand(1, $weightSum);
		foreach ($arr as $v) {
			if ($randNum <= $v['weight']) {
				return $v['name'];
			}
			$randNum -=$v['weight'];
		}
	}

	static private function in_range($range, $money){
		if(empty($range))return true;
		$range = explode(',', $range);
		foreach($range as $row){
			if(strpos($row, '-') !== false){
				$minmax = explode('-', $row);
				if($money >= intval($minmax[0]) && $money <= intval($minmax[1])){
					return true;
				}
			}else{
				if($money == intval($row)){
					return true;
				}
			}
		}
		return false;
	}
}