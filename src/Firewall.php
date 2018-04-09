<?php 

/**
 * 未来扩展方向：
 * 升级: 1、同一接口可以出现在不同的组中 2、对接处理模块（增加返回值的定义，根据值对接不同的处理模块）
 */

namespace Uhowep\Firewall;

use Uhowep\Firewall\Sign\Sign;
use Uhowep\Firewall\Uri\Interface;


class Firewall
{

	protected $nowTime;		// now time
	protected $signLastTime;		// the last time of request_at in sign log
	protected $signEarlyTime;		// the early time of request_at in sign log


	public function authorize($sign, $interface)
	{
		// group
		$groupObj = new Interface();
		$group = $groupObj->getInterfaceGroupRelation($interface);
		// sign
		$signObj = new Sign($sign, $group);
		$signLog = $signObj->getLog();
		// check whether pass
		$result = $this->isThrough($signLog, $group);
		// fresh sign log when it is true
		$result && $signObj->freshLog();
		// return 
		return $result;
	}
	

	/**
	 * check sign log could through with group config data
	 *
	 * @param array $signLog
	 * @param string $group
	 *
	 * @return bool $result
	 */
	protected function isThrough($signLog, $group)
	{
		$interface = new Interface();
		$interfaceGroup = $interface->getInterfaceGroup($group);
		// set data
		$limit = $interfaceGroup['limit'];
		$interval = $interfaceGroup['interval'];
		$minutes = $interfaceGroup['frequency']['minutes'];
		$times = $interfaceGroup['frequency']['times'];
		// invalidate request time and return true when request time small than 2
		if (count($signLog['request_at']) < 2) {
			return true;
		} else {
			$this->signLastTime = end($signLog['request_at']);
			$this->signEarlyTime = reset($signLog['request_at']);
		}
		// invalidate limit when limit is enable
		if ( $limit && ($signLog['times']>=$limit) ) {
			return false;
		}
		// invalidate interval when interval is enable
		if ( $interval && ($this->nowTime-$this->signLastTime)<=$interval ) {
			return false;
		}
		// invalidate frequency when frequency is enable
		if ( $minutes && $times ) {
			// compare sign log times and group limit
			if ($signLog['times']>=$times) {
				$logFrequency = ($this->nowTime - $this->signEarlyTime) / 60;
                $logFrequency = intval(ceil($logFrequency));
                // return
                return ($logFrequency>$minutes);
			} else {
				// calculate frequency and compare
				$frequency = $times / $minutes;
                $logFrequency = $signLog['times'] / (($this->signLastTime - $this->signEarlyTime)*60);
                $frequency = round($frequency, 0);
                $logFrequency = round($logFrequency, 0);
                // return
                return ($logFrequency<$frequency);
			}
		}
		// no limit or pass limit then return true
		return true;
	}

}