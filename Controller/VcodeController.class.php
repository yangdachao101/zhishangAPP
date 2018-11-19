<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class VcodeController 
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
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }
      
      $as = [
      'u'=>$u,
      's'=>$s,
      'vu'=>$vu,
      'vs'=>$vs
      ];
      return $this->app->renderer->render($response, './vcode.php', $as);
    }
    
    public function creatUvcode($request, $response, $args){
      global $db;
      $uid = $args['uid'];
      
      //get customs info
      $u = $db->get('customs','*',['id'=>$uid]);
      $ov = $db->has('member_vcode','*',['uId' => $uid]);
      if(!$ov){
        if($u['vcode'] != NULL){
          $v = $db->get('member_vcode','*',['uId' => NULL]);
          $db->update('member_vcode',[
            'uId'=>$uid,
            'pid'=>$u['vcode'],
            'creattime'=>mktime(),
            'creatDate'=>date('Y-m-d H:i:s'),
            'type'=>2
          ],[
            'id'=>$v['id']
          ]);
        }else{
          $v = $db->get('member_vcode','*',['uId' => NULL]);
          $db->update('member_vcode',[
            'uId'=>$uid,
            'creattime'=>mktime(),
            'creatDate'=>date('Y-m-d H:i:s'),
            'type'=>2
          ],[
            'id'=>$v['id']
          ]);
        }
      }
      return $response->withRedirect('/vcode.html');
    }

    public function getvu($request, $response, $args){
      global $db;
      $v = $db->get('member_vcode','*',['vcode'=>$args['vcode']]);
      if($v){
        if($v['type']==1){
          $vu = $db->get('member',['name','mobile'],['id'=>$v['uId']]);
        }else{
          $vu = $db->get('customs',['name','mobile'],['id'=>$v['uId']]);
        }
        return $response->withJson($vu);
      }else{
        return false;
      }
    }

    public function qrscan($request, $response, $args){
      global $db;

      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者

      $list = [];

      if($s){
        //获取服务者的vcode
        $su = $db->get('member_vcode','*',[
          'AND'=>[
            'type'=>1,
            'uId'=>$s['id']
          ]
        ]);
        $list = $db->select('member_vcode_log','*',[
          'vcode'=>$su['vcode'],
          'ORDER'=>['creattime'=>'DESC'],
          'LIMIT'=>[0,200]
        ]);

      }else{

        if($u){
          $ua = $db->get('member_vcode','*',[
            'AND'=>[
              'type'=>2,
              'uId'=>$u['id']
            ]
          ]);
          $list = $db->select('member_vcode_log','*',[
            'vcode'=>$ua['vcode'],
            'ORDER'=>['creattime'=>'DESC'],
            'LIMIT'=>[0,200]
          ]);

        }

      }
     

      $as = [
        'u'=>$u,
        's'=>$s,
        'list' => $list
      ];
      return $this->app->renderer->render($response, './v/vcode-qrscan.php', $as);
    }

     public function achievement($request, $response, $args){
      global $db;
      // echo "11111";
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $id=$u['id'];//客户id
        $type=2;//客户
        $vcode=$vu['vcode'];//自己的vcode
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $id=$s['id'];//于昂工id
        $type=1;//员工
        $vcode=$vs['vcode'];//自己的vcode
      }
      //计算时间
      if($type==1){
        //员工
        //加入时间
        $oktime=computingtime($id,$type);//参数1 id  参数2 员工或客户
        //计算 签单量 签单金额 团队签单量  团队金额
        $order_a=MyOrder($vcode,$type);//参数1 vcode 参数2  员工或客户
            //查询我完成的订单数量
        $contract=MyService($id,$vcode,$type);//参数1 id  参数2vcode 参数3 类型
        //查询当日收入
        $daymoney=daytakenowMoney($id);
        //查询本月收入
        $monthmoney=monthtakenowMoney($id);
        //查询全部收入
        $allmoney=alltakenowMoney($id);
      }else{
        //客户
        //加入时间
        $oktime=computingtime($id,$type);//参数1 id  参数2 员工或客户
         //计算 签单量 签单金额 团队签单量  团队金额
        $order_a=MyOrder($vcode,$type);//参数1 vcode 参数2  员工或客户
            //查询我完成的订单数量
        $contract=MyService($id,$vcode,$type);//参数
        //查询当日收入
        $daymoney=daytakenowMoney($id);
        //查询本月收入
        $monthmoney=monthtakenowMoney($id);
        //查询全部收入
        $allmoney=alltakenowMoney($id);
      }
      
      // var_dump($allmoney);
      // exit;
      
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'type'=>$type,//类型1是员工2 是客户
        'oktime'=>$oktime,//加入时间
        'order_a'=>$order_a,//计算 签单量 签单金额 团队签单量  团队金额
        'contract'=>$contract,//计算 我完成的服务 团队完成的服务
        'daymoney'=>$daymoney,//今日的收入
        'monthmoney'=>$monthmoney,//本月收入
        'allmoney'=>$allmoney,//全部收入
      ];
      return $this->app->renderer->render($response, './v/achievement.php', $as);
    }

     public function myteam($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
  
      $vu = [];
      $vcode='';
      // var_dump($vcode);
      // exit;
      $vs = [];
      if($s){
        //员工
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $vcode=$vs['vcode'];
      
      }
      $us = $db->select('member_vcode','*',[
        'AND'=>[
            'pid'=>$vcode,
            'type'=>1,
        ]
            
        ]);
      // var_dump($us);
      // exit;
      $list=[];
        if($us){
           $i=0;
            foreach($us as $v){
                  //查询姓名
                $list[$i]['name']=$db->get('member','name',['id'=>$v['uId']]);
                $list[$i]['id']=$v['uId'];
                $list[$i]['vcode']=$v['vcode'];
            
              $i++;
            }
        }
        // var_dump($list);
        // exit;
      $as = [
      'u'=>$u,
      's'=>$s,
      'vu'=>$vu,
      'vs'=>$vs,
      'list'=>$list,
      ];
      return $this->app->renderer->render($response, './v/myteam.php', $as);
    }


      //分销者
    public function myteamU($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }
      //根据路由传入的vcode值查询目标用户身份
      $vcode=$args['vcode'];
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
      if($vcodes['type']==1){
        //服务者
        $data=$db->get('member','*',['id'=>$vcodes['uId']]);
        $type=1;
        //今日成交业绩
        $mydaymoney=mydayMoney($vcodes['vcode'],$type);
        //本月的成交业绩
        $mymonthmoney=mymonthMoney($vcodes['vcode'],$type);
        //本年度成交业绩
        $myyearmoney=myyearMoney($vcodes['vcode'],$type);
        //全部成交业绩
        $myallmoney=myallMoney($vcodes['vcode'],$type);
        //---------------分割线-------------------//
        //我当日完成的合同
        $daycounts=mydayContract($vcodes['uId'],$type);
        //当月完成的合同
        $monthcount=mymonthContract($vcodes['uId'],$type);
        //本年完成的合同
        $yearcount=myyearContract($vcodes['uId'],$type);
        //全部完成的合同
        $allcount=myallContract($vcodes['uId'],$type);
      }else{
        //客户
        $data=$db->get('customs','*',['id'=>$vcodes['uId']]);
        $type=2;
        $mydaymoney=0;
        $mymonthmoney=0;
        $myyearmoney=0;
        $myallmoney=0;

        $daycounts=0;
        $monthcount=0;
        $yearcount=0;
        $allcount=0;
      }
      $as = [
      'u'=>$u,
      's'=>$s,
      'type'=>$type,//判断选择的vcode属于客户还是员工
      'vu'=>$vu,
      'vs'=>$vs,
      'data'=>$data,
      'vcode'=>$vcode,
      'mydaymoney'=>$mydaymoney,//当日的业绩
      'mymonthmoney'=>$mymonthmoney,//当月业绩
      'myyearmoney'=>$myyearmoney,//本年的业绩
      'myallmoney'=>$myallmoney,//全部的业绩
      'daycounts'=>$daycounts,//当日完成的合同
      'monthcount'=>$monthcount,//当月完成的合同
      'yearcount'=>$yearcount,//本年度完成的合同
      'allcount'=>$allcount,//全部完成的合同
      ];
      return $this->app->renderer->render($response, './v/myteam-u.php', $as);
    }



    public function myrecommender($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      $pid='';
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $pid=$vu['pid'];
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $pid=$vs['pid'];
      }
      //根据最后写入的pid 查询我的推荐人
      $vcode=$db->get('member_vcode','*',['vcode'=>$pid]);
       if($vcode['type']==1){
        //服务者
        $data=$db->get('member','*',['id'=>$vcode['uId']]);
      }else{
        //客户
        $data=$db->get('customs','*',['id'=>$vcode['uId']]);
      }
      //根据我的推荐人查询我的上级推荐人
      if(isset($vcode['pid'])&&$vcode['pid']!=''){
        $vcodeup=$db->get('member_vcode','*',['vcode'=>$vcode['pid']]);//上级推荐人的vcode
      if($vcodeup['type']==1){
              $dataup=$db->get('member','*',['id'=>$vcodeup['uId']]);
          }else{
            $dataup='';
          }
      }

      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'data'=>$data,
        'dataup'=>$dataup,
      ];
      return $this->app->renderer->render($response, './v/myrecommender.php', $as);
    }
    
   public function orderoklog($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $id=$u['id'];//客户id
        $type=2;//判断类型
        $vcode=$vu['vcode'];//客户推荐码
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $id=$s['id'];//服务者id
        $type=1;//判断类型
        $vcode=$vs['vcode'];//服务者推荐码
      }
      if($type==1){
        //服务者
        //获取当日业绩
        $mydaymoney=mydayMoney($vcode,$type);
        //当月业绩
        $mymonthmoney=mymonthMoney($vcode,$type);
        //本年度业绩
        $myyearmoney=myyearMoney($vcode,$type);
        //全部业绩
        $myallmoney=myallMoney($vcode,$type);
      //    //----------------分割线-----------------------------//
        //我的团队当日业绩
        $daytamemoney=daytamemoney($vcode,$type);//可以获得我名下团队的所有成员id  数量
        //我的团队当月业绩
        $monthtamemoney=monthtameMoney($vcode,$type);
        //我的团队本年业绩
        $yeartamemoney=yeartameMoney($vcode,$type);
        //我的团队全部业绩
        $alltamemoney=alltameMoney($vcode,$type);
        //----------------------分割线----------------------------
        //我的我的团队的总额度
        $zongdaymoney=$mydaymoney['daymoney']+$daytamemoney['dayprice'];//当日总额度
        $zongmonthmoney=$mymonthmoney['monthmoney']+$monthtamemoney['monthprice'];//当月总额度
        $zongyearmoney=$myyearmoney['yearmoney']+$yeartamemoney['yearprice'];//本年总额度
        $zongallmoney=$myallmoney['allmoney']+$alltamemoney['allprice'];//所有额度
      //    //----------------分割线-----------------------------//
        //服务者个人完结的合同数量
        $daycount=mydayContract($id,$type);
        //查询当月完成的合同数量
        $monthcount=mymonthContract($id,$type);
        //查询本年完成的合同数量
        $yearcount=myyearContract($id,$type);
        //我全部完结的合同数量
        $allcount=myallContract($id,$type);
      //    //----------------分割线-----------------------------//
            //团队完结的合同数量
        $listvcode=vcodeCounts($vcode);//获取团队所有人员vcode
        // 团队当日完成的合同数量
       $daytamecount=daytameCount($listvcode);
       //团队当月完成的合同数量
       $moathtamecount=moathtameCount($listvcode);
       //团队本年完成的合同数量
       $yeartamecount=yeartameCount($listvcode);
       //团队全部完成的合同数量
       $alltamecount=alltameCount($listvcode);
      
      }else{
        //客户
        //查询当日成交的金额
        $mydaymoney=mydayMoney($vcode,$type);
        //当月业绩
        $mymonthmoney=mymonthMoney($vcode,$type);
         //本年度业绩
        $myyearmoney=myyearMoney($vcode,$type);
        //全部业绩
        $myallmoney=myallMoney($vcode,$type);
      //   //----------------分割线-----------------------------//
        //我的团队当日业绩、、、、、、、、、、、、、、、、、、
        $daytamemoney=daytamemoney($vcode,$type);//可以获得我名下团队的所有成员id  数量
        //我的团队当月业绩
        $monthtamemoney=monthtameMoney($vcode,$type);
        //我的团队本年业绩
        $yeartamemoney=yeartameMoney($vcode,$type);
        //我的团队全部业绩
        $alltamemoney=alltameMoney($vcode,$type);
          //----------------------分割线----------------------------
        //我的我的团队的总额度
        $zongdaymoney=$mydaymoney['daymoney']+$daytamemoney['dayprice'];//当日总额度
        $zongmonthmoney=$mymonthmoney['monthmoney']+$monthtamemoney['monthprice'];//当月总额度
        $zongyearmoney=$myyearmoney['yearmoney']+$yeartamemoney['yearprice'];//本年总额度
        $zongallmoney=$myallmoney['allmoney']+$alltamemoney['allprice'];//所有额度
      //    //----------------分割线-----------------------------//
        $daycount=0;//客户没有完结合同
        $monthcount=0;
        $yearcount=0;
        $allcount=0;
      //    //----------------分割线-----------------------------//
        //团队完成的合同数量
        // $vcodeid=0;//团队的所有id
        $daytamecount=0;
        $moathtamecount=0;
        $yeartamecount=0;
        $alltamecount=0;
      }

      // var_dump($listvcode);
      // exit;
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'type'=>$type,
        'vcode'=>$vcode,
        // -------------分割线-------------------//
        'mydaymoney'=>$mydaymoney,//当日业绩
        'mymonthmoney'=>$mymonthmoney,//当月业绩
        'myyearmoney'=>$myyearmoney,//本年度业绩
        'myallmoney'=>$myallmoney,//全部业绩
        //  // -------------分割线-------------------//
        'daytamemoney'=>$daytamemoney,//团队的当日业绩
        'monthtamemoney'=>$monthtamemoney,//团队当月业绩
        'yeartamemoney'=>$yeartamemoney,//团队本年业绩
        'alltamemoney'=>$alltamemoney,//团队所有业绩
        // // -------------分割线-------------------//
        'daycount'=>$daycount,//我完结的合同数量
        'monthcount'=>$monthcount,//我当月完结的合同数量
        'yearcount'=>$yearcount,//我本年完结的合同数量
        'allcount'=>$allcount,//我全部完结的合同数量
        // // -------------分割线-------------------//
        'daytamecount'=>$daytamecount,//团队当天完成的合同数量
        'moathtamecount'=>$moathtamecount,//团队当月完成的合同数量
        'yeartamecount'=>$yeartamecount,//团队本年完成的合同数量
        'alltamecount'=>$alltamecount,//团段全部完成的合同数量
        //=================分割线----------------------
        'zongdaymoney'=>$zongdaymoney,//当日总额度
        'zongmonthmoney'=>$zongmonthmoney,//当月总额度
        'zongyearmoney'=>$zongyearmoney,//本年总额度
        'zongallmoney'=>$zongallmoney,//所有额度
      ];
      return $this->app->renderer->render($response, './v/orderoklog.php', $as);
    }

    
    public function customtips($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }
       //查询带接单订单
      $order=$db->select('orders','id',[
            'AND'=>[
                'status'=>1,
            ]
        ]);
      if($order){
        $ordernum=count($order);
      }else{
        $ordernum=0;
      }
      //查询正在服务中的订单
      $orders=$db->select('orders','id',['status'=>2]);
      if($orders){
        $orderscount=count($orders);
      }else{
        $orderscount=0;
      }
      //查询60日到期的合同
      $dayjia60 = date('Y-m-d', strtotime(' +60 day'));
      $contract=$db->select('contract','id',[
            'AND'=>[
                'stypeId'=>3,
                'status'=>5,
                'end_day[<=]'=>$dayjia60,
                'end_day[!]'=>['0000-00-00',NULL],
            ]
        ]);
      if($contract){
        $endcontract=count($contract);
      }else{
        $endcontract=0;
      }
       //查询30日内生日的客户
       $month=date('n',strtotime("-1 month"));
      $sql = 'select id FROM `zscrm_customs` WHERE month(birthday) = '.$month.''; 
      $birthday = $db->query($sql)->fetchAll();
      if($birthday){
        $birthcou=count($birthday);
      }else{
        $birthcou=0;
      }
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'ordernum'=>$ordernum,//待接订单的数量
        'orderscount'=>$orderscount,//正在服务中的订单
        'endcontract'=>$endcontract,//60日内到期
        'birthcou'=>$birthcou,//30日内过生日的客户
      ];
      return $this->app->renderer->render($response, './v/customtips.php', $as);
    }

    public function whatvcode($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }

      
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs
      ];
      return $this->app->renderer->render($response, './v/whatvcode.php', $as);
    }


    //团队-〉成员

    public function myteamUsales($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }

      
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs
      ];
      return $this->app->renderer->render($response, './v/myteamUsales.php', $as);
    }

    public function myteamUruned($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }

      
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs
      ];
      return $this->app->renderer->render($response, './v/myteamUruned.php', $as);
    }

    public function myteamUsjs($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }

      
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs
      ];
      return $this->app->renderer->render($response, './v/myteamUsjs.php', $as);
    }

   public function myteamUteams($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }
      //根据路由传入的vcode值查询目标用户身份
      $vcode=$args['vcode'];
      
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
        //服务者
        $data=$db->get('member','*',['id'=>$vcodes['uId']]);
        $type=$vcodes['type'];
        $us = $db->select('member_vcode','*',[
            'pid'=>$vcodes['vcode'],
        ]);
      $list=[];
        if($us){
           $i=0;
            foreach($us as $v){
              if($v['type']==1){
                  //查询姓名
                $list[$i]['name']=$db->get('member','name',['id'=>$v['uId']]);
                $list[$i]['id']=$v['uId'];
                $list[$i]['vcode']=$v['vcode'];
            }else{
                $list[$i]['name']=$db->get('customs','name',['id'=>$v['uId']]);
                $list[$i]['id']=$v['uId'];
                $list[$i]['vcode']=$v['vcode'];
            }
              $i++;
            }
        }
      
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'type'=>$type,
        'data'=>$data,
        'list'=>$list,
      ];
      return $this->app->renderer->render($response, './v/myteamUteams.php', $as);
    }

     public function myteamUdailys($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }
       //根据路由传入的vcode值查询目标用户身份
      $vcode=$args['vcode'];
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
      if($vcodes['type']==1){
        //服务者
         $data=$db->get('member',['id','name'],['id'=>$vcodes['uId']]);
         $report=$db->select('member_report','*',['uid'=>$vcodes['uId'],'ORDER' => ['id' => 'DESC']]);
         // var_dump($report);
         // exit;
      }else{
        //客户
         $data=$db->get('customs',['id','name'],['id'=>$vcodes['uId']]);
         $report=0;
      }

      // var_dump($data);
      // exit;
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'vcode'=>$vcode,
        'data'=>$data,
        'report'=>$report,
      ];
      return $this->app->renderer->render($response, './v/myteamUdailys.php', $as);
    }

     public function myteamUts($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }
      //根据路由传入的vcode值查询目标用户身份
      $vcode=$args['vcode'];
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
      if($vcodes['type']==1){
        //服务者
        $data=$db->get('member',['id','name'],['id'=>$vcodes['uId']]);
          //查询我被投诉的信息信息
        $complaint=$db->select('complains','*',['memberid'=>$data['id'],'ORDER'=>['id'=>'DESC']]);
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
      }else{
        //客户
        $data=$db->get('customs',['id','name'],['id'=>$vcodes['uId']]);
          //查询我投诉服务无者的信息信息
        $complaint=$db->select('complains','*',['cid'=>$data['id'],'ORDER'=>['id'=>'DESC']]);
         $a=[];
          for($i=0;$i<count($complaint);$i++){
            if($complaint[$i]['memberid']!=0){
                $a['name']=$db->get('customs','name',['id'=>$complaint[$i]['cid']]);
                $a['mobile']=$db->get('customs','mobile',['id'=>$complaint[$i]['cid']]);
                $complaint[$i]['complaintname']=$a['name'].' '.$a['mobile'];
            }
            //查询处理人
            $complaint[$i]['replyname']=$db->get('member','name',['id'=>$complaint[$i]['replymemberid']]);
            $complaint[$i]['replymobile']=$db->get('member','mobile',['id'=>$complaint[$i]['replymemberid']]);
          }
      }
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'data'=>$data,
        'complaint'=>$complaint,
      ];
      return $this->app->renderer->render($response, './v/myteamUts.php', $as);
    }
    
     public function myteamUinfo($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }
         //根据路由传入的vcode值查询目标用户身份
      $vcode=$args['vcode'];
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
      if($vcodes['type']==1){
        //服务者
        $data=$db->get('member','*',['id'=>$vcodes['uId']]);
        if(isset($u['pics'])&&$u['pics']!=''){
           $pics=$db->get('mcms_attachment','thumbnail',['id'=>$data['pics']]);
         }else{
          $pics='';
         }
        $type=1;
      }else{
        //客户
        $data=$db->get('customs','*',['id'=>$vcodes['uId']]);
        if(isset($u['pics'])&&$u['pics']!=''){
           $pics=$db->get('mcms_attachment','thumbnail',['id'=>$data['pics']]);
         }else{
          $pics='';
         }
        $type=2;
      } 
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'data'=>$data,
        'pics'=>$pics,
      ];
      return $this->app->renderer->render($response, './v/myteamUinfo.php', $as);
    }


    //----
   public function orders($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      if(isset($_GET['s'])&&$_GET['s']!=''){
        $time=$_GET['s'];
        $times=1;
      }else{
        $time='2016-1-1';
        $times='';
      }
      if(isset($_GET['e'])&&$_GET['e']!=''){
        $endtime=$_GET['e'];
      }else{
        $endtime=date('Y-m-d');
      }
       //根据路由传入的vcode值查询目标用户身份
      $vcode=$args['vcode'];
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
      if($vcodes['type']==1){
          //服务者
        $member=$db->get('member',['id','name'],['id'=>$vcodes['uId']]);//姓名
        //计算我名下所有成交的订单数量
        $orderscount=$db->count('orders','*',[
              'AND'=>[
                  'vcode'=>$vcode,
                  'status[!]'=>[0,6],
              ],
          ]);
        if(empty($orderscount)){
          $orderscount=0;
        }
        //判断是有时间参数还是没有时间参数如果优美查询全部
        if($times==1){
          //按时间查询
          $orders=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    'status[!]'=>[0,6],
                    'creattime[>=]'=>$time,
                    'creattime[<=]'=>$endtime,
                ],
                'ORDER'=>['id'=>'DESC'],
                'LIMIT'=>[$srow,10],
            ]);
          $ord=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    'status[!]'=>[0,6],
                    'creattime[>=]'=>$time,
                    'creattime[<=]'=>$endtime,
                ],
                'ORDER'=>['id'=>'DESC'],
            ]);
            $co_a=count($ord);
            $counts=ceil($co_a/10);//计算有多少页
             $i=0;
              $list=[];
              foreach($orders as $o){
                  //查询订单号和订单支付时间
                  $list[$i]['oid']=$o['orderId'];
                  $list[$i]['creattime']=$o['creattime'];
                  //查询客户信息
                  $customs=$db->get('customs',['id','name','mobile'],['id'=>$o['uid']]);
                  $list[$i]['uname']=$customs['name'];
                  $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
                  //订单状态
                  $list[$i]['status']=$db->get('orders_status','name',['id'=>$o['status']]);
                  //查询服务内容
                  if($o['type']==0){
                    //单品
                    $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sid']]);
                  }else{
                    //套餐
                    $list[$i]['title']=$db->get('mcms_group_service','group_title',['id'=>$o['sid']]);
                  }
                  //订单金额
                  $list[$i]['price']=$o['price'];
                  //查询关联企业
                  if(isset($o['comanyId'])&&$o['comanyId']!=0){
                    $list[$i]['comanyname']=$db->get('companies','companyname',['id'=>$o['comanyId']]);
                  }else{
                    $list[$i]['comanyname']=0;
                  }
                  $i++;
              }
        }else{
          //查询全部
          $orders=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    'status[!]'=>[0,6],
                ],
                'ORDER'=>['id'=>'DESC'],
                'LIMIT'=>[$srow,10],
            ]);
           $ord=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    'status[!]'=>[0,6],
                    'creattime[>=]'=>$time,
                    'creattime[<=]'=>$endtime,
                ],
                'ORDER'=>['id'=>'DESC'],
            ]);
            $co_a=count($ord);
            $counts=ceil($co_a/10);//计算有多少页
             $i=0;
              $list=[];
              foreach($orders as $o){
                  //查询订单号和订单支付时间
                  $list[$i]['oid']=$o['orderId'];
                  $list[$i]['creattime']=$o['creattime'];
                  //查询客户信息
                  $customs=$db->get('customs',['id','name','mobile'],['id'=>$o['uid']]);
                  $list[$i]['uname']=$customs['name'];
                  $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
                  //订单状态
                  $list[$i]['status']=$db->get('orders_status','name',['id'=>$o['status']]);
                  //查询服务内容
                  if($o['type']==0){
                    //单品
                    $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sid']]);
                  }else{
                    //套餐
                    $list[$i]['title']=$db->get('mcms_group_service','group_title',['id'=>$o['sid']]);
                  }
                  //订单金额
                  $list[$i]['price']=$o['price'];
                  //查询关联企业
                  if(isset($o['comanyId'])&&$o['comanyId']!=0){
                    $list[$i]['comanyname']=$db->get('companies','companyname',['id'=>$o['comanyId']]);
                  }else{
                    $list[$i]['comanyname']=0;
                  }
                  $i++;
              }
        }
      // var_dump($list);
      // exit;
      }else{
        //客户
        $member=$db->get('customs',['id','name'],['id'=>$vcodes['uId']]);//姓名
         //计算我名下所有成交的订单数量
        $orderscount=$db->count('orders','*',[
              'AND'=>[
                  'vcode'=>$vcode,
                  'status[!]'=>[0,6],
              ],
          ]);
        if(empty($orderscount)){
          $orderscount=0;
        }
        //判断是有时间参数还是没有时间参数如果优美查询全部
        if($times==1){
          //按时间查询
          $orders=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    'status[!]'=>[0,6],
                    'creattime[>=]'=>$time,
                    'creattime[<=]'=>$endtime,
                ],
                'ORDER'=>['id'=>'DESC'],
                'LIMIT'=>[$srow,10],
            ]);
           $ord=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    'status[!]'=>[0,6],
                    'creattime[>=]'=>$time,
                    'creattime[<=]'=>$endtime,
                ],
                'ORDER'=>['id'=>'DESC'],
            ]);
            $co_a=count($ord);
            $counts=ceil($co_a/10);//计算有多少页
             $i=0;
              $list=[];
              foreach($orders as $o){
                  //查询订单号和订单支付时间
                  $list[$i]['oid']=$o['orderId'];
                  $list[$i]['creattime']=$o['creattime'];
                  //查询客户信息
                  $customs=$db->get('customs',['id','name','mobile'],['id'=>$o['uid']]);
                  $list[$i]['uname']=$customs['name'];
                  $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
                  //订单状态
                  $list[$i]['status']=$db->get('orders_status','name',['id'=>$o['status']]);
                  //查询服务内容
                  if($o['type']==0){
                    //单品
                    $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sid']]);
                  }else{
                    //套餐
                    $list[$i]['title']=$db->get('mcms_group_service','group_title',['id'=>$o['sid']]);
                  }
                  //订单金额
                  $list[$i]['price']=$o['price'];
                  //查询关联企业
                  if(isset($o['comanyId'])&&$o['comanyId']!=0){
                    $list[$i]['comanyname']=$db->get('companies','companyname',['id'=>$o['comanyId']]);
                  }else{
                    $list[$i]['comanyname']=0;
                  }
                  $i++;
              }
        }else{
          //查询全部
          $orders=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    'status[!]'=>[0,6],
                ],
                'ORDER'=>['id'=>'DESC'],
                'LIMIT'=>[$srow,10],
            ]);
           $ord=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    'status[!]'=>[0,6],
                    'creattime[>=]'=>$time,
                    'creattime[<=]'=>$endtime,
                ],
                'ORDER'=>['id'=>'DESC'],
            ]);
            $co_a=count($ord);
            $counts=ceil($co_a/10);//计算有多少页
             $i=0;
              $list=[];
              foreach($orders as $o){
                  //查询订单号和订单支付时间
                  $list[$i]['oid']=$o['orderId'];
                  $list[$i]['creattime']=$o['creattime'];
                  //查询客户信息
                  $customs=$db->get('customs',['id','name','mobile'],['id'=>$o['uid']]);
                  $list[$i]['uname']=$customs['name'];
                  $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
                  //订单状态
                  $list[$i]['status']=$db->get('orders_status','name',['id'=>$o['status']]);
                  //查询服务内容
                  if($o['type']==0){
                    //单品
                    $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sid']]);
                  }else{
                    //套餐
                    $list[$i]['title']=$db->get('mcms_group_service','group_title',['id'=>$o['sid']]);
                  }
                  //订单金额
                  $list[$i]['price']=$o['price'];
                  //查询关联企业
                  if(isset($o['comanyId'])&&$o['comanyId']!=0){
                    $list[$i]['comanyname']=$db->get('companies','companyname',['id'=>$o['comanyId']]);
                  }else{
                    $list[$i]['comanyname']=0;
                  }
                  $i++;
              }
        } 
      }
      //查询订单状态表
      $orderstatus=$db->select('orders_status','*',['id[!]'=>[0,6]]);
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'p'=>$p,//分页数
        'vcode'=>$vcode,
        'counts'=>$counts,//共几页
        'list'=>$list,//订单信息
        'time'=>$time,//查询开始时间
        'times'=>$times,//判断有没有时间存在
        'endtime'=>$endtime,//查询结束时间
        'member'=>$member,//姓名
        'co_a'=>$co_a,//我推广成交的数量
        'orderstatus'=>$orderstatus,//订单状态
      ];
      return $this->app->renderer->render($response, './v/orders.php', $as);
    }

   public function contracts($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      if(isset($_GET['s'])&&$_GET['s']!=''){
        $time=$_GET['s'];
        $times=1;
      }else{
        $time='2016-1-1';
        $times='';
      }
      if(isset($_GET['e'])&&$_GET['e']!=''){
        $endtime=$_GET['e'];
      }else{
        $endtime=date('Y-m-d');
      }
      //根据路由传入的vcode值查询目标用户身份
      $vcode=$args['vcode'];
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
      $member=$db->get('member',['id','name'],['id'=>$vcodes['uId']]);
      //计算我名下所有合同的数量
      $contrcount=$db->count('contract','*',[
            'AND'=>[
                'staffId'=>$vcodes['uId'],
                'status'=>[7,8,9,10],
            ],
        ]);
      if(empty($contrcount)){
          $contrcount=0;
      }
      //查寻名下合同的条数
      if($times==1){
           $contract=$db->select('contract','*',[
              'AND'=>[
                  'staffId'=>$vcodes['uId'],
                  'finishtime[>=]'=>$time,
                  'finishtime[<=]'=>$endtime,
                  'status'=>[7,8,9,10],
              ],
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[$srow,10],
          ]);
           $co_a=count($contract);
           $counts=ceil($co_a/10);
            $i=0;
        $contr=[];
        foreach($contract as $c){
            //查询订单号和订单支付时间
            $order=$db->get('orders',['orderId','creattime'],['id'=>$c['orderId']]);
            $contr[$i]['oid']=$order['orderId'];
            $contr[$i]['creattime']=$order['creattime'];
            //查询客户信息
            $customs=$db->get('customs',['id','name','mobile'],['id'=>$c['uId']]);
            $contr[$i]['uname']=$customs['name'];
            $contr[$i]['mobile']=$customs['mobile'];
            $contr[$i]['status']=$db->get('contract_status','statusname',['id'=>$c['status']]);
            //查询服务内容
            $contr[$i]['title']=$db->get('mcms_service','title',['id'=>$c['sId']]);
            //查询完成时间
            $contr[$i]['endtime']=$c['end_day'];
            //查询关联企业
            if(isset($c['comanyId'])&&$c['comanyId']!=0){
              $contr[$i]['comanyname']=$db->get('companies','companyname',['id'=>$c['comanyId']]);
            }else{
              $contr[$i]['comanyname']=0;
            }
            $i++;
        }
        

      }else{
        //没有时间查询全部
        $contract=$db->select('contract','*',[
              'AND'=>[
                  'staffId'=>$vcodes['uId'],
                  'status'=>[7,8,9,10],
              ],
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[$srow,10],
          ]);
        $con_a=$db->count('contract','*',[
              'AND'=>[
                  'staffId'=>$vcodes['uId'],
                  'status'=>[7,8,9,10],
              ],
          ]);
          $counts=ceil($con_a/10);
        $i=0;
        $contr=[];
        foreach($contract as $c){
            //查询订单号和订单支付时间
            $order=$db->get('orders',['orderId','creattime'],['id'=>$c['orderId']]);
            $contr[$i]['oid']=$order['orderId'];
            $contr[$i]['creattime']=$order['creattime'];
            //查询客户信息
            $customs=$db->get('customs',['id','name','mobile'],['id'=>$c['uId']]);
            $contr[$i]['uname']=$customs['name'];
            $contr[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
            $contr[$i]['status']=$db->get('contract_status','statusname',['id'=>$c['status']]);
            //查询服务内容
            $contr[$i]['title']=$db->get('mcms_service','title',['id'=>$c['sId']]);
            //查询完成时间
            $contr[$i]['endtime']=$c['end_day'];
            //查询关联企业
            if(isset($c['comanyId'])&&$c['comanyId']!=0){
              $contr[$i]['comanyname']=$db->get('companies','companyname',['id'=>$c['comanyId']]);
            }else{
              $contr[$i]['comanyname']=0;
            }
            $i++;
        }

      }
     
      // var_dump($contr);
      // exit;
      $contractstatus=$db->select('contract_status','*',['id'=>[8,9,10]]);

      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'p'=>$p,
        'vcode'=>$vcode,
        'counts'=>$counts,//总页数
        'contrcount'=>$contrcount,//所有合同的数量
        'times'=>$times,//判断有没有时间查询
        'time'=>$time,//查询时间开始
        'endtime'=>$endtime,//查询时间结束
        'member'=>$member,//查看员工的姓名
        'contr'=>$contr,//合同信息
        'contractstatus'=>$contractstatus,
        
      ];
      return $this->app->renderer->render($response, './v/contracts.php', $as);
    }


     //客户维护查询客户信息请求
    public function customtipsEdit($request, $response, $args){
       global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $type=2;
        $id=$u['id'];
        $vcode=$vu['vcode'];
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $type=1;
        $id=$s['id'];
        $vcode=$vs['vcode'];
      }
      $name=$_POST['name'];
      if($type==1){
        //查询我名下有哪些合同
        $contract=$db->select('contract','uId',[
              'staffId'=>$id,
          ]);
        // var_dump($contract);
        // exit;
        //服务者
        $customs=$db->select('customs','*',[
          'AND'=>[
            'id'=>$contract,
                'OR'=>[
                    'name[~]'=>$name,
                    'mobile[~]'=>$name,
                ],
          ],
              
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[0,4],
          ]);
        // var_dump($customs);
        // exit;
        if($customs){
          $flag=200;
          $msg='查询成功';
        }else{
          $customs=0;
          $flag=400;
          $msg='该用户不存在';
        }
        $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['customs'=>$customs]);
        return $response->withJson($json);
      }else{
        //客户
         $contract=$db->select('contract','uId',[
              'vcode'=>$vcode,
          ]);
         // var_dump($contract);
         // exit;
        //查询合同
        $customs=$db->select('customs','*',[
          'AND'=>[
            'id'=>$contract,
                'OR'=>[
                    'name[~]'=>$name,
                    'mobile[~]'=>$name,
                ],
          ],
              
              'ORDER'=>['id'=>'DESC'],
              'LIMIT'=>[0,4],
          ]);
        if($customs){
          $flag=200;
          $msg='查询成功';
        }else{
          $customs=0;
          $flag=400;
          $msg='该用户不存在';
        }
        $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['customs'=>$customs]);
        return $response->withJson($json);
      }
    }

     //客户维护企业查询
    public function companiesEdit($request, $response, $args){
       global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $type=2;
        $id=$u['id'];
        $vcode=$vu['vcode'];
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $type=1;
        $id=$s['id'];
        $vcode=$vs['vcode'];
      }
      $name=$_POST['compname'];
      if($type==1){
        //服务者
          $contract=$db->select('contract','comanyId',[
              'AND'=>[
                'staffId'=>$id,
                'comanyId[!]'=>NULL,
              ]
          ]);
          $comp=$db->select('companies',['id','companyname'],[
                'AND'=>[
                    'id'=>$contract,
                    'OR'=>[
                      'decname[~]'=>$name,
                  ],
                ]
            ]);
          if($comp){
            $flag=200;
            $msg='查询成功';
          }else{
            $comp=0;
            $flag=400;
            $msg='该企业不存在';
          }
          $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['comp'=>$comp]);
          return $response->withJson($json);
          // var_dump($comp);
          // exit;
      }else{
        //客户
         $contract=$db->select('contract','comanyId',[
              'AND'=>[
                'vcode'=>$vcode,
                'comanyId[!]'=>NULL,
              ]
          ]);
          $comp=$db->select('companies',['id','companyname'],[
                'AND'=>[
                    'id'=>$contract,
                    'OR'=>[
                      'decname[~]'=>$name,
                  ],
                ]
            ]);
          if($comp){
            $flag=200;
            $msg='查询成功';
          }else{
            $comp=0;
            $flag=400;
            $msg='该企业不存在';
          }
          $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['comp'=>$comp]);
          return $response->withJson($json);
      }
    }

      //日报详情
    public function myteamDaily($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $ids=$u['id'];
        $type=2;
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $ids=$s['id'];
        $type=1;
      }
      $id=$args['id'];
      $look=$db->get('mcms_look','id',['AND'=>[
            'targetId'=>$id,
            'uid'=>$s['id'],
          ]]);
          $name=$db->get('member','name',['id'=>$s['id']]);
          if(empty($look)){
            $md=$db->insert('mcms_look',['targetId'=>$id,'uid'=>$s['id'],'uname'=>$name,'creattime'=>date('y-m-d H:i:s')]);
          }
         //根据路由传入的vcode值查询目标用户身份
      $vcode=$_GET['vcode'];
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
        //服务者
      $data=$db->get('member',['id','name'],['id'=>$vcodes['uId']]);
      $report=$db->get('member_report','*',['id'=>$id]);
      $report['writer']=$db->get('member','name',['id'=> $report['uid']]);
      $pic=json_decode($report['pics']);
      $pics=$db->select('mcms_attachment',['uri'],['id'=>$pic]);
      $names=$db->select('mcms_look',['uname'],['targetId'=>$id ]);
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'data'=>$data,
        'report'=>$report,
        'pics'=>$pics,
        'names'=>$names,
      ];
      return $this->app->renderer->render($response, './v/myteamUdailys-a.php', $as);
    }

     //合同查询
    public function contractQuery($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $ids=$u['id'];
        $type=2;
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $ids=$s['id'];
        $type=1;
      }
      $vcode=$_POST['vcode'];
      //分居vcode查询服务者信息
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
      // $member=$db->get('member',['id','name'],['id'=>$vcodes['uId']]);
      //合同状态
      if($_POST['status']==0){
        $status=[7,8,9,10];
      }else{
        $status=$_POST['status'];
      }
      //电话或姓名
      if(isset($_POST['mobile'])&&$_POST['mobile']!=''){
          $mobile=$_POST['mobile'];
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
      //时间类型
      if($_POST['selects']==0){
        //全部
        $time=date('Y-m-d', strtotime(' -1 year'));
        $endtime=date('Y-m-d');
      }else if($_POST['selects']==1){
        //当日
        $time=date('Y-m-d');
        $endtime=date('Y-m-d');
      }else if($_POST['selects']==2){
        //本月
        $time=date('Y-m-1');
        $endtime=date('Y-m-31');
      }else if($_POST['selects']==3){
        //本月
        $time=date('Y-1-1');
        $endtime=date('Y-12-31');
      }else if($_POST['selects']==4){
        //本月
        $time=$_POST['s'];
        $endtime=$_POST['e'];
      }
      // exit;
      $contract=$db->select('contract','*',[
          'AND'=>[
              'staffId'=>$vcodes['uId'],//服务者id
              'status'=>$status,//按状态查询
              'uId'=>$customsid,//按搜索内容 姓名 电话 查询
              'finishtime[>=]'=>$time,//自定义开始时间
              'finishtime[<=]'=>$endtime,//自定义结束时间

          ]
        ]);
      $i=0;
        $contr=[];
        foreach($contract as $c){
            //查询订单号和订单支付时间
            $order=$db->get('orders',['orderId','creattime'],['id'=>$c['orderId']]);
            $contr[$i]['oid']=$order['orderId'];
            $contr[$i]['creattime']=$order['creattime'];
            //查询客户信息
            $customs=$db->get('customs',['id','name','mobile'],['id'=>$c['uId']]);
            $contr[$i]['uname']=$customs['name'];
            $contr[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
            $contr[$i]['status']=$db->get('contract_status','statusname',['id'=>$c['status']]);
            //查询服务内容
            $contr[$i]['title']=$db->get('mcms_service','title',['id'=>$c['sId']]);
            //查询完成时间
            $contr[$i]['endtime']=$c['end_day'];
            //查询关联企业
            if(isset($c['comanyId'])&&$c['comanyId']!=0){
              $contr[$i]['comanyname']=$db->get('companies','companyname',['id'=>$c['comanyId']]);
            }else{
              $contr[$i]['comanyname']=0;
            }
            $i++;
        }
        // var_dump($contr);
        // exit;
      if($contr){
        $count=count($contr);//查询出的数量
        $flag=200;
        $msg='查询成功';
      }else{
        $count=0;//查询出的数量
        $contract=0;
        $flag=400;
        $msg="查询失败，没有数据";
      }
      $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['contr'=>$contr,'time'=>$time,'endtime'=>$endtime,'count'=>$count]);
      return $response->withJson($json);
    }

    //订单状态查询
    public function ordersQuery($request, $response, $args){
      global $db;
       $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $ids=$u['id'];
        $type=2;
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $ids=$s['id'];
        $type=1;
      }
      $vcode=$_POST['vcode'];
      //分居vcode查询服务者信息
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
       //订单状态
      if($_POST['status']==0){
        $status=[1,2,3];
      }else{
        $status=$_POST['status'];
      }
       //电话或姓名
      if(isset($_POST['mobile'])&&$_POST['mobile']!=''){
          $mobile=$_POST['mobile'];
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
      //时间类型
      if($_POST['selects']==0){
        //全部
        $time=date('Y-m-d', strtotime(' -1 year'));
        $endtime=date('Y-m-d');
      }else if($_POST['selects']==1){
        //当日
        $time=date('Y-m-d');
        $endtime=date('Y-m-d');
      }else if($_POST['selects']==2){
        //本月
        $time=date('Y-m-1');
        $endtime=date('Y-m-31');
      }else if($_POST['selects']==3){
        //本年
        $time=date('Y-1-1');
        $endtime=date('Y-12-31');
      }else if($_POST['selects']==4){
        //自定义时间
        $time=$_POST['s'];
        $endtime=$_POST['e'];
      }
       $orders=$db->select('orders','*',[
          'AND'=>[
              'vcode'=>$vcode,//服务者id
              'status'=>$status,//按状态查询
              'uid'=>$customsid,//按搜索内容 姓名 电话 查询
              'creattime[>=]'=>$time,//自定义开始时间
              'creattime[<=]'=>$endtime,//自定义结束时间

          ]
        ]);
        $i=0;
        $list=[];
        foreach($orders as $o){
            //查询订单号和订单支付时间
            $list[$i]['oid']=$o['orderId'];
            $list[$i]['creattime']=$o['creattime'];
            //查询客户信息
            $customs=$db->get('customs',['id','name','mobile'],['id'=>$o['uid']]);
            $list[$i]['uname']=$customs['name'];
            $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
            //订单状态
            $list[$i]['status']=$db->get('orders_status','name',['id'=>$o['status']]);
            //查询服务内容
            if($o['type']==0){
              //单品
              $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sid']]);
            }else{
              //套餐
              $list[$i]['title']=$db->get('mcms_group_service','group_title',['id'=>$o['sid']]);
            }
            //订单金额
            $list[$i]['price']=$o['price'];
            //查询关联企业
            if(isset($o['comanyId'])&&$o['comanyId']!=0){
              $list[$i]['comanyname']=$db->get('companies','companyname',['id'=>$o['comanyId']]);
            }else{
              $list[$i]['comanyname']=0;
            }
            $i++;
        }
        if($list){
        $count=count($orders);//查询出的数量
        $flag=200;
        $msg='查询成功';
      }else{
        $count=0;//查询出的数量
        $list=0;
        $flag=400;
        $msg="查询失败，没有数据";
      }
      $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['list'=>$list,'time'=>$time,'endtime'=>$endtime,'count'=>$count]);
      return $response->withJson($json);
    }

     //group(团队的推广信息)
      public function groupOrders($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $type=$vu['type'];
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $type=$vs['type'];
      }
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*2)-2;
      }else{
        $p = 1;
        $srow = 0;
      }
      if(isset($_GET['s'])&&$_GET['s']!=''){
        $time=$_GET['s'];
        $times=1;
      }else{
        $time='2016-1-1';
        $times='';
      }
      if(isset($_GET['e'])&&$_GET['e']!=''){
        $endtime=$_GET['e'];
      }else{
        $endtime=date('Y-m-d');
      }
       //根据路由传入的vcode值查询目标用户身份
      $vcode=$args['vcode'];
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
      $listvcode=vcodeCounts($vcode);//计算我的团队所有的vcode
      $member=$db->get('member',['id','name'],['id'=>$vcodes['uId']]);
      if($times==1){
        //如果有时间
           //计算我的团队下所有成交的订单数量
          $orders=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$listvcode,
                    'status[!]'=>[0,6],
                    'creattime[>=]'=>$time,
                    'creattime[<=]'=>$endtime,
                ],
            ]);
            if($orders){
              $orderscount=count($orders);
            }else{
              $orderscount=0;
            }
             $i=0;
          $list=[];
          foreach($orders as $o){
              //查询订单号和订单支付时间
              $list[$i]['oid']=$o['orderId'];
              $list[$i]['creattime']=$o['creattime'];
              //查询客户信息
              $customs=$db->get('customs',['id','name','mobile'],['id'=>$o['uid']]);
              $list[$i]['uname']=$customs['name'];
              $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
              //订单状态
              $list[$i]['status']=$db->get('orders_status','name',['id'=>$o['status']]);
              //查询服务内容
              if($o['type']==0){
                //单品
                $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sid']]);
              }else{
                //套餐
                $list[$i]['title']=$db->get('mcms_group_service','group_title',['id'=>$o['sid']]);
              }
              //订单金额
              $list[$i]['price']=$o['price'];
              //查询关联企业
              if(isset($o['comanyId'])&&$o['comanyId']!=0){
                $list[$i]['comanyname']=$db->get('companies','companyname',['id'=>$o['comanyId']]);
              }else{
                $list[$i]['comanyname']=0;
              }
              //查询属下名称
              $vcodetype=$db->get('member_vcode','*',['vcode'=>$o['vcode']]);
              if($vcodetype['type']==1){
                $list[$i]['vname']=$db->get('member','name',['id'=>$vcodetype['uId']]);
              }else{
                $list[$i]['vname']=$db->get('customs','name',['id'=>$vcodetype['uId']]);
              }
              $i++;
          }
      }else{
        //如果没有时间 查全部
        //计算我的团队下所有成交的订单数量
          $orders=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$listvcode,
                    'status[!]'=>[0,6],
                ],
                'ORDER'=>['id'=>'DESC'],
                'LIMIT'=>[$srow,2],
            ]);
           $order_a=$db->select('orders','*',[
                'AND'=>[
                    'vcode'=>$listvcode,
                    'status[!]'=>[0,6],
                ],
            ]);
          if($order_a){
            $orderscount=count($order_a);
            $counts=ceil($co_a/2);//计算有多少页
          }else{
            $orderscount=0;
            $counts=0;//计算有多少页
          }
          $counts=ceil($orderscount/2);//计算有多少页
          $i=0;
          $list=[];
          foreach($orders as $o){
              //查询订单号和订单支付时间
              $list[$i]['oid']=$o['orderId'];
              $list[$i]['creattime']=$o['creattime'];
              //查询客户信息
              $customs=$db->get('customs',['id','name','mobile'],['id'=>$o['uid']]);
              $list[$i]['uname']=$customs['name'];
              $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
              //订单状态
              $list[$i]['status']=$db->get('orders_status','name',['id'=>$o['status']]);
              //查询服务内容
              if($o['type']==0){
                //单品
                $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sid']]);
              }else{
                //套餐
                $list[$i]['title']=$db->get('mcms_group_service','group_title',['id'=>$o['sid']]);
              }
              //订单金额
              $list[$i]['price']=$o['price'];
              //查询关联企业
              if(isset($o['comanyId'])&&$o['comanyId']!=0){
                $list[$i]['comanyname']=$db->get('companies','companyname',['id'=>$o['comanyId']]);
              }else{
                $list[$i]['comanyname']=0;
              }
              //查询属下名称
              $vcodetype=$db->get('member_vcode','*',['vcode'=>$o['vcode']]);
              if($vcodetype['type']==1){
                $list[$i]['vname']=$db->get('member','name',['id'=>$vcodetype['uId']]);
              }else{
                $list[$i]['vname']=$db->get('customs','name',['id'=>$vcodetype['uId']]);
              }
              $i++;
          }
      }
      //查询订单状态表
      $orderstatus=$db->select('orders_status','*',['id[!]'=>[0,6]]);
      //团队成员
      $vcodename=[];
      for($j=0;$j<count($listvcode);$j++){
        $vcode_a[$j]=$db->get('member_vcode','*',['vcode'=>$listvcode[$j]]);
        if($vcode_a[$j]['type']==1){
          $vcodemember=$db->get('member','name',['id'=>$vcode_a[$j]['uId']]);
          $vcodename[$j]['name']=$vcodemember;
          $vcodename[$j]['vcode']=$vcode_a[$j]['vcode'];
          // $vcodename[$j]['name']=
        }else{
          $vcodemember=$db->get('customs','name',['id'=>$vcode_a[$j]['uId']]);
          $vcodename[$j]['name']=$vcodemember;
          $vcodename[$j]['vcode']=$vcode_a[$j]['vcode'];
        }
      }
        
      // var_dump($vcodename);
      // exit;
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'p'=>$p,
        'vcode'=>$vcode,
        'counts'=>$counts,
        'list'=>$list,//数据
        'time'=>$time,
        'endtime'=>$endtime,
        'times'=>$times,
        'member'=>$member,//员工姓名
        'orderscount'=>$orderscount,//总条数
        'orderstatus'=>$orderstatus,
        'vcodename'=>$vcodename,//按团队成员搜索
      ];
      return $this->app->renderer->render($response, './v/group_orders.php', $as);
    }

    //团队签单搜索请求
    public function groupordersQuery($request, $response, $args){
      global $db;
       $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $ids=$u['id'];
        $type=2;
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $ids=$s['id'];
        $type=1;
      }

      $vcode=$_POST['vcode'];
      //分居vcode查询服务者信息
      $vcodes=$db->get('member_vcode','*',['vcode'=>$vcode]);
      $listvcode=vcodeCounts($vcode);//计算我的团队所有的vcode
      //按成员名称
      if($_POST['vcodename']==0){
        $vcodeids=$listvcode;
      }else{
        $vcodeids=$_POST['vcodename'];
      }
       //订单状态
      if($_POST['status']==0){
        $status=[1,2,3];
      }else{
        $status=$_POST['status'];
      }
      //电话或姓名
      if(isset($_POST['mobile'])&&$_POST['mobile']!=''){
          $mobile=$_POST['mobile'];
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
       //时间类型
      if($_POST['selects']==0){
        //全部
        $time=date('Y-m-d', strtotime(' -1 year'));
        $endtime=date('Y-m-d');
      }else if($_POST['selects']==1){
        //当日
        $time=date('Y-m-d');
        $endtime=date('Y-m-d');
      }else if($_POST['selects']==2){
        //本月
        $time=date('Y-m-1');
        $endtime=date('Y-m-31');
      }else if($_POST['selects']==3){
        //本年
        $time=date('Y-1-1');
        $endtime=date('Y-12-31');
      }else if($_POST['selects']==4){
        //自定义时间
        $time=$_POST['s'];
        $endtime=$_POST['e'];
      }
      $orders=$db->select('orders','*',[
          'AND'=>[
              'vcode'=>$vcodeids,//推荐人
              'status'=>$status,//按状态查询
              'uid'=>$customsid,//按搜索内容 姓名 电话 查询
              'creattime[>=]'=>$time,//自定义开始时间
              'creattime[<=]'=>$endtime,//自定义结束时间
          ]
        ]);
        $i=0;
        $list=[];
        foreach($orders as $o){
            //查询订单号和订单支付时间
            $list[$i]['oid']=$o['orderId'];
            $list[$i]['creattime']=$o['creattime'];
            //查询客户信息
            $customs=$db->get('customs',['id','name','mobile'],['id'=>$o['uid']]);
            $list[$i]['uname']=$customs['name'];
            $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
            //订单状态
            $list[$i]['status']=$db->get('orders_status','name',['id'=>$o['status']]);
            //查询服务内容
            if($o['type']==0){
              //单品
              $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sid']]);
            }else{
              //套餐
              $list[$i]['title']=$db->get('mcms_group_service','group_title',['id'=>$o['sid']]);
            }
            //订单金额
            $list[$i]['price']=$o['price'];
            //查询关联企业
            if(isset($o['comanyId'])&&$o['comanyId']!=0){
              $list[$i]['comanyname']=$db->get('companies','companyname',['id'=>$o['comanyId']]);
            }else{
              $list[$i]['comanyname']=0;
            }
            //查询属下名称
            $vcodetype=$db->get('member_vcode','*',['vcode'=>$o['vcode']]);
            if($vcodetype['type']==1){
              $list[$i]['vname']=$db->get('member','name',['id'=>$vcodetype['uId']]);
            }else{
              $list[$i]['vname']=$db->get('customs','name',['id'=>$vcodetype['uId']]);
            }
            $i++;
        }
        if($list){
        $count=count($orders);//查询出的数量
        $flag=200;
        $msg='查询成功';
      }else{
        $count=0;//查询出的数量
        $list=0;
        $flag=400;
        $msg="查询失败，没有数据";
      }
      $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['list'=>$list,'time'=>$time,'endtime'=>$endtime,'count'=>$count]);
      return $response->withJson($json);
      // var_dump($orders);
      // exit;
    }

   //团队完成的合同
    public function groupContracts($request, $response, $args){
       global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
      }
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*10)-10;
      }else{
        $p = 1;
        $srow = 0;
      }
      if(isset($_GET['s'])&&$_GET['s']!=''){
        $time=$_GET['s'];
        $times=1;
      }else{
        $time='2016-1-1';
        $times='';
      }
      if(isset($_GET['e'])&&$_GET['e']!=''){
        $endtime=$_GET['e'];
      }else{
        $endtime=date('Y-m-d');
      }
      //根据路由传入的vcode值查询目标用户身份
      $vcode=$args['vcode'];
      $listvcode=vcodeCounts($vcode);//计算我的团队所有的vcode
       //团队成员
      $vcodename=[];
      for($j=0;$j<count($listvcode);$j++){
        $vcode_a[$j]=$db->get('member_vcode','*',['vcode'=>$listvcode[$j]]);
        if($vcode_a[$j]['type']==1){
          $vcodename[$j]=$vcode_a[$j]['vcode'];
        }
      }
      if($times==1){
        //有时间传值按时间查询
          $contract=$db->select('contract','*',[
            'AND'=>[
                'staffId'=>$vcodename,
                'status'=>[7,8,9,10],
                'finishtime[>=]'=>$time,
                'finishtime[<=]'=>$endtime,
            ],
            'ORDER'=>['id'=>'DESC'],
            'LIMIT'=>[$srow,10],
          ]);
        $contrcount=$db->count('contract','*',[
              'AND'=>[
                  'staffId'=>$vcodename,
                  'status'=>[7,8,9,10],
                  'finishtime[>=]'=>$time,
                  'finishtime[<=]'=>$endtime,
              ],
          ]);
        $counts=ceil($contrcount/10);
        $i=0;
        $contr=[];
        foreach($contract as $c){
            //查询订单号和订单支付时间
            $order=$db->get('orders',['orderId','creattime'],['id'=>$c['orderId']]);
            $contr[$i]['oid']=$order['orderId'];
            $contr[$i]['creattime']=$order['creattime'];
            //查询客户信息
            $customs=$db->get('customs',['id','name','mobile'],['id'=>$c['uId']]);
            $contr[$i]['uname']=$customs['name'];
            $contr[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
            $contr[$i]['status']=$db->get('contract_status','statusname',['id'=>$c['status']]);
            //查询服务内容
            $contr[$i]['title']=$db->get('mcms_service','title',['id'=>$c['sId']]);
            //查询完成时间
            $contr[$i]['endtime']=$c['finishtime'];
            //查询关联企业
            if(isset($c['comanyId'])&&$c['comanyId']!=0){
              $contr[$i]['comanyname']=$db->get('companies','companyname',['id'=>$c['comanyId']]);
            }else{
              $contr[$i]['comanyname']=0;
            }
            //查询服务者
            $contr[$i]['staffname']=$db->get('member','name',['id'=>$c['staffId']]);
            $i++;
        }
       }else{
        //没有时间传值查询全部
        $contract=$db->select('contract','*',[
            'AND'=>[
                'staffId'=>$vcodename,
                'status'=>[7,8,9,10],
            ],
            'ORDER'=>['id'=>'DESC'],
            'LIMIT'=>[$srow,10],
          ]);
        $contrcount=$db->count('contract','*',[
              'AND'=>[
                  'staffId'=>$vcodename,
                  'status'=>[7,8,9,10],
              ],
          ]);
        $counts=ceil($contrcount/10);
        $i=0;
        $contr=[];
        foreach($contract as $c){
            //查询订单号和订单支付时间
            $order=$db->get('orders',['orderId','creattime'],['id'=>$c['orderId']]);
            $contr[$i]['oid']=$order['orderId'];
            $contr[$i]['creattime']=$order['creattime'];
            //查询客户信息
            $customs=$db->get('customs',['id','name','mobile'],['id'=>$c['uId']]);
            $contr[$i]['uname']=$customs['name'];
            $contr[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
            $contr[$i]['status']=$db->get('contract_status','statusname',['id'=>$c['status']]);
            //查询服务内容
            $contr[$i]['title']=$db->get('mcms_service','title',['id'=>$c['sId']]);
            //查询完成时间
            $contr[$i]['endtime']=$c['finishtime'];
            //查询关联企业
            if(isset($c['comanyId'])&&$c['comanyId']!=0){
              $contr[$i]['comanyname']=$db->get('companies','companyname',['id'=>$c['comanyId']]);
            }else{
              $contr[$i]['comanyname']=0;
            }
            //查询服务者
            $contr[$i]['staffname']=$db->get('member','name',['id'=>$c['staffId']]);
            $i++;
        }
     
       }
      
      $contractstatus=$db->select('contract_status','*',['id'=>[8,9,10]]);
      $vcodename=[];
      for($j=0;$j<count($listvcode);$j++){
        $vcode_a[$j]=$db->get('member_vcode','*',['vcode'=>$listvcode[$j]]);
        if($vcode_a[$j]['type']==1){
          $vcodemember=$db->get('member','name',['id'=>$vcode_a[$j]['uId']]);
          $vcodename[$j]['name']=$vcodemember;
          $vcodename[$j]['vcode']=$vcode_a[$j]['vcode'];
        }
      }
      $as = [
        'u'=>$u,
        's'=>$s,
        'vu'=>$vu,
        'vs'=>$vs,
        'p'=>$p,
        'vcode'=>$vcode,
        'counts'=>$counts,//总页数
        'contrcount'=>$contrcount,//所有合同的数量
        'times'=>$times,//判断有没有时间查询
        'time'=>$time,//查询时间开始
        'endtime'=>$endtime,//查询时间结束
        'contr'=>$contr,//合同信息
        'contractstatus'=>$contractstatus,
        'vcodename'=>$vcodename,
        
      ];
      return $this->app->renderer->render($response, './v/group_contracts.php', $as);
    }

    //我的团队-》团队成员完成合同
    public function groupcontractQuery($request, $response, $args){
       global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $vu = [];
      if($u){
        $vu = $db->get('member_vcode','*',[
        'type'=>2,
        'uId'=>$u['id']
        ]);
        $ids=$u['id'];
        $type=2;
      }
      $vs = [];
      if($s){
        $vs = $db->get('member_vcode','*',[
        'type'=>1,
        'uId'=>$s['id']
        ]);
        $ids=$s['id'];
        $type=1;
      }
      //查询开始
      $vcode=$_POST['vcode'];
       if($_POST['vcodename']==0){
        $vcodeids=$listvcode;
      }else{
        $vcodeids=$_POST['vcodename'];
      }
       //订单状态
      if($_POST['status']==0){
        $status=[8,9,10];
      }else{
        $status=$_POST['status'];
      }
      //电话或姓名
      if(isset($_POST['mobile'])&&$_POST['mobile']!=''){
          $mobile=$_POST['mobile'];
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
       //时间类型
      if($_POST['selects']==0){
        //全部
        $time=date('Y-m-d', strtotime(' -1 year'));
        $endtime=date('Y-m-d');
      }else if($_POST['selects']==1){
        //当日
        $time=date('Y-m-d');
        $endtime=date('Y-m-d');
      }else if($_POST['selects']==2){
        //本月
        $time=date('Y-m-1');
        $endtime=date('Y-m-31');
      }else if($_POST['selects']==3){
        //本年
        $time=date('Y-1-1');
        $endtime=date('Y-12-31');
      }else if($_POST['selects']==4){
        //自定义时间
        $time=$_POST['s'];
        $endtime=$_POST['e'];
      }
      $contract=$db->select('contract','*',[
          'AND'=>[
              'staffId'=>$vcodeids,//服务者
              'status'=>$status,
              'uId'=>$customsid,
              'finishtime[>=]'=>$time,
              'finishtime[<=]'=>$endtime,
          ],
      ]);
      // $count=count($contract);
      $i=0;
        $contr=[];
        foreach($contract as $c){
            //查询订单号和订单支付时间
            $order=$db->get('orders',['orderId','creattime'],['id'=>$c['orderId']]);
            $contr[$i]['oid']=$order['orderId'];
            $contr[$i]['creattime']=$order['creattime'];
            //查询客户信息
            $customs=$db->get('customs',['id','name','mobile'],['id'=>$c['uId']]);
            $contr[$i]['uname']=$customs['name'];
            $contr[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
            $contr[$i]['status']=$db->get('contract_status','statusname',['id'=>$c['status']]);
            //查询服务内容
            $contr[$i]['title']=$db->get('mcms_service','title',['id'=>$c['sId']]);
            //查询完成时间
            $contr[$i]['endtime']=$c['end_day'];
            //查询关联企业
            if(isset($c['comanyId'])&&$c['comanyId']!=0){
              $contr[$i]['comanyname']=$db->get('companies','companyname',['id'=>$c['comanyId']]);
            }else{
              $contr[$i]['comanyname']=0;
            }
            //查询服务者
            $contr[$i]['staffname']=$db->get('member','name',['id'=>$c['staffId']]);
            $i++;
        }
        if($contr){
        $count=count($contract);//查询出的数量
        $flag=200;
        $msg='查询成功';
      }else{
        $count=0;//查询出的数量
        $contr=0;
        $flag=400;
        $msg="查询失败，没有数据";
      }
      $json = array('flag' =>$flag,'msg'=>$msg,'data'=>['contr'=>$contr,'time'=>$time,'endtime'=>$endtime,'count'=>$count]);
      return $response->withJson($json);
    }

    
}
