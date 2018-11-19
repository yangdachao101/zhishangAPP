<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class OpenIDController 
{
	protected $app;
   	
   	public function __construct(ContainerInterface $ci) {
       $this->app = $ci;
   	}
   	public function __invoke($request, $response, $args) {
        //to access items in the container... $this->ci->get('');
   	}
   	
   	public function index($request, $response, $args){
    	global $db;
  		return $response->getBody()->write('禁止直接访问本网址...');
    }

    public function getcodeu($request, $response, $args){
      global $db;
      $code = $_GET['code'];
      $state = $_GET['state'];
      $uri = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx9f2d1785175d240a&secret=2aacbdec8fbe702af7a4747cc838ea2a&code='.$code.'&grant_type=authorization_code';
      $tsk = @file_get_contents($uri);
      $tskjson = json_decode($tsk);
      $openid = $tskjson->openid;
      $access_token = $tskjson->access_token;
      $uuri = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
      $u = @file_get_contents($uuri);
      $u = json_decode($u);
      setcookie("openid", $u->openid, mktime()+31104000,'/');
      setcookie("nickname", $u->nickname, mktime()+31104000,'/');
      setcookie("headimgurl", $u->headimgurl, mktime()+31104000,'/');
      setcookie("unionid", $u->unionid, mktime()+31104000,'/');
      return $response->withRedirect('/regu.html');
    }
    
    public function getcodes($request, $response, $args){
      global $db;
      $code = $_GET['code'];
      $state = $_GET['state'];
      $uri = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx9f2d1785175d240a&secret=2aacbdec8fbe702af7a4747cc838ea2a&code='.$code.'&grant_type=authorization_code';
      $tsk = @file_get_contents($uri);
      $tskjson = json_decode($tsk);
      $openid = $tskjson->openid;
      $access_token = $tskjson->access_token;
      $uuri = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
      $u = @file_get_contents($uuri);
      $u = json_decode($u);
      setcookie("openid", $u->openid, mktime()+31104000,'/');
      setcookie("nickname", $u->nickname, mktime()+31104000,'/');
      setcookie("headimgurl", $u->headimgurl, mktime()+31104000,'/');
      setcookie("unionid", $u->unionid, mktime()+31104000,'/');
      return $response->withRedirect('/regs.html');
    }

    public function getcodeu2($request, $response, $args){
      global $db;
      $code = $_GET['code'];
      $state = $_GET['state'];
      $uri = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx9f2d1785175d240a&secret=2aacbdec8fbe702af7a4747cc838ea2a&code='.$code.'&grant_type=authorization_code';
      $tsk = @file_get_contents($uri);
      $tskjson = json_decode($tsk);
      $openid = $tskjson->openid;
      $access_token = $tskjson->access_token;
      $uuri = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
      $u = @file_get_contents($uuri);
      $u = json_decode($u);
      setcookie("openid", $u->openid, mktime()+31104000,'/');
      setcookie("nickname", $u->nickname, mktime()+31104000,'/');
      setcookie("headimgurl", $u->headimgurl, mktime()+31104000,'/');
      setcookie("unionid", $u->unionid, mktime()+31104000,'/');
      return $response->withRedirect('/u/bindwx.html');
    }
    
    public function getcodes2($request, $response, $args){
      global $db;
      $code = $_GET['code'];
      $state = $_GET['state'];
      $uri = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx9f2d1785175d240a&secret=2aacbdec8fbe702af7a4747cc838ea2a&code='.$code.'&grant_type=authorization_code';
      $tsk = @file_get_contents($uri);
      $tskjson = json_decode($tsk);
      $openid = $tskjson->openid;
      $access_token = $tskjson->access_token;
      $uuri = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
      $u = @file_get_contents($uuri);
      $u = json_decode($u);
      setcookie("openid", $u->openid, mktime()+31104000,'/');
      setcookie("nickname", $u->nickname, mktime()+31104000,'/');
      setcookie("headimgurl", $u->headimgurl, mktime()+31104000,'/');
      setcookie("unionid", $u->unionid, mktime()+31104000,'/');
      return $response->withRedirect('/s/bindwx.html');
    }
    
}
