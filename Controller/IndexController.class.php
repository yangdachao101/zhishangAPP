<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;



class IndexController 
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
  		$u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $as = [
      'u'=>$u,
      's'=>$s
      ];
      return $this->app->renderer->render($response, './index.php', $as);
    }
    
    public function pcindex($request, $response, $args){
      return $response->getBody()->write('请扫描二维码关注至上会计微信公众平台...');
    }

    public function getarea($request, $response, $args){
      global $db;
      $key = $_GET['key'];
      if($key!=''){

        $key = explode(' ',$key);
        $id = 0;
        $prov = $db->get('address','*',[
          'AND'=>[
            'level'=>1,
            'name'=>$key[0]
          ]
        ]);

        $city = $db->get('address','*',[
          'AND'=>[
            'upid'=>$prov['id'],
            'level'=>2,
            'name'=>$key[1]
          ]
        ]);

        $id = $city['id'];

        if($key[2]){
          $area = $db->get('address','*',[
            'AND'=>[
              'upid'=>$city['id'],
              'level'=>3,
              'name'=>$key[2]
            ]
          ]);
          $id = $area['id'];
        }



        $json = array('flag' => 200,'msg' => '获取省市区ID成功', 'data' => $id);
        return $response->withJson($json);
        exit();

      }else{
        $json = array('flag' => 200,'msg' => '参数为空，区域ID获取失败', 'data' => []);
        return $response->withJson($json);
        exit();
      }
      
    }

    public function getrandganhuo($request, $response, $args){
      global $db;
      $count = $db->count('mcms_posts');
      $rand = rand(0,($count-3));
      $list = $db->select('mcms_posts',[
                  '[>]mcms_attachment'=>['thumbnail'=>'id']
                  ],[
                  'mcms_posts.id',
                  'mcms_posts.title',
                  'mcms_attachment.thumbnail(pic)',
                ],[
                  'mcms_posts.thumbnail[!]'=>NULL,
                  'ORDER'=>['mcms_posts.id'=>'DESC'],
                  'LIMIT'=>[$rand,3]
                ]);
      return $response->withJson($list);
    }

    public function imClient($request, $response, $args){
      global $db;
      $uid = isset($request->getQueryParams()['uid']) ? $request->getQueryParams()['uid'] :0;
      $m =[];
      $c = $db->get('customs',['id','name','avatar'],[
        'id'=>$uid
      ]);

      if($c){

        $m['id']=$c['id'];

        if($c['name']!=''){
          $m['name']=$c['name'];
        }else{
          $m['name']='匿名';
        }

        if($c['avatar']!=''){
          $m['avatar']=$c['avatar'];
        }else{
          $m['avatar']='http://www.cw2009.com/templates/dist/img/avatar.png';
        }
        
      }else{
        $m['id']=0;
        $m['name']="游客";
        $m['avatar']="http://www.cw2009.com/templates/dist/img/avatar.png";
      }
      //var_dump($m);
      $as = [
        'm'=>$m,
      ];
      return $this->app->renderer->render($response, './im-client.php', $as);
    }

    public function searchzsd($request, $response, $args){
      global $db;
      $keyword = $request->getParsedBody()['keyword'];

      $pa = new PhpAnalysis();
      $pa->SetSource($keyword);
      $pa->resultType=1;
      $pa->differMax=true;
      
      $pa->StartAnalysis();
       //$arr = $pa->GetFinallyIndex();//全部词
      $arr = $pa->GetFinallyKeywords(3);
      //var_dump($arr);
      $ks = explode(',', $arr);
      // foreach ($arr as $key => $value) {
      //   # code...
      //   array_push($ks, $key);
      // }
      // var_dump($ks);
      $list = $db->select('mcms_posts',[
                  'mcms_posts.id',
                  'mcms_posts.title',
                ],[
                  'mcms_posts.title[~]'=>$ks,
                  'ORDER'=>['mcms_posts.id'=>'DESC'],
                  'LIMIT'=>[0,20]
                ]);
      return $response->withJson($list);
    }
    
}
