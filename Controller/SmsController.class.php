<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class SmsController 
{
	protected $app;
   	
   	public function __construct(ContainerInterface $ci) {
       $this->app = $ci;
   	}
   	public function __invoke($request, $response, $args) {
        //to access items in the container... $this->ci->get('');
   	}
   	
   	public function push($request, $response, $args){
    	global $db;
      
      $key = 'SECFDDF3499543544R22231243434338DFV3DC9DKFJ8';
      $time = $_POST['time'];
      $model = $_POST['model'];
      $client_token = $_POST['token'];
      $server_token = md5(md5($model.$time.$key));
      $tpl = $_POST['tpl_id'];
      $type = $_POST['type'];
      $mobiles = $_POST['mobile'];
      $name = $_POST['name'];
      $keyword = $_POST['keyword'];
      //$type：0单发1群发，单发不意味着只有一个号码发送。而是按_POST['mobile']列表发送

      if($client_token != $server_token){
        echo('error');
        exit;
      }else{
        echo('success');
        //读取tpl
        $t = $db->get('sms_tpl','*',['id'=>$tpl]);
        //生成短信文本
        $smstext = $t['content'];
        $content = preg_match_all("/\\[.*?\\]/is",$smstext,$array);
        
        for($i = 0; $i<count($array[0]);$i++) {
          $f = $array[0][$i];
          $fe = ltrim($f,'[');
          $fe = rtrim($fe,']');
          $smstext = str_replace($f,$keyword[$fe],$smstext);
        }

        //发送短信
        if($type==0){
          for($j = 0; $j<count($mobiles);$j++) {
            //echo($mobiles[$j]);
            $txt = '尊敬的'.$name[$j].'先生/女士：'.$smstext;
            pushSMS($mobiles[$j],$txt,0,$t['name'],0);
          }
        }else{
          //群发
        }

      }


  		
    }
    
    
}
