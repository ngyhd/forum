<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \GatewayWorker\Gateway;
use \Workerman\Autoloader;

// 自动加载类
//require_once __DIR__ . '/../Workerman/Autoloader.php';
require_once __DIR__ .'/../../vendor/workerman/workerman/Autoloader.php';
Autoloader::setRootPath(__DIR__);

// 证书最好是申请的证书
//$context = array(
//	// 更多ssl选项请参考手册 http://php.net/manual/zh/context.ssl.php
//	'ssl' => array(
//		// 请使用绝对路径
//		'local_cert'                 => '/usr/local/nginx/cert/214677858270584.pem', // 也可以是crt文件
//		'local_pk'                   => '/usr/local/nginx/cert/214677858270584.key',
//		'verify_peer'               => false,
//		// 'allow_self_signed' => true, //如果是自签名证书需要开启此选项
//	)
//);
// websocket协议(端口任意，只要没有被其它程序占用就行)
//$gateway = new Gateway("websocket://0.0.0.0:3123", $context);
// 开启SSL，websocket+SSL 即wss
//$gateway->transport = 'ssl';

// gateway 进程
$gateway = new Gateway("websocket://0.0.0.0:3123");
// 设置名称，方便status时查看
$gateway->name = 'ChatGateway';
// 设置进程数，gateway进程数建议与cpu核数相同
$gateway->count = 4;
// 分布式部署时请设置成内网ip（非127.0.0.1）
$gateway->lanIp = '127.0.0.1';
// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口 
$gateway->startPort = 2300;
// 心跳间隔
$gateway->pingInterval = 10;
// 心跳数据
$gateway->pingData = '{"type":"ping"}';
// 服务注册地址
$gateway->registerAddress = '127.0.0.1:1236';

/* 
// 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
$gateway->onConnect = function($connection)
{
    $connection->onWebSocketConnect = function($connection , $http_header)
    {
        // 可以在这里判断连接来源是否合法，不合法就关掉连接
        // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
        if($_SERVER['HTTP_ORIGIN'] != 'http://chat.workerman.net')
        {
            $connection->close();
        }
        // onWebSocketConnect 里面$_GET $_SERVER是可用的
        // var_dump($_GET, $_SERVER);
    };
}; 
*/

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

