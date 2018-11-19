<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class ScenterController 
{
	protected $app;
   	
   	public function __construct(ContainerInterface $ci) {
       $this->app = $ci;
   	}
   	public function __invoke($request, $response, $args) {
        //to access items in the container... $this->ci->get('');
   	}
   	
      public function index($request, $response, $args){
        setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      if($s['id']==''){
        return $response->withRedirect('/loginu.html');
      }
      //查询钱包信息
      $wall=$db->get('wallets','*',[
          'AND'=>[
              'utype'=>1,
              'uid'=>$s['id'],
          ]
        ]);
      if(!isset($wall['poin'])){
        $wall['poin']='0';
      }
      $as = [
      's'=>$s,
      'wall'=>$wall,
      ];
      return $this->app->renderer->render($response, './s.php', $as);
    }

   //商点消费记录
    public function sdlog($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
       if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      //查询商点消费记录
      $poin=$db->select('member_poin','*',[
        'AND'=>[
          'staffId'=>$s['id'],
          'type'=>1,
        ],
        'ORDER'=>['id'=>'DESC'],
        'LIMIT'=>[$srow,10],
        ]);
      $contrcount=$db->count('member_poin','*',[
          'AND'=>[
              'staffId'=>$s['id'],
              'type'=>1,
          ]
        ]);
      $counts=ceil($contrcount/10);//计算有多少页
      $as = [
      's'=>$s,
      'p'=>$p,
      'poin'=>$poin,
      'counts'=>$counts,
      ];
      return $this->app->renderer->render($response, './s/sdlog.php', $as);
    }

    //商点充值记录
    public function rechangelog($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      //查询商点消费记录
      $poin=$db->select('member_poin','*',[
        'AND'=>[
          'staffId'=>$s['id'],
          'type'=>2,
        ],
        'ORDER'=>['id'=>'DESC'],
        'LIMIT'=>[$srow,10],
        ]);
      $contrcount=$db->count('member_poin','*',[
          'AND'=>[
              'staffId'=>$s['id'],
              'type'=>2,
          ]
        ]);
      $counts=ceil($contrcount/10);//计算有多少页
      $as = [
      's'=>$s,
      'p'=>$p,
      'poin'=>$poin,
      'counts'=>$counts,
      ];
      return $this->app->renderer->render($response, './s/rechangelog.php', $as);
    }

    public function sd($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      $s = $request->getAttribute('s');//服务者
      $as = [
      
      's'=>$s
      ];
      return $this->app->renderer->render($response, './s/sd.php', $as);
    }

    //加载我的客户信息
     public function cutsoms($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
       if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }

      //查询我名下的合同
      $contract=$db->select('contract','*',[
          'staffId'=>$s['id'],
          'ORDER'=>['id'=>'DESC'],
          'LIMIT'=>[$srow,10],
        ]);
      $i=0;
      $list=[];
      foreach($contract as $c){
        $list[$i]['id']=$c['id'];//合同id
        $customs=$db->get('customs',['name','mobile'],['id'=>$c['uId']]);
        //客户姓名
        $list[$i]['uname']=$customs['name'];
        //客户电话
        $list[$i]['mobile']=$customs['mobile'];
        //客户所在地址 如果不存在为空
        $address=$db->get('address','*',['id'=>$c['areaId']]);
        $list[$i]['address']=$address['prov'].' '.$address['city'].' '.$address['name'];
        //客户关联的公司 如果没有显示空
        if(isset($c['comanyId'])&&$c['comanyId']!=''){
           $list[$i]['compayname']=$db->get('companies','companyname',['id'=>$c['comanyId']]);
         }else{
          $list[$i]['compayname']='客户没有关联公司';
         }
        //关联合同
        $list[$i]['title']=$db->get('mcms_service','title',['id'=>$c['sId']]);
        //查询合同状态
        $list[$i]['status']=$db->get('contract_status','statusname',['id'=>$c['status']]);

        $i++;
      }

       //查询我名下的合同
      $count=$db->count('contract','*',[
          'staffId'=>$s['id'],
        ]);
       $counts=ceil($count/10);//计算有多少页
      // var_dump($list);
      // exit;
      $as = [
      's'=>$s,
      'p'=>$p,//分页
      'counts'=>$counts,//数据总数
      'list'=>$list,//数据信息
      ];
      return $this->app->renderer->render($response, './s/cutsoms.php', $as);
    }

     public function orderdetail($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $p=$_GET['p'];//分页参数
      $contractid=$args['id'];
      //查询合同信息
      $contract=$db->get('contract','*',['id'=>$contractid]);
      //查询推荐人姓名
      $mnamevcode=$db->get('member_vcode','uId',['vcode'=>$contract['vcode']]);
      $mname=$db->get('member','name',['id'=>$mnamevcode]);
      //查询服务者信息
      if(isset($contract['staffId'])&&$contract['staffId']!=''){
          $staff=$db->get('member',['id','name','mobile'],['id'=>$contract['staffId']]);
      }else{
          $staff='';
      }
      $service=$db->get('mcms_service','title',['id'=>$contract['sId']]);
      $status=$db->get('contract_status','statusname',['id'=>$contract['status']]);
      //根据合同ID查询订单号 企业信息
      $order=$db->get('orders',['orderId','creattime','comanyId','id'],['id'=>$contract['orderId']]);
      if(isset($order['comanyId'])&&$order['comanyId']!=''&&$order['comanyId']!=0){
          $comstatus=1;
          $comst = $db->get('companies',['companyname','id'],['id'=>$order['comanyId']]);
      }else{
        $comstatus=0;
        $comst='';
      }
        //查询处理信息
      $hanles = $db->select('contract_speed','*',['oId'=>$contract['id'],"ORDER"=>['id'=>"DESC"],]);

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
        $remark = $db->select('order_remark','*',[
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
        $companies = $db->select('companies',[
          'id',
          'companyname',
          'cus_1',
          'cus_2',
          'cus_3',
          'cus_4',
          'cus_5',
        ],[
          'OR'=>[
            'cus_1'=>$contract['uId'],
            'cus_2'=>$contract['uId'],
            'cus_3'=>$contract['uId'],
            'cus_4'=>$contract['uId'],
            'cus_5'=>$contract['uId'],
          ]
        ]);
        //var_dump($contract['uId']);
        if(!isset($companies)&&$companies==''){
          $companies=[];
        }

        //查询客户信息
        $customs=$db->get('customs',['name','mobile'],['id'=>$contract['uId']]);
      // var_dump($hanles);
      // exit;
      $as = [
      's'=>$s,
      'p'=>$p,
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
      'contractid'=>$contractid,//合同id
      'customs'=>$customs,//客户信息
      'mname'=>$mname,//推荐人姓名
      ];
      return $this->app->renderer->render($response, './s/orderdetail.php', $as);
    }

   public function orders($request, $response, $args){
    setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
       if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
       //电话或姓名
      if(isset($_GET['mobile'])&&$_GET['mobile']!=''){
          $mobile=$_GET['mobile'];
          if(is_numeric($mobile)){//如果是电话号
            //电话查询客户id 
            $customsid=$db->get('customs','id',['mobile'=>$mobile]);//合同uId
          }else{
            //如果是客户姓名
            $customsid=$db->get('customs','id',['name'=>$mobile]);//用姓名查询客户id
          }
      }else{
         $customsid=$db->select('customs','id');
      };
      if(isset($_GET['s'])&&$_GET['s']!=''){
        $time=$_GET['s'];
        $times=1;
      }else{
        $time='2016-1-1';
        $times='';
      }
      if(isset($_GET['e'])&&$_GET['e']!=''){
        $endtime=$_GET['e'].' '.'23:59:59';
      }else{
        $endtime=date('Y-m-31 23:59:59');
      }
        if(isset($_GET['status'])&&$_GET['status']!=''){
        
        $status=explode(',',$_GET['status']); 
        // $status=$_GET['status'];
      }else{
        $status=5;
      }
       if(!isset($_GET['selecttype'])||$_GET['selecttype']==0){
        $selecttype = [1,2,3,4,5,6,7,8];
      }else{
        $selecttype=$_GET['selecttype'];
      }
      if(isset($_GET['company'])&&$_GET['company']!=''){
        $companyid=$db->get('companies','id',['companyname[~]'=>$_GET['company']]);
        //查询我名下未完成的订单
      $orders=$db->select('contract','*',[
              'AND'=>[
                  'staffId'=>$s['id'],
                  'status'=>$status,
                  'uId'=>$customsid,
                  'stypeId'=>$selecttype,//服务类别
                  'comanyId'=>$companyid,//企业id
                  'creattime[>=]'=>$time,//自定义开始时间
                  'creattime[<=]'=>$endtime,//自定义结束时间
              ],
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[$srow,10],
          ]);
    }else{
         //查询我名下未完成的订单
      $orders = $db->select('contract','*',[
              'AND'=>[
                  'staffId'=>$s['id'],
                  'status'=>$status,
                  'uId'=>$customsid,
                  //'stypeId'=>$selecttype,//服务类别
                  // 'comanyId'=>$companyid,//企业id
                  'creattime[>=]'=>$time,//自定义开始时间
                  'creattime[<=]'=>$endtime,//自定义结束时间
              ],
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[$srow,10],
          ]);
    }
      $orde=$db->select('contract','*',[
              'AND'=>[
                  'staffId'=>$s['id'],
                  'status'=>$status,
              ],
          ]);
      $contrcount=count($orde);
      $counts=ceil($contrcount/10);//计算有多少页
       $i=0;
        $list=[];
        foreach($orders as $o){
            //查询订单号和订单支付时间
            $list[$i]['id']=$o['id'];
            $list[$i]['oid']=$o['orderId'];
            $list[$i]['creattime']=$o['creattime'];
             $list[$i]['sId']=$o['sId'];
            //查询客户信息
            $customs=$db->get('customs',['id','name','mobile'],['id'=>$o['uId']]);
            $list[$i]['uname']=$customs['name'];
            $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
            //订单状态
            $list[$i]['status']=$db->get('contract_status','statusname',['id'=>$o['status']]);
            //服务名称
           $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sId']]);
             //合同金额
           $list[$i]['price']=$o['money_total'];
           //合同开始时间
           $list[$i]['start_day']=$o['start_day'];
           //合同结束时间
           $list[$i]['end_day']=$o['end_day'];

            //查询关联企业
            if(isset($o['comanyId']) && $o['comanyId']!=0){
              $list[$i]['comany'] = $db->get('companies','*',['id'=>$o['comanyId']]);
            }else{
              $list[$i]['comany']='';
            }

             //今日执行状态
            $list[$i]['stypeId']=$o['stypeId'];
            if(isset($o['gostatus'])&&$o['gostatus']!=''){
              $list[$i]['gostatus']=$o['gostatus'];
            }else{
              $list[$i]['gostatus']='';
            }

             //代理记账今日执行的进度
              $list[$i]['stype']=$db->get('contract_speed','stype',[
                    'oId'=>$o['id'],
                    "ORDER"=>['id'=>"DESC"],]);
            $i++;
        }
        //status重新赋值
        if(isset($_GET['status'])&&$_GET['status']!=''){
          $status=$_GET['status'];
        }else{
          $status=5;
        }
      $as = [
      's'=>$s,
      'p'=>$p,
      'list'=>$list,
      'time'=>$time,
      'status'=>$status,
      'endtime'=>$endtime,
      'times'=>$times,
      'counts'=>$counts,
      'contrcount'=>$contrcount,
      ];
      return $this->app->renderer->render($response, './s/orders.php', $as);
    }


   

   public function hall($request, $response, $args){
    setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      



      if(isset($_GET['areaid']) && is_numeric($_GET['areaid']) && $_GET['areaid']>1){
        $areaid = $_GET['areaid'];
        //获取对应的市级。直辖市获取省级
        $a = $db->get('address','*',['id'=>$areaid]);
        
        if($a['provId']==1 || $a['provId']==2 || $a['provId']==9 || $a['provId']==22){
          $scity = $a['provId'];
          $scitypId = $db->select('address','id',['provId'=>$scity]);
          $scityId = $db->select('address','id',['cityId'=>$scitypId]);
        }else{
          $scity = $a['cityId'];
          //查出市下所有下级的id
          $scityId = $db->select('address','id',['cityId'=>$scity]);
        }


        //查询新合同
        $list = $db->select('contract','*',[
          'AND'=>[
            'areaId'=>$scityId,
            'status'=>3
          ],
          'ORDER'=>['id'=>'ASC'],
          'LIMIT'=>[0,30]
        ]);
        // $list=[];
        // $i=0;
        // foreach ($contract as $c) {
        //   $service = $db->get('mcms_service','*',['id'=>$c['sId']]);
        //   $u = $db->get('customs',['name'],['id'=>$c['uId']]);
        //   $list[$i]['id'] = $c['id'];
        //   $list[$i]['title'] = $service['title'];//服务名称
        //   $list[$i]['desc'] = $service['desc'];//服务名称
        //   $list[$i]['commission_run'] = $c['commission_run'];//服务佣金
        //   $list[$i]['iconClass'] = $service['iconClass'];//图标
        //   $list[$i]['iconColor'] = $service['iconColor'];//颜色
        //   $list[$i]['uname'] = $u['name'];
        //   $list[$i]['creattime']=$c['creattime'];
        //   $area = $db->get('address','*',['id'=>$c['areaId']]);//区
        //   $list[$i]['address'] = $area['prov'].' '.$area['city'].' '.$area['name'];
        //   $i++;
        // }
      }else{
        $areaid = 0;
        //查询新合同
        $list = $db->select('contract','*',[
          'status'=>3,
          'ORDER'=>['id'=>'ASC'],
          'LIMIT'=>[0,30]
        ]);
        // $list=[];
        // $i=0;
        // foreach ($contract as $c) {
        //   $service = $db->get('mcms_service','*',['id'=>$c['sId']]);
        //   $u = $db->get('customs',['name'],['id'=>$c['uId']]);
        //   $list[$i]['id'] = $c['id'];
        //   $list[$i]['title'] = $service['title'];//服务名称
        //   $list[$i]['desc'] = $service['desc'];//服务名称
        //   $list[$i]['commission_run'] = $c['commission_run'];//服务佣金
        //   $list[$i]['iconClass'] = $service['iconClass'];//图标
        //   $list[$i]['iconColor'] = $service['iconColor'];//颜色
        //   $list[$i]['uname'] = $u['name'];
        //   $list[$i]['creattime']=$c['creattime'];
        //   $area = $db->get('address','*',['id'=>$c['areaId']]);//区
        //   $list[$i]['address'] = $area['prov'].' '.$area['city'].' '.$area['name'];
        //   $i++;
        // }
      }
      

 
      $as = [
      's'=>$s,
      'areaid'=>$areaid,
      'list'=>$list,
      ];
      return $this->app->renderer->render($response, './s/hall.php', $as);
    }

   
      //抢单
    public function hallContract($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $cid = $_POST['id'];//合同id
      
      $swall = $db->get('wallets','*',[
        'AND'=>['uid'=>$s['id'],
            'utype'=>1]
          ]);
      //判断保证金余额
      if($swall['bzj']<1000){
        $json = array('flag' =>400,'msg'=>'您的保证金少于1000，请先充值。');
        return $response->withJson($json);
      }

      //查询合同有没有被抢
      $contract=$db->get('contract','*',['id'=>$cid]);
      if($contract['status']==5){
        $flag=400;
        $msg='对不起！您下手慢了，请刷新页面';
        $json = array('flag' =>$flag,'msg'=>$msg);
       return $response->withJson($json);
      }else{
        //如果没被处理 修改合同状态写入流水表和处理记录表
        // 1用orderId查询订单
          $order=$db->get('orders','*',[
                'id'=>$contract['orderId'],
            ]);
          $day=$db->get('mcms_service','cycle',['id'=>$contract['sId']]);//获取完成时间
          $end_day= date('Y-m-d',strtotime("+".$day."day"));
          $hand=$db->select('takenow','id',['AND'=>['class'=>52,'targetId'=>$cid]]);

          if(empty($hand)){
              $md=$db->update("contract",[
                'staffId'=>$s['id'],
                'status'=>'5',
                'start_day'=>date('Y-m-d'),
                'end_day'=>$end_day,
              ],[
              'id'=>$cid,
              ]);
          }else{
              $md=$db->update("contract",[
                'staffId'=>$s['id'],
                'status'=>'5',
                'start_day'=>date('Y-m-d'),
              ],[
              'id'=>$cid,
              ]);
          }
          //写入合同处理流水表
          $mid = $db->insert("contract_speed", [
                   "oId"=>$cid,
                   'text'=>'成功抢单',
                   'type'=>'grab',
                   "creattime" => date('Y-m-d H:i:s'),
                   "uid" => $s['id']
                   ]);
          //写入圈子活跃
          $db->insert('mcms_quan',[
            'title'=>'抢单',
            'cateId'=>3,
            'author'=>$s['id'],
            'content'=>'抢单成功，订单号：'.$contract['cno'],
            'creatTime'=>date('Y-m-d H:i:s'),
            'tags'=>'活跃,',
            'name'=>$s['name']
          ]);
          $db->update('member',[
            'jljf[+]'=>1
          ],[
            'id'=>$s['id']
          ]);
          //三级分销
           $content='接单成功，订单号：'.$contract['cno'];
           $content1='[直接管理]接单成功，订单号：'.$contract['cno'];
           $content2='[间接接管理]接单成功，订单号：'.$contract['cno'];
           if($contract['sId']==22||$contract['sId']==23||$contract['sId']==114||$contract['sId']==115||$contract['sId']==121){
            //判断是不是代理记账
              $ad=addtake_1($s['id'],$contract['commission_run']*0.6,'0','52',$contract['id'],$content,$s['id']);
                $upid=getup($s['id']);//查询推荐人
              if(!empty($upid)){
                    $b=addtake_1($upid,$contract['commission_run']*0.24,'0','60',$contract['id'],$content1,$s['id']);
                    $lastid=getup($upid);
                if(!empty($lastid)){
                    $c=addtake_1($lastid,$contract['commission_run']*0.16,'0','61',$contract['id'],$content2,$s['id']);
                  }
                }
            }else{
              //如果不是代理记账类合同
               $ad=addtake($s['id'],$contract['commission_run']*0.6,'0','52',$contract['id'],$content,$s['id']);
                $upid=getup($s['id']);
                  if(!empty($upid)){
                    $b=addtake($upid,$contract['commission_run']*0.24,'0','60',$contract['id'],$content1,$s['id']);
                    $lastid=getup($upid);
                     if(!empty($lastid)){
                     $c=addtake($lastid,$contract['commission_run']*0.16,'0','61',$contract['id'],$content2,$s['id']);
                     }
                  }
            }
            //判断是不是套餐 决定修改订单状态
            if($order['type']==0){
              //tupe=0是单品 直接修改
               $orders=$db->update('orders',[
                        'status'=>2,
                    ],[
                        'id'=>$order['id'],
                    ]);
              }else{
                //如果是套餐，查询套餐下有多少合同是状态3
                 $contractoid=$db->select('contract','status',['orderId'=>$contract['orderId']]);
                if(in_array('3', $contractoid)){
                      //continue;
                 }else{
                $orders=$db->update('orders',[
                      'status'=>2,
                  ],[
                      'id'=>$contract['orderId'],
                  ]);
              }
              }
              if($md){
                  //短信对接
                // $text = "感谢您的支持，您的订单".$order['orderId']."已被服务者接单，服务者：".$s['name']."  手机号： ".$s['mobile'].",为您提供服务，详情请登录https://cw2009.com 或至上会计服务官方微信公众号";
                // //查询客户电话
                $mobile=$db->get('customs',['id','mobile','name'],['id'=>$order['uid']]);
                // $push = pushSMS($mobile['mobile'],$text,$mobile['id'],'服务者接单',0);
                $pust=puch(0,3,[$mobile['mobile'],],[$mobile['name'],],['orderid'=>'【'.$order['orderId'].'】','staffname'=>'【'.$s['name'].'】','mobile'=>'【'.$s['mobile'].'】',]);
                $flag = 200;
                $msg = '合同处理成功';
              }else{
                  $flag = 400;
                  $msg = '合同处理失败';
              }
         $json = array('flag' => $flag,'msg' => $msg);
          return $response->withJson($json);
      }
    }


     public function sjok($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      //获取vcode

      $v = $db->get('member_vcode','*',[
        'AND'=>[
          'type'=>1,
          'uId'=>$s['id']
        ]
      ]);

      $vcode = $v['vcode'];
      if($vcode!=''){

      
      //查询商机信息
      $list=$db->select('orders','*',[
        'AND'=>[
          'vcode'=>$vcode,
          'status'=>[1,2,3,7,8],
        ],
        
        'ORDER'=>['id'=>'DESC'],
        'LIMIT'=>[$srow,10],
        ]);
      //商机总数
       $count = $db->count('orders','*',[
        'AND'=>[
          'vcode'=>$vcode,
          'status'=>[1,2,3,7,8],
        ],
      ]);
     }else{
      $list = false;
      $count = 0;
     }

      $counts=ceil($count/10);//计算有多少页
      $as = [
      's'=>$s,
      'p'=>$p,//分页
      'counts'=>$counts,//共多少页
      'list'=>$list,//商机信息

      ];
      return $this->app->renderer->render($response, './s/sjok.php', $as);
    }

   public function sjbuyed($request, $response, $args){
    setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      //查询商机信息
      $boppo=$db->select('boppo','*',[
        'AND'=>[
          'status'=>2,
          'handleUid'=>$s['id'],
        ],
        
        'ORDER'=>['id'=>'DESC'],
        'LIMIT'=>[$srow,10],
        ]);
      //商机总数
       $count=$db->count('boppo','*',[
        'AND'=>[
          'status'=>2,
          'handleUid'=>$s['id'],
        ],
        ]);
      $counts=ceil($count/10);//计算有多少页
      $as = [
      's'=>$s,
      'p'=>$p,//分页
      'counts'=>$counts,//共多少页
      'boppo'=>$boppo,//商机信息

      ];
      return $this->app->renderer->render($response, './s/sjbuyed.php', $as);
    }

     //服务者-》我抢的商机-》商机详情
    public function sjDetails($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $id=$args['id'];
      //查询商机信息
       $date=$db->get('boppo',[
            "[>]contract_type" => ["cateId" => "id"]
          ],[
            'boppo.id(id)',
            'boppo.uname(uname)',
            'boppo.mobile(mobile)',
            'boppo.cateId(cateId)',
            'boppo.item(itemId)',
            'boppo.prov(prov)',
            'boppo.city(city)',
            'boppo.area(area)',
            'boppo.form(form)',
            'boppo.creattime(creattime)',
            'boppo.status(status)',
            'boppo.text(text)',
            'boppo.qd(qd)',
            "contract_type.typename(typename)"
            ],[
                'boppo.id'=>$id,
            ]);
       $add=$date['prov'].' '.$date['city'].' '.$date['area'];
       //查询处理信息
       $log=$db->select('boppo_go_log','*',['sid'=>$id]);
       if($log){
          $list=[];
          for($i=0;$i<count($log);$i++){
            $list[$i]['id']=$log[$i]['id'];
            $list[$i]['text']=$log[$i]['text'];
            $list[$i]['creatTime']=$log[$i]['creatTime'];
            $member=$db->get('member',['name','mobile'],['id'=>$log[$i]['uid']]);
            $list[$i]['name']=$member['name'];
            $list[$i]['mobile']=$member['mobile'];
          }
       }
      $as = [
      's'=>$s,
      'date'=>$date,//商机信息
      'add'=>$add,//地址
      'list'=>$list,//处理信息
      ];
      return $this->app->renderer->render($response, './s/sjdetails.php', $as);
    }

       //我的商机-》跟进信息
    public function sjfollowup($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
       global $db;
      $s = $request->getAttribute('s');//服务者
      $text=$_POST['text'];//处理结果
      $id=$_POST['id'];//商机id
      //信息写入处理结果表
      $boppo_log=$db->insert('boppo_go_log',[
            'sid'=>$id,//商机id
            'uid'=>$s['id'],//处理商机当前服务者id
            'text'=>$text,//处理结果
            'creatTime'=>date('Y-m-d H:i:s'),//处理时间
        ]);
      if($boppo_log){
        $flag=200;
        $msg='跟进成功';
      }else{
        $boppo_log=0;
        $flag=400;
        $msg='跟进失败';
      }
       $json = array('flag' => $flag,'msg' => $msg);
          return $response->withJson($json);
    }
    //我的商机 签单
    public function sjsignthebill($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $sid=$_POST['id'];
      $b = $db->get('boppo',['id','handleUid','mobile','creattime'],[
          'id'=>$sid
          ]);    
      if($b['handleUid'] != $s['id']){
            $json = array('flag' => 400,'msg' => '对不起，你没有处理该商机的权限', 'data' =>[],'time'=>date('Y-m-d H:i:s'));
           return $response->withJson($json);
      }else{
        if(!empty($_POST['cid'])){
         $cid=$_POST['cid'];
        }
       if(empty($cid)){ 
         $contracts=$db->get('orders', [
          "[>]customs"=>['orders.uid'=>'id']
          ],[
          'orders.id(id)'
          ],[
          'AND'=>['orders.status'=>[1,2,3,4],
          'customs.mobile'=>$b['mobile'],
          'orders.creattime[>]'=>$b['creattime'],
          ],
          'ORDER'=>['id'=>'DESC'],
          ]);
          if(empty($contracts)){
          $flag = 400;
          $msg = '该商机商城还没有支付，请仔细检查该客户是否支付';
          $json = array('flag' => $flag,'msg' => $msg);
          return $response->withJson($json);
         }
         // var_dump($contracts['id']);
         // exit;
         $md=$db->update("boppo",['status'=>'3','handleTime'=>date('Y-m-d H:i:s'),'contractId'=>$contracts['id']],['id'=>$sid]);
       }
        $mid= $db->insert("boppo_go_log", [
           "sid"=>$sid,
          "text"=>$_POST['text'],
           "creattime" => date('Y-m-d H:i:s'),
          "uid" => $_COOKIE['staffID'],
          ]);
           $mde=$db->get("boppo",'*',[
              'id'=>$sid,
        ]);
        if($md){
            // 签约成功 录入商机人+提成
            $member=$db->get('member',['member.name(name)'],['id'=>$s['id']]);//签约人信息
            $membera=$db->get('member',['member.name(name)'],['id'=>$mde['satffId']]);//录入人信息
            $content=$member['name'].' '.'签约了'.' '.$membera['name'].'所录入的商机';
            addtake($mde['satffId'],'50','0','5',$sid,$content,$mde['satffId']);
        }
        if($mid){
           if($md){
                $flag = 200;
                $msg = '处理成功';
              }
          else{
                  $flag = 400;
                  $msg = '处理失败';
              }
        }else{
          $mid = 0;
          $flag = 400;
          $msg = '处理失败';
        }
        $json = array('flag' => $flag,'msg' => $msg,'id' => $mid);
          return $response->withJson($json);
        }
    }

    //商机-》无效商机请求
    public function sjinvalid($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $sid=$_POST['id'];
        $mid= $db->insert("boppo_go_log", [
          "sid"=>$sid,
          "text"=>$_POST['text'],
          "creattime" => date('Y-m-d H:i:s'),
          "uid" => $s['id'],
        ]);
        if($mid){
          $a=$db->update("boppo",['status'=>'4','handleUid'=>$s['id']],['id'=>$sid]);
         $boppo=$db->get('boppo',['satffId','id','form','uname','mobile'],['id'=>$sid]);
          $wall=$db->get('wallets','*',[
                      'uid'=>$boppo['satffId'],
                      'utype'=>1,
                  ]);
          $conten=$boppo['form'].'录入的商机被'.$s['name'].'确认为无效商机。'.' 客户名:'.$boppo['uname'].' 客户电话：'.$boppo['mobile'];

            $takemoney=$db->get('takenow','money',[
                'AND'=>[
                    'uid'=>$s['id'],
                    'type'=>1,
                    'class'=>1,
                    'targetId'=>$sid,
                ]
              ]);
            $a=addtake($wall['uid'],'-'.$takemoney,'1','49',$boppo['id'],$conten,$s['id']);
            $b=money($wall['uid'],'1','49',$sid);
          $flag = 200;
          $msg = '处理成功';

        }else{
          $mid = 0;
          $flag = 400;
          $msg = '处理失败';
        }
       $json = array('flag' => $flag,'msg' => $msg);
          return $response->withJson($json);


    }


    public function sjcreat($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      
    

      $as = [
        's'=>$s
      ];
      return $this->app->renderer->render($response, './s/sjcreat.php', $as);
    }

    //服务者-》录入商机
    public function sinsertBoppo($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $mobile=$_POST['mobile'];
      $boppos=$db->get('boppo',['id','satffId','creattime','yw'],['mobile'=>$mobile]);
      if($boppos){
          // if(isset($boppos['satffId'])&&$boppos['satffId']!=''){
          //     $member=$db->get('member','name',['id'=>$boppos['satffId']]);
          // }else{
          //   $member='客户';
          // }
          $yw = $boppos['yw'].' '.$_POST['yw'];
          $form = $s['name'].$s['mobile'];//新的录入人
          $boppo=$db->update('boppo',[
              'yw'=>$yw,
              'text'=>$_POST['text'],
              'creattime'=>date('Y-m-d H:i:s'),//录入时间修改成最新
              'satffId'=>$s['id'],//重新修改录入人
              'form'=>$form,
            ],[
              'id'=>$boppos['id'],
            ]);
          if($boppo){
            $flag = 200;
            $msg = '添加商机成功';
          }else{
            $mid = 0;
            $flag = 400;
            $msg = '商机添加失败';
          }
          $area = explode(' ',$_POST['city']);//分割地址
          if($_POST['city']==''){
            $area[0]='';
            $area[1]='';
            $area[2]='';
          }
          if($yw==''){
            $yw='详询客户';
          }
          if($_POST['text']==''){
            $ptext = '详询客户';
          }else{
            $ptext = $_POST['text'];
          }
          $db->insert('mcms_quan',[
            'title'=>'录入商机',
            'cateId'=>4,
            'author'=>$s['id'],
            'content'=>'有一条新的商机入库，客户所在地：'.$area[0].$area[1].$area[2].' 业务类型：'.$yw.'。 详情：'.$ptext,
            'creatTime'=>date('Y-m-d H:i:s'),
            'tags'=>'技能,',
            'name'=>$s['name']
          ]);

          $db->update('member',[
            'jljf[+]'=>1
          ],[
            'id'=>$s['id']
          ]);
            $json = array('flag' => $flag,'msg' => $msg);
          return $response->withJson($json);
      }else{
          $area = explode(' ',$_POST['city']);//分割地址
          if($_POST['city']==''){
            $area[0]='';
            $area[1]='';
            $area[2]='';
          }
          $add_b=$db->get('address','*',[
            'name'=>$area[0],
          ]);//相同市
          $add_a=$db->get('address','*',[
            "AND"=>[
                'name'=>$area[2],
                'upid'=>$add_b['id'],
                ]
            ]);//相同区域
          $form = $s['name'].$s['mobile'];
          $mid = $db->insert("boppo", [
            "uname" => $_POST['name'],
            "mobile" =>$_POST['mobile'],
             "prov" => $area[0],
             "city" => $area[1],
             "area" => $area[2],
             "areaId"=>$add_a['id'],
              "text" => $_POST['text'],
              "where"=>0,
              "qd"=>$_POST['qd'],
              "yw"=>$_POST['yw'],
             "cateId"=>$_POST['cateId'],
             "status" => 1,
              "creattime" => date('Y-m-d H:i:s'),
             "form" => $form,
            "satffId" => $s['id'],
        ]);
          if($mid){
            

            $content=$s['name'].'录入了商机'.' '.'客户姓名:'.$_POST['name'].' 客户电话:'.$_POST['mobile'];
            $boppoid = $db->id($mid);
              
              $sj=$db->get('config','sjmoney');//后台设定的商机录入提成
              if(isset($sj)&&$sj!=''){
                $sjmoney=$sj;
              }else{
                $sjmoney=0;
              }
              $a = addtake($s['id'],$sjmoney,'0','1',$boppoid,$content,$s['id']);
              $push= puch(0,4,[$mobile],['',],[]);
               // $data = [$_POST['name'].'['.$area[0].$area[1].']',date('Y.m.d H:i'),0,0,0,'咨询服务相关报价'];
               //  pushsj($data);
          }

          if($mid){
            $flag = 200;
            $msg = '添加商机成功';
          }else{
            $mid = 0;
            $flag = 400;
            $msg = '商机添加失败';
          }

          if($yw==''){
            $yw='详询客户';
          }
          if($_POST['text']==''){
            $ptext = '详询客户';
          }else{
            $ptext = $_POST['text'];
          }
          $db->insert('mcms_quan',[
            'title'=>'录入商机',
            'cateId'=>4,
            'author'=>$s['id'],
            'content'=>'有一条新的商机入库，客户所在地：'.$area[0].$area[1].$area[2].' 业务类型：'.$yw.'。详情： '.$ptext,
            'creatTime'=>date('Y-m-d H:i:s'),
            'tags'=>'技能,',
            'name'=>$s['name']
          ]);

          

          $db->update('member',[
            'jljf[+]'=>1
          ],[
            'id'=>$s['id']
          ]);

          $json = array('flag' => $flag,'msg' => $msg);
          return $response->withJson($json);
      }
    }

      //我录入的商机列表
    public function staffSjs($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
       $s = $request->getAttribute('s');//服务者
      $as = [
      's'=>$s
      ];
      return $this->app->renderer->render($response, './s/sjs.php', $as);
    }

    //我录入的商机列表请求
    public function getstaffSjs($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
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
        'satffId'=>$s['id'],
        'ORDER'=>['id'=>'DESC'],
        'LIMIT'=>[$srow,10]
      ]);
      //求总数
      $count = $db->count('boppo',[
        'customsId'=>$s['id']
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

     //抢商机
    public function sjhall($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }

      if(isset($_GET['areaid']) && is_numeric($_GET['areaid']) && $_GET['areaid']>1){
        $areaid = $_GET['areaid'];
        //获取对应的市级。直辖市获取省级
        $a = $db->get('address','*',['id'=>$areaid]);

        if($a['provId']==1 || $a['provId']==2 || $a['provId']==9 || $a['provId']==22){
          //$b = $db->get('address','*',['id'=>$a['provId']]);
          $scity = $a['prov'];

          // $boppo = $db->select('boppo','*',[
          //   'AND'=>[
          //     'status'=>1,
          //     'prov'=>$scity,
          //   ],
          // 'ORDER'=>['id'=>'DESC'],
          // 'LIMIT'=>[$srow,10],
          // ]);
          $boppo = $db->select('boppo',[
            '[>]boppo_go_log'=>['id'=>'sid']
          ],[
            'boppo.id',
            'boppo.uname',
            'boppo.mobile',
            'boppo.cateId',
            'boppo.item',
            'boppo.text',
            'boppo.creattime',
            'boppo.status',
            'boppo.form',
            'boppo.qd',
            'boppo.prov',
            'boppo.city',
            'boppo.area',
            'boppo.areaId',
            'boppo.where',
          ],[
            'AND'=>[
              'boppo.status'=>1,
              'boppo.prov'=>$scity,
              'boppo_go_log.id'=>NULL
            ],
          'ORDER'=>['boppo.creattime'=>'DESC'],
          'LIMIT'=>[0,10],
          ]);
          //商机总数
          // $count = $db->count('boppo','*',['AND'=>[
          //     'status'=>1,
          //     'prov'=>$scity,
          // ]]);


        }else{

          $scity = $a['city'];
          $ids = [];

          $list100 = $db->select('boppo','*',[
            'city'=>$scity,
            'ORDER'=>['creattime'=>'DESC'],
            'LIMIT'=>[0,800]
          ]);

          for ($i=0;$i<count($list100);$i++) {
            $has = $db->has('boppo_go_log',[
              'sid'=>$list100[$i]['id']
            ]);
            if(!$has){
              array_push($ids, $list100[$i]);
            }
          }

          if(count($ids) >= 30){
            $boppo = $ids;
          }else{
            $boppo2 = $db->select('boppo','*',[
            'status'=>[1,2],
            'city'=>$scity,
            'ORDER'=>[
              'handleTime'=>'ASC'
            ],
            'LIMIT'=>[0,30],
            ]);

            //var_dump($scity);

            $boppo = array_merge($ids,$boppo2);
          }
          

          // $boppo = $db->select('boppo',[
          //   '[>]boppo_go_log'=>['id'=>'sid']
          // ],[
          //   'boppo.id',
          //   'boppo.uname',
          //   'boppo.mobile',
          //   'boppo.cateId',
          //   'boppo.item',
          //   'boppo.text',
          //   'boppo.creattime',
          //   'boppo.status',
          //   'boppo.form',
          //   'boppo.qd',
          //   'boppo.prov',
          //   'boppo.city',
          //   'boppo.area',
          //   'boppo.areaId',
          //   'boppo.where',
          // ],[
          //   'AND'=>[
          //     'boppo.status'=>1,
          //     'boppo.city'=>$scity,
          //     'boppo_go_log.id'=>NULL
          //   ],
          // 'ORDER'=>['boppo.creattime'=>'DESC'],
          // 'LIMIT'=>[$srow,10],
          // ]);

          //商机总数
          // $count = $db->count('boppo','*',['AND'=>[
          //     'status'=>1,
          //     'city'=>$scity,
          //   ]]);
          
        }
        //查询商机信息
          
        //$counts=ceil($count/10);//计算有多少页
        
      }else{

        $areaid = 0;
        //查询没有被处理过的商机
        $boppo = $db->select('boppo','*',[
          'status'=>1,
          'handleTime'=>null,
          'ORDER'=>['id'=>'DESC'],
          'LIMIT'=>[0,30],
          ]);
        // $boppo = $db->select('boppo',[
        //     '[>]boppo_go_log'=>['id'=>'sid']
        //   ],[
        //     'boppo.id',
        //     'boppo.uname',
        //     'boppo.mobile',
        //     'boppo.cateId',
        //     'boppo.item',
        //     'boppo.text',
        //     'boppo.creattime',
        //     'boppo.status',
        //     'boppo.form',
        //     'boppo.qd',
        //     'boppo.prov',
        //     'boppo.city',
        //     'boppo.area',
        //     'boppo.areaId',
        //     'boppo.where',
        //   ],[
        //     'AND'=>[
        //       'boppo.status'=>1,
        //       'boppo.prov'=>$scity,
        //       'boppo_go_log.id'=>NULL
        //     ],
        //   'ORDER'=>['boppo.creattime'=>'DESC'],
        //   'LIMIT'=>[$srow,10],
        //   ]);
        $cu = count($boppo);//获得查询数量
        if($cu<30){
          //如果不够30条 用30减去查询后所得的条数来做条件
          $cou = 30 - $cu;
          $boppob = $db->select('boppo','*',[
                'status'=>1,
                'handleTime[!]'=>null,
                // 'ORDER'=>['id'=>'DESC'],
                'LIMIT'=>[0,$cou],
                ]);
          $boppo=array_merge($boppo,$boppob);
        }
        //商机总数
        $count = $db->count('boppo','*',['status'=>1]);
        $counts = ceil($count/10);//计算有多少页

      }


      //获取服务者商点
      $wallet=$db->get('wallets','*',['uid'=>$s['id'],'utype'=>1]);
      if(isset($wallet['poin'])&&$wallet['poin']!=''){
        $poin=$wallet['poin'];
      }else{
        $poin='0.00';
      }
        //查询配置表给定的商机价值金额
      $sjdot = $db->get('config','sjdot');
      
      $as = [
      's'=>$s,
      'areaid'=>$areaid,
      'p'=>$p,//分页
      'counts'=>$counts,//共多少页
      'poin'=>$poin,//剩余商店
      'boppo'=>$boppo,//商机信息
      'sjdot'=>$sjdot,//商机价值
      ];
      return $this->app->renderer->render($response, './s/sjhall.php', $as);
    }

      //抢商机请求
    public function srobBoppo($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $id=$_POST['id'];//商机id
      //判断商机是不是被处理过
      $boppos=$db->get('boppo','*',['id'=>$id]);

      if($s['status']!=1){
          $flag=400;
          $msg='请先提交实名认证信息。。';
          $json = array('flag' => $flag,'msg' => $msg);
          return $response->withJson($json);
      }else{
        // if($boppos['status']==2){
        //   $flag=400;
        //   $msg='对不起，您来迟了';
        //   $json = array('flag' => $flag,'msg' => $msg);
        //   return $response->withJson($json);
        // }else{
        //如果没有被处理 修改商机状态处理人  修改流水表的提成发放 减去商点
        //查询商点 如果不够 不能抢商机
        $wallets = $db->get('wallets','*',['uid'=>$s['id'],'utype'=>1]);
        $sjdot = $db->get('config','sjdot');
        if($wallets['poin'] < $sjdot){
          //判断商店够不够
            $flag = 100;
            $msg = '对不起，您商点不足了';
            $json = array('flag' => $flag,'msg' => $msg);
            return $response->withJson($json);
        }else{
           //1修改商机状态
          $boppo = $db->update('boppo',[
              'status'=>2,
              'handleUid'=>$s['id'],//处理人
              'handleTime'=>date('Y-m-d H:i:s'),//处理时间
              'time'=>mktime(),//时间戳 用于判断24小时内有效商机
          ],[
              'id'=>$id,
          ]);
        //写入记录
        $boppo_log=$db->insert('boppo_go_log',[
                'sid'=>$id,//商机id
                'uid'=>$s['id'],//处理人id
                'text'=>'抢商机成功',
                'creatTime'=>date('Y-m-d H:i:s'),
                'type'=>1,//抢商机
          ]);
        //修改流水表状态  写入到录入人的钱包
        //1查询流水表 找出录入的这单商机 并且修改状态
        $take=$db->update('takenow',[
              'status'=>1,
              'updatetime'=>date('Y-m-d H:i:s'),
              'remarks'=>'商机被处理',
              'examineUserId'=>$s['id'],
          ],[
            'AND'=>[
                'uid'=>$s['id'],
                'type'=>1,
                'class'=>1,
                'targetId'=>$id,
            ]
          ]);

        //查询写入流水表的商机价格
        $takemoney=$db->get('takenow','money',[
            'AND'=>[
                'uid'=>$s['id'],
                'type'=>1,
                'class'=>1,
                'targetId'=>$id,
            ]
          ]);
        //写入钱包表 录入人
         $wall=$db->update('wallets',[
              'balance[+]'=>$takemoney,//查询出的价格写入余额表
          ],[
              'uid'=>$boppos['satffId'],
              'utype'=>1,
          ]);
        // exit;
          //查询后台设置的抢商机的价格
         //$sjdot=$db->get('config','sjdot');

        //处理人的前保镖商点 减5
        $walls=$db->update('wallets',[
              'poin[-]'=>$sjdot,
          ],[
              'uid'=>$s['id'],
              'utype'=>1,
          ]);
        //写入商点消费记录（未建立表）
        $poin=$db->insert('member_poin',[
              'staffId'=>$s['id'],
              'type'=>1,
              'creattime'=>date('Y-m-d H:i:s'),
              'remark'=>'兑换商机',
              'money'=>$sjdot,
          ]);
          if($poin){
            $flag=200;
            $msg='抢商机成功';
          }else{
            $poin=0;
            $flag=500;
            $msg='抢商机失败';
          }
          $json = array('flag' => $flag,'msg' => $msg,'mobile'=>$boppos['mobile']);
          return $response->withJson($json);
        }
      //}
      }
    }

    //抢商机反馈写入处理表
    public function srobBoppo_log($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $text=$_POST['value'];//处理结果
      $id=$_POST['id'];//商机id
      //信息写入处理结果表
      $boppo_log=$db->insert('boppo_go_log',[
            'sid'=>$id,//商机id
            'uid'=>$s['id'],//处理商机当前服务者id
            'text'=>$text,//处理结果
            'creatTime'=>date('Y-m-d H:i:s'),//处理时间
        ]);
      if($boppo_log){
        $flag=200;
        $msg='添加成功';
      }else{
        $boppo_log=0;
        $flag=400;
        $msg='处理失败';
      }
       $json = array('flag' => $flag,'msg' => $msg);
          return $response->withJson($json);
    }



    public function wallets($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $s['wallets'] = $db->get('wallets','*',[
          'AND'=>[
              'utype'=>1,
              'uid'=>$s['id'],
          ]
        ]);
      $as = [
      
      's'=>$s
      ];
      return $this->app->renderer->render($response, './s/wallets.php', $as);
    }

   public function walletslog($request, $response, $args){
    setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      //查询客户钱包表
      $walet=$db->get('wallets','*',['AND'=>['uid'=>$s['id'],'utype'=>0,'type'=>1]]);
      $list = [0,1,2,3,4,5];
      $as = [
      's'=>$s,
      'walet'=>$walet,
      'list'=>$list
      ];
      return $this->app->renderer->render($response, './s/walletslog.php', $as);
    }
    //服务者交易明细
    public function getWalletlog($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//用户
       //分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      //查询明细表
      $wallets=$db->select('takenow','*',[
          'AND'=>[
              'uid'=>$s['id'],
              'type'=>1,
              'status'=>1,
              ],
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[$srow,10]
              ]);
      //求总数
      $count=$db->count('takenow','*',[
            'AND'=>[
                  'uid'=>$s['id'],
                  'type'=>1,
                  'status'=>1,
            ],
        ]);
      $allp = round($count/10);//总页数
      $json = array('flag' => 200,'msg' => '成功', 'data' => [

        'wallets'=>$wallets,'count'=>$count,'allp'=>$allp
        ]);
          return $response->withJson($json);
    }

    public function complaint($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $complaint=$db->select('complains','*',['memberid'=>$s['id'],'ORDER'=>['id'=>'DESC']]);
      for($i=0;$i<count($complaint);$i++){
            $a['name']=$db->get('customs','name',['id'=>$complaint[$i]['cid']]);
            $a['mobile']=$db->get('customs','mobile',['id'=>$complaint[$i]['cid']]);
            $complaint[$i]['complaintname']=$a['name'].' '.$a['mobile'];
            //查询处理人
            $complaint[$i]['replyname']=$db->get('member','name',['id'=>$complaint[$i]['replymemberid']]);
            $complaint[$i]['replymobile']=$db->get('member','mobile',['id'=>$complaint[$i]['replymemberid']]);
      }
      $a=[];
      $as = [
      's'=>$s,
      'complaint'=>$complaint,
      ];
      return $this->app->renderer->render($response, './s/complaint.php', $as);
    }

    //加载投诉信息详细页面
    public function complaintEdit($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $id=$args['id'];
       //查询投诉表..............................
       $list = $db->get('complains',[//查询投诉表
          "[>]member" => ["memberid"=>"id"],//与员工表关联员工表里的id =投诉表里的memberid
          "[>]customs" => ["cid"=>"id"],//与客户表关联
          ],[
          'complains.id(id)',//投诉表的id
          'complains.text(text)',//投诉表的内容
          'customs.name(tname)',//投诉人的名
          'customs.mobile(tmobile)',//投诉人电话
          'complains.creattime(creattime)',//投诉表的投诉的时间
          'complains.replytime(replytime)',//处理时间
          'member.name(name)',//被投诉人
          'member.mobile(mobile)',//被投诉人电话
          'complains.contractid(contractid)',//合同编号
          'complains.reply(reply)',//处理结果

          ],[
              'complains.id'=>$id,//用get查询单条的时候where条件要写完整
          ]);

      $as = [
      's'=>$s,
      'list'=>$list,//投诉信息
      ];
      return $this->app->renderer->render($response, './s/complaint_edit.php', $as);
    }

    //执行投诉处理的写入
    public function complainInsert($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $id = $_POST['id'];
      //查询处理信息状态
      $comp = $db->get('complains','*',['id'=>$id]);

      $time = date("Y-m-d H:i:s");
      if($comp['status'] == 1){
        $json = array('flag' =>'400','msg' => '此投诉已经被处理', 'data' => [],);
        return $response->withJson($json);
      }else{
         //写入数据库
       $com = $db->update('complains',[
          "reply" => $_POST['reply'],//处理结果
          "replymemberid" => $s['id'],//处理人id
          "replytime" => $time,//处理时间
          'status'=>'1',       
        ],[
            'id'=>$_POST['id'],
        ]);
       if($com){
          $flag = 200;
          $msg = '处理成功';
          $cus = $db->get('customs',['name','mobile'],['id'=>$comp['cid']]);

          $push = puch(0,36,[$cus['mobile'],],[$cus['name'],],[
                  'name'=>$cus['name'],
                  'sname'=>$s['name'],
                  'smobile'=>$s['mobile'],
          ]);

        }else{
          $com = 0;
          $flag = 400;
          $msg = '处理失败，数据有误。';
        }     
        $json = array('flag' => $flag,'msg' => $msg, 'data' => [],);
        return $response->withJson($json);
      }
      
    }

    public function customservice($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      $s = $request->getAttribute('s');//服务者
      // $time=mktime();
      // var_dump($time);
      // exit;
      $as = [
      
      's'=>$s
      ];
      return $this->app->renderer->render($response, './s/customservice.php', $as);
    }

     //获取客户服务请求
    public function sinsertService($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $title=$_POST['title'];
      $text=$_POST['text'];
      $service=$db->insert('customs_service',[
          'name'=>$s['name'],
          'mobile'=>$s['mobile'],
          'title'=>$title,
          'text'=>$text,
          'creattime'=>date('Y-m-d H:i:s'),
          'status'=>0,
          ]);
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

    public function uinfo($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
        if(isset($s['IDPhoto'])&&$s['IDPhoto']!=''){
           $idphoto=$db->get('mcms_attachment','thumbnail',['id'=>$s['IDPhoto']]);
         }else{
          $idphoto='';
         }
         if(isset($s['IDPhoto_1'])&&$s['IDPhoto_1']!=''){
           $idphoto_1=$db->get('mcms_attachment','thumbnail',['id'=>$s['IDPhoto_1']]);
         }else{
          $idphoto_1='';
         }
          if(isset($s['IDPhoto_2'])&&$s['IDPhoto_2']!=''){
           $idphoto_2=$db->get('mcms_attachment','thumbnail',['id'=>$s['IDPhoto_2']]);
         }else{
          $idphoto_2='';
         }
         if(isset($s['certificate'])&&$s['certificate']!=''){
           $certificate=$db->get('mcms_attachment','thumbnail',['id'=>$s['certificate']]);
         }else{
          $certificate='';
         }
         if(isset($s['certificate_1'])&&$s['certificate_1']!=''){
           $certificate_1=$db->get('mcms_attachment','thumbnail',['id'=>$s['certificate_1']]);
         }else{
          $certificate_1='';
         }
         if(isset($s['certificate_2'])&&$s['certificate_2']!=''){
           $certificate_2=$db->get('mcms_attachment','thumbnail',['id'=>$s['certificate_2']]);
         }else{
          $certificate_2='';
         }
         if(isset($s['certificate_3'])&&$s['certificate_3']!=''){
           $certificate_3=$db->get('mcms_attachment','thumbnail',['id'=>$s['certificate_3']]);
         }else{
          $certificate_3='';
         }


         // var_dump($idphoto);
         // exit;
      $as = [
      'idphoto'=>$idphoto,//身份证正面照
      'idphoto_1'=>$idphoto_1,//身份证反面
      'idphoto_2'=>$idphoto_2,//手拿身份证照片
      'certificate'=>$certificate,//职业资格证书
      'certificate_1'=>$certificate_1,//职业资格证书
      'certificate_2'=>$certificate_2,//职业资格证书
      'certificate_3'=>$certificate_3,//职业资格证书
      's'=>$s
      ];
      return $this->app->renderer->render($response, './s/uinfo.php', $as);
    }

    //服务者修改个人信息请求
    public function supdaeInfo($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $IDPhoto=$_POST['IDPhoto'];//身份证正面
      $area=explode(' ',$_POST['area']);//分割地址
      if($_POST['area']==''){
        $area[0]='';
        $area[1]='';
        $area[2]='';
      }
      $updateinfo=$db->update('member',[
        'name'=>$_POST['name'],
        //'mobile'=>$_POST['mobile'],
        'sexy'=>$_POST['sexy'],
        'birthday'=>$_POST['birthday'],
        'prov'=>$area[0],
        'city'=>$area[1],
        'area'=>$area[2],
        'areaID'=>$_POST['areaid'],
        'address'=>$_POST['address'],
        'sfz'=>$_POST['sfz'],
        'IDPhoto'=>$IDPhoto,
        'IDPhoto_1'=>$_POST['IDPhoto_1'],
        'IDPhoto_2'=>$_POST['IDPhoto_2'],
        'certificate'=>$_POST['certificate'],
        'certificate_1'=>$_POST['certificate_1'],
        'certificate_2'=>$_POST['certificate_2'],
        'avatar'=>$_POST['avatar'],
        'status'=>4,//服务者待审核状态
        ],[
        'id'=>$s['id'],
        ]);
      if($updateinfo){
          $json = array('flag' => 200,'msg' => '修改成功');
          return $response->withJson($json);
          exit();
      }else{
          $json = array('flag' => 400,'msg' => '修改失败');
          return $response->withJson($json);
          exit();
      }
    }


    

    public function editpwd($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      $s = $request->getAttribute('s');//服务者
      $as = [
      's'=>$s
      ];
      return $this->app->renderer->render($response, './s/editpwd.php', $as);
    }

      //修改密码
    public function updatePow($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      // $smobile=$_POST['smobile'];//旧密码
      $spassword=$_POST['upassword'];//新密码
      $repassword=$_POST['repassword'];//确认新密码
      // $pwd = MD5($smobile);
      //判断旧密码输入
      // if($pwd==$s['password']){
          if($spassword==$repassword){
                $spdate=$db->update('member',['password'=>$repassword,],['id'=>$s['id'],]);
                if($spdate){
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


   //服务者-》我的服务-》详情-》服务跟进
    public function contrfollowup($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $pics=$_POST['pics'];//图片
      $cid=$_POST['id'];//服务id
      $text=$_POST['text'];//内容
      $type=$_POST['type'];//类型  跟进 其他 票据等等
      //先判断有没有执行权限
      $b = $db->get('contract',['id','staffId','uId','orderId','comanyId'],[
          'id'=>$cid
          ]);
      // var_dump($b['mobile']);
      // exit;
      if($b['staffId'] != $s['id']){
        //如果登录的id 不等于合同里的服务人id 不能处理
           $json = array('flag' => 400,'msg' => '对不起，你没有处理该合同的权限');
           return $response->withJson($json);
        }else{

          //根据合同id 查询订单id 在查询合同里有哪些合同属于同一个订单id并且是同一个服务者
          $cont = $db->select('contract',['id','staffId'],[
              'AND'=>[
                  'orderId'=>$b['orderId'],
                  'staffId'=>$s['id'],
                  'sId[!]'=>[22,23,114,115,121,],
                  'status'=>5,
              ]
            ]);
          //查询后 循环
          if($cont){
            for($i=0;$i<count($cont);$i++){
            $mid[$i]= $db->insert("contract_speed", [
              "oId"=>$cont[$i]['id'],//合同id
              "text"=>$_POST['text'],//跟进内容
              "type"=>'speed',//类别
              "creattime" => date('Y-m-d H:i:s'),//处理时间
              "uid" => $s['id'],//服务者id
              "pic"=>$pics,//图片集
              "cutoms_ok"=>0,//判断合同的完成 0未完成1合同完成
              "stype"=>$_POST['type'],//类别  常规  票据装订  其他
            ]);
             if($mid[$i]){
                //修改合同的处理状态 今日处理的记录
                  $contract=$db->update('contract',['gostatus'=>2],['id'=>$cont[$i]['id']]);
              }

            }
          }else{
            $mid= $db->insert("contract_speed", [
              "oId"=>$cid,//合同id
              "text"=>$_POST['text'],//跟进内容
              "type"=>'speed',//类别
              "creattime" => date('Y-m-d H:i:s'),//处理时间
              "uid" => $s['id'],//服务者id
              "pic"=>$pics,//图片集
              "cutoms_ok"=>0,//判断合同的完成 0未完成1合同完成
              "stype"=>$_POST['type'],//类别  常规  票据装订  其他
            ]);
          }

          $mobile = $db->get('customs',['id','mobile','name'],['id'=>$b['uId']]);
          $company = $db->get('companies',['id','companyname'],['id'=>$b['comanyId']]);
          
          if($company){
            $db->insert('mcms_quan',[
              'title'=>'执行合同',
              'cateId'=>2,
              'author'=>$s['id'],
              'content'=>'录入合同执行记录：'.$_POST['text'].', 客户：'.$company['companyname'],
              'creatTime'=>date('Y-m-d H:i:s'),
              'tags'=>'技能,',
              'name'=>$s['name']
            ]);
          }else{
            $db->insert('mcms_quan',[
              'title'=>'执行合同',
              'cateId'=>2,
              'author'=>$s['id'],
              'content'=>'录入合同执行记录：'.$_POST['text'].', 客户：'.$mobile['name'],
              'creatTime'=>date('Y-m-d H:i:s'),
              'tags'=>'技能,',
              'name'=>$s['name']
            ]);
          }
          

          $db->update('member',[
            'jljf[+]'=>1
          ],[
            'id'=>$s['id']
          ]);


          if($mid){
              //短信对接
                // //查询客户电话
                
                // $push = pushSMS($mobile['mobile'],$texts,$mobile['id'],'服务者跟进',0);
                $push= puch(0,7,[$mobile['mobile'],],['客户',],[
                  'text'=>'【'.$text.'】',
                  'name'=>$s['name'],
                  'mobile'=>$s['mobile']
                ]);
                // var_dump($push);
                // exit;
                // $push= puch(0,7,[$mobile['mobile'],],['客户',],['text'=>$text]);
                $flag = 200;
                $msg = '处理成功';
              } else{
                $mid = 0;
                $flag = 400;
                $msg = '处理失败';
              }
          $json = array('flag' => $flag,'msg' => $msg,);
          return $response->withJson($json);
        }
    }


    //服务者-》我的合同-》详情-》完结
    public function contractEnd($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $pics=$_POST['pics'];//图片
      $cid=$_POST['id'];//服务id
      $text=$_POST['text'];//内容
      $type=$_POST['type'];//类型  跟进 其他 票据等等
      //先判断有没有执行权限
      $b = $db->get('contract',['id','staffId','status','uId'],[
          'id'=>$cid
          ]);
      if($b['staffId'] != $s['id']){
        //如果登录的id 不等于合同里的服务人id 不能处理
           $json = array('flag' => 400,'msg' => '对不起，你没有处理该合同的权限');
           return $response->withJson($json);
        }elseif($b['status'] == 7){
           $json = array('flag' => 400,'msg' => '该合同已经完结', 'data' =>[],'time'=>date('Y-m-d H:i:s'));
           return $response->withJson($json);
        }else{
           $mid= $db->insert("contract_speed", [
              "oId"=>$cid,
              "text"=>'服务已完成并已提交客户确认',
              "type"=>'end',
              "creattime" => date('Y-m-d H:i:s'),
              "uid" => $s['id'],
              'pic'=>$pics,
              'stype'=>$_POST['type'],
            ]);
           if($mid){
              //修改合同状态
              $contract=$db->update('contract',[
                'status'=>7,
                'finishtime'=>date('Y-m-d H:i:s'),
              ],[
                'id'=>$cid,
              ]);
              if($contract){
                $name=$db->get('member','name',['id'=>$b['staffId']]);
                 //短信对接
                // $texts = "您的订单已完结，感谢您的支持，后续有其他业务办理可随时联系我们  服务者:[".$name."],完结内容:[".$text."]， 提示：该订单请到平台确认完结！";
                // //查询客户电话
                $mobile=$db->get('customs',['id','mobile','name'],['id'=>$b['uId']]);
                // $push = pushSMS($mobile['mobile'],$texts,$mobile['id'],'服务者完结',0);
               $push= puch(0,8,[$mobile['mobile'],],['客户',],['name'=>'【'.$name.'】']);
                $flag=200;
                $msg='合同处理成功';
              }else{
                $contract=0;
                $flag=400;
                $msg='处理失败';
              }
            }else{
              $mid = 0;
              $flag = 400;
              $msg = '合同处理失败';
            }
            $json = array('flag' => $flag,'msg' => $msg);
            return $response->withJson($json);
        }

    }

     //服务者-》服务详情-》录入成本
    public function contractCost($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $cid=$args['id'];//合同id
       $as = [
      's'=>$s,
      'cid'=>$cid,//合同id
      ];
      return $this->app->renderer->render($response, './s/cost_form.php', $as);

    }

    //服务详情页的成本录入请求
    public function insertCost($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $cid=$_POST['id'];//合同id
      $money=$_POST['money'];//金额
      $text=$_POST['text'];//备注
      $b=$db->get('contract',['id','staffId'],[
          'id'=>$cid]);
      if($b['staffId']!=$s['id']){
            $flag = 400;
            $msg = '对不起，你没有处理该合同的权限';
            $json = array('flag' => $flag,'msg' => $msg, 'data' => $data);
            return $response->withJson($json);
       }else{
            //写入成本表 并且写入操作记录
          $money1=$db->get('contract','expenditure',['id'=>$cid]);
          $ce = $db->update("contract", [
            "expenditure" => $money+$money1, 
            ],['id'=>$cid]);
          if($ce){
          $flag = 200;
          $msg = '录入成功';
          $celog = $db->insert("contract_cost", [
              "cid" => $cid,
              "money" => $money, 
              "creattime" => date('Y-m-d H:i:s'),    
              "text" => $text,
              "staffid" => $s['id'],
            ]);

        }else{
          $flag = 400;
          $msg = '录入失败，请重试。';
        }
        $json = array('flag' => $flag,'msg' => $msg);
        return $response->withJson($json);
        } 
    }

     //服务者-》放弃服务
    public function contractGiveup($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $cid=$args['id'];//合同id
       $as = [
      's'=>$s,
      'cid'=>$cid,//合同id
      ];
      return $this->app->renderer->render($response, './s/giveup_form.php', $as);
    }

    //服务者-》放弃服务请求
    public function Giveup($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $sid=$_POST['id'];
      $text = $_POST['text'];
      //查询合同
      $b=$db->get('contract',['id','staffId','commission_run','sId','orderId','uId','status'],[
          'id'=>$sid]);
        //判断有没有处理权限
        if($b['staffId']!=$s['id']){
             $flag = 400;
              $msg = '对不起，你没有处理该合同的权限';
             $json = array('flag' => $flag,'msg' => $msg, 'data' => $data);
            return $response->withJson($json);
        }
        //判断合同有没有执行完成
        if($b['status']!=5){
          $flag = 400;
              $msg = '对不起，合同已经完结，不能放弃';
             $json = array('flag' => $flag,'msg' => $msg, 'data' => $data);
            return $response->withJson($json);
        }
        if($b['sId']==22||$b['sId']==23||$b['sId']==114||$b['sId']==115||$b['sId']==121){
              $hand=$db->select('takenow','id',['AND'=>['uid'=>$s['id'],'class'=>52,'targetId'=>$sid,'status'=>0]]);
              $hands=$db->select('takenow','id',['AND'=>['uid'=>$s['id'],'class'=>52,'targetId'=>$sid,'status'=>[0,1]]]);
               $upid=getup($s['id']);
              $hand1=$db->select('takenow','id',['AND'=>['uid'=>$upid,'class'=>60,'targetId'=>$sid,'status'=>0]]);
                $lastid=getup($upid);
              $hand2=$db->select('takenow','id',['AND'=>['uid'=>$lastid,'class'=>61,'targetId'=>$sid,'status'=>0]]);
              $count=count($hand);
              $counts=count($hands);
              $money=($b['commission_run']/$counts)*$count;
              $md = $db->update("contract",['staffId'=>null,
                      'status'=>'3',
                      // 'start_day'=>date('Y-m-d'),
                      'commission_run'=>$money
                    ],['id'=>$sid]);
               $db->update("takenow",['status'=>'2'],['id'=>$hand]);
               $db->update("takenow",['status'=>'2'],['id'=>$hand1]);
               $db->update("takenow",['status'=>'2'],['id'=>$hand2]);
          }else{
              $md = $db->update("contract",['staffId'=>null,
                      'status'=>'3',
                      // 'start_day'=>date('Y-m-d'),
                    ],['id'=>$sid]);
              $upid=getup($_COOKIE['staffID']);
              $lastid=getup($upid);
            $db->update("takenow",['status'=>'2'],['AND'=>['uid'=>$_COOKIE['staffID'],'class'=>52,'targetId'=>$sid,'status'=>0]]);
            $db->update("takenow",['status'=>'2'],['AND'=>['uid'=>$upid,'class'=>60,'targetId'=>$sid,'status'=>0]]);
            $db->update("takenow",['status'=>'2'],['AND'=>['uid'=>$lastid,'class'=>61,'targetId'=>$sid,'status'=>0]]);
          }
          if($md){
          $flag = 200;
          $msg = '退单成功';
          $name=$s['name'];
          $mid= $db->insert("contract_speed", [
                   "oId"=>$sid,
                   'text'=>$name.'已退单，理由：'.$text,
                   'type'=>'speed',
                   "creattime" => date('Y-m-d H:i:s'),
                   "uid" => $s['id']]);
                $name=$db->get('member','name',['id'=>$b['staffId']]);
                 //短信对接
                // $texts = "您好，我是你订单的服务者:[ ".$name." ],因为:[ ".$text."]的原因，本人需暂时将你的单子搁下，后续我们将安排其他服务者跟你对接，给你带来的不便深感抱歉。";
                // //查询客户电话
                $mobile=$db->get('customs',['id','mobile','name'],['id'=>$b['uId']]);
                // $push = pushSMS($mobile['mobile'],$texts,$mobile['id'],'服务者完结',0);
                $puch=puch(0,9,[$mobile['mobile'],],[$mobile['name'],],['text'=>'【'.$text.'】']);
        }else{
          $flag = 400;
          $msg = '退单失败，请重试';
        }
        $json = array('flag' => $flag,'msg' => $msg);
        return $response->withJson($json);
    }


    public function sdrecharge($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $s['wallets'] = $db->get('wallets','*',[
          'AND'=>[
              'utype'=>1,
              'uid'=>$s['id'],
          ]
        ]);
       
      $as = [
        's'=>$s
      ];
      return $this->app->renderer->render($response, './s/sdrecharge.php', $as);
    }
  

    public function walletsrecharge($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $s['wallets'] = $db->get('wallets','*',[
          'AND'=>[
              'utype'=>1,
              'uid'=>$s['id'],
          ]
        ]);
      $as = [
      's'=>$s,
      ];
      return $this->app->renderer->render($response, './s/walletsrecharge.php', $as);
    }

    public function walletstx($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $s['wallets'] = $db->get('wallets','*',[
          'AND'=>[
              'utype'=>1,
              'uid'=>$s['id'],
          ]
        ]);
      $as = [
      's'=>$s,
      ];
      return $this->app->renderer->render($response, './s/walletstx.php', $as);
    }

    //提现申请写入申请表 walletstx
    public function walletsInsert($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      //判断输入的金额是否正确
      $wallets = $db->get('wallets','*',[
          'AND'=>[
              'utype'=>1,
              'uid'=>$s['id'],
          ]
        ]);

      if($_POST['total']>$wallets['balance']){
        //如果输入的金额大雨余额
        $flag=400;
        $msg='提现金额超过余额，请重新输入';
        $json = array('flag' => $flag,'msg' => $msg);
        return $response->withJson($json);
      }else{
        //如果输入的金额小于余额
        //写入提现申请表
        $walletstx = $db->insert('walletstx',[
              'total'=>$_POST['total'],//提现申请金额
              'bank'=>$_POST['bank'],//开户银行
              'bankcard'=>$_POST['bankcard'],//开户行帐号
              'creattime'=>date('Y-m-d H:i:s'),//申请时间
              'staffID'=>$s['id'],//申请人id
              'status'=>1,//状态 1 未发放 2 已发放 3 驳回
          ]);
        if($walletstx){
          //成功后写入修改余额表
          $wall=$db->update('wallets',[
                'balance[-]'=>$_POST['total'],//余额减去申请的金额
                'unavailableBalance[+]'=>$_POST['total'],//提现金额写入冻结字段
            ],[
              'AND'=>[
                  'utype'=>1,
                  'uid'=>$s['id'],
              ]
            ]);
          $flag=200;
          $msg='提交成功';
        }else{
          $flag=400;
          $msg='提交失败';
        }
        $json = array('flag' => $flag,'msg' => $msg);
        return $response->withJson($json);
      }

    }

    public function walletstxlog($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
        //查询提现申请表
      $list=$db->select('walletstx','*',[
          'AND'=>[
              'staffID'=>$s['id'],
          ],
        ]);
      $as = [
      's'=>$s,
      'list'=>$list,
      ];
      return $this->app->renderer->render($response, './s/walletstxlog.php', $as);
    }

     //提现列表请求
    public function getwallestxlog($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
       //分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      //查询明细表
      $wallets=$db->select('walletstx','*',[
          'AND'=>[
              'staffID'=>$s['id'],
              ],
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[$srow,10]
              ]);
      $json = array('flag' => 200,'msg' => '成功', 'data' => [
          'wallets'=>$wallets,]);
      return $response->withJson($json);

    }
    
    public function rechangedo($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $sd = $_POST['sd'];
      $price = $_POST['price'];
      $term = $_POST['term'];
      $sw = $db->get('wallets','*',[
        'uid'=>$s['id']
      ]);
      if($sw['balance']>=$price){

      
        $db->update(
          'wallets',[
            'poin[+]'=>$sd,
            'balance[-]'=>$price
          ],['AND'=>[
            'uid'=>$s['id'],
            'utype'=>1
          ]
        ]);

        //写入记录
        $poin=$db->insert('member_poin',[
              'staffId'=>$s['id'],//服务者id
              'type'=>2,//
              'creattime'=>date('Y-m-d H:i:s'),//充值时间
              'remark'=>'充值商点',//说明
              'money'=>$price,//充值金额
          ]); 

        $json = array('flag' => 200,'msg' => '成功充值商点：'.$sd.'点');
      }else{
        $json = array('flag' => 400,'msg' => '余额已不足支付本次商点充值，请先充值。');
      }
        return $response->withJson($json);
    }

     //加载个人中心个人履历页面
    public function staffResume($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      
      $as = [
      's'=>$s,
      ];
      return $this->app->renderer->render($response, './s/resume.php', $as);
    }

    //执行个人中心个人炉里的修改请求
    public function staffresumeUpdate($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $member=$db->update('member',[
            'school'=>$_POST['school'],//毕业学院
            'specialty'=>$_POST['specialty'],//专业
            'education'=>$_POST['education'],//最高学历
            'familyphone'=>$_POST['familyphone'],//家庭电话
            'address'=>$_POST['address'],//联系地址
            'emergencycontact'=>$_POST['emergencycontact'],//紧急联系人
            'emergencyphone'=>$_POST['emergencyphone'],//紧急电话
            'bank'=>$_POST['bank'],//开户行
            'bankcard'=>$_POST['bankcard'],//银行卡号
        ],[
            'id'=>$s['id'],
        ]);
      if($member){
        $json = array('flag' => 200,'msg' => '修改成功');
        return $response->withJson($json);
        exit();
      }else{
        $json = array('flag' => 200,'msg' => '修改失败');
        return $response->withJson($json);
        exit();
      }

    }

     //服务者添加客户企业信息
    public function scompanyEdit($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者

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
      // var_dump($id);
      // exit;
    $as = [
      's'=>$s,
      'id'=>$id,//合同id
      'c'=>$c,
      'pics'=>$pics,
    ];
    return $this->app->renderer->render($response, './s/company-form.php', $as);
    }

    //服务者执行添加客户企业
    public function scompanyInsert($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      //查询企业名称是不是存在
      $comp=$db->get('companies','id',['companyname'=>$_POST['companyname']]);
      if($comp){
        $json = array('flag' => 400,'msg' => '该企业已经存在', 'data' => []);
         return $response->withJson($json);
         exit();
      }
      //获取合同id
      $contractid=$_POST['contractid'];//合同id 
      //根据合同id 查询该合同的uid 客户ID
      $uid=$db->get('contract','uId',['id'=>$contractid]);//获取客户id 
           //执行添加操作
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
            'cus_1'=>$uid,//联系人
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


    //写入充值记录（订单）
    public function walletsrechargeorder($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $money = $_POST['money'];
      $payid = time().''.rand(1000,9999);
      $o = $db->insert('wallets_recharge',[
        'utype'=>1,
        'uid'=>$s['id'],
        'money'=>$money,
        'type'=>'wallet',
        'time'=>mktime(),
        'creattime'=>date('Y-m-d H:i:s'),
        'meta'=>'钱包充值',
        'payid'=>$payid
      ]);
      $lid = $db->id();
      $json = array('flag' => 200,'msg' => '支付订单已生成，开始支付', 'data' => [
        'id'=>$lid,
        'payid'=>$payid,
        'title'=>'钱包充值',
        'total'=>$money
      ]);
      return $response->withJson($json);
    }

    public function bzj($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $wall=$db->get('wallets','*',[
          'AND'=>[
              'utype'=>1,
              'uid'=>$s['id'],
          ]
      ]);
      $list = $db->select('wallets_bzj','*',[
        'AND'=>['uid'=>$s['id'],
            'utype'=>1]
      ]);
       $list2 = $db->select('wallets_bzj_back','*',[
        'AND'=>['uid'=>$s['id'],
            'utype'=>1]
      ]);
      $as = [
        's'=>$s,
        'wall'=>$wall,
        'list'=>$list,
        'list2'=>$list2,
      ];
      return $this->app->renderer->render($response, './s/bzj.php', $as);
    }
    public function bzjsave($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $money = $_POST['money'];
      //查询余额是否充足
      $n = $db->get('wallets','*',[
        'AND'=>['uid'=>$s['id'],
            'utype'=>1]
          ]);
      if($money>$n['balance']){
        $json = array('flag' => 400,'msg' => '钱包余额已不足，请先充值', 'data' => [
        ]);
        return $response->withJson($json);
      }
      $db->update('wallets',[
          'bzj[+]'=>$money,
          'balance[-]'=>$money,
        ],[
            'AND'=>['uid'=>$s['id'],
            'utype'=>1]
        ]);
      $o = $db->insert('wallets_bzj',[
        'utype'=>1,
        'uid'=>$s['id'],
        'money'=>$money,
        'type'=>'wallet_bzj',
        'time'=>mktime(),
        'creattime'=>date('Y-m-d H:i:s'),
        'meta'=>'保证金充值',
      ]);
      $lid = $db->id();
      $json = array('flag' => 200,'msg' => '保证金充值已完成', 'data' => [
      ]);
      return $response->withJson($json);
    }

    public function bzjback($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      //查询保证金
      $n = $db->get('wallets','*',[
        'AND'=>['uid'=>$s['id'],
            'utype'=>1]
          ]);
      
      if($n['bzj']>0){
        
        $db->update('wallets',[
          'bzj[-]'=>$n['bzj'],
          'balance[+]'=>$n['bzj'],
        ],[
            'AND'=>['uid'=>$s['id'],
            'utype'=>1]
        ]);

        $o = $db->insert('wallets_bzj_back',[
          'utype'=>1,
          'uid'=>$s['id'],
          'money'=>$n['bzj'],
          'time'=>mktime(),
          'creattime'=>date('Y-m-d H:i:s'),
          'meta'=>'保证金退回',
          'status'=>1
        ]);
        $lid = $db->id();

        $json = array('flag' => 200,'msg' => '保证金已退回至您的钱包', 'data' => [
        ]);
        return $response->withJson($json);

      }else{
        $json = array('flag' => 400,'msg' => '无保证金', 'data' => [
        ]);
        return $response->withJson($json);
      }



    }

    public function walletsrechangelog($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }

      $list = $db->select('wallets_recharge','*',[
        'AND'=>[
            'uid'=>$s['id'],
            'utype'=>1,
            'status'=>1
          ],
        'ORDER'=>['id'=>'DESC'],
        'LIMIT'=>[$srow,10]
      ]);
      $count = $db->count('wallets_recharge',[
        'AND'=>[
            'uid'=>$s['id'],
            'utype'=>1,
            'status'=>1
          ],
      ]);
      $counts = ceil($count/10);//计算有多少页
      $as = [
        's'=>$s,
        'list'=>$list,
        'counts'=>$counts,
        'p'=>$p
      ];
      return $this->app->renderer->render($response, './s/walletsrechangelog.php', $as);
    }


    //加载企业的详情页面
    public function scompanyEdits($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $cid=$_GET['id'];//合同id
      $p=$_GET['p'];//分页数
      $id = $args['id'];
      $c = $db->get('companies','*',['id'=>$id]);
      $as = [
        's'=>$s,
        'c'=>$c,
        'p'=>$p,
        'cid'=>$cid,
      ];
      return $this->app->renderer->render($response, './s/company-edit.php', $as);

    }

    //执行企业的修改操作请求
    public function scompanyUpdate($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s=$request->getAttribute('s');//服务者
      $id=$_POST['id'];
      //执行修改
      $update=$db->update('companies',[
            'na'=>$_POST['na'],//国税帐号
            'napwd'=>$_POST['napwd'],//国税密码
            'na_end_day'=>$_POST['na_end_day'],//盘税到期日
            'nb'=>$_POST['nb'],//地税帐号
            'nbpwd'=>$_POST['nbpwd'],//地税密码
            'vpn'=>$_POST['vpn'],//vpn帐号
            'vpnpwd'=>$_POST['vpnpwd'],//vpn密码
            'vpn_end_day'=>$_POST['vpn_end_day'],//到期时间
            'webpname'=>$_POST['webpname'],//网上办事帐号
            'webppwd'=>$_POST['webppwd'],//网上办事密码
        ],[
            'id'=>$id,
        ]);

      if($update){
        $json = array('flag' => 200,'msg' => '修改成功', 'data' => [
        ]);
        return $response->withJson($json);
      }else{
        $json = array('flag' => 400,'msg' => '修改失败', 'data' => [
        ]);
        return $response->withJson($json);
      }

    }


    //服务者-》我的成交-》签单信息
    public function sjokOrders($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $id=$args['id'];
      //根据订单id 查询合同
      $contr=$db->select('contract','*',['orderId'=>$id]);
      //循环查询合同信息
      $i=0;
      $list=[];
      foreach($contr as $c){
        //合同id
        $list[$i]['id']=$c['id'];
        //客户姓名电话
        $customs=$db->get('customs',['name','mobile'],['id'=>$c['uId']]);
        $list[$i]['uname']=$customs['name'];
        //处理客户电话信息，显示部分号码
        $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
        //合同状态
        $list[$i]['status']=$db->get('contract_status','statusname',['id'=>$c['status']]);
        //服务内容
        $list[$i]['title']=$db->get('mcms_service','title',['id'=>$c['sId']]);
        //下单时间
        $list[$i]['creattime']=$c['creattime'];
        //合同金额
        $list[$i]['price']=$c['money_total'];
        //服务者
        $list[$i]['name']=$db->get('member','name',['id'=>$c['staffId']]);
        //推荐人
        if(isset($c['vcode'])&&$c['vcode']!=''){
          $vcode=$db->get('member_vcode','uId',['vcode'=>$c['vcode']]);
          $list[$i]['vname']=$db->get('member','name',['id'=>$vcode]);
        }else{
          $list[$i]['vname']='';
        }
      }
      $as = [
        's'=>$s,
        'list'=>$list,
      ];
      return $this->app->renderer->render($response, './s/sjokorders.php', $as);
    }

    public function ocos($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者

      $keyword = isset($request->getQueryParams()['key']) ? $request->getQueryParams()['key'] : '';//关键词


      $list = $db->select('contract',[
        '[>]companies'=>['comanyId'=>'id']
      ],[
        'contract.id',
        'companies.companyname',
        'companies.na',
        'companies.napwd',
        'companies.na_end_day',
        'companies.nb',
        'companies.nbpwd',
        'contract.cno',
        'contract.start_day',
        'contract.end_day',
      ],[
        'AND'=>[
          'contract.sId'=>[22,23,114,115,121],
          'contract.staffId'=>[84,202],
          'contract.status'=>[3,4,5,7,8,9,10],
          'companies.companyname[~]'=>$keyword
        ],
        'ORDER'=>['contract.id'=>'DESC']
      ]);

      $as = [
        's'=>$s,
        'list'=>$list,
        'key'=>$keyword
      ];
      return $this->app->renderer->render($response, './s/ocos.php', $as);
    }


      //执行修改企业的操作
    public function updatesCompaniesid($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $orderId=$_POST['ordersId'];//订单id
      $id=$_POST['id'];//企业id      //把企业id 修改到订单里 和合同里
      $order=$db->update('orders',[
            'comanyId'=>$id,
        ],[
            'id'=>$orderId,
        ]);
      if($order){
        //修改合同
        $contract=$db->update('contract',[
              'comanyId'=>$id,
          ],[
              'orderId'=>$orderId,
          ]);
        if($contract){
            $json = array('flag' => 200,'msg' => '修改成功', 'data' => [
          ]);
          return $response->withJson($json);
        }else{
          $json = array('flag' => 400,'msg' => '合同修改失败', 'data' => [
        ]);
        return $response->withJson($json);
        }
      }else{
        $json = array('flag' => 400,'msg' => '订单修改失败', 'data' => [
        ]);
        return $response->withJson($json);
      }
      // var_dump($orderId);
      // var_dump($id);
    }

     //个人信息写入cookie请求
    public function cookieInsert1($request, $response, $args){
      global $db;
      // setcookie("sexy[0]", $array, mktime()+7200,'/');
      setcookie("birthday", $_POST['birthday'], mktime()+7200,'/');
    }
    public function cookieInsert2($request, $response, $args){
      global $db;
      setcookie("area", $_POST['area'], mktime()+7200,'/');
    }
    public function cookieInsert3($request, $response, $args){
      global $db;
      setcookie("address", $_POST['address'], mktime()+7200,'/');
    }
    public function cookieInsert4($request, $response, $args){
      global $db;
      setcookie("sfz", $_POST['sfz'], mktime()+7200,'/');
    }




    public function myPrice($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $p = isset($request->getQueryParams()['p']) ? $request->getQueryParams()['p'] : 1;//分页
      $row = ($p * 10) - 10;
      $list = $db->select('mcms_service_price',[
        '[>]mcms_service'=>['sId'=>'id']
      ],[
        'mcms_service_price.id',
        'mcms_service_price.sId',
        'mcms_service_price.area',
        'mcms_service_price.price',
        'mcms_service_price.status',
        'mcms_service_price.time',
        'mcms_service_price.ischeck',
        'mcms_service.title',
      ],[
        'member_id'=>$s['id'],
        'ORDER'=>['id'=>'DESC'],
        'LIMIT'=>[$row,10]
      ]);
      $count = $db->count('mcms_service_price',[
        'member_id'=>$s['id']
      ]);
      $allp = ceil($count/10);
      $as = [
        's'=>$s,
        'list'=>$list,
        'p'=>$p,
        'allp'=>$allp,
        'count'=>$count,
      ];
      return $this->app->renderer->render($response, './s/myprice.php', $as);
    }

    public function myPriceForm($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
      $s = $request->getAttribute('s');//服务者
      $id = $request->getQueryParams()['id'];
      $detail = $db->get('mcms_service_price',"*",['id'=>$id]);
      $detail['title']=$db->get('mcms_service',['title'],['id'=>$detail['sId']]);
      $detail['areas'] = $db->get('address','*',['id'=>$detail['area']]);
 
      $list = $db->select('mcms_service','*',['status'=>0]);
      $as = [
        's'=>$s,
        'list'=>$list,
        'detail'=>$detail
      ];
      return $this->app->renderer->render($response, './s/myprice_form.php', $as);
    }

    public function myPriceSave($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
       global $db;
       $s = $request->getAttribute('s');

       $sid = $request->getParsedBody()['service_id'];
       $price = $request->getParsedBody()['price'];
       $areaid = $request->getParsedBody()['areaid'];
       $had = $db->has('mcms_service_price',[
        'AND'=>[
          'member_id'=>$s['id'],
          'sId'=>$sid,
          'area'=>$areaid
        ]
       ]);
       if($had){
        $db->update('mcms_service_price',[
          'price'=>$price,
          'time'=>time(),
          'ischeck'=>1
        ],[
        'AND'=>[
          'member_id'=>$s['id'],
          'sId'=>$sid,
          'area'=>$areaid
        ]
       ]);
       }else{

        $db->insert('mcms_service_price',[
          'price'=>$price,
          'time'=>time(),
          'ischeck'=>1,
          'member_id'=>$s['id'],
          'sId'=>$sid,
          'area'=>$areaid,
          'commission_yw'=>0,
          'commission_run'=>0
        ]);

        //赠送商点
        $_config = $db->get('config','*');
        if($_config['bj_sjdot']>0){
          //更新钱包商点
          $db->update('wallets',[
            'poin[+]'=>$_config['bj_sjdot']
          ],[
            'AND'=>[
              'utype'=>1,
              'uid'=>$s['id']
            ]
          ]);
          //写入商战充值记录
          $db->insert('member_poin',[
            'staffId'=>$s['id'],
            'type'=>2,
            'creattime'=>date('Y-m-d H:i:s'),
            'remark'=>'提交服务报价赠送商点',
            'money'=>$_config['bj_sjdot']
          ]);
        }


       }
        $json = array('flag' => 200,'msg' => '提交成功', 'data' => [
          'id'=>$id
        ]);
        return $response->withJson($json);
    }

    public function invocefriend($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
       global $db;
       $s = $request->getAttribute('s');
       $as = [
        's'=>$s,
      ];
      return $this->app->renderer->render($response, './s/invocefriend.php', $as);
    }

    public function invocefriendSave($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
       global $db;
        $s = $request->getAttribute('s');
        $mobile = trim($request->getParsedBody()['mobile']);
       $has = $db->has('member',[
          'mobile'=>$mobile
        ]);
        if($has){
          $mid = 0;
          $flag = 400;
          $msg = '手机号已存在';
        }else{
          $city = $request->getParsedBody()['city'];
          $citys = explode(' ', $city);
          $mid = $db->insert("member", [
            "name" => $request->getParsedBody()['name'],
            "mobile" => $mobile,
            "sexy" => $request->getParsedBody()['sexy'],
            "company" => $request->getParsedBody()['company'],
            "areaID" => $request->getParsedBody()['areaid'],
            "prov" => $citys[0],
            "city" => $citys[1],
            "area" => $citys[2],
            'status'=>5,
            'creattime'=>date('Y-m-d H:i:s'),
            'invoceMember'=>$s['id']
          ]);
          $nid = $db->id();
          if($mid){
            //发短信
            $db->insert('wallets',[
              'uid'=>$nid,
              'utype'=>1
            ]);

          $v = $db->get('member_vcode','*',['uId' => NULL]);
          $db->update('member_vcode',[
              'uId'=>$nid,
              'pid'=>NULL,
              'creattime'=>time(),
              'creatDate'=>date('Y-m-d H:i:s'),
              'type'=>1
            ],[
              'id'=>$v['id']
            ]);
            
            puch(0,30,[$mobile,],[$request->getParsedBody()['name'],],[
              'name'=>$request->getParsedBody()['name'],
              'sname'=>$s['name'],
              'smobile'=>$s['mobile']
            ]);

            $flag = 200;
            $msg = '邀请成功。';
          }else{
            $mid = 0;
            $flag = 400;
            $msg = '邀请失败。';
          }
        }
        $json = array('flag' => $flag,'msg' => $msg, 'data' => [
         
        ]);
        return $response->withJson($json);
    }

    public function companyascustom($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
      global $db;
       $s = $request->getAttribute('s');
       $as = [
        's'=>$s,
      ];
      return $this->app->renderer->render($response, './s/companyascustom.php', $as);
    }

    public function companyascustomSave($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
        global $db;
        $s = $request->getAttribute('s');
        $companyId = $request->getParsedBody()['company'];
        $customId = $request->getParsedBody()['custom'];
        if($companyId=='' || $customId==''){
          $json = array('flag' => 400,'msg' => '客户ID或者企业ID有误，请先确认正确。', 'data' => []);
          return $response->withJson($json);
        }
        $has = $db->has('companies',['id'=>$companyId]);
        if($has){
          //定位到cus_1
          $co = $db->get('companies','*',['id'=>$companyId]);
          
          if($co['cus_2']==0){
            $db->update('companies',[
              'cus_2'=>$customId,
            ],[
              'id'=>$companyId
            ]);
            $json = array('flag' => 200,'msg' => '已完成关联', 'data' => []);
            return $response->withJson($json);
          }

          if($co['cus_3']==0){
            $db->update('companies',[
              'cus_3'=>$customId,
            ],[
              'id'=>$companyId
            ]);
             $json = array('flag' => 200,'msg' => '已完成关联', 'data' => []);
            return $response->withJson($json);
          }

          if($co['cus_4']==0){
            $db->update('companies',[
              'cus_4'=>$customId,
            ],[
              'id'=>$companyId
            ]);
             $json = array('flag' => 200,'msg' => '已完成关联', 'data' => []);
            return $response->withJson($json);
          }

          if($co['cus_5']==0){
            $db->update('companies',[
              'cus_5'=>$customId,
            ],[
              'id'=>$companyId
            ]);
             $json = array('flag' => 200,'msg' => '已完成关联', 'data' => []);
            return $response->withJson($json);
          }
          

        }else{
          $json = array('flag' => 400,'msg' => '企业不存在', 'data' => []);
          return $response->withJson($json);
        }
        
    }

    public function getacompany($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
        global $db;
        $s = $request->getAttribute('s');
        $key = $request->getParsedBody()['key'];
        if($key!=''){
          $c = $db->get('companies','*',[
            'companyname[~]'=>$key
          ]);
          if($c){
            $json = array('flag' => 200,'msg' => '已查询到', 'data' => $c);
            return $response->withJson($json);
          }
          else{
            $json = array('flag' => 400,'msg' => '企业不存在', 'data' => []);
            return $response->withJson($json);
          }
          
        }else{
          $json = array('flag' => 400,'msg' => '参数为空', 'data' => []);
          return $response->withJson($json);
        }
    }

    public function getacustom($request, $response, $args){
      setcookie("thismyuri", '/scenter.html', mktime()+31104000,'/');
        global $db;
        $s = $request->getAttribute('s');
        $key = $request->getParsedBody()['key'];
        if($key!=''){
          $c = $db->get('customs','*',[
            'OR'=>[
              'name[~]'=>$key,
              'mobile'=>$key
            ]
          ]);
          if($c){
            $json = array('flag' => 200,'msg' => '已查询到', 'data' => $c);
            return $response->withJson($json);
          }
          else{
            $json = array('flag' => 400,'msg' => '客户不存在', 'data' => []);
            return $response->withJson($json);
          }
          
        }else{
          $json = array('flag' => 400,'msg' => '参数为空', 'data' => []);
          return $response->withJson($json);
        }
    }
}
