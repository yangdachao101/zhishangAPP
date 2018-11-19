<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class HelpController 
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

    public function about($request, $response, $args){
      global $db;
      $as = [
      ];
      return $this->app->renderer->render($response, './page/about.php', $as);
    }

     public function aboutDetail($request, $response, $args){
      global $db;
      $id=$args['id'];
      $article=$db->get('articles','*',['AND'=>['type'=>$id,'status'=>1]]);
      $as = [
        'article'=>$article,
      ];
      return $this->app->renderer->render($response, './page/about-detail.php', $as);
    }

    public function help($request, $response, $args){
      global $db;
      $as = [
      ];
      return $this->app->renderer->render($response, './page/help.php', $as);
    }

   public function getHelp($request, $response, $args){
      global $db;
      //  分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      //查询帮助数据articles
      $list=$db->select('articles','*',[
            'AND'=>[
                'type'=>7,
                'status'=>1,
            ],
            'ORDER'=>['id'=>'DESC'],
            'LIMIT'=>[$srow,10],
        ]);
      $count=count($list);//数据总条数
      $allp = round($count/10);//总页数
      $json = array('flag' => 200,'msg' => '成功', 'data' => [
        'list'=>$list,
        'count'=>$count,
        'allp'=>$allp
        ]);
        return $response->withJson($json);
    }

       //帮助查询
    public function selectHelp($request, $response, $args){
      global $db;
      $text=$_GET['name'];
      //根据查询的内容查询帮助表
      $list=$db->select('articles','*',[
            'AND'=>[
                'type'=>7,
                'status'=>1,
              'OR'=>[
                'title[~]'=>$text,
                'content[~]'=>$text,
            ],
            ],
            
            'ORDER'=>['id'=>'DESC'],
            // 'LIMIT'=>[$srow,10],
        ]);
      $json = array('flag' => 200,'msg' => '成功', 'data' => [
        'list'=>$list,
        ]);
        return $response->withJson($json);

    }

     public function aboutType($request, $response, $args){
      global $db;
      $type = $args['id'];
      $title=$db->get('articles_type','*',['id'=>$type]);
      //查询帮助信息
      $about=$db->select('articles','*',['AND'=>['type'=>$type,'status'=>1,]]);
      $as = [
        'type'=>$type,
        'about'=>$about,
        'title'=>$title,
      ];
      return $this->app->renderer->render($response, './page/about-type.php', $as);
    }

    public function helpDetail($request, $response, $args){
      global $db;
      $as = [
      ];
      return $this->app->renderer->render($response, './page/help-detail.php', $as);
    }

   public function aboutChina($request, $response, $args){
      global $db;
      //查询至上中国文章下的列表信息
      $all=$db->select('articles','*',['AND'=>['type'=>6,'status'=>1,]]);
      $as = [
        'all'=>$all,
      ];
      return $this->app->renderer->render($response, './page/about-china.php', $as);
    }

    public function uaboutDetail($request, $response, $args){
      global $db;
      $id=$args['id'];
      $article = $db->get('articles','*',['id'=>$id]);
      $as = [
        'article'=>$article,
      ];
      return $this->app->renderer->render($response, './page/zs-about-detail.php', $as);
    }
    
    
}
