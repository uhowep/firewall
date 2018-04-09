<?php 

namespace Uhowep\Firewall\Sign;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;


class Sign
{

	// config
	protected $cachePrefix;
	protected $cacheExpire;
    protected $logExpireUnit = 'daily';     // 记录的有效期单位（天）：过了天则清空
    protected $frequency  = [];		// 频率：minutes分钟内times次

    // sign log of groups
    protected $logs = [];

    // sign string 
    protected $sign = '';

    // group string
    protected $group = '';


	public function __construct(string $sign, string $group)
	{
		$this->cachePrefix = config('firewall.cache_key_prefix') ?: 'firewall';
		$this->cacheExpire = config('firewall.cache_expire_time') ?: 1440;
		$this->logExpireUnit = config('firewall.log_expire_unit') ?: 'daily';
		$this->frequency = config('firewall.interface_group.frequency');
		$this->sign = $this->format($sign);
		$this->group = $cachePrefix.'_'.$group;
	}


	/**
	 * get log by sign 
	 * 
	 * @return array $log
     * @var $log : group =>  [
     *         sign =>  [
     *             'times'  =>  0,
     *             'request_at'  =>  [ time1, time2, time3, ...]
     *         ],
     *         ...
     *     ]
	 */
	public function getLog()
	{
		// new log
        $newLog = [
            'times' =>  0,
            'request_at'    =>  [],
        ];
        // get sign logs
        $logs = Cache::get("{$this->group}") ?? [];
        if (!isset($logs[$this->sign]) || empty($logs[$this->sign])) {
        	$logs[$this->sign] = $newLog;
        	Cache::put("{$this->group}", $logs, $this->cacheExpire);
        	return $newLog;
        }

        // is renew by logExpireUnit
        $signLastTime = end($logs[$this->sign]['request_at']);
        $lastDay = date('d', $signLastTime);
        $nowDay = date('d');
        if ( $lastDay!=$nowDay ){
            $logs[$this->sign] = $newLog;
            Cache::put("{$this->group}", $logs, $this->cacheExpire);
        }
        // return
        return $logs[$this->sign];
	}


	/**
	 * write sign log into cache
	 *
	 * @return void
	 */
	public function freshLog()
	{
        // get sign logs
        $logs = Cache::get("{$this->group}");
        // set sign log data
        $signLog = $logs[$this->sign];
        $signLog['times'] += 1;		// increment times for this visit
        if ($signLog['times'] > $this->frequency['times']) {
            array_push($signLog['request_at'], time());
            array_shift($signLog['request_at']);
        } else {
            array_push($signLog['request_at'], time());
        }
        // refresh sign logs
        $logs[$this->sign] = $signLog;
        Cache::put("{$this->group}", $logs, $this->cacheExpire);
	}


	/**
	 * format sign which could be prevented to produce porblem when make the sign into key name just like the dot of ip
	 *
	 * @param string $sign
	 *
	 * @return string $string
	 */
	protected function format(string $sign)
	{
		return md5($sign);
	}

}