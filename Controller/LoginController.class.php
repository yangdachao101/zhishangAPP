<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class LoginController 
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
      $u = $request->getAttribute('u');
      // if($u['id']){
      //   return $response->withRedirect('/ucenter.html');
      // }
      $as = [];
      return $this->app->renderer->render($response, './login.php', $as);
    }

    public function loginu($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');
      // if($u['id']){
      //   return $response->withRedirect('/ucenter.html');
      // }
      if(isset($_GET['openid']) && $_GET['openid']!=''){
        setcookie("openid", $_GET['openid'], mktime()+31104000,'/');
      }
      $as = [];
      return $this->app->renderer->render($response, './login_user.php', $as);
    }

    public function logins($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');
      if($s['id']){
        return $response->withRedirect('/scenter.html');
      }
      $as = [];
      return $this->app->renderer->render($response, './login_staff.php', $as);
    }



    public function loginassms($request, $response, $args){
      $as = [];
      return $this->app->renderer->render($response, './loginassms.php', $as);
    }

    public function loginassmspost($request, $response, $args){
      global $db;
      //先验证手机号与短信

      $ntime = time()-1800;
      $has = $db->get('sms_vcode',['mobile'],[
        'AND'=>[
          'mobile'=>$_POST['mobile'],
          'code'=>$_POST['smscode'],
          'time[>=]'=>$ntime
        ]
      ]);

      if($has){
        //客户
        $u = $db->get('customs',['id','mobile','name'],[
            'mobile'=>$_POST['mobile']
        ]);
        
        $s = $db->get('member',['id','mobile','name'],[
            'mobile'=>$_POST['mobile']
        ]);
        
        if($u){
          setcookie("umobile", $u['mobile'], mktime()+31104000,'/');
          setcookie("uid", $u['id'], mktime()+31104000,'/');
        }else{
          //注册
          $db->insert('customs',[
            'mobile'=>$_POST['mobile'],
            "creattime" => date('Y-m-d H:i:s'),
            'from'=>1
          ]);
          $nu = $db->get('customs',['id','mobile'],[
            'id'=>$db->id()
          ]);
          setcookie("umobile", $nu['mobile'], mktime()+31104000,'/');
          setcookie("uid", $nu['id'], mktime()+31104000,'/');
        }

        if($s){
         
          $db->insert('mcms_quan',[
              'title'=>'登录',
              'cateId'=>3,
              'author'=>$s['id'],
              'content'=>'成功登录微信公众平台',
              'creatTime'=>date('Y-m-d H:i:s'),
              'tags'=>'活跃,',
              'name'=>$s['name']
            ]);
          $db->update('member',[
            'jf[+]'=>1
          ],[
            'id'=>$s['id']
          ]);
          setcookie("smobile", $s['mobile'], mktime()+31104000,'/');
          setcookie("staffID", $s['id'], mktime()+31104000,'/');
        }else{
          //注册
          $db->insert("member", [
            "name" => $name,
            "mobile" => $_POST['mobile'],
            "creattime" => date('Y-m-d H:i:s'),
            'status'=>5
          ]); 
          $nid = $db->id();

          $nm = $db->get('member',['id','name','mobile'],[
            'id'=>$nid
          ]);

          $db->insert('mcms_quan',[
              'title'=>'登录',
              'cateId'=>3,
              'author'=>$nm['id'],
              'content'=>'成功登录微信公众平台',
              'creatTime'=>date('Y-m-d H:i:s'),
              'tags'=>'活跃,',
              'name'=>$nm['name']
            ]);
          $db->update('member',[
            'jf[+]'=>1
          ],[
            'id'=>$nm['id']
          ]);

          $db->insert('wallets',[
            'uid'=>$nid,
            'utype'=>1,
          ]);
          setcookie("smobile", $nm['mobile'], mktime()+31104000,'/');
          setcookie("staffID", $nm['id'], mktime()+31104000,'/');

        }

        //服务者
        $json = array('flag' => 200,'msg' => '注册并登录成功', 'data' => []);
      }else{
        $json = array('flag' => 400,'msg' => '验证码错误或已过期', 'data' => []);
      }

      
          return $response->withJson($json);

    }

    public function regu($request, $response, $args){
      global $db;
      //wechat api call back url;
      $uri = urlencode('http://m.cw2009.com/getcodeu.html');

      if(!isset($_COOKIE['openid']) || $_COOKIE['openid']==''){
        
        return $response->withRedirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx9f2d1785175d240a&redirect_uri='.$uri.'&response_type=code&scope=snsapi_userinfo&state=001#wechat_redirect');
      }

      $qrvlog = $db->get('member_vcode_log','*',[
        'openID'=>$_COOKIE['openid'],
        'ORDER'=>['apitime'=>'DESC']
      ]);

      if($qrvlog){
        $vcode = $qrvlog['vcode'];
      }else{
        $vcode = '';
      }
      
      $as = [
        'vcode'=>$vcode
      ];
      return $this->app->renderer->render($response, './regu.php', $as);
    }

    public function regs($request, $response, $args){
      global $db;
      $uri = urlencode('http://m.cw2009.com/getcodes.html');

      if(!isset($_COOKIE['openid']) || $_COOKIE['openid']==''){
        
        return $response->withRedirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx9f2d1785175d240a&redirect_uri='.$uri.'&response_type=code&scope=snsapi_userinfo&state=002#wechat_redirect');
      }
      $qrvlog = $db->get('member_vcode_log','*',[
        'openID'=>$_COOKIE['openid'],
        'ORDER'=>['apitime'=>'DESC']
      ]);
      if($qrvlog){
        $vcode = $qrvlog['vcode'];
      }else{
        $vcode = '';
      }
      
      $as = [
        'vcode'=>$vcode
      ];
      return $this->app->renderer->render($response, './regs.php', $as);
    }

    public function saveu($request, $response, $args){
      global $db;
      if(isset($_POST['mobile']) && $_POST['mobile']!=''){
        //是否已注册
        $has = $db->get('customs',['mobile'],['mobile'=>$_POST['mobile']]);
        if($has){
          $json = array('flag' => 400,'msg' => '您已注册，请不要重复登录。', 'data' => []);
          return $response->withJson($json);
          exit();
        }
        //判断验证码有效性
        $ldate = strtotime(date('Y-m-d H:i:s'));
        $v = $db->get('sms_vcode',['mobile','code','creattime'],[
          'AND'=>[
            'mobile'=>$_POST['mobile'],
            'code'=>$_POST['smscode'],
          ]
          ]);
        if(!$v){
          $json = array('flag' => 400,'msg' => '验证码错误', 'data' => []);
          return $response->withJson($json);
          exit();
        }
        $vdate = $ldate - strtotime($v['creattime']);
        if($vdate > 1800){
          $json = array('flag' => 400,'msg' => '验证码已过期', 'data' => []);
          return $response->withJson($json);
          exit();
        }
        if(isset($_POST['name']) && $_POST['name']!=''){
          $name = $_POST['name'];
        }else{
          $name = 'u'.$_POST['mobile'];
        }

        if(isset($_POST['vcode']) && $_POST['vcode']!=''){
          $vcode = $_POST['vcode'];
        }else{
          $vcode = NULL;
        }

        if(isset($_POST['openID']) && $_POST['openID']!=''){
          $openID = $_POST['openID'];
        }else{
          $openID = NULL;
        }

        if(isset($_POST['avatar']) && $_POST['avatar']!=''){
          $avatar = $_POST['avatar'];
        }else{
          $avatar = NULL;
        }

        $reg = $db->insert("customs", [
          "name" => $name,
          "mobile" => $_POST['mobile'],
          "password" => $_POST['password'],
          "creattime" => date('Y-m-d H:i:s'),
          "vcode" =>$vcode,
          "avatar" =>$avatar,
          "openID" =>$openID,
          'from'=>1
        ]);
        if($reg){

          setcookie("umobile", $v['mobile'], mktime()+31104000,'/');
          setcookie("uid", $db->id(), mktime()+31104000,'/');
          
          //写入三级分销关系树
          if(isset($_POST['vcode']) && $_POST['vcode']!=''){
            $vcode = $_POST['vcode'];
          }else{
            $vcode = NULL;
          }

          $v = $db->get('member_vcode','*',['uId' => NULL]);
          $db->update('member_vcode',[
            'uId'=>$db->id(),
            'pid'=>$vcode,
            'creattime'=>mktime(),
            'creatDate'=>date('Y-m-d H:i:s'),
            'type'=>2
          ],[
            'id'=>$v['id']
          ]);

          $json = array('flag' => 200,'msg' => '注册成功', 'data' => []);
          return $response->withJson($json);
        }
        
      }else{
        $json = array('flag' => 400,'msg' => '手机号码不能为空', 'data' => []);
        return $response->withJson($json);
      }
    }

    public function saves($request, $response, $args){
      global $db;
      if(isset($_POST['mobile']) && $_POST['mobile']!=''){
        //是否已注册
        $has = $db->get('member',['mobile'],['mobile'=>$_POST['mobile']]);
        if($has){
          $json = array('flag' => 400,'msg' => '您已注册，请不要重复登录。', 'data' => []);
          return $response->withJson($json);
          exit();
        }
        //判断验证码有效性
        $ldate = strtotime(date('Y-m-d H:i:s'));
        $v = $db->get('sms_vcode',['mobile','code','creattime'],[
          'AND'=>[
            'mobile'=>$_POST['mobile'],
            'code'=>$_POST['smscode'],
          ]
          ]);
        if(!$v){
          $json = array('flag' => 400,'msg' => '验证码错误', 'data' => []);
          return $response->withJson($json);
          exit();
        }
        $vdate = $ldate - strtotime($v['creattime']);
        if($vdate > 1800){
          $json = array('flag' => 400,'msg' => '验证码已过期', 'data' => []);
          return $response->withJson($json);
          exit();
        }
        if(isset($_POST['name']) && $_POST['name']!=''){
          $name = $_POST['name'];
        }else{
          $name = 'u'.$_POST['mobile'];
        }

        if(isset($_POST['vcode']) && $_POST['vcode']!=''){
          $vcode = $_POST['vcode'];
        }else{
          $vcode = NULL;
        }

        if(isset($_POST['openID']) && $_POST['openID']!=''){
          $openID = $_POST['openID'];
        }else{
          $openID = NULL;
        }

        $reg = $db->insert("member", [
          "name" => $name,
          "mobile" => $_POST['mobile'],
          "password" => $_POST['password'],
          "creattime" => date('Y-m-d H:i:s'),
          "openID" =>$openID,
          'status'=>4
        ]);
        if($reg){
          $staffid = $db->id();
          setcookie("smobile", $v['mobile'], mktime()+31104000,'/');
          setcookie("staffID", $staffid, mktime()+31104000,'/');
          //写入赠送商机点
          $s = $db->get('sd_config','*',['id'=>1]);
          $pn = date("Y-m-d",strtotime("+ ".$s['term']." day"));
          $db->insert('wallets',[
            'uid'=>$staffid,
            'utype'=>1,
            'balance'=>0,
            'availableBalance'=>0,
            'unavailableBalance'=>0,
            'bzj'=>0,
            'poin'=>$s['sd'],
            'pointerm'=>$pn
          ]);
          //写入商点操作记录
          $db->insert('member_poin',[
            'staffId'=>$staffid,
            'type'=>2,
            'creattime'=>date('Y-m-d H:i:s'),
            'remark'=>'注册成功，赠送商点',
            'money'=>$s['sd']
          ]);


          //写入三级分销关系树
          if(isset($_POST['vcode']) && $_POST['vcode']!=''){
            $vcode = $_POST['vcode'];
          }else{
            $vcode = NULL;
          }

          $v = $db->get('member_vcode','*',['uId' => NULL]);
          
          $db->update('member_vcode',[
            'uId'=>$staffid,
            'pid'=>$vcode,
            'creattime'=>mktime(),
            'creatDate'=>date('Y-m-d H:i:s'),
            'type'=>1
          ],[
            'id'=>$v['id']
          ]);

          $json = array('flag' => 200,
            'msg' => '服务者注册已成功提交，注册信息需人工审核，请耐心等待', 
            'data' => []);
          return $response->withJson($json);
        }
        
      }else{
        $json = array('flag' => 400,'msg' => '手机号码不能为空', 'data' => []);
        return $response->withJson($json);
      }
    }



    
    public function ulogin($request, $response, $args){
      global $db;
      
      $flag = 200;
      $msg = '';


      $u = $db->get('customs',['id','mobile','password'],[
        'AND'=>[
          'mobile'=>$_POST['umobile'],
          'password'=>$_POST['upassword'],
        ]
      ]);

      $s = $db->get('member',['id','name','mobile','password'],[
        'AND'=>[
          'mobile'=>$_POST['umobile'],
          'password'=>$_POST['upassword'],
        ]
      ]);

      if($u){
        setcookie("umobile", $u['mobile'], mktime()+31104000,'/');
        setcookie("uid", $u['id'], mktime()+31104000,'/');
      }

      if($s){
        $db->insert('mcms_quan',[
            'title'=>'登录',
            'cateId'=>3,
            'author'=>$s['id'],
            'content'=>'成功登录微信公众平台',
            'creatTime'=>date('Y-m-d H:i:s'),
            'tags'=>'活跃,',
            'name'=>$s['name']
          ]);
        $db->update('member',[
          'jf[+]'=>1
        ],[
          'id'=>$s['id']
        ]);
        setcookie("smobile", $s['mobile'], mktime()+31104000,'/');
        setcookie("staffID", $s['id'], mktime()+31104000,'/');
      }

      if(!$u && !$s){
        $flag = 405;
        $msg = '登录失败，用户名和密码不匹配。';
      }else{
        $flag = 200;
        $msg = '登录成功。';
      }

      $json = array('flag' => $flag,'msg' => $msg, 'data' => []);
       return $response->withJson($json);


    }
    
    public function slogin($request, $response, $args){
      global $db;
      $v = $db->get('member',['id','name','mobile','password'],[
        'AND'=>[
          'mobile'=>$_POST['smobile'],
          'password'=>$_POST['spassword'],
        ]
      ]);
      if($v){
        $db->insert('mcms_quan',[
            'title'=>'登录',
            'cateId'=>3,
            'author'=>$v['id'],
            'content'=>'成功登录微信公众平台',
            'creatTime'=>date('Y-m-d H:i:s'),
            'tags'=>'活跃,',
            'name'=>$v['name']
          ]);
        $db->update('member',[
          'jf[+]'=>1
        ],[
          'id'=>$v['id']
        ]);
        setcookie("smobile", $v['mobile'], mktime()+31104000,'/');
        setcookie("staffID", $v['id'], mktime()+31104000,'/');
        $json = array('flag' => 200,'msg' => '登录成功', 'data' => []);
        return $response->withJson($json);
        exit();
      }else{
        $json = array('flag' => 400,'msg' => '密码错误', 'data' => []);
        return $response->withJson($json);
        exit();
      }
    }

    public function ulogout($request, $response, $args){
      setcookie("umobile", '', mktime()+43200,'/');
      setcookie("uid", '', mktime()+43200,'/');

     
      return $response->withRedirect('/loginu.html');
    }

    public function logout($request, $response, $args){
      setcookie("umobile", '', mktime()+43200,'/');
      setcookie("uid", '', mktime()+43200,'/');
       setcookie("smobile", '', mktime()+43200,'/');
      setcookie("staffID", '', mktime()+43200,'/');
      return $response->withRedirect('/loginu.html');
    }

    

    public function slogout($request, $response, $args){
      setcookie("smobile", '', mktime()+43200,'/');
      setcookie("staffID", '', mktime()+43200,'/');
      return $response->withRedirect('/logins.html');
    }
    
    public function getsmsvcode($request, $response, $args){
      global $db;

      if(isset($_POST['mobile']) && $_POST['mobile']!=''){
            $mobile = $_POST['mobile'];
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

     //服务者忘记密码页面
    public function getpwds($request, $response, $args){
      global $db;
      $as = [
      ];
      return $this->app->renderer->render($response, './getpwds.php', $as);

    }

    //客户忘记密码页面
    public function getpwdu($request, $response, $args){
      global $db;
      $as = [
      ];
      return $this->app->renderer->render($response, './getpwdu.php', $as);

    }


    //服务者忘记密码操作
    public function updatePostpwds($request, $response, $args){
      global $db;
       //判断验证码有效性
        $ldate = strtotime(date('Y-m-d H:i:s'));
        $v = $db->get('sms_vcode',['mobile','code','creattime'],[
          'AND'=>[
            'mobile'=>$_POST['mobile'],
            'code'=>$_POST['smscode'],
          ]
          ]);
        if(!$v){
          $json = array('flag' => 400,'msg' => '验证码错误', 'data' => []);
          return $response->withJson($json);
          exit();
        }
        $vdate = $ldate - strtotime($v['creattime']);
        if($vdate > 1800){
          $json = array('flag' => 400,'msg' => '验证码已过期', 'data' => []);
          return $response->withJson($json);
          exit();
        }

        //修改密码
        $member=$db->update('member',['password'=>$_POST['password']],['mobile'=>$_POST['mobile']]);
        if($member){
          $flag=200;
          $msg='修改成功';
          $json = array('flag' => 200,'msg' => '修改成功', 'data' => []);
          return $response->withJson($json);
          exit();
        }else{
          $member=0;
          $flag=400;
          $msg='修改失败';
          $json = array('flag' => 400,'msg' => '修改失败', 'data' => []);
          return $response->withJson($json);
          exit();
        }
    }

    //客户忘记密码操作
    public function updatePostpwdu($request, $response, $args){
      global $db;
       //判断验证码有效性
        $ldate = strtotime(date('Y-m-d H:i:s'));
        $v = $db->get('sms_vcode',['mobile','code','creattime'],[
          'AND'=>[
            'mobile'=>$_POST['mobile'],
            'code'=>$_POST['smscode'],
          ]
          ]);
        if(!$v){
          $json = array('flag' => 400,'msg' => '验证码错误', 'data' => []);
          return $response->withJson($json);
          exit();
        }
        $vdate = $ldate - strtotime($v['creattime']);
        if($vdate > 1800){
          $json = array('flag' => 400,'msg' => '验证码已过期', 'data' => []);
          return $response->withJson($json);
          exit();
        }

        //修改密码
        $customs=$db->update('customs',['password'=>$_POST['password']],['mobile'=>$_POST['mobile']]);
        if($customs){
          $flag=200;
          $msg='修改成功';
          $json = array('flag' => 200,'msg' => '修改成功', 'data' => []);
          return $response->withJson($json);
          exit();
        }else{
          $member=0;
          $flag=400;
          $msg='修改失败';
          $json = array('flag' => 400,'msg' => '修改失败', 'data' => []);
          return $response->withJson($json);
          exit();
        }
    }

    public function ubindwx($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//服务者
      $uri = urlencode('http://m.cw2009.com/getcodeu2.html');

      if(!isset($_COOKIE['openid']) || $_COOKIE['openid']==''){
        
        return $response->withRedirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx9f2d1785175d240a&redirect_uri='.$uri.'&response_type=code&scope=snsapi_userinfo&state=001#wechat_redirect');
      }

      $as = [
         'u'=>$u
      ];
      return $this->app->renderer->render($response, './u/bindwx.php', $as);
    }

    public function sbindwx($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $uri = urlencode('http://m.cw2009.com/getcodes2.html');

      if(!isset($_COOKIE['openid']) || $_COOKIE['openid']==''){
        
        return $response->withRedirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx9f2d1785175d240a&redirect_uri='.$uri.'&response_type=code&scope=snsapi_userinfo&state=001#wechat_redirect');
      }

      $as = [
         's'=>$s
      ];
      return $this->app->renderer->render($response, './s/bindwx.php', $as);

    }

    public function ubindwxsave($request, $response, $args){
        global $db;
        $s = $request->getAttribute('s');//服务者
        $mobile = $_POST['mobile'];
        $openID = $_POST['openID'];
        if($openID!=''){
          $new = $db->update('customs',[
            'openID'=>$openID
          ],[
            'mobile'=>$mobile
          ]);
          if($new){
            $json = array('flag' => 200,'msg' => '绑定微信成功', 'data' => []);
            return $response->withJson($json);
          }else{
            $json = array('flag' => 400,'msg' => '绑定失败，未知用户', 'data' => []);
            return $response->withJson($json);
          }
        }else{
           $json = array('flag' => 400,'msg' => '绑定失败，未正常读取您的openID', 'data' => []);
            return $response->withJson($json);
        }
        
    }

    public function sbindwxsave($request, $response, $args){
        global $db;
        $s = $request->getAttribute('s');//服务者
        $mobile = $_POST['mobile'];
        $openID = $_POST['openID'];
        if($openID!=''){
          $new = $db->update('member',[
            'openID'=>$openID
          ],[
            'mobile'=>$mobile
          ]);
          if($new){
            $json = array('flag' => 200,'msg' => '绑定微信成功', 'data' => []);
            return $response->withJson($json);
          }else{
            $json = array('flag' => 400,'msg' => '绑定失败，未知用户', 'data' => []);
            return $response->withJson($json);
          }
        }else{
           $json = array('flag' => 400,'msg' => '绑定失败，未正常读取您的openID', 'data' => []);
            return $response->withJson($json);
        }
        
    }

    public function choosemyidentity($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $u = $request->getAttribute('u');//用户
      $as = [
         's'=>$s,
         'u'=>$u
      ];
      return $this->app->renderer->render($response, './choosemyidentity.php', $as);
    }

    public function my($request, $response, $args){
      global $db;
        if(isset($_COOKIE['thismyuri']) && $_COOKIE['thismyuri']!=''){
              $myuri = $_COOKIE['thismyuri'];

            }else{
              if(isset($_COOKIE['staffID'])){ 
              $myuri = '/scenter.html';
            }

              if(isset($_COOKIE['uid'])){ 
                $myuri = '/ucenter.html';
              }

              if(!isset($_COOKIE['uid']) && !isset($_COOKIE['staffID'])){ 
                $myuri = '/loginassms.html';
              }
            }
          return $response->withRedirect($myuri);
    }
    
}
