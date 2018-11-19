<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class UcenterController 
{
  protected $app;

  public function __construct(ContainerInterface $ci) {
   $this->app = $ci;
 }
 public function __invoke($request, $response, $args) {
        //to access items in the container... $this->ci->get('');
 }

 public function index($request, $response, $args){
  setcookie("thismyuri", '/ucenter.html', mktime()+31104000,'/');
  global $db;
      $u = $request->getAttribute('u');//用户
      if($u['id']==''){
        return $response->withRedirect('/loginu.html');
      }
      //查询我的企业
      $companies=$db->select('companies',['id','companyname','cus_1'],[
        'OR'=>[
          'cus_1'=>$u['id'],
          'cus_2'=>$u['id'],
          'cus_3'=>$u['id'],
          'cus_4'=>$u['id'],
          'cus_5'=>$u['id'],
        ]
      ]);
      if(!isset($companies)&&$companies==''){
        $companies=[];
      }
      //查询客户订单信息
      $orders=$db->select('orders','*',['AND'=>['uid'=>$u['id'],'status'=>[1,2],]]);
      $contract=[];
      for($i=0;$i<count($orders);$i++){
        //判断企业有没有关联
        if(isset($orders[$i]['comanyId'])&&$orders[$i]['comanyId']!=''){
          $orders[$i]['comanystatus']=1;
          $orders[$i]['comst']=$db->get('companies','companyname',['id'=>$orders[$i]['comanyId']]);
        }else{
          $orders[$i]['comanystatus']='';
          $orders[$i]['comst']='';
        }
        $contract=$db->select('contract','*',['orderId'=>$orders[$i]['id']]);
        for($j=0;$j<count($contract);$j++){
          $contract[$j]['title']=$db->get('mcms_service','title',['id'=>$contract[$j]['sId']]);
          $contract[$j]['iconClass']=$db->get('mcms_service','iconClass',['id'=>$contract[$j]['sId']]);
          $contract[$j]['iconColor']=$db->get('mcms_service','iconColor',['id'=>$contract[$j]['sId']]);
        }
        $orders[$i]['contract']=$contract;
      }
        //工商类
      $cont=$db->select('contract',['id','status','sId','staffId'],['AND'=>['uid'=>$u['id'],'status'=>7]]);
      $sum=0;
      for($i=0;$i<count($cont);$i++){
        if($cont[$i]['status']==7){
          $sum+=1;
        }
      }
      //代帐
      $conts=$db->select('contract',['id','status','sId','staffId'],['AND'=>['uid'=>$u['id'],'status'=>5,'sId'=>[22,23,114,115,121]]]);

      $sums=0;
      $c=[];
      for($j=0;$j<count($conts);$j++){
        $speed[$j]=$db->get('contract_speed',['id','cutoms_ok'],['AND'=>[
          'uid'=>$conts[$j]['staffId'],
          'oId'=>$conts[$j]['id'],
          'stype'=>'binding',
        ]]);
        if(!isset($speed[$j]['cutoms_ok'])){
          $c[$j]['id']=$conts[$j]['id'];
          $c[$j]['status']=$conts[$j]['status'];
          $c[$j]['sId']=$conts[$j]['sId'];
          $c[$j]['staffId']=$conts[$j]['staffId'];
        }
      }
      $cuntr=array_merge($cont,$c);
      if($cuntr){
        $count=count($cuntr);
      }else{
       $count=0;
     }
     //查询退款记录有多少需要客户确认的
     $refund=$db->select('orders_refund','id',['status'=>3,'uid'=>$u['id']]);
     if(isset($refund)&&$refund!=''){
      $refunds=$refund;
     }else{
      $refunds=0;
     }
     $as = [
      'u'=>$u,
      's'=>$s,
      'orders'=>$orders,
      'companies'=>$companies,
      'cuntr'=>$cuntr,
      'count'=>$count,
      'refunds'=>$refunds,
    ];
    return $this->app->renderer->render($response, './u.php', $as);
  }

  public function orders($request, $response, $args){
    setcookie("thismyuri", '/ucenter.html', mktime()+31104000,'/');
    global $db;
      $u = $request->getAttribute('u');//用户

      $status = isset($request->getQueryParams()['status']) ? $request->getQueryParams()['status'] : 'all';
      //查询 我的订单
      if($status=='all'){
        $orders = $db->select('orders','*',['AND'=>['uid'=>$u['id']]]);
      }else{
        $orders = $db->select('orders','*',['AND'=>['uid'=>$u['id'],'status'=>$status]]);
      }  
        $contract=[];
        for($i=0;$i<count($orders);$i++){
          //判断企业有没有关联
          if(isset($orders[$i]['comanyId'])&&$orders[$i]['comanyId']!=''){
            $orders[$i]['comanystatus']=1;
            $orders[$i]['comst']=$db->get('companies','companyname',['id'=>$orders[$i]['comanyId']]);
          }else{
            $orders[$i]['comanystatus']='';
            $orders[$i]['comst']='';
          }
          $contract=$db->select('contract','*',['orderId'=>$orders[$i]['id']]);
          for($j=0;$j<count($contract);$j++){
            $contract[$j]['title']=$db->get('mcms_service','title',['id'=>$contract[$j]['sId']]);
            $contract[$j]['iconClass']=$db->get('mcms_service','iconClass',['id'=>$contract[$j]['sId']]);
            $contract[$j]['iconColor']=$db->get('mcms_service','iconColor',['id'=>$contract[$j]['sId']]);
            $contract[$j]['start_day']=$contract[$j]['start_day'];
            $contract[$j]['end_day']=$contract[$j]['end_day'];
            if(isset($contract[$j]['staffId'])&&$contract[$j]['staffId']!=''){
              $contract[$j]['staff']=1;
              $contract[$j]['staffname']=$db->get('member','name',['id'=>$contract[$j]['staffId']]);
              $contract[$j]['staffid']=$db->get('member','id',['id'=>$contract[$j]['staffId']]);
              $orders[$i]['statusstaff']=1;
            }else{
              $contract[$j]['staff']=0;
              $orders[$i]['statusstaff']=0;
              $contract[$j]['staffname']='';
              $contract[$j]['staffid']='';
            }

              //查询状态
            $contract[$j]['statusname']=$db->get('contract_status','statusname',['id'=>$contract[$j]['status']]);
              //查询评价判断合同有没有被评价
            $evals=$db->select('orders_evaluate','id',['orderID'=>$contract[$j]['id']]);
            if($evals){
              $contract[$j]['eva']=1;//已评价
            }else{
              $contract[$j]['eva']=2;//未评价
            }
          }
          $orders[$i]['contract']=$contract;
            //查询评价
        }
      
       //查询我的企业
      $companies=$db->select('companies',['id','companyname','cus_1'],[
      'OR'=>[
          'cus_1'=>$u['id'],
          'cus_2'=>$u['id'],
          'cus_3'=>$u['id'],
          'cus_4'=>$u['id'],
          'cus_5'=>$u['id'],
        ]
      ]);
      if(!isset($companies)&&$companies==''){
        $companies=[];
      }
      
      $as = [
        'u'=>$u,
        'orders'=>$orders,
        'companies'=>$companies,
        'status'=>$status
        // 'endorders'=>$endorders,
      ];
      return $this->app->renderer->render($response, './u/orders.php', $as);
    }

    public function ordersDetail($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $contractid=$args['id'];
      //查询合同信息
      $contract=$db->get('contract','*',['id'=>$contractid]);
       //查询服务者信息
      if(isset($contract['staffId'])&&$contract['staffId']!=''){
        $staff=$db->get('member',['id','name','mobile'],['id'=>$contract['staffId']]);
      }else{
        $staff='';
      }
      $service=$db->get('mcms_service',['title','iconClass','iconColor'],['id'=>$contract['sId']]);
      $status=$db->get('contract_status','statusname',['id'=>$contract['status']]);
      //根据合同ID查询订单号 企业信息
      $order=$db->get('orders',['orderId','creattime','comanyId','id','status'],['id'=>$contract['orderId']]);
      if(isset($order['comanyId'])&&$order['comanyId']!=''){
        $comstatus=1;
        $comst=$db->get('companies','companyname',['id'=>$order['comanyId']]);
      }else{
        $comstatus=0;
        $comst='';
      }
      //查询处理信息
      //查询处理信息
      $hanles=$db->select('contract_speed','*',['oId'=>$contract['id'],"ORDER"=>['id'=>"DESC"],]);

      for($i=0;$i<count($hanles);$i++){
        $hanles[$i]['name']=$db->get('member','name',['id'=>$hanles[$i]['uid']]);
        $hanles[$i]['mobile']=$db->get('member','mobile',['id'=>$hanles[$i]['uid']]);
        $hanles[$i]['avatar']=$db->get('member','avatar',['id'=>$hanles[$i]['uid']]);
        if(!empty($hanles[$i]['pic'])){
          $pic=json_decode($hanles[$i]['pic']);
          $pics=[];
          foreach ($pic as $key => $value) {
            $pics[$key] = ($db->get('mcms_attachment','*',['AND'=>['type'=>0,'id'=>$value]]));
          }
          $hanles[$i]['pic']=$pics;
        }
      }

      //查询备注
      $remark=$db->select('order_remark','*',[
        'AND'=>[ 'orderId'=>$contract['orderId'],'OR'=>['type'=>'pic','name'=>'remark',],]]);
      for($i=0;$i<count($remark);$i++){
        if($remark[$i]['type']=='pic'){
          $cu= json_decode($remark[$i]['content']);
          for($j=0;$j<count($cu);$j++){
            $area[$j]=$db->get('mcms_attachment','*',[
              'id'=>$cu[$j],
            ]);
            $remark[$i]['pic']=$area;
          }
        }
      }
      if(empty($remark)){
        $remark=0;
      }
          //查询我的企业
      $companies=$db->select('companies',['id','companyname','cus_1'],[
        'OR'=>[
          'cus_1'=>$u['id'],
          'cus_2'=>$u['id'],
          'cus_3'=>$u['id'],
          'cus_4'=>$u['id'],
          'cus_5'=>$u['id'],
        ]
      ]);
      if(!isset($companies)&&$companies==''){
        $companies=[];
      }
       //判断该合同有没有品论过
      $eva=$db->select('orders_evaluate','id',['orderID'=>$contract['id']]);
      if($eva){
        $evastatus=1;//存在
      }else{
        $evastatus=2;//不存在
      }
      // var_dump($hanles);
      // exit;
      $as = [
      'u'=>$u,
      'order'=>$order,//订单信息 订单号 购买时间
      'service'=>$service,//服务名称
      'status'=>$status,//合同状态
      'comstatus'=>$comstatus,//判断有没有关联企业
      'comst'=>$comst,//企业名称
      'staff'=>$staff,//服务者信息 
      'hanles'=>$hanles,//处理信息
      'remark'=>$remark,//备注信息
      'companies'=>$companies,//企业信息
      'contract'=>$contract,//合同信息
      'evastatus'=>$evastatus,//判断有没有评价
    ];
    return $this->app->renderer->render($response, './u/order-detail.php', $as);
  }

  public function companies($request, $response, $args){
    setcookie("thismyuri", '/ucenter.html', mktime()+31104000,'/');
    global $db;
      $u = $request->getAttribute('u');//用户
      //查询客户的关联企业列表
      $company=$db->select('companies',['id','companyname','logo'],[
        'OR'=>[
          'cus_1'=>$u['id'],
          'cus_2'=>$u['id'],
          'cus_3'=>$u['id'],
          'cus_4'=>$u['id'],
          'cus_5'=>$u['id'],
        ]
      ]);
      for($i=0;$i<count($company);$i++){
        if(isset($company[$i]['logo'])&&$company[$i]['logo']!=''){
          $company[$i]['pics']=$db->get('mcms_attachment','thumbnail',['id'=>$company[$i]['logo']]);
        }else{
          $company[$i]['pics']='';
        }
      }
      if(isset($company)){
        $com=1;
      }else{
        $com=0;
      }
      $as = [
        'u'=>$u,
        'company'=>$company,
        'com'=>$com,
      ];
      return $this->app->renderer->render($response, './u/companies.php', $as);
    }

    public function companyDetail($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      if(isset($args['id']) && is_numeric($args['id']) &&  $args['id']!=0){
        $id = $args['id'];
        $data = $db->get('companies','*',['id'=>$id]);
        if($data){
          $company = $data;
          if($company['area']!=''){
            $add=$company['prov'].' '.$company['city'].' '.$company['area'];
          }else{
            $add=$company['prov'].' '.$company['city'];
          }
            //查询图标logo
          $company['pic']=$db->get('mcms_attachment','thumbnail',['id'=>$company['logo']]);
        }else{
          $company = [];
          $add='';
        }
      }else{
        $id = 0;
        $company=[];
        $add='';
      }
      $as = [
        'u'=>$u,
        'id'=>$id,
        'company'=>$company,
        'add'=>$add,
      ];
      return $this->app->renderer->render($response, './u/company-detail.php', $as);
    }

    public function companyEdit($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      if(isset($_GET['id']) && is_numeric($_GET['id']) &&  $_GET['id']!=0){
        $id = $_GET['id'];
        $data = $db->get('companies','*',['id'=>$id]);
        if($data){
          $c = $data;
          if(isset($data['logo'])&&$data['logo']!=''){
           $pics=$db->get('mcms_attachment','thumbnail',['id'=>$data['logo']]);
         }else{
          $pics='';
        }
      }else{
        $c = '';
        $pics='';
      }
    }else{
      $id = 0;
      $c='';
      $pics='';
    }
      // var_dump($c);
      // exit;
    $as = [
      'u'=>$u,
      'id'=>$id,
      'c'=>$c,
      'pics'=>$pics,
    ];
    return $this->app->renderer->render($response, './u/company-form.php', $as);
  }

  public function mystaffs($request, $response, $args){
    setcookie("thismyuri", '/ucenter.html', mktime()+31104000,'/');
    global $db;
     $u = $request->getAttribute('u');//用户          
      //查询我的在执行中的合同
     $contract=$db->select('contract',['id','staffId','sId'],['AND'=>['uId'=>$u['id'],'status'=>5]]);
     $cont=[];
     for($i=0;$i<count($contract);$i++){
      $member=$db->get('member',['avatar','name','mobile','id'],['id'=>$contract[$i]['staffId']]);
          $cont[$i]['avatar']=$member['avatar'];//服务者头像
          $cont[$i]['name']=$member['name'];//服务者姓名
          $cont[$i]['mobile']=$member['mobile'];//服务者电话
          $cont[$i]['staffid']=$member['id'];//服务者ID
          $cont[$i]['title']=$db->get('mcms_service','title',['id'=>$contract[$i]['sId']]);
        };
     // var_dump($count);
     // exit;
        $as = [
          'u'=>$u,
          'cont'=>$cont,
        ];
        return $this->app->renderer->render($response, './u/mystaffs.php', $as);
      }



      public function complaint($request, $response, $args){
        global $db;
      $u = $request->getAttribute('u');//用户
      //查询投诉建议信息
      $complaint=$db->select('complains','*',['cid'=>$u['id'],'ORDER'=>['id'=>'DESC']]);
      $a=[];
      for($i=0;$i<count($complaint);$i++){
        if($complaint[$i]['memberid']!=0){
          $a['name']=$db->get('member','name',['id'=>$complaint[$i]['memberid']]);
          $a['mobile']=$db->get('member','mobile',['id'=>$complaint[$i]['memberid']]);
          $complaint[$i]['complaintname']=$a['name'].' '.$a['mobile'];
        }
        //查询处理人
        $complaint[$i]['replyname']=$db->get('member','name',['id'=>$complaint[$i]['replymemberid']]);
        $complaint[$i]['replymobile']=$db->get('member','mobile',['id'=>$complaint[$i]['replymemberid']]);
      }
      $as = [
        'u'=>$u,
        'complaint'=>$complaint,
      ];
      return $this->app->renderer->render($response, './u/complaint.php', $as);
    }

  public function creatComplaint($request, $response, $args){
    setcookie("thismyuri", '/ucenter.html', mktime()+31104000,'/');
      global $db;
      $u = $request->getAttribute('u');//用户
      if(isset($_GET['id'])&&$_GET['id']!=''){
        $id=$_GET['id'];
        $member=$db->get('member',['id','name','mobile'],['id'=>$id]);
      }else{
        $id=0;
        $member='';
      }
      // var_dump($id);
      // exit;
      $as = [
        'u'=>$u,
        'id'=>$id,
        'member'=>$member,
      ];
      return $this->app->renderer->render($response, './u/complaint-form.php', $as);
    }

    public function customservice($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $as = [
        'u'=>$u,
      ];
      return $this->app->renderer->render($response, './u/customservice.php', $as);
    }


    public function creatsj($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $as = [
        'u'=>$u,
      ];
      return $this->app->renderer->render($response, './u/creatsj.php', $as);
    }

    public function sjs($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $as = [
        'u'=>$u,
      ];
      return $this->app->renderer->render($response, './u/sjs.php', $as);
    }

    public function uinfo($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      if(isset($u['pics'])&&$u['pics']!=''){
       $pics=$db->get('mcms_attachment','thumbnail',['id'=>$u['pics']]);
     }else{
      $pics='';
    }
    $as = [
      'u'=>$u,
      'pics'=>$pics,
    ];
    return $this->app->renderer->render($response, './u/uinfo.php', $as);
  }

  public function editpwd($request, $response, $args){
    setcookie("thismyuri", '/ucenter.html', mktime()+31104000,'/');
    global $db;
      $u = $request->getAttribute('u');//用户
      $as = [
        'u'=>$u,
      ];
      return $this->app->renderer->render($response, './u/editpwd.php', $as);
    }


    public function wallet($request,$response,$args){
      global $db;
      $u = $request->getAttribute('u');//用户
      //查询客户钱包表
      $walet=$db->get('wallets','*',['AND'=>['uid'=>$u['id'],'utype'=>0,]]);
      $list = [0,1,2,3,4,5];
      $as = [
        'u'=>$u,
        'list'=>$list,
        'walet'=>$walet,
      ];
      return $this->app->renderer->render($response, './u/wallet.php', $as);
    }

     //getWallet(钱包明细)
    public function getWallet($request,$response,$args){
      global $db;
      $u = $request->getAttribute('u');//用户
       //分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      //查询明细表
      $wallets=$db->select('takenow','*',['AND'=>['uid'=>$u['id'],'type'=>[1,0],'class'=>[1,55,58,59]],'ORDER'=>['id'=>'DESC'],'LIMIT'=>[$srow,10],]);
      // var_dump($wallet);
      // exit;
      $wallet=[];
      for($i=0;$i<count($wallets);$i++){
          //查询合同
        if($wallets[$i]['class']==55||$wallets[$i]['class']==58||$wallets[$i]['class']==59){
          $money=$db->get('orders','price',['id'=>$wallets[$i]['targetId']]);
          $wallets[$i]['ordermoney']=$money;
          //查询购买的服务
        }else{
          $wallets[$i]['ordermoney']=0;
        }
        if($wallets[$i]['class']==55||$wallets[$i]['class']==58||$wallets[$i]['class']==59){
          $orders=$db->get('orders','*',['id'=>$wallets[$i]['targetId']]);

          //查询购买的服务
          if($orders['type']==1){
            //套餐
            $service=$db->get('mcms_group_service','*',['id'=>$orders['sid']]);
            //查询套餐第一个商品
            $services=$db->get('mcms_service','title',['id'=>$service['sid0']]);
            $wallets[$i]['contrname']=$services;
            //计算数量
            $cou=$db->select('contract','id',['orderId'=>$orders['id']]);
            $wallets[$i]['count']=count($cou);
          }else{
            //单品
            $service=$db->get('mcms_service','title',['id'=>$orders['sid']]);
              // $wallet[$i]['num']='';
            $wallets[$i]['contrname']=$service;
            $wallets[$i]['count']=1;
          }
        }
      }
      //求总数
      $count=$db->count('takenow','*',[
        'AND'=>[
          'uid'=>$u['id'],
          'type'=>0,
        ],
      ]);
      $allp = round($count/10);//总页数
      $json = array('flag' => 200,'msg' => '成功', 'data' => [

        'wallets'=>$wallets,'count'=>$count,'allp'=>$allp
      ]);
      return $response->withJson($json);
         // var_dump($wallet);
    }


    
    //update password
    public function updatePow($request,$response,$args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $umobile=$_POST['umobile'];//旧密码
      $upassword=$_POST['upassword'];//新密码
      $repassword=$_POST['repassword'];//确认新密码
      $pwd=MD5($umobile);
      //判断旧密码输入
      //if($pwd==$u['password']){
        if($upassword==$repassword){
          $update=$db->update('customs',['password'=>$repassword,],['id'=>$u['id'],]);
          if($update){
            $json = array('flag' => 200,'msg' => '修改成功', 'data' => []);
            return $response->withJson($json);
            exit();
          }else{
            $json = array('flag' => 400,'msg' => '修改失败', 'data' => []);
            return $response->withJson($json);
            exit();
          }
        }else{
          $json = array('flag' => 400,'msg' => '两次密码不一致', 'data' => []);
          return $response->withJson($json);
          exit();
        }
      // }else{
      //   $json = array('flag' => 400,'msg' => '旧密码错误', 'data' => []);
      //   return $response->withJson($json);
      //   exit();
      // }
    }
    
    //update info(修改个人资料)
    public function updateInfo($request,$response,$args){
      global $db;
      $u = $request->getAttribute('u');//用户
       $picsid=$_POST['picsd'];//图片id
      $area=explode(' ',$_POST['city']);//分割地址
      if($_POST['city']==''){
        $area[0]='';
        $area[1]='';
        $area[2]='';
      }
      //执行修改
      $updateinfo=$db->update('customs',[
        'sexy'=>$_POST['sexy'],
        'birthday'=>$_POST['birthday'],
        'prov'=>$area[0],
        'city'=>$area[1],
        'area'=>$area[2],
        'address'=>$_POST['address'],
        'sfz'=>$_POST['sfz'],
        'name'=>$_POST['name'],
        'pics'=>$picsid,
      ],[
        'id'=>$u['id'],
      ]);
      if($updateinfo){
        $json = array('flag' => 200,'msg' => '修改成功', 'data' => []);
        return $response->withJson($json);
        exit();
      }else{
        $json = array('flag' => 400,'msg' => '修改失败', 'data' => []);
        return $response->withJson($json);
        exit();
      }
    }

     //insert boppo(客户提供商机)
    public function insertBoppo($request,$response,$args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $area=explode(' ',$_POST['city']);//分割地址
      if($_POST['city']==''){
        $area[0]='';
        $area[1]='';
        $area[2]='';
      }
      $form='客户'.$u['name'].$u['mobile'];
      //写入商机表
      $boppo=$db->insert('boppo',['uname'=>$_POST['name'],'mobile'=>$_POST['mobile'],'cateId'=>$_POST['cateId'],'qd'=>$_POST['qd'],'text'=>$_POST['text'],'prov'=>$area[0],'city'=>$area[1],'area'=>$area[2],'creattime'=>date('Y-m-d H:i:s'),'form'=>$form,'customsId'=>$u['id'],'status'=>1]);
      if($boppo){
        $json = array('flag' => 200,'msg' => '添加成功', 'data' => []);
        return $response->withJson($json);
        exit();
      }else{
        $json = array('flag' => 400,'msg' => '添加失败', 'data' => []);
        return $response->withJson($json);
        exit();
      }
    }

    //insert service(获取客户的提问内容)
    public function insertService($request,$response,$args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $title=$_POST['title'];
      $text=$_POST['text'];
      //执行写入问题表
      $service=$db->insert('customs_service',['name'=>$u['name'],'mobile'=>$u['mobile'],'title'=>$title,'text'=>$text,'creattime'=>date('Y-m-d H:i:s'),'status'=>0,]);
      if($service){
       $json = array('flag' => 200,'msg' => '提交成功', 'data' => []);
       return $response->withJson($json);
       exit();
     }else{
       $json = array('flag' => 400,'msg' => '提交失败', 'data' => []);
       return $response->withJson($json);
       exit();
     }

   }

     //insert company(客户添加关联企业)
   public function insertCompany($request,$response,$args){
        global $db;
        $u = $request->getAttribute('u');//用户
        if(!isset($_GET['id'])){
          $id = 0;
        }else{
          $id = $_GET['id'];
        }
        if($id != 0){
           //执行修改操作
         $ress = explode(' ',$_POST['prov']);
         if($_POST['prov']==''){
          $ress[0]='';
          $ress[1]='';
          $ress[2]='';
        }
             //写入企业表
        $company=$db->update('companies',[
            'companyname'=>$_POST['companyname'],//企业名称
            'decname'=>$_POST['decname'],//企业简称
            'cno'=>$_POST['cno'],//营业执照编号
            'ctype'=>$_POST['ctype'],//企业类型
            'swno'=>$_POST['swno'],//税务登记号
            'prov'=>$ress[0],//省
            'city'=>$ress[1],//市
            'area'=>$ress[2],//区
            'address'=>$_POST['address'],//具体地址
            'companyctime'=>$_POST['companyctime'],//工商注册日期
            'fr'=>$_POST['fr'],//法人
            'companym'=>$_POST['companym'],//注册资金
            'hy'=>$_POST['hy'],//所属行业
            'na'=>$_POST['na'],//国税帐号
            'napwd'=>$_POST['napwd'],//国税密码
            'na_end_day'=>$_POST['na_end_day'],//国税到期时间
            'nb'=>$_POST['nb'],//地税帐号
            'nbpwd'=>$_POST['nbpwd'],//地税密码
            'vpn'=>$_POST['vpn'],//vpn帐号
            'vpnpwd'=>$_POST['vpnpwd'],//vpn密码
            'vpn_end_day'=>$_POST['vpn_end_day'],//vpn到期时间
            'webpname'=>$_POST['webpname'],//网上办事帐号
            'webppwd'=>$_POST['webppwd'],//网上办事密码
            'content'=>$_POST['content'],//经营范围
            'cus_1'=>$u['id'],//联系人
            'logo'=>$_POST['picsd'],//企业logo
          ],['id'=>$id]);
        if($company){
          $json = array('flag' => 200,'msg' => '修改成功', 'data' => []);
          return $response->withJson($json);
          exit();
        }else{
         $json = array('flag' => 400,'msg' => '修改失败', 'data' => []);
         return $response->withJson($json);
         exit();
       }
     }else{
          //执行添加操作
        //查询企业名称是不是存在
      $comp=$db->get('companies','id',['companyname'=>$_POST['companyname']]);
      if($comp){
        $json = array('flag' => 400,'msg' => '该企业已经存在', 'data' => []);
         return $response->withJson($json);
         exit();
      }
      $ress = explode(' ',$_POST['prov']);
      if($_POST['prov']==''){
        $ress[0]='';
        $ress[1]='';
        $ress[2]='';
      }
        //写入企业表
      $company = $db->insert('companies',[
            'companyname'=>$_POST['companyname'],//企业名称
            'decname'=>$_POST['decname'],//企业简称
            'cno'=>$_POST['cno'],//营业执照编号
            'ctype'=>$_POST['ctype'],//企业类型
            'swno'=>$_POST['swno'],//税务登记号
            'prov'=>$ress[0],//省
            'city'=>$ress[1],//市
            'area'=>$ress[2],//区
            'address'=>$_POST['address'],//具体地址
            'companyctime'=>$_POST['companyctime'],//工商注册日期
            'fr'=>$_POST['fr'],//法人
            'companym'=>$_POST['companym'],//注册资金
            'hy'=>$_POST['hy'],//所属行业
            'na'=>$_POST['na'],//国税帐号
            'napwd'=>$_POST['napwd'],//国税密码
            'na_end_day'=>$_POST['na_end_day'],//国税到期时间
            'nb'=>$_POST['nb'],//地税帐号
            'nbpwd'=>$_POST['nbpwd'],//地税密码
            'vpn'=>$_POST['vpn'],//vpn帐号
            'vpnpwd'=>$_POST['vpnpwd'],//vpn密码
            'vpn_end_day'=>$_POST['vpn_end_day'],//vpn到期时间
            'webpname'=>$_POST['webpname'],//网上办事帐号
            'webppwd'=>$_POST['webppwd'],//网上办事密码
            'content'=>$_POST['content'],//经营范围
            'cus_1'=>$u['id'],//联系人
            'logo'=>$_POST['picsd'],//企业logo
          ]);
      if($db->id() >0){
        $json = array('flag' => 200,'msg' => '保存成功', 'data' => ['sql'=>$company]);
        return $response->withJson($json);
        exit();
      }else{
       $json = array('flag' => 400,'msg' => '保存失败', 'data' => ['sql'=>$company]);
       return $response->withJson($json);
       exit();
     }
   }
 }

 public function getsjs($request,$response,$args){
  setcookie("thismyuri", '/ucenter.html', mktime()+31104000,'/');
  global $db;
      $u = $request->getAttribute('u');//用户
      //分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      //查询当前客户录入的上级信息
      $list = $db->select('boppo','*',[
        'customsId'=>$u['id'],
        'ORDER'=>['id'=>'DESC'],
        'LIMIT'=>[$srow,10]
      ]);
      //求总数
      $count = $db->count('boppo',[
        'customsId'=>$u['id']
      ]);
      //循环查询处理结果
      $a=[];
      for($i=0;$i<count($list);$i++){
       $a=$db->select('boppo_go_log',['text','creatTime','uid'],['sid'=>$list[$i]['id']]);
       for($j=0;$j<count($a);$j++){
        $a[$j]['name']=$db->get('member','name',['id'=>$a[$j]['uid']]);
        $a[$j]['mobile']=$db->get('member','mobile',['id'=>$a[$j]['uid']]);
      }


      $list[$i]['result']=$a;
       //业务类型
      $list[$i]['type']=$db->get('contract_type','typename',['AND'=>['id'=>$list[$i]['cateId'],'pid'=>0]]);
    }
      $allp = round($count/10);//总页数
      $json = array('flag' => 200,'msg' => '成功', 'data' => [

        'list'=>$list,'count'=>$count,'allp'=>$allp
      ]);
      return $response->withJson($json);

    }

    public function insertComplaint($request,$response,$args){
      setcookie("thismyuri", '/ucenter.html', mktime()+31104000,'/');
      global $db;
      $u = $request->getAttribute('u');//用户
      $type = $_POST['type'];//类型  1建议  2 投诉
      $text = $_POST['text'];//内容
      $mobile = $_POST['mobile'];//被投诉人的电话
      // if(isset($_POST['staffid'])&&$_POST['staffid']!=0){
      //   $id=$_POST['staffid'];
      // }else{
      //   $id=null;
      // };
      
      if($type==0){
        //建议
        $comp = $db->insert('complains',[
          'cid'=>$u['id'],
          'text'=>$text,
          'memberid'=>0,
          'creattime'=>date('Y-m-d H:i:s'),
          'status'=>0,
        ]);
        if($comp){
          //发送短信-客户
          
            $push= puch(0,19,[$u['mobile'],],['',],[]);
          
          

          $json = array('flag' => 200,'msg' => '您的问题我们已经收到！客服快马加鞭赶过来了', 'data' => []);
          return $response->withJson($json);
          exit();
        }else{
          $json = array('flag' => 400,'msg' => '提交失败', 'data' => []);
          return $response->withJson($json);
          exit();
        }
      }else{
        //投诉
        // $mobile=$_POST['mobile'];
        if($mobile!=''){
          $id = $db->get('member','id',['mobile[~]'=>$mobile]);
        }else{
          $id = NULL;
        }
        $member = $db->get('member',['id','name','mobile'],['id'=>$id]);
        $comp=$db->insert('complains',[
          'cid'=>$u['id'],
          'text'=>$text,
          'memberid'=>$id,
          'creattime'=>date('Y-m-d H:i:s'),
          'status'=>0,
        ]);
        if($comp){
          //投诉短信发送-客户
           $push= puch(0,17,[$u['mobile'],],['',],['name'=>'【'.$member['name'].'】']);
           //投诉短信发送-服务者
           $push= puch(0,18,[$member['mobile'],],['',],['name'=>'【'.$u['name'].'】','text'=>'【'.$text.'】']);
          $json = array('flag' => 200,'msg' => '您的问题我们已经收到！客服快马加鞭赶过来了', 'data' => []);
          return $response->withJson($json);
          exit();
        }else{
          $json = array('flag' => 400,'msg' => '提交失败', 'data' => []);
          return $response->withJson($json);
          exit();
        }
        
      }
    }

    public function compMobile($request,$response,$args){
      global $db;
      $mobile=$args['mobile'];
      $member=$db->get('member','name',['mobile'=>$mobile]);
      if($member){
        return $response->withJson($member);
      }else{
        return false;
      }
    }

      //upddate star(客户赠送服务者星星)
    public function updateStar($request,$response,$args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $staffid=$_POST['staffid'];
      //查询星星表 member_star
      $star = $db->count('member_star',['AND'=>['customsId'=>$u['id'],'staffId'=>$staffid]]);
      if($star>10){
        //如果存在 不能提交
        $json = array('flag' => 200,'msg' => '您已经给过TA星星了', 'data' => []);
        return $response->withJson($json);
        exit();
      }else{
        //如果不存在可以提交
        // $member = $db->get('member','star',['id'=>$staffid]);
        // if(isset($member)&&$member!=''){
        //   $member+=1;
        // }else{
        //   $member=1;
        // }
        $memberstar = $db->update('member',['star[+]'=>1],['id'=>$staffid]);
        if($memberstar){
            //写入星星表
          $stars=$db->insert('member_star',[
           'customsId'=>$u['id'],
           'staffId'=>$staffid,
           'creattime'=>date('Y-m-d H:i:s'),
           'remarks'=>'赠送星星',
         ]);
          if($stars){
            $json = array('flag' => 200,'msg' => '感谢您给了TA一颗星星', 'data' => []);
            return $response->withJson($json);
            exit();
          }else{
            $json = array('flag' => 400,'msg' => '赠送星星失败', 'data' => []);
            return $response->withJson($json);
            exit();
          }
        }else{
          $json = array('flag' => 200,'msg' => '赠送星星失败', 'data' => []);
          return $response->withJson($json);
          exit();
        }
      }

    }

    //update companies(关联企业 修改订单企业)
    public function updateCompaniesid($request,$response,$args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $ordersid=$_POST['ordersId'];//订单id
      $comid=$_POST['id'];//企业id
      //修改订单的企业ID
      $order=$db->update('orders',['comanyId'=>$comid],['id'=>$ordersid]);
      if($order){
          //根据订单id 查询合同
        $orders=$db->select('contract',['id','sId'],['orderId'=>$ordersid]);
        for($i=0;$i<count($orders);$i++){
          $update=$db->update('contract',['comanyId'=>$comid],['id'=>$orders[$i]['id']]);
        }
        $json = array('flag' => 200,'msg' => '关联企业成功', 'data' => []);
        return $response->withJson($json);
        exit();
      }else{
        $json = array('flag' => 400,'msg' => '关联企业失败', 'data' => []);
        return $response->withJson($json);
        exit();
      }

    }

    //inser tel(客户拨打电话写入记录)
    public function inserTel($request,$response,$args){
      global $db;
       $u = $request->getAttribute('u');//用户
       $tel=$_POST['tel'];
       $id=$db->get('member','id',['mobile'=>$tel]);
       $tels=$db->insert('tel',[
        'customsId'=>$u['id'],
        'memberId'=>$id,
        'creattime'=>date('Y-m-d H:i:s'),
            'type'=>1,//客户给员工打
          ]);
       if($tels){
         $json = array('flag' => 200,'data' => ['id'=>$db->id()]);
         return $response->withJson($json);
         exit();
       }else{
         $json = array('flag' => 400,'msg'=>'拨打失败','data' => []);
         return $response->withJson($json);
         exit();
       }
     }

    //update tel(客户拨打电话修改记录)
     public function updateTel($request,$response,$args){
      global $db;
       $u = $request->getAttribute('u');//用户
       $id=$_POST['id'];
       $comment=$_POST['comment'];
       $update=$db->update('tel',[
        'comment'=>$comment,
      ],[
        'id'=>$id,
      ]);
       if($update){
         $json = array('flag' => 200,'data' => []);
         return $response->withJson($json);
         exit();
       }else{
         $json = array('flag' => 400,'msg'=>'拨打失败','data' => []);
         return $response->withJson($json);
         exit();
       }

     }

    //customOK (客户确认合同完结)
     public function contractOk($request,$response,$args){
       global $db;
       $sid=$_POST['id'];
       $b = $db->get('contract',['id','staffId','orderId','uId'],[
        'id'=>$sid
      ]);
       $mid= $db->insert("contract_speed", [
         "oId"=>$sid,
         "text"=>'客户确认完结',
         "type"=>'customok',
         "creattime" => date('Y-m-d H:i:s'),
         "uid" => $b['staffId'],
         'cutoms_ok'=>'1'
       ]);
      //查询合同 查看属于哪一类
       if($mid){
         $md=$db->update("contract",['status'=>'8'],['id'=>$sid]);
          //   $ordernumber=$db->get('contract','orderId',['id'=>$sid]);
          // $db->update('orders',['status'=>3],['id'=>$ordernumber]);
         $order=$db->get('orders','*',[
          'id'=>$b['orderId'],
        ]);
             //判断是部是套餐
         if($order['type']==0){
          $orders=$db->update('orders',[
            'status'=>3,
          ],[
            'id'=>$b['orderId'],
          ]);
        }
        if($order['type']==1){
         $contractoid=$db->select('contract','status',['orderId'=>$b['orderId']]);
         if(in_array('7', $contractoid)||in_array('5', $contractoid)||in_array('3', $contractoid)){
                          //continue;
         }else{
          $orders=$db->update('orders',[
            'status'=>3,
          ],[
            'id'=>$b['orderId'],
          ]);
        }
      }

      if($md){
        $flag = 200;
        $msg = '合同处理成功';
      }
      else{
        $flag = 400;
        $msg = '合同处理失败';
      }
    }
    else{
      $flag = 400;
      $msg = '合同处理失败';
    }
    $json = array('flag' => $flag,'msg' => $msg);
    return $response->withJson($json);
  }

    //ucontractOk （客户确认代帐类特殊服务）
  public function ucontractOk($request,$response,$args){
    global $db;
    $da=$_POST['da'];
    $mid=$db->update("contract_speed",[
      "cutoms_ok"=>$da['type']
    ],[
      "id"=>$da['id']
    ]);
    //查询第代理记账第三此确认  代理记账类合同客户确认服务写入
    $contractspeed=$db->get('contract_speed','*',[
      'AND'=>[
       'id'=>$da['id'],
       'cutoms_ok'=>1,
       'stype'=>'binding',
     ]
   ]);
    if(!empty($contractspeed)){
          //查询合同信息 需要服务提成 
      $contract=$db->get('contract','*',[
        'id'=>$contractspeed['oId'],
      ]);
    //     //根据订单id查询流水表
      $takenow=$db->get('takenow','*',[
        'AND'=>[
          'class'=>52,
          'targetId'=>$contract['id'],
          'uid'=>$contract['staffId'],
          'status'=>0,
        ]
      ]);
    //     //修改流水状态并且修改余额表
      $take=$db->update('takenow',[
        'status'=>1,
        'updatetime'=>date('Y-m-d H:i:s'),
        'remarks'=>'客户确认完成，服务费发放',
      ],[
        'id'=>$takenow['id'],
      ]);
      if($take){
            //修改成功后 写入余额表
        $wall=$db->update('wallets',[
          'balance[+]'=>$takenow['money'],
        ],[
          'AND'=>[
            'utype'=>1,
            'uid'=>$takenow['uid'],
          ]
        ]);
      }
      $upid=getup($contract['staffId']);
      if(!empty($upid)){
       $takenow_1=$db->get('takenow','*',[
        'AND'=>[
          'class'=>60,
          'targetId'=>$contract['id'],
          'uid'=>$upid,
          'status'=>0,
        ]
      ]);
        //修改流水状态并且修改余额表
       $take_1=$db->update('takenow',[
        'status'=>1,
        'updatetime'=>date('Y-m-d H:i:s'),
        'remarks'=>'客户确认完成，服务费发放',
      ],[
        'id'=>$takenow_1['id'],
      ]);
       if($take_1){
            //修改成功后 写入余额表
        $wall_1=$db->update('wallets',[
          'balance[+]'=>$takenow_1['money'],
        ],[
          'AND'=>[
            'utype'=>1,
            'uid'=>$takenow_1['uid'],
          ]
        ]);
      }
      $lastid=getup($upid);
      if(!empty($upid)){

       $takenow_2=$db->get('takenow','*',[
        'AND'=>[
          'class'=>61,
          'targetId'=>$contract['id'],
          'uid'=>$lastid,
          'status'=>0,
        ]
      ]);
        //修改流水状态并且修改余额表
       $take_2=$db->update('takenow',[
        'status'=>1,
        'updatetime'=>date('Y-m-d H:i:s'),
        'remarks'=>'客户确认完成，服务费发放',
      ],[
        'id'=>$takenow_2['id'],
      ]);
       if($take_2){
            //修改成功后 写入余额表
        $wall=$db->update('wallets',[
          'balance[+]'=>$takenow_2['money'],
        ],[
          'AND'=>[
            'utype'=>1,
            'uid'=>$takenow_2['uid'],
          ]
        ]);
      }
    }
  }
}
    //代理记账合同服务提成写入完成
if($mid){
  $flag=200;
  $msg='确认成功';
}else{
  $flag=400;
  $msg='确认失败，数据错误';
}
$json = array('flag' => $flag,'msg'=>$msg);
return $response->withJson($json);
}

public function invoice($request,$response,$args){
  setcookie("thismyuri", '/ucenter.html', mktime()+31104000,'/');
  global $db;
      $u = $request->getAttribute('u');//用户
      //查询我申请发票的信息
      $invoice=$db->select('orders_invoice','*',[
        'AND'=>[
          'customId'=>$u['id'],
          'status[!]'=>2,
        ]]);
      $as = [
        'u'=>$u,
        'invoice'=>$invoice,
      ];
      return $this->app->renderer->render($response, './u/invoice.php', $as);
    }

    public function invoiceAdd($request,$response,$args){
      global $db;
      $u = $request->getAttribute('u');//用户
      //查询客户名下有哪些可以开的发票
      $invoice=$db->select('orders','*',[
        'AND'=>[
          'uid'=>$u['id'],
          'status'=>[1,2,3,7],
          'isinvoice'=>0,
        ]
        // 'status'=>3
      ]);
      //查询客户名下的发票申请

      $list = $db->get('orders_invoice','*',['customId'=>$u['id']]);
      if(empty($list)){
        $list=[];
      }
      // var_dump($invoice);
      // exit;
      $as = [
        'u'=>$u,
        'invoice'=>$invoice,
        'list'=>$list,
      ];
      return $this->app->renderer->render($response, './u/invoice-form.php', $as);
    }

    public function invoiceDetail($request,$response,$args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $id=$args['id'];
      $invoice=$db->get('orders_invoice','*',['id'=>$id]);
      $as = [
        'u'=>$u,
        'invoice'=>$invoice,
      ];
      return $this->app->renderer->render($response, './u/invoice-detail.php', $as);
    }


     //update address(客户修改收获地址)
    public function updateAddress($request,$response,$args){
      global $db;
    $u = $request->getAttribute('u');//用户
    // $id=$_POST['id'];//客户id
    $address=$_POST['address'];//具体地址
    $city=explode(' ',$_POST['city']);//先择的省市区
    //修改地址
    $prov=$db->update('customs',['prov'=>$city[0],'city'=>$city[1],'area'=>$city[2],'address'=>$address,],[ 'id'=>$u['id'],]);
    if($prov){
      $flag=200;
      $msg='地址修改成功';
    }else{
      $flag=400;
      $msg='修改地址失败';
    }
    $json = array('flag' => $flag,'msg'=>$msg);
    return $response->withJson($json);
  }

    //form insert invoice(客户申请发票请求)
  public function formInvoice($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $type=$_POST['selectid'];
    $oid=explode(' ',$_POST['oid']);
    for($i=0;$i<count($oid);$i++){
      if($oid[$i]!=''){
        //根据订单id 修改状态
        $order=$db->update('orders',[
          'isinvoice'=>1,
        ],[
          'orderId'=>$oid[$i],
        ]);
      }
    }
    $invoiceId=num();
    if($type==3){
      $invoice=$db->insert('orders_invoice',[
        'invoiceId'=>$invoiceId,
        'customId'=>$u['id'],
        'customName'=>$u['name'],
        'customMobile'=>$_POST['mobile'],
        'custiomAddress'=>$_POST['address'],
        'total'=>$_POST['total'],
        'remark'=>$_POST['remark'],
        'status'=>0,
        'creattime'=>date('Y-m-d H:i:s'),
        'utype'=>$type,
        'companyname'=>$_POST['name'],
        'content'=>$_POST['oid'],
      ]);
      if($invoice){
        $flag=200;
        $msg='申请发票成功，等办理';
      }else{
        $flag=400;
        $msg='申请发票失败，请重新申请';
      }
      $json = array('flag' => $flag,'msg'=>$msg);
      return $response->withJson($json);
    }else{
      $invoice=$db->insert('orders_invoice',[
        'invoiceId'=>$invoiceId,
        'customId'=>$u['id'],
        'customName'=>$u['name'],
        'customMobile'=>$_POST['mobile'],
        'custiomAddress'=>$_POST['address'],
        'total'=>$_POST['total'],
        'remark'=>$_POST['remark'],
        'status'=>0,
        'creattime'=>date('Y-m-d H:i:s'),
        'utype'=>$type,
        'openingBank'=>$_POST['zh'],
        'swno'=>$_POST['sh'],
        'bank'=>$_POST['kh'],
        'companyname'=>$_POST['name'],
        'content'=>$_POST['oid'],
        'laddress'=>$_POST['laddress'],
      ]);
      if($invoice){
        $flag=200;
        $msg='申请发票成功，等办理';
      }else{
        $flag=400;
        $msg='申请发票失败，请重新申请';
      }
      $json = array('flag' => $flag,'msg'=>$msg);
      return $response->withJson($json);

    }

  }

    //invoice update('客户撤销申请')
  public function invoiceUpdate($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $id=$_POST['id'];
    //查询发表信息
    $invoice=$db->get('orders_invoice','*',['id'=>$id]);
    // var_dump($invoice);
    // exit;
    $in=explode(' ',$invoice['content']);
    for($i=0;$i<count($in);$i++){
      if($in[$i]!=''){
            //修改订单的发票
        $order=$db->update('orders',['isinvoice'=>0],['orderId'=>$in[$i]]);
      }
    }
    //修改发票状态
    $inv=$db->update('orders_invoice',['status'=>2],['id'=>$id]);
    if($inv){
      $flag=200;
      $msg='撤销成功';
    }else{
      $flag=400;
      $msg='撤销失败';
    }
    $json = array('flag' => $flag,'msg'=>$msg);
    return $response->withJson($json);

  }


  //加载客户未支付的订单列表
  public function noAlipay($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    //查询客户订单信息
    $time = date('Y-m-d H:i:s',strtotime("-1 hours"));
    $orders = $db->select('orders','*',[
      'AND'=>[
        'uid'=>$u['id'],
        'status'=>0,
        'creattime[>]'=>$time
      ],
      'ORDER'=>['id'=>'DESC'],
      ]);
    // var_dump($orders);
    // exit;
    $i=0;
    $list=[];
    $contract=[];
    foreach($orders as $o){
      //下单时间
      $list[$i]['creattime']=$o['creattime'];
      //订单号
      $list[$i]['orderId']=$o['orderId'];
      //订单价格
      $list[$i]['price']=$o['price'];
      //订单付款编号(点击付款的传值)
      $list[$i]['payid']=$o['payid'];

      //服务信息需要判断是单品还是套餐
      if($o['type']==0){
        //单品 查询单品信息
          $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sid']]);
          $list[$i]['iconClass']=$db->get('mcms_service','iconClass',['id'=>$o['sid']]);
          $list[$i]['iconColor']=$db->get('mcms_service','iconColor',['id'=>$o['sid']]);
          // $list[$i]['contract']=$contract;
          //单品
          $list[$i]['type']=0;

      }else{
        //套餐 
        //1 查询套餐包含哪些服务
        $group=$db->get('mcms_group_service','*',['id'=>$o['sid']]);
        //点击付款后的title传值
        $list[$i]['title']=$group['group_title'];
        $ar[$i]=array($group['sid0'],$group['sid1'],$group['sid2'],$group['sid3'],$group['sid4'],$group['sid5']);
        $arr[$i]=array_filter($ar[$i]);
        //循环查询套餐内容
        for($j=0;$j<count($arr[$i]);$j++){
            $contract[$j]['title']=$db->get('mcms_service','title',['id'=>$arr[$i][$j]]);
            $contract[$j]['iconClass']=$db->get('mcms_service','iconClass',['id'=>$arr[$i][$j]]);
            $contract[$j]['iconColor']=$db->get('mcms_service','iconColor',['id'=>$arr[$i][$j]]);
             $list[$i]['contract']=$contract;
        }
        //套餐
        $list[$i]['type']=1;
      }

      $i++;
    }

    // var_dump($list);
    // exit;
    // var_dump($orders);
    // exit;
    $as = [
      'u'=>$u,
      'list'=>$list,//订单数据
    ];
    return $this->app->renderer->render($response, './u/orders_noalipay.php', $as);
  }

   //加载客户评价页面
  public function orderComment($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $id = $args['id'];
    $contract = $db->get('contract',['id','sId'],['id'=>$id]);
    $sw = $db->get('mcms_service',[
      '[>]mcms_attachment'=>['thumbnail'=>'id']
    ],[
      'mcms_service.id',
      'mcms_service.thumbnail',
      'mcms_attachment.thumbnail(thumbnail320)'
    ],[
      'mcms_service.id'=>$contract['sId']
    ]);
    // var_dump($id);
    // exit;
     $as = [
      'u'=>$u,
      'id'=>$id,
      'sw'=>$sw
    ];
    return $this->app->renderer->render($response, './u/comment.php', $as);
  }

  //执行评价写入
  public function inserEvaluate($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $id = $_POST['cid'];//合同id
    $star = $_POST['star'];//评价星级
    $text = $_POST['text'];//评价的内容
    $pics = $_POST['pics'];//评价的图片
    //查询合同内容
    $contract = $db->get('contract',['id','staffId','sId'],['id'=>$id]);
    // var_dump($contract);
    // exit;
     //查询评价表 判断是不是重复评价
    $ev=$db->get('orders_evaluate','*',[
        "AND"=>[
          'customID'=>$u['id'],//客户id
          'staffID'=>$contract['staffId'],//服务者id
          'orderID'=>$id,//合同id
          ]
      ]);
     if($ev){
        $flag = 200;
        $msg='请不要重复评价';
        $json = array('flag' =>200,'msg' =>'已经评价过了请不要重复评价');
        return $response->withJson($json);

    }else{
      //写入订单评价表
     $eval = $db->insert('orders_evaluate',[
         'orderID'=>$id,//订单id
        'star'=>$star,//星级数量
        'evatext'=>$text,//评价内容
        'creattime'=>date('Y-m-d H:i:s'),//添加时间
        'staffID'=>$contract['staffId'],//服务者
        'customID'=>$u['id'],//客户id
        'sid'=>$contract['sId'],//服务id
        'pics'=>$pics
      ]);

     if($eval){
       //查询统计表
        $sta=$db->select('member_statistics','*',[
            'staffID'=>$contract['staffId'],
          ]);
        if(empty($sta)){
              //如果不存在统计信息 从新插入
              $stati=$db->insert('member_statistics',[
                    'staffID'=>$contract['staffId'],//服务者id
                    'stars'=>$star,//星级数量
                    'orders_eva'=>1,//评价数量
                ]);
               if($stati){
                    $flag=200;
                    $msg='评价成功';
                  }else{
                    $stati=0;
                    $flag=400;
                    $msg='评价失败，数据错误';
                 };
               $json = array('flag' => $flag,'msg' => $msg);
              return $response->withJson($json);
          }else{
              //修改统计表
              $stati=$db->update('member_statistics',[
                    'stars[+]'=>$star,//星级数量
                    'orders_eva[+]'=>1,//评价的数量
                ],[
                    'staffID'=>$contract['staffId'],
                ]);
              if($stati){
                    $flag=200;
                    $msg='评价成功';
                }else{
                    $stati=0;
                    $flag=400;
                    $msg='评价失败，数据错误';
                }
               $json = array('flag' => $flag,'msg' => $msg);
                return $response->withJson($json);
          }
      }else{
         $json = array('flag' =>400,'msg' =>'评价失败');
        return $response->withJson($json);
      }
    }
  }

    //加载客户添加备注页面
  public function orderRemark($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $oid=$args['id'];//订单id
    $cid=$_GET['cid'];//合同id
    $as = [
      'u'=>$u,
      'oid'=>$oid,//订单id
      'cid'=>$cid,//合同id
    ];
    return $this->app->renderer->render($response, './u/orders_remark.php', $as);
  }

  //客户添加备注请求
  public function uinsertRemark($request,$response,$args){
     global $db;
     $u = $request->getAttribute('u');//用户
     $oid=$_POST['id'];//订单id
     $text=$_POST['text'];//内容
     $pics=$_POST['pics'];//图片id
    //查询订单 获得类型 套餐或者单品
     $order=$db->get('orders','*',[
          'id'=>$oid,
      ]);
     if($pics==''){
          //写入备注表
        $remark=$db->insert('order_remark',[
              'orderId'=>$oid,//订单id
              'orderType'=>$order['type'],//订单类型
              'sid'=>$order['sid'],//服务id
              'name'=>'remark',//名称
              'content'=>$text,//内容
              'type'=>'varchar',//数据类型
              'creatTime'=>date('Y-m-d H:i:s'),//提交时间
          ]);
         if($remark){
          $flag=200;
          $msg='添加备注成功';
        }else{
          $flag=400;
          $msg='添加失败，请重新添加';
        }
        $json = array('flag' => $flag,'msg'=>$msg);
        return $response->withJson($json);
    }else{
      $remarka=$db->insert('order_remark',[
          'orderId'=>$oid,//订单id
          'orderType'=>$order['type'],//订单类型
          'sid'=>$order['sid'],//服务id
          'name'=>'remark',//名称
          'content'=>$text,//内容
          'type'=>'varchar',//数据类型
          'creatTime'=>date('Y-m-d H:i:s'),//提交时间
      ]);
      $remarks=$db->insert('order_remark',[
          'orderId'=>$oid,//订单id
          'orderType'=>$order['type'],//订单类型
          'sid'=>$order['sid'],//服务id
          'name'=>'remark',//名称
          'content'=>$pics,//内容
          'type'=>'pic',//数据类型
          'creatTime'=>date('Y-m-d H:i:s'),//提交时间
      ]);
       if($remarka){
          $flag=200;
          $msg='添加备注成功';
        }else{
          $flag=400;
          $msg='添加失败，请重新添加';
        }
        $json = array('flag' => $flag,'msg'=>$msg);
            return $response->withJson($json);
    }

     // exit;
  }

    //加载客户申请退款页面

 public function urefundForm($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $id=$_GET['id'];//订单id

    //查询订单信息
    $orders=$db->get('orders','*',['id'=>$id]);
    //查询订单是不是套餐
    if($orders['type']==1){
      //套餐 查询套餐表
      $service=$db->get('mcms_group_service','*',['id'=>$orders['sid']]);
      //获取套餐下包含哪些服务
      $arr=array($service['sid0'],$service['sid1'],$service['sid2'],$service['sid3'],$service['sid4'],$service['sid5']);
        $arra=array_filter($arr);
        //循环查询套餐内容
        for($j=0;$j<count($arra);$j++){
            $contract[$j]['title']=$db->get('mcms_service','title',['id'=>$arra[$j]]);
            $contract[$j]['iconClass']=$db->get('mcms_service','iconClass',['id'=>$arra[$j]]);
            $contract[$j]['iconColor']=$db->get('mcms_service','iconColor',['id'=>$arra[$j]]);
        }
    }else{
      //单品
      $service=$db->get('mcms_service','*',['id'=>$orders['sid']]);
      // var_dump($service);
      // exit;
      $contract[0]['title']=$db->get('mcms_service','title',['id'=>$service['id']]);
      $contract[0]['iconClass']=$db->get('mcms_service','iconClass',['id'=>$service['id']]);
      $contract[0]['iconColor']=$db->get('mcms_service','iconColor',['id'=>$service['id']]);
    }

    //查询订单有没有正在进行退款
    $ref = $db->get('orders_refund','*',[
      'oid'=>$id
    ]);

    if($ref){
      $or['statusa'] = $ref['status'];//处理的状态
      //退款金额
      if(isset($ref['price'])&&$ref['price']!=''){
        $or['price']=$ref['price'];
      }else{
        $or['price']='';
      }
   
    }else{
      $or['statusa']='';
    }
    
     //查询退款的进度
      //查询处理信息
    $list=[];
    if($ref){
      $record = $db->select('orders_refund_record','*',[
        'refundid'=>$ref['id'],
        'ORDER'=>['id'=>'DESC'],
        ]);
    
    // var_dump($record);
    // exit;
    $i=0;
    
    if($record){
       foreach($record as $r){
      if($r['type']==1){
        //客户
        $list[$i]['name']='您';

      }else{
        //员工
        $list[$i]['name']=$db->get('member','name',['id'=>$r['staffid']]);
      }
      //处理时间
        $list[$i]['creattime']=$r['creattime'];
      //内容
        $list[$i]['content']=$r['content'];
      $i++;
      }
    }

    }
    // var_dump($or);
    // exit;
     $as = [
      'u'=>$u,
      'orders'=>$orders,
      'contract'=>$contract,
      'ref'=>$ref,
      'or'=>$or,
      'list'=>$list,//退款的进度
    ];
    return $this->app->renderer->render($response, './u/orders_refund_form.php', $as);
  }
  //执行客户退款申请操作
  public function inserRefund($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $id=$_POST['oid'];//订单id
    $text=$_POST['text'];//客户输入的原因
    //判断重复提交
    $refunds=$db->get('orders_refund','id',['oid'=>$id]);
    if($refunds){
      $json = array('flag' => 200,'msg'=>'订单已经申请了退款,请等待工作人员审核');
        return $response->withJson($json);
        exit();
    }
    //根据订单查询订单数据
    $orders=$db->get('orders','*',['id'=>$id]);
    //写入申请表
    $refund=$db->insert('orders_refund',[
          'uid'=>$orders['uid'],//客户id
          'oid'=>$orders['id'],//订单id
          'status'=>1,//状态 1 新申请
          'total'=>$orders['price'],//付款金额
          'creattime'=>date('Y-m-d H:i:s'),//申请时间
          'content'=>$text,//原因 内容
      ]);
    //写入申请流水表
    if($refund){
      $reid=$db->id($refund);
        $record=$db->insert('orders_refund_record',[
              'uid'=>$orders['uid'],//客户id
              'oid'=>$orders['id'],//订单id
              'creattime'=>date('Y-m-d H:i:s'),//处理时间
              'content'=>'提交申请,等待审核',
              'type'=>1,//类型1客户2 服务者
              'refundid'=>$reid,//关联的申请id
          ]);
        $push= puch(0,20,[$u['mobile'],],['',],[]);//发送短信告知客户申请信息收到
        //查询售后服务者的信息
        $member=$db->get('member','mobile',['id'=>273]);
        $pushs= puch(0,21,[$member,],['',],['name'=>'【'.$u['name'].'】','text'=>'【'.$text.'】']);//告知售后人员 有新申请退款信息
        $json = array('flag' => 200,'msg'=>'提交成功,请等待售后人员与您联系');
        return $response->withJson($json);
        exit();
    }else{
       $json = array('flag' =>400,'msg'=>'提交失败,请重新填写');
        return $response->withJson($json);
        exit();
    }
  }

  //加载客户申请的退款列表页
  public function urefundList($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    //查询客户名下的申请所记录
    $refund=$db->select('orders_refund','*',[
          'uid'=>$u['id'],//客户id
          'ORDER'=>['id'=>'DESC'],//倒序
      ]);
    //循环
    $i=0;
    $list=[];
    foreach($refund as $r){
      //申请id
      $list[$i]['id']=$r['id'];
      //订单号
      $order=$db->get('orders',['id','creattime','sid','type','orderId','status'],['id'=>$r['oid']]);
      $list[$i]['orderId']=$order['orderId'];
      //下单时间
      $list[$i]['ocreattime']=$order['creattime'];
      //服务名称
      if($order['type']==1){
        //套餐
        $list[$i]['title']=$db->get('mcms_group_service','group_title',['id'=>$order['sid']]);
      }else{
        //单品
        $list[$i]['title']=$db->get('mcms_service','title',['id'=>$order['sid']]);
      }
      //处理状态
      $list[$i]['statusname']=$db->get('orders_refund_status','statusname',['id'=>$r['status']]);
      $list[$i]['status']=$r['status'];//状态
      //退款申请时间
      $list[$i]['creattime']=$r['creattime'];
      //退款金额
      $list[$i]['price']=$r['price'];
      $i++;
    }
    // var_dump($refund);
    // exit;
    $as = [
      'u'=>$u,
      'list'=>$list,
    ];
    return $this->app->renderer->render($response, './u/orders_refund_list.php', $as);
  }

  //加载客户申请的退款记录的详情页
  public function urefundEdit($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $id=$args['id'];//申请据库id
    //根据记录id 查询申请记录信息
    $refund=$db->get('orders_refund','*',['id'=>$id]);
    //查询订单信息
    $orders=$db->get('orders','*',['id'=>$refund['oid']]);
    if($orders['type']==1){
      //套餐
      $orders['title']=$db->get('mcms_group_service','group_title',['id'=>$orders['sid']]);
      $orders['iconClass']='';
      $orders['iconColor']='';
    }else{
      //单品
      $orders['title']=$db->get('mcms_service','title',['id'=>$orders['sid']]);
      $orders['iconClass']=$db->get('mcms_service','iconClass',['id'=>$orders['sid']]);
      $orders['iconColor']=$db->get('mcms_service','iconColor',['id'=>$orders['sid']]);
    }
    //订单状态
    $orders['statusname']=$db->get('orders_status','name',['id'=>$orders['status']]);
    $orders['statusa']=$refund['status'];//处理的状态
     //退款金额
    if(isset($refund['price'])&&$refund['price']!=''){
      $orders['price']=$refund['price'];
    }
    //查询处理信息
    $record=$db->select('orders_refund_record','*',[
      'refundid'=>$id,
      'ORDER'=>['id'=>'DESC'],
      ]);
    $i=0;
    $list=[];
    foreach($record as $r){
      if($r['type']==1){
        //客户
        $list[$i]['name']='您';

      }else{
        //员工
        $list[$i]['name']=$db->get('member','name',['id'=>$r['staffid']]);
      }
      //处理时间
        $list[$i]['creattime']=$r['creattime'];
      //内容
        $list[$i]['content']=$r['content'];
      $i++;
    }

    $as = [
      'u'=>$u,
      'orders'=>$orders,
      'list'=>$list,
      'id'=>$id,
      
    ];
    return $this->app->renderer->render($response, './u/orders_refund_edit.php', $as);
  }

   //执行客户取消申请操作
  public function urefundDel($request,$response,$args){
     global $db;
     $u = $request->getAttribute('u');//用户
     $id=$_POST['id'];//申请记录id
     //查询记录信息
     $oid=$db->get('orders_refund','oid',['id'=>$id]);
     //执行修改状态
     $refund=$db->update('orders_refund',[
          'status'=>7,
      ],[
          'id'=>$id,
      ]);
     if($refund){
        //写入执行记录
      $record=$db->insert('orders_refund_record',[
            'uid'=>$u['id'],//客户id
            'oid'=>$oid,//订单id
            'creattime'=>date('Y-m-d H:i:s'),//执行时间
            'content'=>'取消退款',
            'type'=>1,
            'refundid'=>$id,//记录id
        ]);
      $json = array('flag' => 200,'msg'=>'取消成功');
        return $response->withJson($json);
        exit();
     }else{
      $json = array('flag' => 200,'msg'=>'取消失败');
        return $response->withJson($json);
        exit();
     }
  }

   //客户同意处理意见并修改状态
  public function urefundOK($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $id=$_POST['id'];//申请记录id
     //查询记录信息
     $oid=$db->get('orders_refund','oid',['id'=>$id]);
     //执行修改状态
     $refund=$db->update('orders_refund',[
          'status'=>5,
      ],[
          'id'=>$id,
      ]);
     if($refund){
        //写入执行记录
      $record=$db->insert('orders_refund_record',[
            'uid'=>$u['id'],//客户id
            'oid'=>$oid,//订单id
            'creattime'=>date('Y-m-d H:i:s'),//执行时间
            'content'=>'同意该处理意见,财务退款中。。。',
            'type'=>1,
            'refundid'=>$id,//记录id
        ]);
        //查询财务电话
      $member=$db->get('member','mobile',['id'=>84]);//财务周希
      $push= puch(0,23,[$member,],['',],['name'=>'['.$u['name'].']']);
      $json = array('flag' => 200,'msg'=>'确认成功');
        return $response->withJson($json);
        exit();
     }else{
      $json = array('flag' => 200,'msg'=>'取消失败');
        return $response->withJson($json);
        exit();
     }
  }

   //执行线下付款的操作
  public function ordersOkline($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $payid=$_POST['payid'];//订单支付号
    $orders= $db->update('orders',[
          'status'=>7,
          // 'paysource'=>'wechat',
          'paytime'=>date('Y-m-d H:i:s'),
          'remark'=>'客户通过线下打款方式付款',
          // 'paysorder'=>$result["transaction_id"]
        ],[
          'AND'=>[
            'payid'=>$payid,
          ]
        ]);

    if($orders){
      $json = array('flag' => 200,'msg'=>'成功,请等待我们核对打款信息');
        return $response->withJson($json);
        exit();
      }else{
        $json = array('flag' => 400,'msg'=>'付款失败');
        return $response->withJson($json);
        exit();
      }
  }

  //客户待续费的合同列表页
  public function stayRenew($request,$response,$args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $time = date('Y-m-d',strtotime('-2 month'));
    //查询登录客户名下的代帐合同别切是60日内到期的
    $contract = $db->select('contract','*',[
        'AND'=>[
            'uId'=>$u['id'],        
            'sId'=>[22,23,114,115,121],
            'status'=>5,
            'end_day[>=]'=>date('Y-m-d'),
            'end_day[<=]'=>date('Y-m-d',strtotime("+3 month")),
        ]
      ]);
    $list=[];
    $i=0;
    foreach($contract as $c){
      //合同id
      $list[$i]['id']=$c['id'];
      //订单号
      $list[$i]['con']=$db->get('orders','orderId',['id'=>$c['orderId']]);
      //订单购买时间
      $list[$i]['creattime']=$db->get('orders','creattime',['id'=>$c['orderId']]);
      //查询服务名称和配置
      $service=$db->get('mcms_service',['title','iconClass','iconColor'],['id'=>$c['sId']]);
      //名称 样式  颜色
      $list[$i]['title']=$service['title'];
      $list[$i]['iconClass']=$service['iconClass'];
      $list[$i]['iconColor']=$service['iconColor'];
        //获取服务金额
      $list[$i]['money']=$c['money_total'];
      //合同开始时间  
      $list[$i]['start_day']=$c['start_day'];
      //合同结束时间
      $list[$i]['end_day']=$c['end_day'];
      //合同的服务者
      $list[$i]['staffid']=$c['staffId'];
      $i++;
    }
    $as = [
      'u'=>$u, 
      'list'=>$list,    
    ];
    return $this->app->renderer->render($response, './u/orders_renew_list.php', $as);

  }

 //加载续费的支付页面
  public function renewPay($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      //将要续费的合同id
      $id=$_GET['id'];//将要续费的合同id
      // var_dump($orderid);
      //查询合同全部信息
      $contract=$db->get('contract','*',['id'=>$id]);
      $title=$db->get('mcms_service','title',['id'=>$contract['sId']]);
      $type='renew';
      $as = [
        'u'=>$u,
        'contract'=>$contract,
        // 'payid'=>$payid,
        // 'total'=>$total,
        'title'=>$title,
        'type'=>$type,
        // 'cid'=>$_GET['id'],//即将到期合同的id 用于付款成功后查询老合同获取结束时间
      ];
      return $this->app->renderer->render($response, './renewpay.php', $as);
  }

  //续费线下付款的请求操作
  public function ordersOkrenew($request, $response, $args){
      global $db;
    $u = $request->getAttribute('u');//用户
    $payid=$_POST['payid'];//订单支付号
    // var_dump($payid);
    // exit;
    $orders= $db->update('orders',[
          'status'=>8,
          // 'paysource'=>'wechat',
          'paytime'=>date('Y-m-d H:i:s'),
          'remark'=>'客户通过线下打款方式付款续费合同',
          // 'paysorder'=>$result["transaction_id"]
        ],[
          'AND'=>[
            'payid'=>$payid,
          ]
        ]);

    if($orders){
      $json = array('flag' => 200,'msg'=>'成功,请等待我们核对打款信息');
        return $response->withJson($json);
        exit();
      }else{
        $json = array('flag' => 400,'msg'=>'付款失败');
        return $response->withJson($json);
        exit();
      }
  }

  //加载客户将要续费的合同具体信息
  public function renewEdit($request, $response, $args){
     global $db;
    $u = $request->getAttribute('u');//用户
    $id=$args['id'];//合同id
    //生成payid
    $payid = time().''.rand(1000,9999);
    $con=$db->update('contract',[
          'payid'=>$payid,
      ],[
          'id'=>$id,
      ]);
    //查询合同信息
    $contract=$db->get('contract','*',[
        'id'=>$id,
      ]);

    if(empty($contract['utype'])&&$contract['utype']==''){
      //查询记录表  关联合同，如果有记录 按照记录里的开始时间和结束时间来设置，如果没有 按照合同里的开始时间和结束时间设置
      $renew=$db->get('contract_renew','*',[
          'contractid'=>$id,
        ]);
      //判断如果记录表里没有续费信息 那么时间按照原合同的时间开始后延
      if(empty($renew)){
          // 如果没有选择时间 那么按合同的时间算
          $start_day=$contract['start_day'];
          $end_day=$contract['end_day'];
          $a=$end_day-$start_day;
          $t = strtotime($end_day) - strtotime($start_day);//拿当前时间-开始时间 = 相差时间
          $a = $t/(3600*24);//此时间单位为 天
          $b=ceil($a/31);//如果客户不选择续费时间 按照  问题 如果是第二期续费 那么就是开始到结束的时间算会时间更长
          $start_day=strtotime($contract['end_day']."+1 day");
          $end_day=strtotime($contract['end_day']."+".$b." month");
            //将时间戳转换时间格式写入
          $starts_day=date('Y-m-d',$start_day);
          $ends_day=date('Y-m-d',$end_day);
          $contract['end_day']=$ends_day;
          $contract['b']=$b;
      }else{
        //如果记录表里面存在记录那么查询记录表里的开始时间和结束时间来算作续费的时间
        $start_day=$renew['start_day'];
        $end_day=$renew['end_day'];
        $a=$end_day-$start_day;
        $t = strtotime($end_day) - strtotime($start_day);//拿当前时间-开始时间 = 相差时间
        $a = $t/(3600*24);//此时间单位为 天
        $b=ceil($a/31);//如果客户不选择续费时间 按照  问题 如果是第二期续费 
        //那么就是开始到结束的时间算会时间更长
        // var_dump($b);
        // exit;
        $start_day=strtotime($renew['end_day']."+1 day");
        $end_day=strtotime($renew['end_day']."+".$b." month");
          //将时间戳转换时间格式写入
        $starts_day=date('Y-m-d',$start_day);
        $ends_day=date('Y-m-d',$end_day);
        
        $contract['end_day']=$ends_day;
        $contract['b']=$b;
       
      }
    }else if($contract['utype']==1){
      //续费1个月 直接修改结束时间+1个月，
      $end_day=strtotime($contract['end_day']."+1 month");
      $contract['end_day']=date('Y-m-d',$end_day);
    }else if($contract['utype']==2){
      //续费三个月
      $end_day=strtotime($contract['end_day']."+3 month");
      $contract['end_day']=date('Y-m-d',$end_day);
    }else if($contract['utype']==3){
      //续费半年
      $end_day=strtotime($contract['end_day']."+6 month");
      $contract['end_day']=date('Y-m-d',$end_day);
    }else if($contract['utype']==4){
      //续费1年
      $end_day=strtotime($contract['end_day']."+1 year");
      $contract['end_day']=date('Y-m-d',$end_day);
    }
    //查询服务名称和配置
      $service=$db->get('mcms_service',['title','iconClass','iconColor'],['id'=>$contract['sId']]);
      //名称 样式  颜色
      $contract['title']=$service['title'];
      $contract['iconClass']=$service['iconClass'];
      $contract['iconColor']=$service['iconColor'];
    $as = [
      'u'=>$u, 
      'contract'=>$contract,    
    ];
    return $this->app->renderer->render($response, './u/orders_renew_edit.php', $as);
  }

  //执行现在续费的请求修改订单状态
  public function renewPayment($request, $response, $args){
    global $db;
    $payid = $_POST['payid'];
    //根据payid查询合同信息做修改
    
    $db->update('contract',[
          'paymode'=>'line',
      ],[
        'payid'=>$payid,
      ]);

    $contract = $db->get('contract','*',['payid'=>$payid]);
    $ed = strtotime($contract['end_day']);
    
    $endday = date('Y-m-d',strtotime("+1 year",$ed));

    $newscs = $db->insert('contract_renew',[
            'contractid'=>$contract['id'],
            'price'=>$contract['money_total'],
            'sid'=>$contract['sid'],
            'creattime'=>date('Y-m-d H:i:s'),
            'start_day'=>$contract['end_day'],
            'end_day'=>$endday,
            // 'type'=>$utype,
            'status'=>1,
            'paysource'=>'Offline'
     ]);


    
    $json = array('flag' => 200,'msg'=>'成功,请等待我们核对打款信息'.$endday);
    return $response->withJson($json);
        

  }
   
  public  function myevaluate($request, $response, $args){
    global $db;
    $u = $request->getAttribute('u');//用户
    $list = $db->select('orders_evaluate','*',[
      'customID'=>$u['id']
    ]);
    $as = [
      'u'=>$u, 
      'list'=>$list,    
    ];
    return $this->app->renderer->render($response, './u/myevaluate.php', $as);
  }

  
}
