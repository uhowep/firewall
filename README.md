# firewall
A simple anti-irrigation water filtration detection and monitoring for Laravel

---

应用于Laravel中的简单的防灌水过滤检测
- 支持以用户、IP地址、主机等等作为检测对象
- 支持定义接口组，以接口组为单位进行单独的配置
- 配置的过滤机制包含每天访问的总次数、每次访问的时间间隔、以及访问的频率

---

### Install
composer it
```
composer require uhowep/firewall:dev-master
```

Then run these commands to publish config：
```
php artisan vendor:publish --provider="Uhowep\Firewall\FirewallServiceProvider"
```


### Config
在`config/firewall.php`文件中配置`interface_group`，该数组中的每个key为接口组的名称，所有的检测配置基于该接口组，其下有`interface`、`interval`、`limit`及`frequency`字段。
- `interface`为该接口组中的接口列表，可配置为路由uri
- `limit`为总次数(当天，目前只支持到按天)
- `interval`为访问的时间间隔(秒)
- `frequency`为访问的频率(次/分钟)，其下有minutes及times两个字段，代表minutes分钟内times次

**注：frequency中1分钟10次和2分钟20次不是同一个概念**

### Usage
常用于路由中：
```
...
use Uhowep\Firewall\Firewall;

...
$sign = 'sign';		// 此处可以用登录用户的主键，也可以用来源的IP地址
$interface = Route::current()->uri;		// 获取当前访问路径的uri
$firewallObj = new Firewall;
$isPass = $firewallObj->authorize($sign, $interface);		// 通过与否的结果
```
注意：
- `sign`用以标识要进行检测的用户/IP等等，若为空则直接通行
- `interface`将与config中的interface接口列表进行匹配，若出现在列表里则进行检测，否则直接通行
- 检测时，以接口组为单位，以接口组为单位，以接口组为单位，不以接口为单位！

### Drawback
- `暂不支持同一接口出现在不同的接口组中`
- 可优化增加驱动支持



