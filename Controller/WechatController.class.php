<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class WechatController 
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

    }
    
    public function push($request, $response, $args){
      global $db;
      
      $key = 'SECFDDF3499543544R22231243434338DFV3DC9DKFJ8';
      $time = $_POST['time'];
      $model = $_POST['model'];
      $client_token = $_POST['token'];
      $server_token = md5(md5($model.$key));
      $tpl = $_POST['tpl_id'];
      $type = $_POST['type']; //单发0//群发所有关注者1//群发服务者2
      $openID = $_POST['openID'];
      $url = $_POST['url'];
      $remark = $_POST['remark'];
      $title = $_POST['title'];
      $name = $_POST['name'];
      $keyword = $_POST['keyword'];//数组
     


      if($client_token != $server_token){
        echo('error');
        exit;
      }else{

        if($type==0){
          for($j = 0; $j<count($openID);$j++) {
            $o = $openID[$j];
            echo($o);
            $data = '{
               "touser":"'.$o.'",
               "template_id":"'.$tpl.'",
               "url":"'.$url.'",
               "data":{
                "first": {
                  "value":"您好，'.$name[$j].':\n'.$title.'",
                  "color":"#ff6600"
                },
                "keyword1":{
                  "value":"'.$keyword[0].'",
                  "color":"#ff6600"
                },
                "keyword2": {
                  "value":"'.$keyword[1].'",
                  "color":"#ff6600"
                },
                "keyword3": {
                  "value":"'.$keyword[2].'",
                  "color":"#ff6600"
                },
                "keyword4": {
                  "value":"'.$keyword[3].'",
                  "color":"#ff6600"
                },
                "keyword5": {
                  "value":"'.$keyword[4].'",
                  "color":"#ff6600"
                },
                "remark":{
                  "value":"'.$remark.'",
                  "color":"#ff6600"
                }
              }
            }';
            $r  = pushWechat($data);
            echo $r;
          }
        }elseif($type==1){

        }elseif($type==2){
          
          $s = $db->select('member',['id','name','openID'],[
            'AND'=>[
              'status'=>1,
              'openID'=>''
            ]
            
          ]);
          
          foreach ($s as $sv) {
            $data = '{
               "touser":"'.$sv['openID'].'",
               "template_id":"'.$tpl.'",
               "url":"'.$url.'",
               "data":{
                "first": {
                  "value":"您好，'.$sv['name'].':\n'.$title.'",
                  "color":"#ff6600"
                },
                "keyword1":{
                  "value":"'.$keyword[0].'",
                  "color":"#ff6600"
                },
                "keyword2": {
                  "value":"'.$keyword[1].'",
                  "color":"#ff6600"
                },
                "keyword3": {
                  "value":"'.$keyword[2].'",
                  "color":"#ff6600"
                },
                "keyword4": {
                  "value":"'.$keyword[3].'",
                  "color":"#ff6600"
                },
                "keyword5": {
                  "value":"'.$keyword[4].'",
                  "color":"#ff6600"
                },
                "remark":{
                  "value":"'.$remark.'",
                  "color":"#ff6600"
                }
              }
            }';
            $r  = pushWechat($data);
            echo $r;
          }
        }

        return  'success';



      }

    }
    
}
