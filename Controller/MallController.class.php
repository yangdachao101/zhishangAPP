<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class MallController 
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
      if(isset($_GET['openid']) && $_GET['openid']!=''){
        setcookie("openid", $_GET['openid'], mktime()+31104000,'/');
      }
      //头条
      $toutiao = $db->get('mcms_posts','*',[
        'cateId'=>1,
        'ORDER'=>['id'=>'DESC']
      ]);
      //推荐的圈子
      $quan = $db->select('mcms_quan','*',[
        'ORDER'=>['zhan'=>'DESC','creatTime'=>'DESC'],
        'LIMIT'=>[0,1]
      ]);
      //查询服务 （公司注册）
      $company = $db->select('mcms_service',['id','title','thumbnail','allPrice','iconClass','iconColor'],['AND'=>['cateId'=>12,'status'=>0],'ORDER'=>['views'=>'DESC','id'=>'ASC'],'LIMIT'=>[0,9]]);
      //查询服务（商标注册）
      $trademark=$db->select('mcms_service',['id','title','thumbnail','allPrice','iconClass','iconColor'],['AND'=>['cateId'=>[16,17],'status'=>0],'ORDER'=>['views'=>'DESC'],'LIMIT'=>[0,9],]);
      //查询服务（代理记账）
      $account=$db->select('mcms_service',['id','title','thumbnail','allPrice','iconClass','iconColor'],['AND'=>['cateId'=>19,'status'=>0],'ORDER'=>['views'=>'DESC'],'LIMIT'=>[0,9],]);
      //查询服务会计培训
      $train=$db->select('mcms_service',['id','title','thumbnail','allPrice','iconClass','iconColor'],['AND'=>['cateId'=>[23,24],'status'=>0],'ORDER'=>['views'=>'DESC'],'LIMIT'=>[0,9],]);
      //查询品牌设计
       $design=$db->select('mcms_service',['id','title','thumbnail','allPrice','iconClass','iconColor'],['AND'=>['cateId'=>[25,26,27,28,29,30,31],'status'=>0],'ORDER'=>['views'=>'DESC'],'LIMIT'=>[0,9],]);

        //查询轮播图
       $ads=$db->select('ads',['title','photoUrl','url'],[
            'AND'=>[
                'status'=>1,//0隐藏1显示
                'utype'=>0,//0微信端显示1 pc端显示
                'adsPosition'=>1,//首页轮播图显示
            ]
        ]);

  		$as = [
      'u'=>$u,
      's'=>$s,
      'toutiao'=>$toutiao,
      'quan'=>$quan,
      'company'=>$company,//公司注册
      'trademark'=>$trademark,//商标注册
      'account'=>$account,//代理记账
      'train'=>$train,//学会计
      'design'=>$design,//品牌设计
      'ads'=>$ads,//微信主页轮播图
      ];
      return $this->app->renderer->render($response, './mall.php', $as);
    }

    public function goods($request, $response, $args){
      global $db;
      $as = [
      ];
      return $this->app->renderer->render($response, './goods.php', $as);
    }

    public function good($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $id=$args['id'];//服务id
      //查询服务基本信息
      $service=[];
      $service=$db->get('mcms_service','*',['id'=>$id]);
      $db->update('mcms_service',[
        'views[+]'=>1
      ],[
        'id'=>$id
      ]);
      if($service['pics']=='[]'){
        //不存在取thumbnail
        if(isset($service['thumbnail'])&&$service['thumbnail']!=0){
            $service['pic']=$db->get('mcms_attachment','thumbnail_640',['id'=>$service['thumbnail']]);
          }else{
            $service['pic']='';
          }
          $num=0;
      }else{
        $num=1;
        $pic= json_decode($service['pics']);
        $service['pic']=[];
          if(isset($pic)&&$pic!=''){
              for($i=0;$i<count($pic);$i++){
                $service['pic'][$i]=$db->get('mcms_attachment','thumbnail_640',['id'=>$pic[$i]]);
              }
          }
      }
      //获取评价数据
      $evaluate=$db->select('orders_evaluate','*',[
            'sid'=>$id,
            'ORDER'=>['id'=>'DESC'],
            'LIMIT'=>[0,4],
          ]);
      $eva=[];
      // $orde[$i]['mobile']= substr($mobile,0,3)."*****".substr($mobile,8,3);
      if($evaluate){
          for($i=0;$i<count($evaluate);$i++){
              $eva[$i]['star']=$evaluate[$i]['star'];//评价星级
              $eva[$i]['text']=$evaluate[$i]['evatext'];//评价内容
              $uname=$db->get('customs',['name','mobile'],['id'=>$evaluate[$i]['customID']]);
              $eva[$i]['uname']=mb_substr($uname['name'],0,1, 'utf-8')."**";//客户姓名
              $eva[$i]['umobile']= substr($uname['mobile'],0,3)."*****".substr($uname['mobile'],8,3);//客户电话
              $eva[$i]['creattime']=$evaluate[$i]['creattime'];//客户评价时间
              //查询服务者信息
              $member=$db->get('member',['name','avatar'],['id'=>$evaluate[$i]['staffID']]);
              $eva[$i]['mname']=$member['name'];
              $eva[$i]['staffID']=$evaluate[$i]['staffID'];
              $eva[$i]['avatar']=$member['avatar'];
          }
      }else{
        $eva=0;
      }
      $detail = [];
      $as = [
      'u'=>$u,
      's'=>$s,
      'detail'=>$detail,
      'service'=>$service,
      'num'=>$num,//判断是单独图片还是数组
      'eva'=>$eva,//评论数据
      ];
      return $this->app->renderer->render($response, './good.php', $as);
    }

    //专题页->服务
    public function feature($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      if(isset($_GET['id']) && is_numeric($_GET['id'])){
        $id = $_GET['id'];
      }else{
        $id = 0;
      }
      if(isset($_GET['type']) && $_GET['type']!=''){
        $type = $_GET['type'];
      }else{
        $type = 'other';
      }
      $as = [
        'id'=>$id,
        'type'=>$type,
        'u'=>$u,
      ];
      return $this->app->renderer->render($response, './feature.php', $as);
    }
    //套餐页
    public function gs($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $sid=$args['id'];
        $content = $db->get('mcms_group_service','*',[
          'id'=>$args['id']
        ]);
      $as = [
        'u'=>$u,
        'content'=>$content,
        'sid'=>$sid,

      ];
      return $this->app->renderer->render($response, './gs.php', $as);
    }
    
     //postArea(查询服务的特殊价格)
    public function postArea($request, $response, $args){
      global $db;
      $area = $_POST['areaid'];//地区id
      $sid = $_POST['sid'];//服务id
      $service = $db->get('mcms_service','*',['id'=>$sid]);
      $price = [];
      if($service['unifiedPrice']==0){
            //查询服务特殊价格
          $price = $db->get('mcms_service_price',[
            'id',
            'price'
          ],[
            'AND'=>[
              'sId'=>$sid,
              'area'=>$area,
              'member_id'=>0 //官方报价
            ]]);
        }else{
          $price['price'] = $service['allPrice'];
          $price['id'] = $service['id'];
        }

        //服务者报价 
      $mprice = $db->get('mcms_service_price',[
            'id',
            'price',
            'member_id'
          ],[
            'AND'=>[
              'sId'=>$sid,
              'area'=>$area,
              'member_id[>]'=>0
            ]]);
      if($price){
        $flag = 200;
        $msg = '查询成功';
      }else{
        
          $flag = 400;
          $msg = '该地区暂无报价，请拨打客服电话！';
        
      }
      $json = array('flag' => $flag,'msg'=>$msg, 'data'=>[
        'price'=>$price,
        'mprice'=>$mprice
      ]);
        return $response->withJson($json);

      // var_dump($price);
    }

    //good comments(更多评价)
    public function goodComments($request, $response, $args){
      global $db;
      $sid=$args['id'];//服务id
      //查询评论条数
      //全部
      $allcount=$db->count('orders_evaluate','id',['sid'=>$sid, ]);
      //差评
      $badcount=$db->count('orders_evaluate','id',['AND'=>['sid'=>$sid,'star'=>[1,2]]]);
      //中评
      $reviewcount=$db->count('orders_evaluate','id',['AND'=>['sid'=>$sid,'star'=>[3,4]]]);
      //好评
      $praisecount=$db->count('orders_evaluate','id',['AND'=>['sid'=>$sid,'star'=>5]]);
      $as = [
        'sid'=>$sid,
        'allcount'=>$allcount,
        'badcount'=>$badcount,
        'reviewcount'=>$reviewcount,
        'praisecount'=>$praisecount,
      ];
      return $this->app->renderer->render($response, './comments-list.php', $as);

    }
    
    public function goodComment($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $sid=$args['id'];//服务id
       //分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      $type=$_GET['type'];
      if($type==0){
        //获取评价数据
        $evaluate=$db->select('orders_evaluate','*',[
              'sid'=>$sid,
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[$srow,10],
            ]);
        $eva=[];
        // $orde[$i]['mobile']= substr($mobile,0,3)."*****".substr($mobile,8,3);
        if($evaluate){
            for($i=0;$i<count($evaluate);$i++){
                $eva[$i]['star']=$evaluate[$i]['star'];//评价星级
                $eva[$i]['text']=$evaluate[$i]['evatext'];//评价内容
                $uname=$db->get('customs',['name','mobile'],['id'=>$evaluate[$i]['customID']]);
                $eva[$i]['uname']=mb_substr($uname['name'],0,1, 'utf-8')."**";//客户姓名
                $eva[$i]['umobile']= substr($uname['mobile'],0,3)."*****".substr($uname['mobile'],8,3);//客户电话
                $eva[$i]['creattime']=$evaluate[$i]['creattime'];//客户评价时间
                //查询服务者信息
                $member=$db->get('member',['name','avatar'],['id'=>$evaluate[$i]['staffID']]);
                $eva[$i]['mname']=$member['name'];
                $eva[$i]['staffID']=$evaluate[$i]['staffID'];
                $eva[$i]['avatar']=$member['avatar'];
            }
         }
         if($eva){
            $flag=200;
            $msg='成功';
          }else{
            $flag=400;
            $msg='无';
          }
          $json = array('flag' =>$flag,'msg'=>$msg, 'data'=>['eva'=>$eva]);
          return $response->withJson($json);
      }else if($type==1){
        //好评
         //获取评价数据
        $evaluate=$db->select('orders_evaluate','*',[
          'AND'=>[
                'sid'=>$sid,
                'star'=>5,
           ],
              
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[$srow,10],
            ]);
        $eva=[];
        // $orde[$i]['mobile']= substr($mobile,0,3)."*****".substr($mobile,8,3);
        if($evaluate){
            for($i=0;$i<count($evaluate);$i++){
                $eva[$i]['star']=$evaluate[$i]['star'];//评价星级
                $eva[$i]['text']=$evaluate[$i]['evatext'];//评价内容
                $uname=$db->get('customs',['name','mobile'],['id'=>$evaluate[$i]['customID']]);
                $eva[$i]['uname']=mb_substr($uname['name'],0,1, 'utf-8')."**";//客户姓名
                $eva[$i]['umobile']= substr($uname['mobile'],0,3)."*****".substr($uname['mobile'],8,3);//客户电话
                $eva[$i]['creattime']=$evaluate[$i]['creattime'];//客户评价时间
                //查询服务者信息
                $member=$db->get('member',['name','avatar'],['id'=>$evaluate[$i]['staffID']]);
                $eva[$i]['mname']=$member['name'];
                $eva[$i]['staffID']=$evaluate[$i]['staffID'];
                $eva[$i]['avatar']=$member['avatar'];
            }
         }
         if($eva){
            $flag=200;
            $msg='查询成功';
          }else{
            $flag=400;
            $msg='该地区暂无报价，请拨打客服电话！';
          }
          $json = array('flag' =>$flag,'msg'=>$msg, 'data'=>['eva'=>$eva]);
          return $response->withJson($json);
      }else if($type==2){
        //中评
        //获取评价数据
        $evaluate=$db->select('orders_evaluate','*',[
          'AND'=>[
                'sid'=>$sid,
                'star'=>[3,4],
           ],
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[$srow,10],
            ]);
        $eva=[];
        // $orde[$i]['mobile']= substr($mobile,0,3)."*****".substr($mobile,8,3);
        if($evaluate){
            for($i=0;$i<count($evaluate);$i++){
                $eva[$i]['star']=$evaluate[$i]['star'];//评价星级
                $eva[$i]['text']=$evaluate[$i]['evatext'];//评价内容
                $uname=$db->get('customs',['name','mobile'],['id'=>$evaluate[$i]['customID']]);
                $eva[$i]['uname']=mb_substr($uname['name'],0,1, 'utf-8')."**";//客户姓名
                $eva[$i]['umobile']= substr($uname['mobile'],0,3)."*****".substr($uname['mobile'],8,3);//客户电话
                $eva[$i]['creattime']=$evaluate[$i]['creattime'];//客户评价时间
                //查询服务者信息
                $member=$db->get('member',['name','avatar'],['id'=>$evaluate[$i]['staffID']]);
                $eva[$i]['mname']=$member['name'];
                $eva[$i]['staffID']=$evaluate[$i]['staffID'];
                $eva[$i]['avatar']=$member['avatar'];
            }
         }
         if($eva){
            $flag=200;
            $msg='查询成功';
          }else{
            $flag=400;
            $msg='该地区暂无报价，请拨打客服电话！';
          }
          $json = array('flag' =>$flag,'msg'=>$msg, 'data'=>['eva'=>$eva]);
          return $response->withJson($json);
      }else if($type==3){
        //差评
        //获取评价数据
        $evaluate=$db->select('orders_evaluate','*',[
          'AND'=>[
                'sid'=>$sid,
                'star'=>[1,2],
           ],
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[$srow,10],
            ]);
        $eva=[];
        // $orde[$i]['mobile']= substr($mobile,0,3)."*****".substr($mobile,8,3);
        if($evaluate){
            for($i=0;$i<count($evaluate);$i++){
                $eva[$i]['star']=$evaluate[$i]['star'];//评价星级
                $eva[$i]['text']=$evaluate[$i]['evatext'];//评价内容
                $uname=$db->get('customs',['name','mobile'],['id'=>$evaluate[$i]['customID']]);
                $eva[$i]['uname']=mb_substr($uname['name'],0,1, 'utf-8')."**";//客户姓名
                $eva[$i]['umobile']= substr($uname['mobile'],0,3)."*****".substr($uname['mobile'],8,3);//客户电话
                $eva[$i]['creattime']=$evaluate[$i]['creattime'];//客户评价时间
                //查询服务者信息
                $member=$db->get('member',['name','avatar'],['id'=>$evaluate[$i]['staffID']]);
                $eva[$i]['mname']=$member['name'];
                $eva[$i]['staffID']=$evaluate[$i]['staffID'];
                $eva[$i]['avatar']=$member['avatar'];
            }
         }
         if($eva){
            $flag=200;
            $msg='查询成功';
          }else{
            $flag=400;
            $msg='该地区暂无报价，请拨打客服电话！';
          }
          $json = array('flag' =>$flag,'msg'=>$msg, 'data'=>['eva'=>$eva]);
          return $response->withJson($json);
      }
      // var_dump($type);
    }

    public function buy($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $sid=$_GET['sid'];
      // var_dump($sid);
      // exit;
      $areaid=$_GET['areaid'];
      //查询服务名称
      $service=$db->get('mcms_service','*',['id'=>$sid]);
      //查询所选择的服务地区
      $areaname=$db->get('address','*',['id'=>$areaid]);//区
      $cityname=$db->get('address','*',['id'=>$areaname['upid']]);//市
      $provname=$db->get('address','*',['id'=>$cityname['upid']]);//省
      $address=$provname['name'].' '.$cityname['name'].' '.$areaname['name'];
      
      $pac = $db->select('mcms_group_service','*',[
        'AND'=>[
            'sid0'=>$sid,
            'status'=>0,
          ]
        ]);
      

      if($pac){
        $arr=[];
        $i=0;
        foreach ($pac as $value) {
          if($value['unifiedPrice'] == 1){
            
            $st = $db->select('mcms_service',['id','title','allPrice'],[
              'id'=>[
                $value['sid0'], 
                $value['sid1'], 
                $value['sid2'], 
                $value['sid3'], 
                $value['sid4'], 
                $value['sid5']
            ]]);
            foreach($st as $stv){
              $arr[$i]['content'] .= "<span class='pull-right'>¥ ".$stv['allPrice']."</span>".$stv['title']."<br />";
            }
            $arr[$i]['id']=$value['id'];
            $arr[$i]['title']=$value['group_title'];
            $arr[$i]['price']=$value['price'];
            $arr[$i]['at']=$st;
            $arr[$i]['marketprice']=$value['marketprice'];
            $arr[$i]['text']=$value['text'];

          }else{

             $ps = $db->get('mcms_group_service_price','*',[
              'AND'=>[
                'gsid'=>$value['id'],
                'area'=>$areaid
              ]]);

            
            if($ps){
              
              $st = $db->select('mcms_service',['id','title','allPrice'],[
              'id'=>[
                $value['sid0'], 
                $value['sid1'], 
                $value['sid2'], 
                $value['sid3'], 
                $value['sid4'], 
                $value['sid5']
              ]]);
              foreach($st as $stv){
                $arr[$i]['content'] .= "<span class='pull-right'>¥ ".$stv['allPrice']."</span>".$stv['title']."<br />";
              }

              $arr[$i]['id']=$value['id'];
              $arr[$i]['title']=$value['group_title'];
              $arr[$i]['price']=$ps['price'];
              $arr[$i]['at'] = $st;
              $arr[$i]['marketprice']=$value['marketprice'];
              $arr[$i]['desc']=$value['text'];
            }
            
          }
          $i++;
          
        }
       // var_dump($arr);
       // exit;

        $as = [
        'u'=>$u,
        'sid'=>$sid,
        'service'=>$service,//服务信息
        'areaid'=>$areaid,//服务地区ids
        'address'=>$address,//服务地区
        'arr'=>$arr,//套餐信息
        ];
        return $this->app->renderer->render($response, './buy.php', $as);
      }else{
         return $response->withRedirect('/creatorder.html?areaid='.$areaid.'&id='.$sid);
      }
    }

    public function creatorder($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      
      $areaid = $_GET['areaid'];//地址id

      if(isset($_GET['brandcatid']) && is_numeric($_GET['brandcatid'])){
         $brandcatid = $_GET['brandcatid'];
      }else{
        $brandcatid = NULL;
      }

      if(isset($_GET['id']) && is_numeric($_GET['id'])){
        $gid = $_GET['id'];
      }else{
        $gid = NULL;
      }

      if(isset($_GET['member_id']) && is_numeric($_GET['member_id'])){
        $mid = $_GET['member_id'];
      }else{
        $mid = 0;
      }

      //查询单独服务信息
      $service = $db->get('mcms_service','*',['id'=>$gid]);
      //查询地址
     
      $areaname = $db->get('address','*',['id'=>$areaid]);//区
      $cityname = $db->get('address','*',['id'=>$areaname['upid']]);//市
      $provname = $db->get('address','*',['id'=>$cityname['upid']]);//省
      $address = $provname['name'].' '.$cityname['name'].' '.$areaname['name'];
      //查询特殊地址价格
      if($mid==0){
        if($service['unifiedPrice']==1){
          $price = $service['allPrice'];
        }else{
          $pric = $db->get('mcms_service_price','*',[
                'AND'=>[
                    'sId'=>$gid,
                    'area'=>$areaid,
                    'member_id'=>$mid
                ]
            ]);
          $price = $pric['price'];
        }
      }else{
        $pric = $db->get('mcms_service_price','*',[
                'AND'=>[
                    'sId'=>$gid,
                    'area'=>$areaid,
                    'member_id'=>$mid
                ]
            ]);
        $price = $pric['price'];
      }
      //查询商标注册的信息 
      $stype=$db->select('trademark_type','*');//商标大类
      $typename=$db->get('trademark_type','*',['id'=>$brandcatid]);

      // 单品多项选择
      //查询公司注册地址
      $compaddre=$db->select('mcms_service',['id','title'],['id'=>[15,120,123]]);
      $i=0;
      $add=[];
      foreach($compaddre as $c){
          $pric=$db->get('mcms_service_price','price',[
                'AND'=>[
                      'sId'=>$c['id'],
                      'area'=>$areaid,
                ]
            ]);
          if($pric){
            $add[$i]['price']=$pric;
            $add[$i]['id']=$c['id'];
            $add[$i]['title']=$c['title'];
          }
          $i++;
      }
      //查询刻章信息
      $chapter=$db->select('mcms_service',['id','title'],['id'=>[48,49,53,69]]);
      $j=0;
      $chap=[];
      foreach($chapter as $t){
           $pric=$db->get('mcms_service_price','price',[
                'AND'=>[
                      'sId'=>$t['id'],
                      'area'=>$areaid,
                ]
            ]);
           if($pric){
            $chap[$j]['price']=$pric;
            $chap[$j]['id']=$t['id'];
            $chap[$j]['title']=$t['title'];
          }
          $j++;
      }
      //查询代帐信息
      $accounting=$db->select('mcms_service',['id','title'],['id'=>[22,23]]);
      $b=0;
      $account=[];
      foreach($accounting as $a){
          $pric=$db->get('mcms_service_price','price',[
                'AND'=>[
                      'sId'=>$a['id'],
                      'area'=>$areaid,
                ]
            ]);
          if($pric){
            $account[$b]['price']=$pric;
            $account[$b]['id']=$a['id'];
            $account[$b]['title']=$a['title'];
          }
          $b++;
      }
      //查询开户服务
      $bank=$db->get('mcms_service',[
          '[>]mcms_service_price'=>['id'=>'sId'],
        ],[
            'mcms_service.title(title)',
            'mcms_service.id(id)',
            'mcms_service_price.price(price)',
        ],[
            'mcms_service.id'=>112,
            'mcms_service_price.area'=>$areaid,
        ]);
      //查询其他资质
      $qual=$db->select('mcms_service',['id','title'],['id'=>[42,84,59,60]]);
      $q=0;
      $qua=[];
      foreach($qual as $qu){
          $pric=$db->get('mcms_service_price','price',[
                'AND'=>[
                      'sId'=>$qu['id'],
                      'area'=>$areaid,
                ]
            ]);
          if($pric){
            $qua[$q]['price']=$pric;
            $qua[$q]['id']=$qu['id'];
            $qua[$q]['title']=$qu['title'];
          }
          $q++;
      }

        //捆绑销售
      if($service['id']==129){
        //查询代理记账
        $kservice=$db->get('mcms_service','*',['id'=>23]);
        $kprice=$kservice['allPrice']+$service['allPrice'];
      }else{
        $kservice=[];
        $kprice=0;
      }

      //结束

      $as = [
        'u'=>$u,
        'gid'=>$gid,
        'areaid'=>$areaid,
        'service'=>$service,
        'address'=>$address,
        'price'=>$price,
        'stype'=>$stype,
        'brandcatid'=>$brandcatid,
        'typename'=>$typename,
        'add'=>$add,//公司注册地址选择
        'chap'=>$chap,//刻章选择
        'account'=>$account,//代帐选择
        'bank'=>$bank,//开户选择
        'qua'=>$qua,//其他资质选择
        'kservice'=>$kservice,//如果服务id 是129的话捆绑代理记账
        'kprice'=>$kprice,
        'mid'=>$mid
      ];
      return $this->app->renderer->render($response, './creat-order.php', $as);
    }

    //写入订单后跳转的页面-套餐
    public function creatordergs($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      //$id=$_GET['oid'];//订单id
      $payid = $_GET['payid'];//支付批号
      
      if($payid){
        $order = $db->select('orders','*',['payid'=>$payid]);
      }else{
        $order=[];
      }
      if(isset($_COOKIE['openid']) && $_COOKIE['openid']!=''){
        $qrvlog = $db->get('member_vcode_log','*',[
          'openID'=>$_COOKIE['openid'],
          'ORDER'=>['apitime'=>'DESC']
        ]);
        if($qrvlog){
          $vcode = $qrvlog['vcode'];
        }else{
          $vcode = '';
        }
      }else{
        $vcode = '';
      }
      

      
      
      $as = [
        'u'=>$u,
        'order'=>$order,
        'payid'=>$payid,
        'vcode'=>$vcode
      ];
      return $this->app->renderer->render($response, './creat-order-gs.php', $as);
    }

    public function pay($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $payid = $_GET['payid'];
      if(isset($_GET['type']) && $_GET['type']!=''){
        $type = $_GET['type'];
      }else{
        $type = NULL;
      }

      if($payid){
        if($type=='order'){
          $ot = $db->sum('orders','price',['payid'=>$payid]);
        }elseif($type=='wallet'){
          $ot = $db->sum('wallets_recharge','money',[
            'AND'=>[
              'payid'=>$payid,
              'status'=>0
            ]
            
          ]);
        }
        
      }else{
        $ot = 0;
      }

      $total = $ot;
      $title = $_GET['title'];
      $as = [
        'u'=>$u,
        'payid'=>$payid,
        'total'=>$total,
        'title'=>$title,
        'type'=>$type
      ];
      return $this->app->renderer->render($response, './pay.php', $as);
    }

    public function payresult($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $result = $_GET;
      $as = [
        'u'=>$u,
        'result'=>$result
      ];
      return $this->app->renderer->render($response, './payresult.php', $as);
    }

    //good insert boppo(客户在详情页咨询)
    public function goodBoppo($request, $response, $args){
      global $db;
      // $u = $request->getAttribute('u');//用户
      $sid=$_POST['sid'];//服务id
      $name=$_POST['name'];//咨询人称呼
      $mobile=$_POST['mobile'];//咨询人电话
      $areas=$_POST['area'];//咨询人选择的地址
      $areaid=$_POST['areaid'];//咨询人选择的地址id
      if($_POST['areaid']==''){
        $areaid=null;
      }else{
        $areaid=$_POST['areaid'];
      }
      if($areas!=''){
        $add=explode(' ',$areas);
        $prov=$add[0];
        $city=$add[1];
        $area=$add[2];
      }else{
        $prov=null;
        $city=null;
        $area=null;
      }
      $service=$db->get('mcms_service','title',['id'=>$sid]);
      $text='客户 '.$name.' 想要了解关于 '.$service.' 的服务';
      //写入商机表
      $boppo=$db->insert('boppo',[
            'uname'=>$name,
            'mobile'=>$mobile,
            'text'=>$text,
            'creattime'=>date('Y-m-d H:i:s'),
            'status'=>1,
            'qd'=>'wechatgood',
            'prov'=>$prov,
            'city'=>$city,
            'area'=>$area,
            'where'=>0,
            'areaId'=>$areaid,
            'yw'=>$service,
        ]);
      if($boppo){
        $flag=200;
        $msg='咨询成功，小至会尽快联系您,请保持您的电话畅通，以便至上会计客户服务专员会通过电话与您取得联系处理咨询。';
        $push= puch(0,4,[$mobile,],['',],[]);
      }else{
        $boppo=0;
        $flag=400;
        $msg='咨询失败，请拨打客服热线';
      }
      $json = array('flag' =>$flag,'msg'=>$msg);
       return $response->withJson($json);
    }


    // buy insert order(选择套餐先写入订单表)
    public function buyinsertOrder($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $sid=$_POST['sid'];//套餐id
      $areaid=$_POST['areaid'];//地址信息
      $payid = time().''.rand(1000,9999);
      //获取注册时候的推荐人
      if(isset($u['vcode'])&&$u['vcode']!=''){
        $vcode=$u['vcode'];
      }else{
        $vcode=null;
      }
      //获取套餐价格
      $group=$db->get('mcms_group_service','*',['id'=>$sid]);
      if(isset($group['unifiedPrice'])&&$group['unifiedPrice']==0){
        //使用地区报价
        $price=$db->get('mcms_group_service_price','price',['AND'=>['gsid'=>$sid,'area'=>$areaid,]]);
      }else{
        $price=$group['price'];
      }
          // 写入订单表
          $mid = $db->insert("orders", [
            "orderId"=>num(),
            "uid"=>$u['id'],
            "sid"=>$sid,
            "creattime" => date('Y-m-d H:i:s'),
            "price"=>$price,
            "num"=>'1',
            "status"=>0,
            "vcode"=>$vcode,
            "areaId"=>$areaid,
            "type"=>1,
            "remark"=>'',
            "source"=>'wechat',
            'payid'=>$payid
            ]);
          $id=$db->id($mid);
          if($mid){
            $flag=200;
            $msg='生成订单成功';
          }else{
            $mid=0;
            $flag=400;
            $msg='生成订单失败';
          }
          $json = array('flag' =>$flag,'msg'=>$msg,'id'=>$id,'payid'=>$payid);
          return $response->withJson($json);

    }

     //首页 套餐 地址查询
    public function postGsarea($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $sid=$_POST['sid'];//套餐id
      $areaid=$_POST['areaid'];//地址id
      $service=$db->get('mcms_group_service','*',['id'=>$sid]);
      //查套餐价格
      $group=$db->get('mcms_group_service_price','*',[
            'AND'=>[
                'gsid'=>$sid,
                'area'=>$areaid,
            ]
        ]);
      if($group['price']){
        $price=$group['price'];
      }else{
        $price=$service['price'];
      }
      $s = $db->select('mcms_service',['id','allPrice','title'],[
              'id'=>[
                $service['sid0'],
                $service['sid1'],
                $service['sid2'],
                $service['sid3'],
                $service['sid4'],
                $service['sid5'],
              ]
            ]);
      $i=0;
      foreach($s as $v){
        $c=$db->get('mcms_service_price','price',[
              'AND'=>[
                  'sId'=>$v['id'],
                  'area'=>$areaid,
              ]
          ]);
        if(isset($c)&&$c!=''){
           $s[$i]['price']=$c;
        }else{
          $s[$i]['price']=$v['allPrice'];
        }
        $i++;
      }
      //查询套餐下的服务

      if($price){
        $flag=200;
        $msg='查询成功';
      }else{
        $flag=400;
        $msg='该地区无报价';
      }
      $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['price'=>$price,'s'=>$s]);
          return $response->withJson($json);
      // var_dump($group);
    }

    //good boppo (首页 套餐 免费咨询)
    public function gsBoppo($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $sid=$_POST['sid'];//套餐服务id
      $name=$_POST['name'];//客户称呼
      $mobile=$_POST['mobile'];//客户电话
      $areaid=$_POST['areaid'];//客户地址
      $area=$_POST['area'];//服务地址
      if($_POST['areaid']==''){
        $areaid=null;
      }else{
        $areaid=$_POST['areaid'];
      }
      if($area!=''){
        $add=explode(' ',$area);
        $prov=$add[0];
        $city=$add[1];
        $area=$add[2];
      }else{
        $prov=null;
        $city=null;
        $area=null;
      }
      $service=$db->get('mcms_group_service','group_title',['id'=>$sid]);
      $text='客户 '.$name.' 想要了解关于 '.$service.' 的服务';
      //写入商机表
      $boppo=$db->insert('boppo',[
            'uname'=>$name,
            'mobile'=>$mobile,
            'text'=>$text,
            'creattime'=>date('Y-m-d H:i:s'),
            'status'=>1,
            'qd'=>'wechatgs',
            'prov'=>$prov,
            'city'=>$city,
            'area'=>$area,
            'where'=>0,
            'areaId'=>$areaid,
        ]);
      if($boppo){
        $flag=200;
        $msg='咨询成功，小至会尽快联系您,请保持您的电话畅通，以便至上会计客户服务专员会通过电话与您取得联系处理咨询。';
      }else{
        $boppo=0;
        $flag=400;
        $msg='咨询失败，请拨打客服热线';
      }
      $json = array('flag' =>$flag,'msg'=>$msg);
       return $response->withJson($json);

    }

    //creat insert orders(商标服务写入订单)
    public function creatinsertOrder($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $sid=$_POST['sid'];//商标的服务id
      $area=$_POST['areaid'];//地区id
      $mid = $_POST['mid'];//下单选定的服务者id
      $price = $_POST['price'];//服务价格
      $tms = explode(',',$_POST['ordermeta']);//商标类别id
      $remark = serialize($tms);//序列化类别
      $payid = time().''.rand(1000,9999);
      //写入订单
      if(isset($u['vcode'])&&$u['vcode']!=''){
        $vcode = $u['vcode'];
      }else{
        $vcode=null;
      }
      $mid = $db->insert("orders", [
              "orderId"=>num(),
              "uid"=>$u['id'],
              "sid"=>$sid,
              'member_id'=>$mid,
              "creattime" => date('Y-m-d H:i:s'),
              "price"=>$price,
              "num"=>'1',
              "status"=>0,
              'vcode'=>$vcode,
              "type"=>0,
              "source"=>'wechat',
              'areaId'=>$area,
              'payid'=>$payid
            ]);
       $oid = $db->id($mid);
       if($oid){
          $rema = $db->insert("order_remark",[
              'orderId'=>$oid,
              'orderType'=>0,
              'sid'=>$sid,
              'name'=>"trademarks",
              'content'=>$remark,
              'type'=>'json',
              'creatTime'=>date('Y-m-d H:i:s'),
          ]);
          $all['姓名']=$_POST['uname'];//客户姓名
          $all['电话']=$_POST['mobile'];//客户电话
          $all['商标名称']=$_POST['brandanme'];//商标名称
          $rem=serialize($all);//序列化信息
          $data=$db->insert("order_remark",[
                'orderId'=>$oid,
                'orderType'=>0,
                'sid'=>$sid,
                'name'=>"uid",
                'content'=>$rem,
                'type'=>'json',
                'creatTime'=>date('Y-m-d H:i:s'),
          ]);
          $flag=200;
          $msg='生成订单成功';
       }else{
          $flag=400;
          $msg='订单生成失败，请重新提交';
       }
       $json = array('flag' =>$flag,'msg'=>$msg,'oid'=>$oid,'payid'=>$payid);
       return $response->withJson($json);
    }

    //creat insert orders(普通服务写入订单)
    public function creatinsertOrderB($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $sid = $_POST['sid'];//商标的服务id
      $area = $_POST['areaid'];//地区id
      $mid = $_POST['mid'];//下单选定的服务者id
      $price = $_POST['price'];//服务价格
      $payid = time().''.rand(1000,9999);
      $os = explode(',',$_POST['othersid']);//附加服务
      //写入订单
      if(isset($u['vcode']) && $u['vcode']!=''){
        $vcode = $u['vcode'];
      }else{
        $vcode = null;
      }
      $mid = $db->insert("orders", [
          "orderId"=>num(),
          "uid"=>$u['id'],
          "sid"=>$sid,
          'member_id'=>$mid,
          "creattime" => date('Y-m-d H:i:s'),
          "price"=>$price,
          "num"=>'1',
          "status"=>0,
          'vcode'=>$vcode,
          "type"=>0,
          "source"=>'wechat',
          'areaId'=>$area,
          'payid'=>$payid
      ]);
      //附加服务
      for($i=0;$i<count($os);$i++){
        if($os[$i]!=''){
          //获取服务价格

          //先判断是否统一价
          $ispa = $db->get('mcms_service',[
            'id',
            'allPrice',
            'unifiedPrice'],
            ['id'=>$os[$i]]
          );
          if($ispa['unifiedPrice']==1){
            $np = $ispa['allPrice'];
          }else{
            //获取地区价
            $g = $db->get('mcms_service_price',[
              'id',
              'price',
              'sId',
              'area'],
              [
                'AND'=>[
                'sId'=>$os[$i],
                'area'=>$area
              ]]
            );
            $np = $g['price'];
          }

          $db->insert("orders", [
            "orderId"=>num(),
            "uid"=>$u['id'],
            "sid"=>$os[$i],
            "creattime" => date('Y-m-d H:i:s'),
            "price"=>$np,
            "num"=>'1',
            "status"=>0,
            'vcode'=>$vcode,
            "type"=>0,
            "source"=>'wechat',
            'areaId'=>$area,
            'payid'=>$payid
          ]);
        }
      }
      $oid = $db->id($mid);
      if($oid){
          $flag=200;
          $msg='生成订单成功';
      }else{
          $flag=400;
          $msg='订单生成失败，请重新提交';
      }
      $json = array('flag' =>$flag,'msg'=>$msg,'oid'=>$oid,'payid'=>$payid);
      return $response->withJson($json);
    }


    //updata order by payid
    public function updataorderbypayid($request, $response, $args){
      global $db;
      $payid = $_POST['payid'];
      $vcode =  $_POST['vcode'];
      $consignee =  $_POST['consignee'];
      //查询支付流水相关的订单
      $o = $db->select('orders',['id'],[
        'payid'=>$payid
      ]);

      foreach($o as $ov){
        
        if($consignee=='' && $vcode==''){

        }elseif($consignee!='' && $vcode==''){
          $db->update('orders',[
            'consignee'=>$consignee
          ],[
            'id'=>$ov['id']
          ]);
        }elseif($consignee=='' && $vcode!=''){
          $db->update('orders',[
            'vcode'=>$vcode
          ],[
            'id'=>$ov['id']
          ]);
        }else{
          $db->update('orders',[
            'vcode'=>$vcode,
            'consignee'=>$consignee
          ],[
            'id'=>$ov['id']
          ]);
        }

      }
      $json = array('flag' =>200,'msg'=>'更新成功');
      return $response->withJson($json);
    }


      //商城首页的查询写入商机表-》公司注册
    public function mallinsertBoppoa($request, $response, $args){
      global $db;
      $remark=$_POST['companyname'];//想要查询的内容
      $name=$_POST['aname'];//客户姓名
      $mobile=$_POST['amobile'];//客户电话
      $areaid=$_POST['areaid'];//选择的地区id
      $text='客户：'.$name.' 想要咨询：'.$remark.' 的公司名称是否可以注册';
      //根据地区id查询所在地信息
      $address=$db->get('address','*',['id'=>$areaid]);
      //uinsertboppo客户自己添加的咨询
      $boppo=uinsertboppo($remark,$name,$mobile,$areaid,$text,'工商事务','3');
       if($boppo){
          $flag=200;
          $msg='提交成功,电话请保持畅通,小至会尽快跟您联系';
          $push= puch(0,4,[$mobile,],['',],[]);
        }else{
          $flag=400;
          $msg='提交失败！';
        }
        $json = array('flag' => $flag,'msg'=>$msg);
        return $response->withJson($json);
    }

      //商城首页的查询写入商机表->商标
    public function mallinsertBoppob($request, $response, $args){
      global $db;
      $remark=$_POST['markname'];//想要查询的内容
      $name=$_POST['bname'];//客户姓名
      $mobile=$_POST['bmobile'];//客户电话
      $areaid=$_POST['areaid'];//选择的地区id
      $text='客户：'.$name.' 想要咨询：'.$remark.' 的商标名称是否可以注册';
      //根据地区id查询所在地信息
      $address=$db->get('address','*',['id'=>$areaid]);
      //uinsertboppo客户自己添加的咨询
      $boppo=uinsertboppo($remark,$name,$mobile,$areaid,$text,'工商事务','3');
       if($boppo){
          $flag=200;
          $msg='提交成功,电话请保持畅通,小至会尽快跟您联系';
          $push= puch(0,4,[$mobile,],['',],[]);
        }else{
          $flag=400;
          $msg='提交失败！';
        }
        $json = array('flag' => $flag,'msg'=>$msg);
        return $response->withJson($json);
    }

        //商城首页的查询写入商机表->代帐
    public function mallinsertBoppoc($request, $response, $args){
      global $db;
      $remark=$_POST['dainame'];//想要查询的内容
      $name=$_POST['cname'];//客户姓名
      $mobile=$_POST['cmobile'];//客户电话
      $areaid=$_POST['areaid'];//选择的地区id
      $text='客户：'.$name.' 想要咨询：'.$remark.' 的公司代帐服务';
      //根据地区id查询所在地信息
      $address=$db->get('address','*',['id'=>$areaid]);
      //uinsertboppo客户自己添加的咨询
      $boppo=uinsertboppo($remark,$name,$mobile,$areaid,$text,'代理记账','3');
       if($boppo){
          $flag=200;
          $msg='提交成功,电话请保持畅通,小至会尽快跟您联系';
          $push= puch(0,4,[$mobile,],['',],[]);
        }else{
          $flag=400;
          $msg='提交失败！';
        }
        $json = array('flag' => $flag,'msg'=>$msg);
        return $response->withJson($json);
    }
     //商城首页的查询写入商机表->会计培训
    public function mallinsertBoppod($request, $response, $args){
      global $db;
      // $remark=$_POST['dainame'];//想要查询的内容
      $name=$_POST['dname'];//客户姓名
      $mobile=$_POST['dmobile'];//客户电话
      $areaid=$_POST['areaid'];//选择的地区id
      $text='客户：'.$name.' 想要咨询：会计培训的相关服务';
      //根据地区id查询所在地信息
      $address=$db->get('address','*',['id'=>$areaid]);
      //uinsertboppo客户自己添加的咨询
      $boppo=uinsertboppo('',$name,$mobile,$areaid,$text,'会计培训','3');
       if($boppo){
          $flag=200;
          $msg='提交成功,电话请保持畅通,小至会尽快跟您联系';
          $push= puch(0,4,[$mobile,],['',],[]);
        }else{
          $flag=400;
          $msg='提交失败！';
        }
        $json = array('flag' => $flag,'msg'=>$msg);
        return $response->withJson($json);
    }

     //加载免费起名页面
    public function companynameList($request, $response, $args){
      global $db;
       //查询轮播图
       $ads=$db->select('ads',['title','photoUrl','url'],[
            'AND'=>[
                'status'=>1,//0隐藏1显示
                'utype'=>0,//0微信端显示1 pc端显示
                'adsPosition'=>1,//首页轮播图显示
            ]
        ]);
      // echo "111";
      $as = [
        'ads'=>$ads,//轮播图
      ];
      return $this->app->renderer->render($response, './mall_company.php', $as);
    }
    //随机生成名字
    public function Companyname($request, $response, $args){
        global $db;
        $type=$_POST['type'];
        if(isset($_POST['sname'])&&$_POST['sname']!=''){
          //随机生成2个字的汉字组合
                  $b ='';
                  $a=[];
                for($j=0;$j<20;$j++){
                  for ($i=0; $i<$type; $i++) {
                      // 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
                      $a = chr(mt_rand(0xB0,0xD0)).chr(mt_rand(0xA1, 0xF0));
                      // 转码
                      $b.= iconv('GB2312', 'UTF-8', $a);
                      
                  }
                  $rem[$j]=$_POST['sname'].$b;
                  $b ='';
                }
        }else{
            $remark=$db->select('companyname','name',['type'=>$type]);
            $key=array_rand($remark,20); //随机获取数组的键
            for($i=0;$i<count($key);$i++){
              //循环键  并且重新赋值变量
              $a=$key[$i];
              $rem[$i]=$remark[$a];
            }

        }
       
      if($rem){
        $flag=200;
        $msg='查询成功';
      }else{
        $rem=0;
        $flag=400;
        $msg="查询失败，没有数据";
      }
      $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['list'=>$rem]);
      return $response->withJson($json);
    } 

     //creat insert orders(捆绑写入订单)
    public function creatinsertOrderC($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $sid = $_POST['sid'];//商标的服务id
      $kid=$_POST['kid'];
      $area = $_POST['areaid'];//地区id
      $payid = time().''.rand(1000,9999);
      $os = array($sid,$kid);
      //写入订单
      if(isset($u['vcode']) && $u['vcode']!=''){
        $vcode = $u['vcode'];
      }else{
        $vcode = null;
      }
      for($i=0;$i<count($os);$i++){
        //先判断是否统一价
          $ispa = $db->get('mcms_service',[
            'id',
            'allPrice',
            'unifiedPrice'],
            ['id'=>$os[$i]]
          );
          if($ispa['unifiedPrice']==1){
            $np = $ispa['allPrice'];
          }else{
            //获取地区价
            $g = $db->get('mcms_service_price',[
              'id',
              'price',
              'sId',
              'area'],
              [
                'AND'=>[
                'sId'=>$os[$i],
                'area'=>$area
              ]]
            );
            $np = $g['price'];
          }
        //查询服务价格
        $service=$db->get('mcms_service','*',['id'=>$os[$i]]);
        $mid = $db->insert("orders", [
          "orderId"=>num(),
          "uid"=>$u['id'],
          "sid"=>$service['id'],
          "creattime" => date('Y-m-d H:i:s'),
          "price"=>$service['allPrice'],
          "num"=>'1',
          "status"=>0,
          'vcode'=>$vcode,
          "type"=>0,
          "source"=>'wechat',
          'areaId'=>$area,
          'payid'=>$payid
      ]);
      }
      $oid = $db->id($mid);
      if($oid){
          $flag=200;
          $msg='生成订单成功';
      }else{
          $flag=400;
          $msg='订单生成失败，请重新提交';
      }
      $json = array('flag' =>$flag,'msg'=>$msg,'oid'=>$oid,'payid'=>$payid);
      return $response->withJson($json);
    }


    public function getsprice($request, $response, $args){
      global $db;
      $id = $request->getQueryParams()['id'];
      $area = $request->getQueryParams()['area'];
      $p = isset($request->getQueryParams()['p']) ? $request->getQueryParams()['p'] : 1;//分页
      $row = ($p * 20) - 20;


      $list = $db->select('mcms_service_price',[
        '[>]member'=>['member_id'=>'id']
      ],'*',[
        'AND'=>[
          'mcms_service_price.sId'=>$id,
          'mcms_service_price.area'=>$area,
          'mcms_service_price.status'=>0,
          'mcms_service_price.member_id[>]'=>0,
        ],
        'ORDER'=>['member.views'=>'DESC'],
        'LIMIT'=>[$row,20]
      ]);

      $count = $db->count('mcms_service_price',[
        'AND'=>[
          'mcms_service_price.sId'=>$id,
          'mcms_service_price.area'=>$area,
          'mcms_service_price.status'=>0,
          'mcms_service_price.member_id[>]'=>0
        ]
      ]);

      $allp = ceil($count/20);

      $as = [
        'list'=>$list,//轮播图
        'id'=>$id,
        'area'=>$area,
        'allp'=>$allp
      ];
      return $this->app->renderer->render($response, './getsprice.php', $as);
    }
    public function searchgood($request, $response, $args){
        global $db;
        $keyword = $_POST['keyword'];
        $list = $db->select('mcms_service','*',[
          'AND'=>[
            'status'=>0,
            'title[~]'=>$keyword
          ]
        ]);
        $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['list'=>$list]);
        return $response->withJson($json);
    }
}
