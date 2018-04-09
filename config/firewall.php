<?php

return [

    /*
    |--------------------------------------------------------------------------
    | group router config
    |--------------------------------------------------------------------------
    |
    | define parameter variable of the interface group and it will help
    | you control which interface router could through your application.
    | you can define multiple interface group and parameter by itself
    | just follow the following format:
    |
    |--------------------------------------------------------------------------
    | group router config format:
    |--------------------------------------------------------------------------
    | 'interface_group' => [
    |     'group_name1'    =>  [
    |         'interface' =>  ['interface1', 'interface2', ...],
    |         'limit'     =>  0,
    |         'interval'  =>  0,
    |         'frequency' =>  [
    |             'minutes'   =>  0,
    |             'times'     =>  0,
    |         ]
    |     ],
    |     'group_name2'   =>  [
    |
    |     ],
    |     ...
    | ],
    |
    */

    'interface_group'   =>  [
        'agree'    =>  [            // group name
            'interface' =>  [       // interface list
                'article_agree',
                'question_agree',
            ],
            'limit'     =>  8,      // 一天的次数限制/次,0为不限制
            'interval'  =>  2,      // 时间间隔/秒,0为不限制
            'frequency' =>  [       // 频率：minutes分钟内times次
                'minutes'   =>  1,  // 频率-分钟内,0为不限制
                'times'     =>  5,  // 频率-访问次数(该值设置将影响空间存储)
            ]
        ],
    ],



    /*
    |--------------------------------------------------------------------------
    | prefix string of group cache name
    |--------------------------------------------------------------------------
    |
    | the prefix string of group cache name used '_' character.
    | for example: the result cache key is 'firewall_agree' when the
    | group name is agree and prefix is firewall
    |
    */

    'cache_key_prefix' => 'firewall',


    /*
    |--------------------------------------------------------------------------
    | cache drivers
    |--------------------------------------------------------------------------
    |
    | the cache drivers used ( now only support default which is same as the cache driver of application used )
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'cache_driver'  =>  'default',


    /*
     |-------------------------------------------------------------------------
     | cache expire time of minute
     |-------------------------------------------------------------------------
     |
     | the cache expire time used minute for unit
     |
     */

    'cache_expire_time' => 1440,


    /*
     |-------------------------------------------------------------------------
     | sign log expire unit 
     |-------------------------------------------------------------------------
     |
     | sign log expire unit, support value : daily
     |
     */
     
    'log_expire_unit' => 'daily',

];
