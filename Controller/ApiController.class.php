<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class ApiController 
{
  protected $app;

  public function __construct(ContainerInterface $ci) {
    $this->app = $ci;
  }
  public function __invoke($request, $response, $args) {
        //to access items in the container... $this->ci->get('');
  }

  public  function getcode($request, $response, $args){
    global $db;
    $data = [];
    $mobile = $request->getParsedBody()['mobile'];
    
    if(isset($mobile) && $mobile!=''){
            $creattime = date('Y-m-d H:i:s');
            $stpl = $db->get('sms_tpl',['content'],['id'=>1]);
            $keyword =[];
            $code = rand('100000','999999');
            $keyword['vcode'] = $code;
            
            $has = $db->has('sms_vcode',['mobile'=>$mobile]);

              if($has == true){
                $db->update('sms_vcode',[
                  'code'=>$code,
                  'creattime'=>$creattime,
                  'time'=>mktime()
                  ],['mobile'=>$mobile]);
              }else{
                $db->insert('sms_vcode',[
                  'mobile'=>$mobile,
                  'code'=>$code,
                  'creattime'=>$creattime,
                  'time'=>mktime()
                  ]);
              }


            $smstext =$stpl['content'];
            $content = preg_match_all("/\\[.*?\\]/is",$smstext,$array);
            //$content='为您服务创先争优，本次验证码为: '.$code.', 30分钟内有效。';
            for($i = 0; $i<count($array[0]);$i++) {
              $f = $array[0][$i];
              $fe = ltrim($f,'[');
              $fe = rtrim($fe,']');
              $smstext = str_replace($f,$keyword[$fe],$smstext);
            }


            $push = pushSMS($mobile,$smstext,0,'注册验证码',0);

            //$push = true;
            if ($push == 'success'){
              //写入数据库
              $hadmobile = $db->has('sms_vcode',['mobile'=>$mobile]);
              
              if($hadmobile){
                $db->update('sms_vcode',[
                  'code'=>$code,
                  'creattime'=>$creattime,
                  'time'=>mktime()
                  ],['mobile'=>$mobile]);
              }else{
                $db->insert('sms_vcode',[
                  'mobile'=>$mobile,
                  'code'=>$code,
                  'creattime'=>$creattime,
                  'time'=>mktime()
                  ]);
              }

              $json = array('flag' => 200,'msg' => '短信验证码发送成功，请注意查收', 'data' => []);
              return $response->withJson($json);
            }else{
              $json = array('flag' => 400,'msg' => '发送失败', 'data' => []);
              return $response->withJson($json);
            }
            
      }
  }

  public function getcodeVoice($request, $response, $args){
    global $db;
    $data = [];
    $mobile = $request->getParsedBody()['mobile'];
    
    if(isset($mobile) && $mobile!=''){
            $creattime = date('Y-m-d H:i:s');
            $stpl = $db->get('sms_tpl',['content'],['id'=>1]);
            $keyword =[];
            $code = rand('100000','999999');
            $keyword['vcode'] = $code;
            
            $has = $db->has('sms_vcode',['mobile'=>$mobile]);

              if($has == true){
                $db->update('sms_vcode',[
                  'code'=>$code,
                  'creattime'=>$creattime,
                  'time'=>mktime()
                  ],['mobile'=>$mobile]);
              }else{
                $db->insert('sms_vcode',[
                  'mobile'=>$mobile,
                  'code'=>$code,
                  'creattime'=>$creattime,
                  'time'=>mktime()
                  ]);
              }


            $smstext =$code;
            $content = preg_match_all("/\\[.*?\\]/is",$smstext,$array);
            //$content='为您服务创先争优，本次验证码为: '.$code.', 30分钟内有效。';
            for($i = 0; $i<count($array[0]);$i++) {
              $f = $array[0][$i];
              $fe = ltrim($f,'[');
              $fe = rtrim($fe,']');
              $smstext = str_replace($f,$keyword[$fe],$smstext);
            }


            $push = pushVoice($mobile,$smstext,0,'注册验证码',0);
            // $push = 'success';
            //$push = true;
            if ($push == 'success'){
              //写入数据库
              $hadmobile = $db->has('sms_vcode',['mobile'=>$mobile]);
              
              if($hadmobile){
                $db->update('sms_vcode',[
                  'code'=>$code,
                  'creattime'=>$creattime,
                  'time'=>mktime()
                  ],['mobile'=>$mobile]);
              }else{
                $db->insert('sms_vcode',[
                  'mobile'=>$mobile,
                  'code'=>$code,
                  'creattime'=>$creattime,
                  'time'=>mktime()
                  ]);
              }

              $json = array('flag' => 200,'msg' => '语音验证码已请求成功，请注意接听来电。', 'data' => []);
              return $response->withJson($json);
            }else{
              $json = array('flag' => 400,'msg' => '语音验证码已请求失败', 'data' => []);
              return $response->withJson($json);
            }
            
      }
  }

  public function userAgreement($request, $response, $args){
    global $db;
    $data = [];
    $i = $db->get('config','*');
    $data['user_agreement'] = $i['xy_staff'].$i['xy_custom'];
    $json = array('flag' => 200,'msg' => '', 'data' => $data);
    return $response->withJson($json);
  }

  public  function login($request, $response, $args){
    global $db;
    $data = [];
    $mobile = $request->getParsedBody()['mobile'];
    $code = $request->getParsedBody()['code'];
    //验证短信码是否为真
    $ntime = time() - 1800;
    $codeyes = $db->has('sms_vcode',[
      'AND'=>[
        'mobile'=>$mobile,
        'code'=>$code,
        'time[>=]'=>$ntime
      ]
    ]);

    if(!$codeyes){
      $json = array('flag' =>401,'msg'=>'短信验证码已过期或者错误。','data'=>$data);
      return $response->withJson($json);
    }

    $getone = $db->get('member','*',['mobile'=>$mobile]);
    //查询是否存在用户
    if($getone){
      $data['member'] = $getone;
    }else{
      //先注册
      $db->insert('member',[
        'mobile'=>$mobile
      ]);
      $data['member'] = $db->get('member','*',['mobile'=>$mobile]);
    }

    
    $appKey = 'p5tvi9dspngb4';
    $appSecret = 'rpBSGXu7JaQJq';
    $jsonPath = "IMAPI/jsonsource/";
    $RongCloud = new RongCloud($appKey,$appSecret);

    $m_id = $data['member']['id'];

    if($data['member']['name'] == NULL){
      $m_name = $data['member']['mobile'];
    }else{
      $m_name = $data['member']['name'];
    }

    $data['member']['zone_pic'] = 'http://app.cw2009.com/zoneDetault.jpg';

    if($data['member']['avatar'] == NULL){
      $m_avatar= 'http://app.cw2009.com/nopic.png';
    }else{
      $m_avatar = $data['member']['avatar'];
    }

    $result = $RongCloud->user()->getToken($m_id,$m_name , $m_avatar);
    $re = json_decode($result);
    $data['member']['im_result'] = $re;
    $data['member']['im_token'] = $re->token;
    
    $json = array('flag' =>200,'msg'=>'登录成功','data'=>$data);
    return $response->withJson($json);
  }

  public function loginPwd($request, $response, $args){
    global $db;
    $data = [];
    $mobile = $request->getParsedBody()['mobile'];
    $password = $request->getParsedBody()['password'];
    

    $getone = $db->get('member','*',[
      'AND'=>[
        'mobile'=>$mobile,
        'password'=>$password,
      ]
    ]);
    //查询是否存在用户
    if(!$getone){
      
      $json = array('flag' =>400,'msg'=>'手机号或密码错误，请使用短信验证码登录。','data'=>$data);
      return $response->withJson($json);
    }

    $data['member'] = $getone;

    
    $appKey = 'p5tvi9dspngb4';
    $appSecret = 'rpBSGXu7JaQJq';
    $jsonPath = "IMAPI/jsonsource/";
    $RongCloud = new RongCloud($appKey,$appSecret);

    $m_id = $data['member']['id'];

    if($data['member']['name'] == NULL){
      $m_name = $data['member']['mobile'];
    }else{
      $m_name = $data['member']['name'];
    }

    $data['member']['zone_pic'] = 'http://app.cw2009.com/zoneDetault.jpg';

    if($data['member']['avatar'] == NULL){
      $m_avatar= 'http://app.cw2009.com/nopic.png';
    }else{
      $m_avatar = $data['member']['avatar'];
    }

    $result = $RongCloud->user()->getToken($m_id,$m_name , $m_avatar);
    $re = json_decode($result);
    $data['member']['im_result'] = $re;
    $data['member']['im_token'] = $re->token;
    
    $json = array('flag' =>200,'msg'=>'登录成功','data'=>$data);
    return $response->withJson($json);
  }

  public function getUserInfo($request, $response, $args){
    global $db;
    $data = [];
    $userid = isset($request->getParsedBody()['userid']) ? $request->getParsedBody()['userid'] : 0 ;
    if($userid!=0){
      $data['userInfo'] = $db->get('member','*',['id'=>$userid]);
      $json = array('flag' => 200,'msg' => '获取用户信息成功', 'data' => $data);
      return $response->withJson($json);
    }else{
      $json = array('flag' => 400,'msg' => '没有查到用户信息', 'data' => []);
      return $response->withJson($json);
    }
  }

  public function dreamCate($request, $response, $args){
    global $db;
    $data = [];
    $data['list'] = $db->select('dream_cate','*');
    $json = array('flag' => 200,'msg' => '获取Dream分类列表成功', 'data' => $data);
    return $response->withJson($json);
    
  }
  public function dreams($request, $response, $args){
    global $db;
    $data = [];
    
    $p = isset($request->getParsedBody()['p']) ? $request->getParsedBody()['p'] : 1 ;
    $cateid = isset($request->getParsedBody()['cateid']) ? $request->getParsedBody()['cateid'] : 0 ;
    if($cateid==0){
      $cateid = $db->select('dream_cate','id');
    }
    $userid = isset($request->getParsedBody()['userid']) ? $request->getParsedBody()['userid'] : 0 ;

    $row = ($p * 10) - 10;


    if($userid!=0){
      $data['list'] = $db->select('dream',[
        '[>]member'=>['userid'=>'id']
      ],[
        'member.id(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
        'member.status(user_status)',
        'dream.id(dream_id)',
        'dream.userid(dream_userid)',
        'dream.title(dream_title)',
        'dream.cateid(dream_cateid)',
        'dream.content(dream_content)',
        'dream.zhan(dream_zhan)',
        'dream.target_zhan(dream_target_zhan)',
        'dream.pics(dream_pics)',
        'dream.endday(dream_endday)',
        'dream.creattime(dream_creattime)',
        'dream.video(dream_video)',
        'dream.thumb(dream_thumb)',
        'dream.status(dream_status)',
      ],[
        'AND'=>[
          'dream.userid'=>$userid,
          'dream.cateid'=>$cateid
        ],
        'ORDER'=>['dream.creattime'=>'DESC'],
        'LIMIT'=>[$row,10]
      ]);
      $data['ps']['count'] = $db->count('dream',[
        'AND'=>[
          'userid'=>$userid,
          'cateid'=>$cateid
        ]
      ]);
      $data['ps']['allpages'] = ceil($data['ps']['count']/10);
      $data['ps']['thispage'] = $p;
      $json = array('flag' => 200,'msg' => '获取列表成功', 'data' => $data);
      return $response->withJson($json);
    }else{
      
      $data['list'] = $db->select('dream',[
        '[>]member'=>['userid'=>'id']
      ],[
        'member.id(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
        'member.status(user_status)',
        'dream.id(dream_id)',
        'dream.userid(dream_userid)',
        'dream.title(dream_title)',
        'dream.cateid(dream_cateid)',
        'dream.content(dream_content)',
        'dream.zhan(dream_zhan)',
        'dream.target_zhan(dream_target_zhan)',
        'dream.pics(dream_pics)',
        'dream.endday(dream_endday)',
        'dream.creattime(dream_creattime)',
        'dream.video(dream_video)',
        'dream.thumb(dream_thumb)',
        'dream.status(dream_status)',
      ],[
        'AND'=>[
          // 'dream.userid'=>$userid,
          'dream.cateid'=>$cateid
        ],
        'ORDER'=>['dream.creattime'=>'DESC'],
        'LIMIT'=>[$row,10]
      ]);
      $data['ps']['count'] = $db->count('dream',[
        'AND'=>[
          //'userid'=>$userid,
          'cateid'=>$cateid
        ]
      ]);
      $data['ps']['allpages'] = ceil($data['ps']['count']/10);
      $data['ps']['thispage'] = $p;
      $json = array('flag' => 200,'msg' => '获取列表成功', 'data' => $data);
      return $response->withJson($json);
    }
  }

  public function dreamsTopic($request, $response, $args){
    global $db;
    $data = [];

      $data['list'] = $db->select('dream',[
        'dream.id(dream_id)',
        'dream.title(dream_title)',
        'dream.thumb(dream_thumb)',
        'dream.ad_pic(dream_ad_pic)',
      ],[
        'ORDER'=>['dream.views'=>'DESC'],
        'LIMIT'=>[0,5]
      ]);
      $json = array('flag' => 200,'msg' => '获取列表成功', 'data' => $data);
      return $response->withJson($json);
  }

  public function dreamCreat($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];
    $title = $request->getParsedBody()['title'];
    $content = $request->getParsedBody()['content'];
    $pics = $request->getParsedBody()['pics'];
    $video = $request->getParsedBody()['video'];
    $money = $request->getParsedBody()['money'];
    $endday = $request->getParsedBody()['endday'];
    $cateid = isset($request->getParsedBody()['cateid']) ? $request->getParsedBody()['cateid'] : 1 ;
    // $endday = strtotime("+1 month");
    $target_zhan = $money*10;
    $creat = $db->insert('dream',[
      'userid'=>$userid,
      'title'=>$title,
      'cateid'=>$cateid,
      'content'=>$content,
      'target_zhan'=>$target_zhan,
      'endday'=>$endday,
      'creattime'=>time(),
      'status'=>0,
      'pics'=>$pics,
      'video'=>$video,
      'money'=>$money,
    ]);

    $data['new_dream_id'] = $db->id();

    $json = array('flag' => 200,'msg' => 'Dream发布成功', 'data' => $data);
    return $response->withJson($json);
  }

  public function dreamsGetone($request, $response, $args){
    global $db;
    $data = [];
    
    $id = isset($request->getParsedBody()['dreamid']) ? $request->getParsedBody()['dreamid'] : false ;
    

    if($id){
      $data['detail'] = $db->get('dream',[
        '[>]member'=>['userid'=>'id'],
      ],[
        'member.id(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
        'member.company(user_company)',
        'member.status(user_status)',
        'dream.id(dream_id)',
        'dream.userid(dream_userid)',
        'dream.title(dream_title)',
        'dream.cateid(dream_cateid)',
        'dream.content(dream_content)',
        'dream.zhan(dream_zhan)',
        'dream.target_zhan(dream_target_zhan)',
        // 'dream.pics(dream_pics)',
        'dream.endday(dream_endday)',
        'dream.creattime(dream_creattime)',
        'dream.video(dream_video)',
        'dream.video_thumb(dream_video_thumb)',
        'dream.thumb(dream_thumb)',
        'dream.status(dream_status)',
      ],[
        'AND'=>[
          'dream.id'=>$id,
        ],
      ]);
      $data['detail']['ablum']=$db->select('dream_ablum',
        // 'id(pic_id)',
        'url',['dreamid'=>$id]);
      $json = array('flag' => 200,'msg' => '获取Dream成功', 'data' => $data);
      return $response->withJson($json);
    }else{
      $json = array('flag' => 400,'msg' => 'Dream不存在', 'data' => $data);
      return $response->withJson($json);
    }
  }

  public function dreamsGetoneZhan($request, $response, $args){
    global $db;
    $data = [];
    $p = isset($request->getParsedBody()['p']) ? $request->getParsedBody()['p'] : 1 ;
    $row = ($p * 100) - 100;

    $id = isset($request->getParsedBody()['dreamid']) ? $request->getParsedBody()['dreamid'] : false ;
    
    if($id){
      $data['list'] = $db->select('dream_zhan',[
        '[>]member'=>['userid'=>'id']
      ],[
        'member.id(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
        'member.status(user_status)',
        'dream_zhan.id(dream_zhan_id)',
        'dream_zhan.zhan(dream_zhan_zhan)',
        'dream_zhan.dreamid(dream_zhan_dreamid)',
        'dream_zhan.time(dream_zhan_time)',
      ],[
        'AND'=>[
          'dream_zhan.dreamid'=>$id,
        ],
        'ORDER'=>['dream_zhan.time'=>'DESC'],
        'LIMIT'=>[$row,100]
      ]);
      $json = array('flag' => 200,'msg' => '获取Dream点赞列表成功', 'data' => $data);
      return $response->withJson($json);
    }else{
      $json = array('flag' => 400,'msg' => 'Dream不存在', 'data' => $data);
      return $response->withJson($json);
    }
  }

  public function dreamsGetoneComments($request, $response, $args){
    global $db;
    $data = [];
    $p = isset($request->getParsedBody()['p']) ? $request->getParsedBody()['p'] : 1 ;
    $row = ($p * 50) - 50;

    $id = isset($request->getParsedBody()['dreamid']) ? $request->getParsedBody()['dreamid'] : false ;
    
    if($id){
      $data['list'] = $db->select('dream_comment',[
        '[>]member'=>['userid'=>'id']
      ],[
        'member.id(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
        'member.status(user_status)',
        'dream_comment.id(dream_comment_id)',
        'dream_comment.comment(dream_comment_comment)',
        'dream_comment.dreamid(dream_comment_dreamid)',
        'dream_comment.time(dream_comment_time)',
      ],[
        'AND'=>[
          'dream_comment.dreamid'=>$id,
        ],
        'ORDER'=>['dream_comment.time'=>'DESC'],
        'LIMIT'=>[$row,50]
      ]);
      $json = array('flag' => 200,'msg' => '获取Dream评论列表成功', 'data' => $data);
      return $response->withJson($json);
    }else{
      $json = array('flag' => 400,'msg' => 'Dream不存在', 'data' => $data);
      return $response->withJson($json);
    }
  }

  public function dreamGivetoZhan($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];
    $dreamid = $request->getParsedBody()['dreamid'];
    $zhan = $request->getParsedBody()['zhan'];
    $creat = $db->insert('dream_zhan',[
      'userid'=>$userid,
      'dreamid'=>$dreamid,
      'zhan'=>$zhan,
      'time'=>time(),
    ]);
    $data['new_zhan_id'] = $db->id();

    //更新到总赞
    $db->update('dream',[
      'zhan[+]'=>$zhan
    ],[
      'id'=>$dreamid
    ]);

    $json = array('flag' => 200,'msg' => '点赞成功', 'data' => $data);
    return $response->withJson($json);
  }

  public function dreamGivetoComment($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];
    $dreamid = $request->getParsedBody()['dreamid'];
    $comment = $request->getParsedBody()['comment'];
    $creat = $db->insert('dream_comment',[
      'userid'=>$userid,
      'dreamid'=>$dreamid,
      'comment'=>$comment,
      'time'=>time(),
    ]);
    $data['new_comment_id'] = $db->id();

    

    $json = array('flag' => 200,'msg' => '发表评论成功', 'data' => $data);
    return $response->withJson($json);
  }

  public function getUserHavezhans($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];
    $data['member_zhan'] = $db->get('member_can_zhan',[
      'zhans',
      'lastfreetime',
    ],[
      'userid'=>$userid
    ]);
    if(!$data['member_zhan']){
        $data['member_zhan']['zhans']=0;
        $data['member_zhan']['lastfreetime']=time();
        // $data['member_zhan']['ispop']=0;
        // $data['member_zhan']['ispoptime']=time();
       $db->insert('member_can_zhan',[
        'userid'=>$userid,
        'lastfreetime'=>mktime(0,0,0,date('m'),date('d')-1,date('Y')),
        'zhans'=>0,
        // 'ispop'=>0,
        // 'ispoptime'=>time()
      ]);
    }

    $db->update('member_can_zhan_pop',['canpop'=>1],['userid'=>$userid]);

    $json = array('flag' => 200,'msg' => '获取指定用户的可用赞数量成功', 'data' => $data);
    return $response->withJson($json);
  }

  public function userGetFreeZhan($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];
    $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
    $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
    //查询是否可领
    $can = $db->get('member_can_zhan','*',[
      'userid'=>$userid
    ]);

    if($can){
      if($can['lastfreetime'] >= $beginToday && $can['lastfreetime'] <= $endToday){
        $json = array('flag' => 400,'msg' => '今日已领过了，不能重复领取', 'data' => $data);
        return $response->withJson($json);
      }else{
        $db->update('member_can_zhan',[
          'zhans[+]'=>10,
          'lastfreetime'=>time()
        ],[
        'userid'=>$userid
        ]);
        //logo
        $db->insert('member_can_zhan_log',[
          'userid'=>$userid,
          'zhans'=>10,
          'time'=>time()
        ]);

        $data['zhans'] = $db->get('member_can_zhan','zhans',[
          'userid'=>$userid
        ]);

        $json = array('flag' => 200,'msg' => '领取免费点赞机会成功', 'data' => $data);
        return $response->withJson($json);
      }
    }else{
      $db->insert('member_can_zhan',[
          'zhans'=>10,
          'lastfreetime'=>time(),
          'userid'=>$userid
      ]);
      $data['zhans'] = 10;
      $json = array('flag' => 200,'msg' => '领取免费点赞机会成功', 'data' => $data);
      return $response->withJson($json);
    }
  }

  public function userZhanPop($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];
    
    $data['member_zhan_pop'] = $db->get('member_can_zhan_pop',[
        'canpop',
        'time'
    ],[
      'userid'=>$userid
    ]);
    if($data['member_zhan_pop'] == false){
      //如果记录不存在，则创建
      $db->insert('member_can_zhan_pop',[
        'canpop'=>0,
        'userid'=>$userid,
        'time'=>time()
      ]);
      $data['member_zhan_pop']['time'] = time();
      $data['member_zhan_pop']['canpop'] = 0;
    }else{
      //如果数据存在，则对比
      //比较最后一次时间是否当天
      $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
      $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
      if($data['member_zhan_pop']['time'] >= $beginToday && $data['member_zhan_pop']['time'] <= $endToday){
        //是今天则不弹
        $data['member_zhan_pop']['canpop'] = 1;
        $data['member_zhan_pop']['time'] = time();

      }else{
        $data['member_zhan_pop']['canpop'] = 0;
        $data['member_zhan_pop']['time'] = time();
      }
      //更新接口当时时间
      $db->update('member_can_zhan_pop',[
        'time'=>time()
      ],[
        'userid'=>$userid
      ]);
    }
    
    $json = array('flag' => 200,'msg' => '获取指定用户弹窗状态', 'data' => $data);
    return $response->withJson($json);
  }
  //朋友圈

  public function zone($request, $response, $args){
    global $db;
    $data = [];
    $p = isset($request->getParsedBody()['p']) ? $request->getParsedBody()['p'] : 1 ;
    $userid = isset($request->getParsedBody()['userid']) ? $request->getParsedBody()['userid'] : 0 ;
    $row = ($p * 10) - 10;

    if($userid!=0){
      $list = $db->select('mcms_quan',[
        '[>]member'=>['author'=>'id']
      ],[
        'mcms_quan.id',
        'mcms_quan.content',
        'mcms_quan.creatTime',
        'mcms_quan.views',
        'mcms_quan.author',
        'mcms_quan.pics',
        'mcms_quan.video',
        'mcms_quan.zhan',
        'mcms_quan.comments',
        'member.id(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
      ],[
        'mcms_quan.author'=>$userid,
        'ORDER'=>['mcms_quan.id'=>'DESC'],
        'LIMIT'=>[$row,10]
      ]);
      $data['ps']['count'] = $db->count('mcms_quan',['author'=>$userid
      ]);
      $data['ps']['allpages'] = ceil($data['ps']['count']/10);
      $data['ps']['thispage'] = $p;
    }else{
      $list = $db->select('mcms_quan',[
        '[>]member'=>['author'=>'id']
      ],[
        'mcms_quan.id',
        'mcms_quan.content',
        'mcms_quan.creatTime',
        'mcms_quan.views',
        'mcms_quan.author',
        'mcms_quan.pics',
        'mcms_quan.video',
        'mcms_quan.zhan',
        'mcms_quan.comments',
        'member.id(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
      ],[
        'ORDER'=>['mcms_quan.id'=>'DESC'],
        'LIMIT'=>[$row,10]
      ]);
      $data['ps']['count'] = $db->count('mcms_quan');
      $data['ps']['allpages'] = ceil($data['ps']['count']/10);
      $data['ps']['thispage'] = $p;
    }
    

    if(count($list) == 0){
      $data['list'] = [];
      $json = array('flag' => 400,'msg' => '没有更多了', 'data' => $data);
      return $response->withJson($json);
    }
    
    foreach ($list as $key => $value) {
      $data['list'][$key] = $value;
      $data['list'][$key]['album'] = [];
      $a =  $db->select('mcms_attachment',[
        'uri',
        'thumbnail_640',
        'thumbnail'
      ],[
        'id'=>json_decode($value['pics'])
      ]);

      foreach ($a as $key2 => $value2) {
        $data['list'][$key]['album'][$key2]['thumbnail_320'] = 'http://assets.cw2009.com/'.$value2['thumbnail'];
        $data['list'][$key]['album'][$key2]['thumbnail_640'] = 'http://assets.cw2009.com/'.$value2['thumbnail_640'];
        $data['list'][$key]['album'][$key2]['source_url'] = 'http://assets.cw2009.com/'.$value2['uri'];
        $g = getimagesize('http://assets.cw2009.com/'.$value2['uri']);
        $data['list'][$key]['album'][$key2]['source_width'] = $g[0];
        $data['list'][$key]['album'][$key2]['source_height'] = $g[1];
      }
      
      // $data['list'][$key]['user'] = $db->select('member',[
      //   'member.id(user_id)',
      //   'member.name(user_name)',
      //   'member.avatar(user_avatar)',
      // ],[
      //   'id'=>$value['author']
      // ]);

      $data['list'][$key]['zhans'] = $db->select('mcms_zhan',[
        '[>]member'=>['uid'=>'id']
      ],[
        'mcms_zhan.uid(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
      ],[
        'targetId'=>$value['id']
      ]);
      $data['list'][$key]['commentslist'] = $db->select('mcms_comments',[
        '[>]member'=>['uid'=>'id']
      ],[
        'mcms_comments.uid(user_id)',
        'mcms_comments.comment(comment_content)',
        'mcms_comments.creattime(comment_time)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
      ],[
        'AND'=>[
          'mcms_comments.targetId'=>$value['id'],
          'mcms_comments.ctype'=>1
        ]
      ]);
    }

     //$data['list'] = $list;

    $json = array('flag' => 200,'msg' => '获取成功', 'data' => $data);
    return $response->withJson($json);
  }

  public function zoneGetone($request, $response, $args){
    global $db;
    $data = [];
    $zoneid = $request->getParsedBody()['zoneid'];
    
    $data['getone'] = $db->get('mcms_quan',[
        '[>]member'=>['author'=>'id']
      ],[
        'mcms_quan.id',
        'mcms_quan.content',
        'mcms_quan.creatTime',
        'mcms_quan.views',
        'mcms_quan.author',
        'mcms_quan.pics',
        'mcms_quan.video',
        'mcms_quan.zhan',
        'mcms_quan.comments',
        'member.id(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
      ],[
        'mcms_quan.id'=>$zoneid
      ]);

      $a =  $db->select('mcms_attachment',[
        'uri',
        'thumbnail_640',
        'thumbnail',
        'w',
        'h'
      ],[
        'id'=>json_decode($data['getone']['pics'])
      ]);

      foreach ($a as $key2 => $value2) {
        $data['getone']['album'][$key2]['thumbnail_320'] = 'http://assets.cw2009.com/'.$value2['thumbnail'];
        $data['getone']['album'][$key2]['thumbnail_640'] = 'http://assets.cw2009.com/'.$value2['thumbnail_640'];
        $data['getone']['album'][$key2]['source_url'] = 'http://assets.cw2009.com/'.$value2['uri'];
        
        if($value2['w'] == null){
          $g = getimagesize($data['getone']['album'][$key2]['source_url']);
          $data['getone']['album'][$key2]['source_width'] = $g[0];
          $data['getone']['album'][$key2]['source_height'] = $g[1];
        }else{
          $data['getone']['album'][$key2]['source_width'] = $value2['w'];
          $data['getone']['album'][$key2]['source_height'] = $value2['h'];
        }
  
      }

      // $data['getone']['user'] = $db->select('member',[
      //   'member.id(user_id)',
      //   'member.name(user_name)',
      //   'member.avatar(user_avatar)',
      // ],[
      //   'id'=>$data['getone']['author']
      // ]);
      

      $data['getone']['zhans'] = $db->select('mcms_zhan',[
        '[>]member'=>['uid'=>'id']
      ],[
        'mcms_zhan.uid(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
      ],[
        'targetId'=>$data['getone']['id']
      ]);
      $data['getone']['commentslist'] = $db->select('mcms_comments',[
        '[>]member'=>['uid'=>'id']
      ],[
        'mcms_comments.uid(user_id)',
        'mcms_comments.comment(comment_content)',
        'mcms_comments.creattime(comment_time)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
      ],[
        'AND'=>[
          'mcms_comments.targetId'=>$data['getone']['id'],
          'mcms_comments.ctype'=>1
        ]
      ]);


      //更新占赞评论的阅读状态
      $db->update('mcms_zhan',[
        'iamread'=>0,
        ],[
          'targetId'=>$zoneid
      ]);

      $db->select('mcms_comments',[
          'iamread'=>0,
        ],[
          'targetId'=>$zoneid
      ]);

    $json = array('flag' => 200,'msg' => '', 'data' => $data);
    return $response->withJson($json);
  }

  public function zoneNewMsg($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];

    $data['zhans'] = $db->select('mcms_zhan',[
        '[>]member'=>['uid'=>'id']
      ],[
        'mcms_zhan.targetId(target_id)',
        'mcms_zhan.uid(user_id)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
        'mcms_zhan.creattime(time)'
      ],[
        'AND'=>[
          'mcms_zhan.forUid'=>$userid,
          'mcms_zhan.iamread'=>1
        ]
    ]);

    $data['mcms_comments'] = $db->select('mcms_comments',[
        '[>]member'=>['uid'=>'id']
      ],[
        'mcms_comments.uid(user_id)',
        'mcms_comments.targetId(target_id)',
        'mcms_comments.comment(comment_content)',
        'mcms_comments.creattime(comment_time)',
        'member.name(user_name)',
        'member.avatar(user_avatar)',
      ],[
        'AND'=>[
          'mcms_comments.forUid'=>$userid,
          'mcms_comments.iamread'=>1
        ]
      ]);

      // $db->update('mcms_zhan',[
      //     'iamread'=>0,
      //   ],[
      //     'AND'=>[
      //       'forUid'=>$userid,
      //       'iamread'=>1
      //     ]
      // ]);

      // $db->update('mcms_comments',[
      //     'iamread'=>0,
      //   ],[
      //     'AND'=>[
      //       'forUid'=>$userid,
      //       'iamread'=>1
      //     ]
      // ]);

    $json = array('flag' => 200,'msg' => '获取未阅读的点赞消息和评论消息成功', 'data' => $data);
    return $response->withJson($json);
  }

  public function zoneHaveRead($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];

    $db->update('mcms_zhan',[
        'iamread'=>0,
      ],[
        'AND'=>[
          'forUid'=>$userid,
          'iamread'=>1
        ]
    ]);

    $db->select('mcms_comments',[
        'iamread'=>0,
      ],[
        'AND'=>[
          'forUid'=>$userid,
          'iamread'=>1
        ]
    ]);


    $json = array('flag' => 200,'msg' => '已上报阅读状态', 'data' => $data);
    return $response->withJson($json);
  }

  public function zoneGiveZhan($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];

    $json = array('flag' => 200,'msg' => '', 'data' => $data);
    return $response->withJson($json);
  }

  public function zoneCreat($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];

    $json = array('flag' => 200,'msg' => '', 'data' => $data);
    return $response->withJson($json);
  }

  public function zonePostComment($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];

    $json = array('flag' => 200,'msg' => '', 'data' => $data);
    return $response->withJson($json);
  }

  //ebook

  public function ebookContrast($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];

    $data['list'] = array(
      array(
        'user_name'=>'周静',
        'user_mobile'=>13800000000,
        'user_used_app'=>0
      ),
      array(
        'user_name'=>'王静',
        'user_mobile'=>13800000000,
        'user_used_app'=>1
      ),
      array(
        'user_name'=>'李静',
        'user_mobile'=>13800000000,
        'user_used_app'=>1
      ),
      array(
        'user_name'=>'月静',
        'user_mobile'=>13800000000,
        'user_used_app'=>0
      )
    );

    $json = array('flag' => 200,'msg' => '', 'data' => $data);
    return $response->withJson($json);
  }

  public function userGPS($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];

    $json = array('flag' => 200,'msg' => '', 'data' => $data);
    return $response->withJson($json);
  }

  public function userNearby($request, $response, $args){
    global $db;
    $data = [];
    $userid = $request->getParsedBody()['userid'];
    
    $data['list'] = array(
      array(
        'user_id'=>1,
        'user_name'=>'周静',
        'user_zone_url'=>'http://app.cw2009.com/s/52.html',
        'user_mobile'=>13800000000,
        'user_avatar'=>'http://assets.cw2009.com/u/20171201/thumb_1512161390.jpeg',
        'gps'=>array('lat'=>116.550872,'lng'=>39.970042)
      ),
      array(
        'user_id'=>2,
        'user_name'=>'王静',
        'user_zone_url'=>'http://app.cw2009.com/s/52.html',
        'user_mobile'=>13800000000,
        'user_avatar'=>'http://assets.cw2009.com/u/20171201/thumb_1512161390.jpeg',
        'gps'=>array('lat'=>116.550972,'lng'=>39.970242)
      ),
      array(
        'user_id'=>3,
        'user_name'=>'李静',
        'user_zone_url'=>'http://app.cw2009.com/s/52.html',
        'user_mobile'=>13800000000,
        'user_avatar'=>'http://assets.cw2009.com/u/20171201/thumb_1512161390.jpeg',
        'gps'=>array('lat'=>116.550978,'lng'=>39.973042)
      ),
      array(
        'user_id'=>4,
        'user_name'=>'月静',
        'user_zone_url'=>'http://app.cw2009.com/s/52.html',
        'user_mobile'=>13800000000,
        'user_avatar'=>'http://assets.cw2009.com/u/20171201/thumb_1512161390.jpeg',
        'gps'=>array('lat'=>116.550942,'lng'=>39.970042)
      )
      );

    $json = array('flag' => 200,'msg' => '', 'data' => $data);
    return $response->withJson($json);
  }

  //上传

  public function uploadPic($request, $response, $args){
      global $flag,$msg,$data,$db;
      $filename = strtotime(date('Y-m-d H:i:s x'));
      $dis = date('Ymd');
      $handle = new upload($_FILES['files']);
      if($handle->image_src_type == 'png' || $handle->image_src_type == 'jpg' || $handle->image_src_type == 'jpeg' || $handle->image_src_type == 'gif'){
          
          if($handle->uploaded) {
            $handle->file_new_name_body   = $filename;
            $handle->image_resize         = false;
            $handle->process('/var/www/assets/u/'.$dis);
            if($handle->processed){
              $name = $handle->file_dst_name;
              $an = 'http://assets.cw2009.com/u/'.$dis.'/'.$handle->file_dst_name;
              $data['source_url'] = $an;
              $data['source_width'] = $handle->image_dst_x;
              $data['source_height'] = $handle->image_dst_y;
            }
          }
          if($handle->uploaded) {
            $handle->file_new_name_body   = $filename;
            $handle->file_name_body_pre = 'thumb_';
            $handle->image_resize         = true;
            $handle->image_x              = 320;
            $handle->image_y              = 320;
            $handle->image_ratio_fill     = true;
            $handle->image_ratio_crop     = true;
            $handle->process('/var/www/assets/u/'.$dis);
            if($handle->processed){
              $bn = 'http://assets.cw2009.com/u/'.$dis.'/'.$handle->file_dst_name;
              $data['thumbnail_320'] = $bn;
            }
          }
          if($handle->uploaded) {
            $handle->file_new_name_body   = $filename;
            $handle->file_name_body_pre = '640_thumb_';
            $handle->image_resize         = true;
            $handle->image_x              = 640;
            $handle->image_y              = 640;
            $handle->image_ratio_fill     = true;
            $handle->process('/var/www/assets/u/'.$dis);
            if($handle->processed){
              $cn = 'http://assets.cw2009.com/u/'.$dis.'/'.$handle->file_dst_name;
              $data['thumbnail_640'] = $cn;
              $handle->clean();
            }
          }
          //写入数据库
          $a = $db->insert('mcms_attachment',[
              'name'=>$name,
              'fromID'=>$_COOKIE['staffID'],
              'type'=>0,
              'uri'=>$an,
              'thumbnail'=>$bn,
              'thumbnail_640'=>$cn,
              'creatTime'=>date('Y-m-d H:i:s')
            ]);
          $flag = 200;
          $data['id'] = $db->id();
          $msg = '已上传并生成缩略图';

        }else{
            $flag = 400;
            $msg = '错误: 非图片文件';
        }

      $json = array('flag' => $flag,'msg' => $msg, 'data' => $data);
      return $response->withJson($json);
  }

  public function uploadPics($request, $response, $args){
      global $flag,$msg,$data,$db;
      $filename = strtotime(date('Y-m-d H:i:s x')).rand(1000,9999);
      $dis = date('Ymd');
      $data = [];
      // $i == 0;
      foreach ($_FILES['files']['name'] as $k=>$file) {
        //$foo = new upload($file);
        $s = [
          'name'=>$file,
          'tmp_name'=>$_FILES['files']['tmp_name'][$k],
          'type'=>$_FILES['files']['type'][$k],
          'error'=>$_FILES['files']['error'][$k],
          'size'=>$_FILES['files']['size'][$k],
        ];

        $foo = new upload($s);
        if($foo->image_src_type == 'image/png' || $foo->image_src_type == 'png' || $foo->image_src_type == 'jpg' || $foo->image_src_type == 'jpeg' || $foo->image_src_type == 'gif'){
            
            if($foo->uploaded) {
              $foo->file_new_name_body   = $filename;
              $foo->image_resize         = false;
              $foo->process('/var/www/assets/u/'.$dis);
              if($foo->processed){
                $name = $foo->file_dst_name;
                $an = 'http://assets.cw2009.com/u/'.$dis.'/'.$foo->file_dst_name;
                $data['f'][$k]['source_url'] = $an;
                $data['f'][$k]['source_width'] = $foo->image_dst_x;
                $data['f'][$k]['source_height'] = $foo->image_dst_y;
              }
            }
            if($foo->uploaded) {
              $foo->file_new_name_body   = $filename;
              $foo->file_name_body_pre = 'thumb_';
              $foo->image_resize         = true;
              $foo->image_x              = 320;
              $foo->image_y              = 320;
              $foo->image_ratio_fill     = true;
              $foo->image_ratio_crop     = true;
              $foo->process('/var/www/assets/u/'.$dis);
              if($foo->processed){
                $bn = 'http://assets.cw2009.com/u/'.$dis.'/'.$foo->file_dst_name;
                $data['f'][$k]['thumbnail_320'] = $bn;
              }
            }
            if($foo->uploaded) {
              $foo->file_new_name_body   = $filename;
              $foo->file_name_body_pre = '640_thumb_';
              $foo->image_resize         = true;
              $foo->image_x              = 640;
              $foo->image_y              = 640;
              $foo->image_ratio_fill     = true;
              $foo->process('/var/www/assets/u/'.$dis);
              if($foo->processed){
                $cn = 'http://assets.cw2009.com/u/'.$dis.'/'.$foo->file_dst_name;
                $data['f'][$k]['thumbnail_640'] = $cn;

                
              }
            }

            $foo->clean();
            //写入数据库
            $db->insert('mcms_attachment',[
                'name'=>$name,
                'fromID'=>$_COOKIE['staffID'],
                'type'=>0,
                'uri'=>$an,
                'thumbnail'=>$bn,
                'thumbnail_640'=>$cn,
                'w'=>$data['f'][$k]['source_width'],
                'h'=>$data['f'][$k]['source_height'],
                'creatTime'=>date('Y-m-d H:i:s')
              ]);
            $data['f'][$k]['id'] = $db->id();
            $flag = 200;
            // $data['uri'] = $db->id();
            $msg = '已上传并生成缩略图';
        }else{
            $flag = 400;
            $msg = '错误: 非图片文件';
        }
        
      }

      //$data['s'] = $_FILES;

      // $data['f'] = $foo;

      $json = array('flag' => $flag,'msg' => $msg, 'data' => $data);
      return $response->withJson($json);
  }

  public function uploadVideo($request, $response, $args){
      global $flag,$msg,$data,$db;
      $filename = strtotime(date('Y-m-d H:i:s x'));
      $dis = date('Ymd');
      $f = $_FILES['video'];
      $ft = $f['type'];

      if($ft == 'video/mp4'){
         move_uploaded_file($_FILES['video']['tmp_name'], '/var/www/assets/u/'.$dis.'/'.$filename.'.mp4');
          $a = $db->insert('mcms_attachment',[
                'name'=>$filename,
                'fromID'=>$_COOKIE['staffID'],
                'type'=>'mp4',
                'uri'=>'u/'.$dis.'/'.$filename.'.mp4',
                'creatTime'=>date('Y-m-d H:i:s')
            ]);
            $flag = 200;
            $data['id'] = $db->id();
            $data['source_url'] = 'http://assets.cw2009.com/u/'.$dis.'/'.$filename.'.mp4';
            $data['thumbnai'] = 'http://app.cw2009.com/nopic.png';
            $data['source_width'] = 640;
            $data['source_height'] = 480;
            $msg = '上传已成功';
       }else{
          $flag = 400;
          $msg = '上传失败，不允许上传该格式文件。';
       }
      // $data['f'] = $f;
      $json = array('flag' => $flag,'msg' => $msg, 'data' => $data);
      return $response->withJson($json);
  }

  public function getDynamics($request, $response, $args){
    global $db;
    $data = [];
    $p = isset($request->getParsedBody()['p']) ? $request->getParsedBody()['p'] : 1 ;
    $type = isset($request->getParsedBody()['type']) ? $request->getParsedBody()['type'] : 1 ;
    $userid = isset($request->getParsedBody()['userid']) ? $request->getParsedBody()['userid'] : 0 ;

    if($userid == 0){
      $data['list'] = [];
      $json = array('flag' => 400,'msg' => '没有找到更多', 'data' => $data);
      return $response->withJson($json);
    }
    $row = ($p * 10) - 10;

    $list = $db->select('member',[
      'id(user_id)',
      'name(user_name)',
      'avatar(user_avatar)',
      'company(user_company)',
    ],[
      'LIMIT'=>[$row,10]
    ]);
    if(count($list)==0){
      $data['list'] = [];
      $json = array('flag' => 400,'msg' => '没有找到更多', 'data' => $data);
      return $response->withJson($json);
    }
    foreach ($list as $key => $value) {
      $data['list'][$key]['user_id']=$value['user_id'];
      $data['list'][$key]['user_name']=$value['user_name'];
      $data['list'][$key]['user_avatar']=$value['user_avatar'];
      $data['list'][$key]['user_company']=$value['user_company'];
      $data['list'][$key]['time']=time();
    }

    $data['ps']['count'] = $db->count('member');
    $data['ps']['allpages'] = ceil($data['ps']['count']/10);
    $data['ps']['thispage'] = $p;

    $json = array('flag' => 200,'msg' => '获取成功', 'data' => $data);
    return $response->withJson($json);
  }

  public function alipay($request, $response, $args){
  }

  public function alipayReback($request, $response, $args){
  }
  
}
