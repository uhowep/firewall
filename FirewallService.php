<?php

/**
 * 未来扩展方向：
 * 拆分: 1、配置模块 2、检测判断模块  3、路由解析模块 4、驱动模块
 * 升级: 1、同一接口可以出现在不同的组中 2、对接处理模块（增加返回值的定义，根据值对接不同的处理模块）
 */


namespace App\Services;

use Illuminate\Support\Facades\Cache;

class FirewallService
{

    protected $cache;

    const CONFIG_FILE_NAME = 'firewall';

    /**
     * 接口组的寻址路由
     * @var interfaceBelongGroup - [interface=>group]
     */
    protected $interfaceBelongGroup = [];

    protected $configInterfaceGroup = [];   // 配置文件中的接口组
    protected $cacheKeyPrefix;   // prefix string of group name for cache key

    protected $nowTime;             // now time
    protected $signLastTime;        // the last time of request_at in sign log
    protected $signEarlyTime;       // the early time of request_at in sign log

    protected $logExpireUnit = 'daily';     // 记录的有效期单位（天）：过了天则清空
    protected $cacheExpire;  // cache expire time by minutes


    /**
     * config parameter
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        $this->cacheExpire = 24*60;
        $this->nowTime = time();
        // set the protected variable of parameter
        $this->loadParameterConfig();
        // parse group router of config to set interface belong group variable
        $this->setInterfaceBelongGroup($this->configInterfaceGroup);
    }


    /**
     * set config of parameter variable
     */
    protected function loadParameterConfig()
    {
        // read config from config file
        $configFile = config(self::CONFIG_FILE_NAME);
        // set config parameter variable
        $this->configInterfaceGroup = $configFile['interface_group'];
        $this->groupNamePrefix = $configFile['cache_key_prefix'];
    }


    /**
     * parse group router of config into group router of this service mode
     * @param configInterfaceGroup - array - group router config
     * @return void
     */
    protected function setInterfaceBelongGroup(array $configInterfaceGroup)
    {
        // extract to [interface=>group] from group router config
        foreach ($configInterfaceGroup as $group => $value) {
            foreach ($value['interface'] as $interface) {
                $this->interfaceBelongGroup[$interface] = $group;
            }
        }
    }


    /**
     * authorize through firewall
     * @param signStr - string - 用以标志信息的值（如ip地址，用户id等）
     * @param interface - string - 访问的接口
     * @return result - bool - 通过/不通过(true/false)
     */
    public function authorize($signStr, $interface)
    {
        if (is_null($signStr) || empty($signStr)) {
            return true;
        }
        if (is_null($interface) || empty($interface)) {
            return true;
        }
        // transfer sign string to prevent to produce problem when make the sign into key name
        $sign = $this->formatSign($signStr);
        // get the group name by interface
        $group = $this->transferInterfaceToGroup($interface);
        if (is_null($group)) {
            return true;
        }
        // get log by sign in group
        $log = $this->logBySign($sign, $group);
        // dump($log);     // 历史记录（不含本次）
        // check whether legal
        $result = $this->isThrough($log, $group);
        // write into sign log with this request when it is true
        $result && $this->writeIntoSignLog($sign, $group);
        // return
        return $result;
    }



    /**
     * format sign string to prevent to produce porblem when make the sign into key name just like the dot of ip
     * @param sign - string - wait to format string
     * @return result - string - string after transfer
     */
    protected function formatSign($sign)
    {
        return md5($sign);
    }


    /**
     * transfer interface to group name (search)
     * @param interface - string - interface
     * @return groupName - string - group name
     */
    protected function transferInterfaceToGroup($interface)
    {
        if (empty($interface)) {
            return null;
        }
        if ( isset($this->interfaceBelongGroup[$interface]) ) {
            return $this->interfaceBelongGroup[$interface];
        } else {
            return null;
        }
    }


    /**
     * get log byt sign in group
     * @param sign - string - sign
     * @param group - string - group
     * @return log - array - log data array
     * @var log - group =>  [
     *         sign =>  [
     *             'times'  =>  0,
     *             'request_at'  =>  [ time1, time2, time3, ...]
     *         ],
     *         ...
     *     ]
     */
    protected function logBySign($sign, $group)
    {
        // new log
        $newLog = [
            'times' =>  0,
            'request_at'    =>  [],
        ];
        // set cache key and get group log data
        $groupKey = ( empty($this->cacheKeyPrefix) ? $group : ($this->cacheKeyPrefix.'_'.$group) );
        $groupLog = $this->cache::get("$groupKey");
        // get sign log from group log
        if (empty($groupLog)) {
            $groupLog = [];
            $groupLog[$sign] = $newLog;
            $this->cache::put($groupKey, $groupLog, $this->cacheExpire);
            return $newLog;
        } else if (!isset($groupLog[$sign]) || empty($groupLog[$sign])) {
            $groupLog[$sign] = $newLog;
            $this->cache::put($gourpKey, $groupLog, $this->cacheExpire);
            return $newLog;
        }
        // is renew by logExpireUnit
        $signLastTime = end($groupLog[$sign]['request_at']);
        $lastDay = date('d', $signLastTime);
        $nowDay = date('d');
        if ( $lastDay!=$nowDay ){
            $groupLog[$sign] = $newLog;
            $this->cache::put($groupKey, $groupLog, $this->cacheExpire);
            return $groupLog[$sign];
        }
        // return old log
        return $groupLog[$sign];
    }


    /**
     * check whether through with sign log
     * @param log - array - sign log to validated
     * @param group - string - group name
     * @return result - bool - true/false
     */
    public function isThrough($log, $group)
    {
        $limit = $this->configInterfaceGroup[$group]['limit'];
        $interval = $this->configInterfaceGroup[$group]['interval'];
        $minutes = $this->configInterfaceGroup[$group]['frequency']['minutes'];
        $times = $this->configInterfaceGroup[$group]['frequency']['times'];

        // invalidate request time and return true when request time small than 2
        if ( count($log['request_at'])<2 ) {
            return true;
        } else {
            $this->signLastTime = end($log['request_at']);
            $this->signEarlyTime = reset($log['request_at']);
        }
        // invalidate limit when limit is enable
        if ( $limit && ($log['times']>=$limit) ) {
            return false;
        }
        // invalidate interval when interval is enable
        if ( $interval ) {
            if ( ($this->nowTime - $this->signLastTime) <= $interval)
            return false;
        }
        // invalidate frequency when frequency is enable
        if ( $minutes && $times ) {
            // compare sign times and group limit
            if ($log['times']>=$times) {
                $logFrequency = ($this->nowTime - $this->signEarlyTime) / 60;
                $logFrequency = intval(ceil($logFrequency));
                if ($logFrequency<=$minutes) {
                    return false;
                } else {
                    return true;
                }
            } else {
                // calculate frequency and compare
                $frequency = $times / $minutes;
                $logFrequency = $log['times'] / (($this->signLastTime - $this->signEarlyTime)*60);
                $frequency = round($frequency, 0);
                $logFrequency = round($logFrequency, 0);
                if ( $logFrequency>=$frequency ) {
                    return false;
                } else {
                    return true;
                }
            }
        }
        // return
        return true;
    }


    /**
     * write into sign log with this request
     * @param sign - string - sign
     * @param group - string - group name
     * @return result - bool - true/false
     */
    public function writeIntoSignLog($sign, $group)
    {
        // cache time - minutes
        $minutes = 24*60;
        $times = $this->configInterfaceGroup[$group]['frequency']['times'];
        // set cache key and get group log data
        $groupKey = ( empty($this->cacheKeyPrefix) ? $group : ($this->cacheKeyPrefix.'_'.$group) );
        $groupLog = $this->cache::get("$groupKey");
        // write sign log
        $signLog = $groupLog[$sign];
        $signLog['times']++;
        if ($signLog['times']>$times) {
            array_push($signLog['request_at'], time());
            array_shift($signLog['request_at']);
        } else {
            array_push($signLog['request_at'], time());
        }
        // set cache
        $groupLog[$sign] = $signLog;
        $result = $this->cache::put("$groupKey", $groupLog, $this->cacheExpire);
        // return
        return $result;
    }



}
