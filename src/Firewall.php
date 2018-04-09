<?php 

/**
 * 未来扩展方向：
 * 升级: 1、同一接口可以出现在不同的组中 2、对接处理模块（增加返回值的定义，根据值对接不同的处理模块）
 */

namespace Uhowep\Firewall;

use Uhowep\Firewall\Sign\Sign;
use Uhowep\Firewall\Uri\Uri;


class Firewall
{

	protected $nowTime;		// now time
	protected $signLastTime;		// the last time of request_at in sign log
	protected $signEarlyTime;		// the early time of request_at in sign log


	public function __construct()
	{
		$this->nowTime = time();
	}


	/**
	 * authorize the sign could be passed the interface
	 *
	 * @param string $sign
	 * @param string $interface
	 *
	 * @return bool $result
	 */
	public function authorize(string $sign, string $interface)
	{
		// group
		$groupObj = new Uri();
		$group = $groupObj->getInterfaceGroupRelation($interface);
		// return true if no that group limit setting for this interface
		if (is_null($group)) {
			return true;
		}
		// sign
		$signObj = new Sign($sign, $group);
		$signLog = $signObj->getLog();
		// check whether pass
		$result = $this->isThrough($signLog, $group);
		// fresh sign log when it is true
		$result && $signObj->freshLog($this->nowTime);
		// return 
		return $result;
	}
	

	/**
	 * show sign log
	 *
	 * @param string $sign
	 * @param string $group
	 *
	 * @return array $signLog
	 */
	public function getSignLog(string $sign, string $interface)
	{
		// group
		$groupObj = new Uri();
		$group = $groupObj->getInterfaceGroupRelation($interface);
		// sign log
		$signObj = new Sign($sign, $group);
		return $signObj->getLog();
	}


	/**
	 * check sign log could through with group config data
	 * 访问成功才会记录到log
	 * @param array $signLog
	 * @param string $group
	 *
	 * @return bool $result
	 */
	protected function isThrough($signLog, $group)
	{
		$interface = new Uri();
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
				$frequencyMinutes = ($this->nowTime - $this->signEarlyTime) / 60;
                $frequencyMinutes = intval(ceil($frequencyMinutes));
                // return
                return ($frequencyMinutes>$minutes);
			}
		}
		// no limit or pass limit then return true
		return true;
	}

}