<?php
session_start();
error_reporting(E_ALL); 
ini_set('display_errors', false); 
// error_reporting(E_ALL&~E_NOTICE);
// ini_set('display_errors', '1');
require 'vendor/autoload.php';
require 'Controller/PHPAnalysis.class.php';
date_default_timezone_set('Asia/Chongqing');
$settings = require 'inc/settings.php';//应用配置
$homeuri = $settings['settings']['renderer']['home_path'];

use Medoo\Medoo;

$db = new medoo([
	    'database_type' => $settings['settings']['db']['database_type'],
	    'database_name' => $settings['settings']['db']['database_name'],
	    'server' => $settings['settings']['db']['server'],
	    'username' => $settings['settings']['db']['username'],
	    'password' => $settings['settings']['db']['password'],
	    'charset' => $settings['settings']['db']['charset'],
	    'port'=>$settings['settings']['db']['port'],
	    'prefix' => 'zscrm_'
 
	]);
$db2 = new medoo([
        'database_type' => $settings['settings']['db2']['database_type'],
        'database_name' => $settings['settings']['db2']['database_name'],
        'server' => $settings['settings']['db2']['server'],
        'username' => $settings['settings']['db2']['username'],
        'password' => $settings['settings']['db2']['password'],
        'charset' => $settings['settings']['db2']['charset'],
        'port'=>$settings['settings']['db2']['port'],
        'prefix' => 'zscrm_'
 
    ]);
require 'inc/function.php';//公共函数

$assets_path = $settings['settings']['renderer']['assets_path'];

$settings['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
            //global $homeuri;
        return $response->withRedirect('/');

         //    if($_SERVER["HTTP_REFERER"]!=''){
         //        $uri = $_SERVER["HTTP_REFERER"];
         //    }else{
         //        $uri = $homeuri;
         //    }
        	// return $c['response']
         //    ->withStatus(200)
         //    ->withHeader('Content-Type', 'text/html;charset=utf-8')
         //    ->write('<meta name="viewport" content="initial-scale=1, maximum-scale=1"><link rel="stylesheet" href="'.$homeuri.'/templates/dist/css/app.css" /><link rel="stylesheet" href="//at.alicdn.com/t/font_412959_f4bg7ajoveku766r.css" /><div style="text-align:center;padding-top:23%;font-size:.8rem;color:#999;line-height:200%;"><i class="iconfont icon-dengdaimaijiachulishenqingzhuanhuan" style="font-size:5rem;"></i><br /><br /><br />小至正在努力为您提供更加优秀的服务，敬请期待<br /><span style="font-size:1.1rem;">至上会计，创优争先</span><br /><br /><br /><a href="'.$uri.'" style="color:#f90;background:#fff;border:1px solid #fc0;padding:8px 20px;border-radius:25px;text-decoration:none;">返回</a></div>');
    };
};

include 'IMAPI/rongcloud.php';




$app = new \Slim\App($settings);

//判断是否手机访问，否则显示二维码
$app->add(function ($request, $response, $next) {
	//$is = isMobile();
    //if($is){
    	//将登录用户的基础信息以参数的方式传递到控制器
    	$u = getu();
    	$s = gets();
    	$request = $request->withAttribute('u', $u);
    	$request = $request->withAttribute('s', $s);

        //添加访问记录
        creat_access_statistics($s,$u);
    	$response = $next($request, $response);
    	return $response; 
    //}else{
            //return $response->withRedirect('https://cw2009.com');
    	    //return $response->write('<div style="text-align:center;padding-top:50px;">请扫描二维码关注至上会计微信公众平台<br /><img src="/static/qr.jpg" /></div>');
    //}
});

$container = $app->getContainer();

require 'inc/dependencies.php';//附加属性
require 'Middleware/middleware.php';
require 'inc/routes.php';//页面路由-get
require 'inc/routes-post.php';//提交路由-post
require 'inc/routes-api.php';//提交路由-post
$app->run();
