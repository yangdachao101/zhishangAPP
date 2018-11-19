<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UriInterface as Uri;
use \interop\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use MyApp\Chat;

class FinderController 
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
      return $this->app->renderer->render($response, './finder.php', $as);
    }

    
    public function toutiao($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $keyword = isset($request->getQueryParams()['keyword']) ? $request->getQueryParams()['keyword'] :'';//关键词
      //分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*20)-20;
      }else{
        $p = 1;
        $srow = 0;
      }

      $list = $db->select('mcms_posts','*',[
        'AND'=>['cateId'=>1,
        'status'=>0],
        'ORDER'=>['id'=>'DESC'],
        'LIMIT'=>[$srow,20]
      ]);

      $count = $db->count('mcms_posts',[
        'cateId'=>1
      ]);



      $as = [
      'u'=>$u,
      's'=>$s,
      'list'=>$list,
      'p'=>$p,
      'count'=>$count,
      'keyword'=>$keyword
      ];
      return $this->app->renderer->render($response, './finder-toutiao.php', $as);
    }

    public function toutiaodetail($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $db->update('mcms_posts',['views[+]'=>1],['id'=>$args['id']]);
      $detail = $db->get('mcms_posts','*',['id'=>$args['id']]);
      $detail['thumbnail'] = $db->get('mcms_attachment','thumbnail',['id'=>$detail['thumbnail']]);
      $as = [
      'u'=>$u,
      's'=>$s,
      'detail'=>$detail
      ];
      return $this->app->renderer->render($response, './finder-toutiao-detail.php', $as);
    }

    public function notice($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      
      //分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*20)-20;
      }else{
        $p = 1;
        $srow = 0;
      }

      $list = $db->select('notice','*',[
        'AND'=>[
          'catId'=>0,
          //'type'=>1
        ],
        'ORDER'=>['id'=>'DESC'],
        'LIMIT'=>[$srow,20]
      ]);

      $count = $db->count('notice','*',[
        'AND'=>[
          'catId'=>0,
          //'type'=>1
        ],
      ]);


      
      $as = [
      'u'=>$u,
      's'=>$s,
      'list'=>$list,
      'p'=>$p,
      'count'=>$count
      ];
      return $this->app->renderer->render($response, './finder-notice.php', $as);
    }

    public function noticedetail($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $detail = $db->get('notice','*',['id'=>$args['id']]);
      $as = [
      'u'=>$u,
      's'=>$s,
      'detail'=>$detail
      ];
      return $this->app->renderer->render($response, './finder-notice-detail.php', $as);
    }
    
    public function ubook($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      $ubook_custom = [];//客户电话
      $ubook_staff = [];//服务者电话
      if($s){
        //如果是服务者登录
        //我名下的客户
        $id=$s['id'];
        $ts=$db->select('contract','uId',['staffId'=>$id]);//查询我名下的客户
        $t = array_unique ($ts);
        $i=0;
        foreach($t as $ti){
          $ubook_custom[$i]['name']=$db->get('customs','name',['id'=>$ti]);
          $ubook_custom[$i]['phone']=$db->get('customs','mobile',['id'=>$ti]);
          $i++;
        }
      }else{
        //如果是客户登录 
        //为我服务的服务者
        $id=$u['id'];
         $ts=$db->select('contract','staffId',['uId'=>$id]);//查询我名下有哪些服务者
        $t = array_unique ($ts);
        $i=0;
        foreach($t as $ti){
          $ubook_custom[$i]['name']=$db->get('member','name',['id'=>$ti]);
          $ubook_custom[$i]['phone']=$db->get('member','mobile',['id'=>$ti]);
          $i++;
        }
      }

      // var_dump($t);
      // exit;
      $ubook_common = $db->select('ubook',['name','phone'],[
            'uid'=>0
        ]);
     



      $ubook_other_a = $db->select('ubook',['name','phone'],[
            'AND'=>[
              'utype'=>0,
              'uid'=>1
            ]
          
        ]);
      $ubook_other_b = $db->select('ubook',['name','phone'],[
          
            'AND'=>[
              'utype'=>1,
              'uid'=>1
            ]
          
        ]);
      $as = [
        'u'=>$u,
        's'=>$s,
        'ubook_common'=>$ubook_common,
        'ubook_custom'=>$ubook_custom,
        'ubook_staff'=>$ubook_staff,
        'ubook_other_a'=>$ubook_other_a,
        'ubook_other_b'=>$ubook_other_b
      ];
      return $this->app->renderer->render($response, './finder-ubook.php', $as);
    }

    public function zone($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      if($s){
        $id=$s['id'];
      }else{
        $id=$u['id'];
      }
      $qq=$db->select('qqfares','*');
      // //分页参数
      // if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
      //   $p = $_GET['p'];
      //   $srow = ($p*20)-20;
      // }else{
      //   $p = 1;
      //   $srow = 0;
      // }

      // $list = $db->select('mcms_quan','*',[
      //   'cateId'=>8,
      //   'ORDER'=>['id'=>'DESC'],
      //   'LIMIT'=>[$srow,20]
      // ]);
      // for($i=0;$i<count($list);$i++){
      //   //查询点赞表
      //   $zhan[$i]=$db->select('mcms_zhan',['uid','type'],['targetId'=>$list[$i]['id']]);
      //   if($zhan[$i]){
      //     $a=[];
      //     for($j=0;$j<count($zhan[$i]);$j++){
      //         if($zhan[$i][$j]['type']==1){
      //           $a[$i][$j]['name']=$db->get('member','name',['id'=>$zhan[$i][$j]['uid']]);
      //         }else{
      //           $a[$i][$j]['name']=$db->get('customs','name',['id'=>$zhan[$i][$j]['uid']]);

      //         }
      //     }
      //      $list[$i]['dz']=$a[$i];
      //      if(empty($list[$i]['dz'])){
      //       $list[$i]['dz']=[];
      //      }
      //     // echo "1";
      //   }
      //   //查询评论
      //   $pl[$i]=$db->select('mcms_comments',['comment','name'],['targetId'=>$list[$i]['id']]);
      //    if($pl[$i]){
      //     $b=[];
      //     for($j=0;$j<count($pl[$i]);$j++){
      //           $b[$i][$j]['comment']=$pl[$i][$j]['comment'];
      //           $b[$i][$j]['name']=$pl[$i][$j]['name'];

      //     }
      //      $list[$i]['pl']=$b[$i];
      //      if(empty($list[$i]['pl'])){
      //       $list[$i]['pl']='';
      //      }
      //     // echo "1";
      //   }

      // }
      // $count = $db->count('mcms_quan',['cateId'=>8,]);
      // $counts=ceil($count/20);
      // var_dump($counts);
      // exit;
      $as = [
      'u'=>$u,
      's'=>$s,
      'qq'=>$qq,
      // 'list'=>$list,
      // 'p'=>$p,
      // 'counts'=>$counts
      ];
      return $this->app->renderer->render($response, './finder-zone.php', $as);
    }
    
    public function zoneJson($request, $response, $args){
        global $db;
        $u = $request->getAttribute('u');//用户
        $s = $request->getAttribute('s');//服务者
        $sid = isset($request->getQueryParams()['sid']) ? $request->getQueryParams()['sid'] : 0;
        $catId = isset($request->getQueryParams()['catId']) ? $request->getQueryParams()['catId'] : [1,2,3,4,5,6,7,8,9,10];
        $number = isset($request->getQueryParams()['number']) ? $request->getQueryParams()['number'] : 0;//分页
        if($sid==0){
        $a = $db->select('mcms_quan',[
          '[>]member'=>['author'=>'id'],
          '[>]customs'=>['author'=>'id'],
        ],[
          'mcms_quan.id',
          'mcms_quan.content',
          'mcms_quan.name',
          'mcms_quan.creatTime',
          'mcms_quan.zhan',
          'mcms_quan.pics',
          'mcms_quan.utype',
          'mcms_quan.author',
          'member.avatar(savatar)',
          'member.status(status)',
          'member.inoffice(inoffice)',
          'customs.avatar(uavatar)',
          'customs.sfz(sfz)',
        ],
          [
            'cateId'=>$catId,
            'ORDER'=>['mcms_quan.id'=>'DESC'],
            'LIMIT'=>[$number,5]
          ]
        );
      }else{
        $a = $db->select('mcms_quan',[
          '[>]member'=>['author'=>'id'],
          '[>]customs'=>['author'=>'id'],
        ],[
          'mcms_quan.id',
          'mcms_quan.content',
          'mcms_quan.name',
          'mcms_quan.creatTime',
          'mcms_quan.zhan',
          'mcms_quan.pics',
          'mcms_quan.utype',
          'mcms_quan.author',
          'member.avatar(savatar)',
          'member.status(status)',
          'member.inoffice(inoffice)',
          'customs.avatar(uavatar)',
          'customs.sfz(sfz)',
        ],
          [ 
            'AND'=>[
              'cateId'=>$catId,
              'mcms_quan.author'=>$sid,
              'mcms_quan.utype'=>0
            ],
            'ORDER'=>['mcms_quan.id'=>'DESC'],
            'LIMIT'=>[$number,5]
          ]
        );
      }
        $list = [];
        $i = 0;
        foreach ($a as $v) {

          $list[$i]['id']=$v['id'];
          $list[$i]['content']=$v['content'];
          $list[$i]['name']=$v['name'];
          $list[$i]['creatTime']=tmspan(strtotime($v['creatTime']));
          $list[$i]['zhan']=$v['zhan'];
          $list[$i]['pics']=$v['pics'];
          $list[$i]['utype']=$v['utype'];
          $list[$i]['author']=$v['author'];
          $list[$i]['savatar']=$v['savatar'];
          $list[$i]['inoffice']=$v['inoffice'];
          $list[$i]['uavatar']=$v['uavatar'];
          
          $list[$i]['status']=$v['status'];
          $list[$i]['sfz']=$v['sfz'];

          $list[$i]['comments'] = $db->select('mcms_comments','*',[
            'AND'=>[
            'targetId'=>$v['id'],
            'ctype'=>1
          ]]);

          $list[$i]['zhans'] = $db->select('mcms_zhan',[
            '[>]member'=>['uid'=>'id'],
            '[>]customs'=>['uid'=>'id'],
          ],[
            'mcms_zhan.type',
            'member.name(sname)',
            'customs.name(uname)'
          ],[
            'AND'=>[
            'mcms_zhan.targetId'=>$v['id'],
            'mcms_zhan.ctype'=>1
          ]]);

          if($list[$i]['pics']!='[]'){
            $list[$i]['album'] = $db->select('mcms_attachment','*',[
                'id'=>json_decode($v['pics'])
            ]);
          }else{
            $list[$i]['album'] = 'NULL';
          }


          $i++;
        }


        $json = array('flag' => 200,'msg' => '成功', 'data' => $list);
        return $response->withJson($json);

    }

    public function toutiaoJson($request, $response, $args){

        global $db;
        $u = $request->getAttribute('u');//用户
        $s = $request->getAttribute('s');//服务者

        $number = isset($request->getQueryParams()['number']) ? $request->getQueryParams()['number'] : 0;//分页
        $keyword = isset($request->getQueryParams()['keyword']) ? $request->getQueryParams()['keyword'] :'';//关键词

        $list = $db->select('mcms_posts','*',[
          'AND'=>[
            'title[~]'=>$keyword,
            'status'=>0,
          ],
            'ORDER'=>['id'=>'DESC'],
            'LIMIT'=>[$number,15]
          ]);
 
        $json = array('flag' => 200,'msg' => '成功', 'data' => $list);
        return $response->withJson($json);
    }

    public function zoneDetail($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      if($s){
        $id=$s['id'];
      }else{
        $id=$u['id'];
      }

      //分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*20)-20;
      }else{
        $p = 1;
        $srow = 0;
      }

      $list = $db->select('mcms_quan','*',[
        'id'=>$args['id']
      ]);
      for($i=0;$i<count($list);$i++){
        //查询点赞表
        $zhan[$i]=$db->select('mcms_zhan',['uid','type'],['targetId'=>$list[$i]['id']]);
        if($zhan[$i]){
          $a=[];
          for($j=0;$j<count($zhan[$i]);$j++){
              if($zhan[$i][$j]['type']==1){
                $a[$i][$j]['name']=$db->get('member','name',['id'=>$zhan[$i][$j]['uid']]);
              }else{
                $a[$i][$j]['name']=$db->get('customs','name',['id'=>$zhan[$i][$j]['uid']]);

              }
          }
           $list[$i]['dz']=$a[$i];
           if(empty($list[$i]['dz'])){
            $list[$i]['dz']=[];
           }
          // echo "1";
        }
        //查询评论
         $pl[$i]=$db->select('mcms_comments',['comment','name','id'],[
          'AND'=>[
              'targetId'=>$list[$i]['id'],
              'pid'=>null,
          ]]);
         if($pl[$i]){
          $b=[];
          for($j=0;$j<count($pl[$i]);$j++){
                $b[$i][$j]['comment']=$pl[$i][$j]['comment'];
                $b[$i][$j]['name']=$pl[$i][$j]['name'];
                $b[$i][$j]['id']=$pl[$i][$j]['id'];
                $b[$i][$j]['number']=$db->count('mcms_comments','id',[
                  'AND'=>[
                      'targetId'=>$list[$i]['id'],
                      'pid'=>$pl[$i][$j]['id'],
                  ]]);

          }
           $list[$i]['pl']=$b[$i];
           if(empty($list[$i]['pl'])){
            $list[$i]['pl']='';
           }
          // echo "1";
        }

      }
      $count = 1;
      $counts=1;
      // var_dump($counts);
      // exit;
      $qq = $db->select('qqfares','*');
      
      //如果阅读者是自己 则更新comment状态
      if($list[0]['author'] == $s['id'] && $list[0]['utype']==0){
        $db->update('mcms_comments',[
          'iamread'=>0
        ],[
          'targetId'=>$list[0]['id']
        ]);
        $db->update('mcms_zhan',[
          'iamread'=>0
        ],[
          'targetId'=>$list[0]['id']
        ]);
      }

      $as = [
      'u'=>$u,
      's'=>$s,
      'list'=>$list,
      'p'=>$p,
      'counts'=>$counts,
      'qq'=>$qq,
      ];
      return $this->app->renderer->render($response, './finder-zone-detail.php', $as);
    }

    //发布圈子信息
    public function insertZone($request, $response, $args){
     global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者

      if($s){
        $id = $s['id'];//服务者
        $utype = 0;//员工
        $name = $s['name'];
      }else{
        $id= $u['id'];//客户
        $name = $u['name'];
        $utype = 1;//客户
      }
      $text=$_POST['texta'];

      $cs = preg_match_all("/\\#.*?\\ /is",$text,$array);
      $s = '';
      for($i = 0; $i<count($array[0]);$i++) {
          $f = $array[0][$i];
          $f = ltrim($f,'#');
          $f = rtrim($f,' ');
          $s .= $f.',';
      }



      $pics=$_POST['pics'];
      $fund=$db->insert('mcms_quan',[
            'title'=>'',
            'cateId'=>8,
            'content'=>$text,
            'creatTime'=>date('Y-m-d H:i:s'),
            'pics'=>$pics,//图集
            'status'=>0,
            'utype'=>$utype,
            'author'=>$id,
            'name'=>$name,
            'tags'=>$s
        ]);
      if($fund){
        $id = $db->id();
        
        
        
        $urls = array(
            'https://www.cw2009.com/quan/'.$id.'.html'
        );
        posttobaidu($urls);
        $urlsb = array(
          'https://www.cw2009.com/quan/'.$id.'.html',
        );
        $s = posttobaijia($urlsb);
        $flag=200;
        $msg='发布成功';
        $db->update('member',[
          'jljf[+]'=>1
        ],[
          'id'=>$id
        ]);
      }else{
        $fund=0;
        $flag=400;
        $msg='发布失败';
      }
      $json = array('flag' => $flag,'msg' => $msg);
      return $response->withJson($json);

    }


    
    public function zonecreat($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      //查询图片
      $qq=$db->select('qqfares','*');
      $as = [
        'u'=>$u,
        's'=>$s,
        'qq'=>$qq,
      ];
      return $this->app->renderer->render($response, './finder-zone-creat.php', $as);
    }

    //圈子点赞请求
    public function finderZonezan($request, $response, $args){
       global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      if($s){
        $id=$s['id'];
        $name=$db->get('member','name',['id'=>$id]);//员工姓名
        $type=1;
      }else{
        $id=$u['id'];
        $name=$db->get('customs','name',['id'=>$id]);//客户姓名
        $type=2;
      }
      $targetid=$_POST['id'];//说说id
      //分居说说id修改信息
      $quan=$db->select('mcms_zhan',['targetId','uid','type'],[
            'AND'=>[
                'targetId'=>$targetid,
                'uid'=>$id,
                'type'=>$type,
            ]
        ]);
      if($quan){
        //如果存在表示已经存在
        $flag=400;
        $msg='这条信息您已经赞过了';
        $json = array('flag' =>$flag,'msg'=>$msg);
        return $response->withJson($json);
      }else{
        $view=1;
        $zhan=1;
        //如果不存在表示还没有点赞过
        $quan=$db->update('mcms_quan',[
              'views[+]'=>$view,
              'zhan[+]'=>$zhan,
          ],[
              'id'=>$targetid,
          ]);
        //写入点赞记录
        $zan=$db->insert('mcms_zhan',[
              'targetId'=>$targetid,
              'creattime'=>date('Y-m-d H:i:s'),
              'uid'=>$id,
              'ctype'=>1,
              'type'=>$type,
          ]);
        if($zan){
          $flag=200;
          $msg='成功点赞';
        }else{
          $flag=400;
          $msg='点赞失败';
        }
        $json = array('flag' =>$flag,'msg'=>$msg,'name'=>$name);
        return $response->withJson($json);
      }
      
    }

    //finderpl(朋友圈评论添加)
    public function finderpl($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      if($s){
        $id=$s['id'];
      }else{
        if($u){
          $id=$u['id'];
        }else{
          $id=0;
        }
        
      }
      $targetid = $_POST['id'];
      $text = $_POST['text'];
      $time = date('Y-m-d H:i:s');
      $name = $_POST['name'];
      
      $com = $db->insert('mcms_comments',[
          // 'pid'=>$_POST['uid'],//被评论信息的发布人id
          'uid'=>$id,//评论人的id
          'ctype'=>1,//资讯或圈子
          'targetId'=>$targetid,//关联id对应哪条信息
          'comment'=>$text,//内容
          'creattime'=>$time,//发表时间
          'name'=>$name,
          'iamread'=>1
          ]);
      //更新评论数
      $db->update('mcms_quan',[
        'comments[+]'=>1
      ],[
        'id'=>$targetid
      ]);

      if($com){
        $flag=200;
        $msg='评论成功';
      }else{
        $com=0;
        $flag=400;
        $msg='评论失败，数据错误';
      }
      $json = array('flag' =>$flag,'msg'=>$msg);
        return $response->withJson($json);
    }

    public function staffDetail($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//用户
      $id = $args['id'];
      
      if(isset($_GET['backuri']) && $_GET['backuri']!=''){
        $backuri = $_GET['backuri'];
      }else{
        $backuri = '/zone.html';
      }
      
      //根据服务者ID查询基本信息
      $member = $db->get('member','*',['id'=>$id]);
      $as = [
      'u'=>$u,
      's'=>$s,
      'backuri'=>$backuri,
      'member'=>$member,
      ];
      return $this->app->renderer->render($response, './staff-detail.php', $as);
    }

    public function userDetail($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//用户
      $id=$args['id'];
      if(isset($_GET['backuri']) && $_GET['backuri']!=''){
        $backuri = $_GET['backuri'];
      }else{
        $backuri = '/zone.html';
      }
      //根据服务者ID查询基本信息
      $member=$db->get('customs','*',['id'=>$id]);
      $as = [
      's'=>$s,
      'u'=>$u,
      'backuri'=>$backuri,
      'member'=>$member,
      ];
      return $this->app->renderer->render($response, './user-detail.php', $as);
    }

     //通讯录-》员工通讯录
    public function sbook($request, $response, $args){
      global $db;
      //分页
      //分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*50)-50;
      }else{
        $p = 1;
        $srow = 0;
      }
      //查询员工姓名电话
      $member=$db->select('member',['name','mobile'],[
            'AND'=>[
              'status'=>1,
              'inoffice'=>1
            ],
            'LIMIT'=>[$srow,50],
        ]);
      //计算全部数据条数
      $counts=$db->count('member','id',['AND'=>[
              'status'=>1,
              'inoffice'=>1
            ]]);
      $count=ceil($counts/50);//计算有多少页
      $as = [
        'p'=>$p,//分页参数
        'count'=>$count,//数据总个数
        'member'=>$member,//员工数据
      ];
      return $this->app->renderer->render($response, './finder-sbook.php', $as);

    }


     //发现-》首页-》签单排行榜
    
    public function rankingList2($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $day = isset($request->getQueryParams()['day']) ? $request->getQueryParams()['day'] : date('Y-m-d');//分页
      $month = date('Y-m',strtotime($day));

      $as = [
        's'=>$s,
        'day'=>$day,
        'month'=>$month
      ];
      return $this->app->renderer->render($response, './finder-ranking2.php', $as);
    }





    public function rankingList($request, $response, $args){
      global $db;
      if(isset($_GET['type'])&&$_GET['type']!=''){
        $type=$_GET['type'];//上月
      }else{
        $type = 0;//当月
      }

      if($type == 0){
         $da=date('y-m');
      $income=$db ->query("SELECT zscrm_orders.vcode FROM zscrm_orders
        where (zscrm_orders.status=1 or zscrm_orders.status=2 or zscrm_orders.status=3) and zscrm_orders.creattime>= '".$da."-1 0:0:0'  and zscrm_orders.creattime<='".$da."-31 23:59:59'
         group by zscrm_orders.vcode order by sum(zscrm_orders.price) desc" )->fetchALL();

      // var_dump($income);
      // exit;

     $count['a']=$db->count('orders','id',['AND'=>[
      'creattime[>=]'=>date('y-m-1 0:0:0'),
      'creattime[<=]'=>date('y-m-31 23:59:59'),
      'status'=>[1,2,3],
       ]]);
      $count['money']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('y-m-1 0:0:0'),
      'creattime[<=]'=>date('y-m-31 23:59:59'),
      'status'=>[1,2,3],
       ]]);
        $count['daymoney']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('y-m-d 0:0:0'),
      'creattime[<=]'=>date('y-m-d 23:59:59'),
      'status'=>[1,2,3],
       ]]);
      // var_dump($count[])
     for($i=0;$i<count($income);$i++){
         $income[$i]['member']=$db->get('member_vcode',['type','uId'],['vcode'=>$income[$i]['vcode']]);
          if($income[$i]['member']['type']==1){
          $income[$i]['name']=$db->get('member','name',['id'=>$income[$i]['member']['uId']]);
          $income[$i]['pic']=$db->get('member','avatar',['id'=>$income[$i]['member']['uId']]);
          $income[$i]['avatar']=$db->get('mcms_attachment','thumbnail',['id'=>$income[$i]['pic']]);
          $income[$i]['num']=$i+1;
          }
          else{
            $income[$i]['name']=$db->get('customs','name',['id'=>$income[$i]['member']['uId']]);
            $income[$i]['pic']=$db->get('customs','avatar',['id'=>$income[$i]['member']['uId']]);
            $income[$i]['avatar']=$db->get('mcms_attachment','thumbnail',['id'=>$income[$i]['pic']]);
            $income[$i]['num']=$i+1;
           }
      $income[$i]['number']=$db->count('orders','id',['AND'=>[
      'creattime[>=]'=>date('y-m-1 0:0:0'),
      'creattime[<=]'=>date('y-m-31 23:59:59'),
      'status'=>[1,2,3],
      'vcode'=>$income[$i]['vcode'],
       ]]);
      $income[$i]['money']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('y-m-1 0:0:0'),
      'creattime[<=]'=>date('y-m-31 23:59:59'),
      'status'=>[1,2,3],
      'vcode'=>$income[$i]['vcode'],
       ]]);
      $income[$i]['num']=$i+1;
     }
   }else if($type==1){
    //上月
     $income=$db ->query("SELECT zscrm_orders.vcode FROM zscrm_orders
      where (zscrm_orders.status=1 or zscrm_orders.status=2 or zscrm_orders.status=3) and date_format(zscrm_orders.creattime,'%Y-%m')=date_format(DATE_SUB(curdate(), INTERVAL 1 MONTH),'%Y-%m') 
         group by zscrm_orders.vcode order by sum(zscrm_orders.price) desc" )->fetchALL();
      $count['a']=$db->count('orders','id',['AND'=>[
      'creattime[>=]'=>date('Y-m-01 0:0:0' , strtotime("-1 month")),
      'creattime[<=]'=>date('Y-m-31 23:59:59', strtotime("-1 month")),
      'status'=>[1,2,3],
       ]]);
      $count['money']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('Y-m-01 0:0:0' , strtotime("-1 month")),
      'creattime[<=]'=>date('Y-m-31 23:59:59', strtotime("-1 month")),
      'status'=>[1,2,3],
       ]]);
      $count['daymoney']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('y-m-d 0:0:0'),
      'creattime[<=]'=>date('y-m-d 23:59:59'),
      'status'=>[1,2,3],
       ]]);
     for($i=0;$i<count($income);$i++){
         $income[$i]['member']=$db->get('member_vcode',['type','uId'],['vcode'=>$income[$i]['vcode']]);
          if($income[$i]['member']['type']==1){
          $income[$i]['name']=$db->get('member','name',['id'=>$income[$i]['member']['uId']]);
           $income[$i]['pic']=$db->get('member','avatar',['id'=>$income[$i]['member']['uId']]);
          $income[$i]['avatar']=$db->get('mcms_attachment','thumbnail',['id'=>$income[$i]['pic']]);
          $income[$i]['num']=$i+1;
          }
          else{
            $income[$i]['name']=$db->get('customs','name',['id'=>$income[$i]['member']['uId']]);
            $income[$i]['pic']=$db->get('customs','avatar',['id'=>$income[$i]['member']['uId']]);
            $income[$i]['avatar']=$db->get('mcms_attachment','thumbnail',['id'=>$income[$i]['pic']]);
            $income[$i]['num']=$i+1;
           }
      $income[$i]['number']=$db->count('orders','id',['AND'=>[
      'creattime[>=]'=>date('Y-m-01 0:0:0' , strtotime("-1 month")),
      'creattime[<=]'=>date('Y-m-31 23:59:59', strtotime("-1 month")),
      'status'=>[1,2,3],
      'vcode'=>$income[$i]['vcode'],
       ]]);
      $income[$i]['money']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('Y-m-01 0:0:0' , strtotime("-1 month")),
      'creattime[<=]'=>date('Y-m-31 23:59:59', strtotime("-1 month")),
      'status'=>[1,2,3],
      'vcode'=>$income[$i]['vcode'],
       ]]);
      $income[$i]['num']=$i+1;
     }
   }else if($type==3){
    //当日
    $da=date('Y-m-d');
       $income=$db ->query("SELECT zscrm_orders.vcode FROM zscrm_orders
        where (zscrm_orders.status=1 or zscrm_orders.status=2 or zscrm_orders.status=3) and zscrm_orders.creattime>= '".$da." 0:0:0'  and zscrm_orders.creattime<='".$da." 23:59:59'
         group by zscrm_orders.vcode order by sum(zscrm_orders.price) desc" )->fetchALL();

      $count['a']=$db->count('orders','id',['AND'=>[
      'creattime[>=]'=>date('Y-m-d 00:00:00'),
      'creattime[<=]'=>date('Y-m-d 23:59:59'),
      'status'=>[1,2,3],
       ]]);
      $count['money']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('Y-m-d 00:00:00'),
      'creattime[<=]'=>date('Y-m-d 23:59:59'),
      'status'=>[1,2,3],
       ]]);
         $count['daymoney']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('y-m-d 0:0:0'),
      'creattime[<=]'=>date('y-m-d 23:59:59'),
      'status'=>[1,2,3],
       ]]);
      // var_dump($count);
      // exit;
     for($i=0;$i<count($income);$i++){
         $income[$i]['member']=$db->get('member_vcode',['type','uId'],['vcode'=>$income[$i]['vcode']]);
          if($income[$i]['member']['type']==1){
            $income[$i]['name']=$db->get('member','name',['id'=>$income[$i]['member']['uId']]);
             $income[$i]['pic']=$db->get('member','avatar',['id'=>$income[$i]['member']['uId']]);
            $income[$i]['avatar']=$db->get('mcms_attachment','thumbnail',['id'=>$income[$i]['pic']]);
            $income[$i]['num']=$i+1;
          }else{
            $income[$i]['name']=$db->get('customs','name',['id'=>$income[$i]['member']['uId']]);
            $income[$i]['pic']=$db->get('customs','avatar',['id'=>$income[$i]['member']['uId']]);
            $income[$i]['avatar']=$db->get('mcms_attachment','thumbnail',['id'=>$income[$i]['pic']]);
            $income[$i]['num']=$i+1;
           }

      $income[$i]['number']=$db->count('orders','id',['AND'=>[
      'creattime[>=]'=>date('Y-m-d 00:00:00'),
      'creattime[<=]'=>date('Y-m-d 23:59:59'),
      'status'=>[1,2,3],
      'vcode'=>$income[$i]['vcode'],
       ]]);
      $income[$i]['money']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('Y-m-d 00:00:00'),
      'creattime[<=]'=>date('Y-m-d 23:59:59'),
      'status'=>[1,2,3],
      'vcode'=>$income[$i]['vcode'],
       ]]);
      $income[$i]['num']=$i+1;
     }
  
     // var_dump($income);
     // exit;
   }
     
     // var_dump($income);
     // var_dump($count);

     // exit;
     
      $as = [
        'income'=>$income,
        'count'=>$count,
        'type'=>$type,
      ];
      return $this->app->renderer->render($response, './finder-ranking.php', $as);
    }


     //上月的签单榜
    public function monthrankingList($request, $response, $args){
      global $db;
      $income=$db ->query("SELECT zscrm_orders.vcode FROM zscrm_orders
      where (zscrm_orders.status=1 or zscrm_orders.status=2 or zscrm_orders.status=3) and date_format(zscrm_orders.creattime,'%Y-%m')=date_format(DATE_SUB(curdate(), INTERVAL 1 MONTH),'%Y-%m') 
         group by zscrm_orders.vcode order by sum(zscrm_orders.price) desc" )->fetchALL();
      $count['a']=$db->count('orders','id',['AND'=>[
      'creattime[>=]'=>date('Y-m-01 0:0:0' , strtotime("-1 month")),
      'creattime[<=]'=>date('Y-m-31 23:59:59', strtotime("-1 month")),
      'status'=>[1,2,3],
       ]]);
      $count['money']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('Y-m-01 0:0:0' , strtotime("-1 month")),
      'creattime[<=]'=>date('Y-m-31 23:59:59', strtotime("-1 month")),
      'status'=>[1,2,3],
       ]]);
     for($i=0;$i<count($income);$i++){
         $income[$i]['member']=$db->get('member_vcode',['type','uId'],['vcode'=>$income[$i]['vcode']]);
          if($income[$i]['member']['type']==1){
          $income[$i]['name']=$db->get('member','name',['id'=>$income[$i]['member']['uId']]);
           $income[$i]['pic']=$db->get('member','avatar',['id'=>$income[$i]['member']['uId']]);
          $income[$i]['avatar']=$db->get('mcms_attachment','thumbnail',['id'=>$income[$i]['pic']]);
          $income[$i]['num']=$i+1;
          }
          else{
            $income[$i]['name']=$db->get('customs','name',['id'=>$income[$i]['member']['uId']]);
            $income[$i]['pic']=$db->get('customs','avatar',['id'=>$income[$i]['member']['uId']]);
            $income[$i]['avatar']=$db->get('mcms_attachment','thumbnail',['id'=>$income[$i]['pic']]);
            $income[$i]['num']=$i+1;
           }
      $income[$i]['number']=$db->count('orders','id',['AND'=>[
      'creattime[>=]'=>date('Y-m-01 0:0:0' , strtotime("-1 month")),
      'creattime[<=]'=>date('Y-m-31 23:59:59', strtotime("-1 month")),
      'status'=>[1,2,3],
      'vcode'=>$income[$i]['vcode'],
       ]]);
      $income[$i]['money']=$db->sum('orders','price',['AND'=>[
      'creattime[>=]'=>date('Y-m-01 0:0:0' , strtotime("-1 month")),
      'creattime[<=]'=>date('Y-m-31 23:59:59', strtotime("-1 month")),
      'status'=>[1,2,3],
      'vcode'=>$income[$i]['vcode'],
       ]]);
      $income[$i]['num']=$i+1;
     }


    $as=[
      'income'=>$income,
      'count'=>$count,
    ];
    return $this->app->renderer->render($response,'./finder-ranking_month.php',$as);
    }

     //加载业绩具体信息页面
    public function rankingDetails($request, $response, $args){
      global $db;
      $type=$args['type'];
      $vcode=$args['id'];
      if($type==0){
        //当月信息
        // echo '1';
        $time=date('Y-m-1 00:00:00');//当月时间
        $endtime=date('Y-m-31 23:59:59');//当月时间
        $orders=$db->select('contract','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    // 'status'=>[1,2,3],
                    'creattime[>=]'=>$time,
                    'creattime[<=]'=>$endtime,
                ],
                'ORDER'=>['id'=>'DESC'],
            ]);
        // var_dump($orders);
        // exit;
      }else if($type==1){
        //上月信息
        // echo '2';
        $time=date('Y-m-1 00:00:00',strtotime('-1 month'));//当月时间
        $endtime=date('Y-m-31 23:59:59',strtotime('-1 month'));//当月时间
         $orders=$db->select('contract','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    // 'status'=>[1,2,3],
                    'creattime[>=]'=>$time,
                    'creattime[<=]'=>$endtime,
                ],
                'ORDER'=>['id'=>'DESC'],
            ]);
         // var_dump($orders);
         // exit;
      }else if($type==3){
        $time=date('Y-m-d 00:00:00');//当月时间
        $endtime=date('Y-m-d 23:59:59');//当月时间
         $orders=$db->select('contract','*',[
                'AND'=>[
                    'vcode'=>$vcode,
                    // 'status'=>[1,2,3],
                    'creattime[>=]'=>$time,
                    'creattime[<=]'=>$endtime,
                ],
                'ORDER'=>['id'=>'DESC'],
            ]);
      }
      // var_dump($orders);
      // exit;
      $i=0;
      $list=[];
      foreach($orders as $o){
          //查询订单号和订单支付时间
          $list[$i]['oid']=$o['orderId'];
          $list[$i]['creattime']=$o['creattime'];
          //查询客户信息
          $customs=$db->get('customs',['id','name','mobile'],['id'=>$o['uId']]);
          $list[$i]['uname']=$customs['name'];
          $list[$i]['mobile']=substr_replace($customs['mobile'],'****',3,4);
          //订单状态
          $list[$i]['status']=$db->get('contract_status','statusname',['id'=>$o['status']]);
          //查询服务内容
          
            //单品
            $list[$i]['title']=$db->get('mcms_service','title',['id'=>$o['sId']]);
          
          //订单金额
          $list[$i]['price']=$o['money_total'];
          //查询关联企业
          if(isset($o['comanyId'])&&$o['comanyId']!=0){
            $list[$i]['comanyname']=$db->get('companies','companyname',['id'=>$o['comanyId']]);
          }else{
            $list[$i]['comanyname']=0;
          }
           //查询服务者
          if(isset($o['staffId'])&&$o['staffId']!=''){
            $list[$i]['sname']=$db->get('member','name',['id'=>$o['staffId']]);
          }else{
            $list[$i]['sname']='';
          }
          $i++;
      }
      // var_dump($list);
      // exit;

     $as=[
      'list'=>$list,
    ];
    return $this->app->renderer->render($response,'./finder-ranking_details.php',$as);

    }
    
    public function savecomment($request, $response, $args){
      global $db;
      if(isset($request->getParsedBody()['postid']) && $request->getParsedBody()['postid']>0){
        if($request->getParsedBody()['uid']>0){
          $status = 0;
        }else{
          $status = 1;
        }
        $new = $db->insert('mcms_comments',[
          'uid'=>$request->getParsedBody()['uid'],
          'ctype'=>$request->getParsedBody()['ctype'],
          'comment'=>$request->getParsedBody()['comment'],
          'creattime'=>date('Y-m-d H:i:s'),
          'name'=>$request->getParsedBody()['name'],
          'mobile'=>$request->getParsedBody()['mobile'],
          'status'=>$status,
          'targetId'=>$request->getParsedBody()['postid'],
          'ip'=>getip(),
          'iamread'=>1
        ]);
        $json = array('flag' => 200,'msg' => '保存已成功', 'data' => [
          'id'=>$new,
        ]);
        return $response->withJson($json);
      }else{
        $json = array('flag' => 400,'msg' => '该圈子已删除，不允许发表评论', 'data' => []);
        return $response->withJson($json);
      }
      
    }

     //加载报销申请列表页
    public function reimburdementList($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//用户
      $list=[];
      $list=$db->select('member_reimbursement','*',['staffid'=>$s['id'],'ORDER'=>['id'=>'DESC']]);

      $as=[
        's'=>$s,
        'list'=>$list,
      ];
      return $this->app->renderer->render($response,'./finder_reimburdement_list.php',$as);
    }
    //加载报销申请的具体信息页面
    public function reimburdementEdit($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//用户
      $id=$args['id'];//数据id
      //根据id 查询数据
      $list=[];
      $list=$db->get('member_reimbursement','*',['id'=>$id]);
      //服务者上传的申请报销的票据证明
      if($list['pic']!=[] && $list['pic']!=''){
          $json = json_decode($list['pic']);
          $i=0;
          $pics=[];
          foreach($json as $j){
            $l=$db->get('mcms_attachment','*',['id'=>$j]);
            $pics[$i]['uri']=$l['uri'];
            $pics[$i]['thumbnail']=$l['thumbnail'];
            $i++;
          }
        };
        //上传的打款证明
         if($list['pics']!=[] && $list['pics']!=''){
          $jsons = json_decode($list['pics']);
          $j=0;
          $picse=[];
          foreach($jsons as $s){
            $a=$db->get('mcms_attachment','*',['id'=>$s]);
            $picse[$j]['uri']=$a['uri'];
            $picse[$j]['thumbnail']=$a['thumbnail'];
            $j++;
          }
        };
        // var_dump($picse);
        // exit;

      $as=[
      's'=>$s,
      'list'=>$list,
      'pics'=>$pics,//申请人上传的票据图片
      'picse'=>$picse,//后台审核上传的打款证明
    ];
    return $this->app->renderer->render($response,'./finder_reimburdement_edit.php',$as);
    }

    //加载申请报销的表单页面
    public function reimburdement($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//用户

    $as=[
      's'=>$s,
    ];
    return $this->app->renderer->render($response,'./finder_reimburdement_form.php',$as);
    }
    //执行报销申请的数据写入操作
    public function insertReimburdement($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//用户
      if(isset($_POST['contractid'])&&$_POST['contractid']!=''){
        $contractid=$_POST['contractid'];
      }else{
        $contractid=null;
      }
       if(isset($_POST['title'])&&$_POST['title']!=''){
        $title=$_POST['title'];
      }else{
        $title=null;
      }
      //执行写入操作
      $reim=$db->insert('member_reimbursement',[
          'staffid'=>$_POST['staffid'],//申请人id
          'name'=>$_POST['name'],//申请人姓名
          'mobile'=>$_POST['mobile'],//申请人电话
          'type'=>$_POST['type'],//申请类别 成本或费用
          'contractid'=>$contractid,//合同id
          'title'=>$title,//服务内容
          'bank'=>$_POST['bank'],//开户银行
          'bankname'=>$_POST['bankname'],//开户人姓名
          'banknumber'=>$_POST['banknumber'],//银行帐号
          'text'=>$_POST['text'],//具体的报销内容
          'pic'=>$_POST['pics'],//上传的图片id
          'status'=>0,//申请状态0新申请
          'creattime'=>date('Y-m-d H:i:s'),//添加时间
          'price'=>$_POST['price'],//报销金额
        ]);
      if($reim){
        $json = array('flag' => 200,'msg' => '申请成功，等待审核！');
        return $response->withJson($json);
      }else{
        $json = array('flag' => 400,'msg' => '申请失败，数据错误！');
        return $response->withJson($json);
      }

    }

     //评论的子类评论
    public function zonesDetail($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      if($s){
        $id=$s['id'];
      }else{
        $id=$u['id'];
      }

      //分页参数
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>1){
        $p = $_GET['p'];
        $srow = ($p*20)-20;
      }else{
        $p = 1;
        $srow = 0;
      }
      // var_dump($args['id']);//子类评论id
      // exit;
      //根据子类id查询主评论信息
      $pinglun=$db->get('mcms_comments',['targetId','comment','id','pid'],['id'=>$args['id']]);
      // var_dump($pinglun);
      // exit;

      $list = $db->get('mcms_quan','*',[
        'id'=>$pinglun['targetId'],
      ]);
      // var_dump($list);
      // exit;

      //   //查询点赞表
      //   $zhan[$i]=$db->select('mcms_zhan',['uid','type'],['targetId'=>$list[$i]['id']]);
      //   if($zhan[$i]){
      //     $a=[];
      //     for($j=0;$j<count($zhan[$i]);$j++){
      //         if($zhan[$i][$j]['type']==1){
      //           $a[$i][$j]['name']=$db->get('member','name',['id'=>$zhan[$i][$j]['uid']]);
      //         }else{
      //           $a[$i][$j]['name']=$db->get('customs','name',['id'=>$zhan[$i][$j]['uid']]);

      //         }
      //     }
      //      $list[$i]['dz']=$a[$i];
      //      if(empty($list[$i]['dz'])){
      //       $list[$i]['dz']=[];
      //      }
      //     // echo "1";
      //   }
      //   //查询评论
        $pl=$db->select('mcms_comments',['comment','name','id','creattime'],['pid'=>$pinglun['id']]);
         if($pl){
          $b=[];
          for($j=0;$j<count($pl);$j++){
                $b[$j]['comment']=$pl[$j]['comment'];
                $b[$j]['name']=$pl[$j]['name'];
                $b[$j]['id']=$pl[$j]['id'];
                $b[$j]['creattime']=$pl[$j]['creattime'];
          }
           $lists=$b;
           if(empty($lists)){
            $lists='';
           }
          // echo "1";
        }

      // var_dump($lists);
      // exit;
      // var_dump($lists);
      // exit;
      $count = 1;
      $counts=1;
      // var_dump($counts);
      // exit;
      $qq=$db->select('qqfares','*');
      $as = [
      'u'=>$u,
      's'=>$s,
      'list'=>$list,
      'p'=>$p,
      'counts'=>$counts,
      'qq'=>$qq,
      'pinglun'=>$pinglun,
      'lists'=>$lists,
      ];
      return $this->app->renderer->render($response, './finder-zones-detail.php', $as);
    }


     //finderpl(朋友圈评论添加)
    public function finderpls($request, $response, $args){
      global $db;
      $u = $request->getAttribute('u');//用户
      $s = $request->getAttribute('s');//服务者
      if($s){
        $id=$s['id'];
      }else{
        if($u){
          $id=$u['id'];
        }else{
          $id=0;
        }
        
      }
      $targetid = $_POST['id'];
      $pid = $_POST['pid'];
      $text = $_POST['text'];
      $time = date('Y-m-d H:i:s');
      $name = $_POST['name'];
      //写入评论表
      $com=$db->insert('mcms_comments',[
          'pid'=>$pid,
          'uid'=>$id,//评论人的id
          'ctype'=>1,//资讯或圈子
          'targetId'=>$targetid,//关联id对应哪条信息
          'comment'=>$text,//内容
          'creattime'=>$time,//发表时间
          'name'=>$name,
          'iamread'=>1
        ]);
      // var_dump($targetid);
      // var_dump($pid);
      // exit;
      // //更新评论数
      $db->update('mcms_quan',[
        'comments[+]'=>1
      ],[
        'id'=>$targetid
      ]);

      if($com){
        $flag=200;
        $msg='评论成功';
      }else{
        $com=0;
        $flag=400;
        $msg='评论失败，数据错误';
      }
      $json = array('flag' =>$flag,'msg'=>$msg);
        return $response->withJson($json);
    }

    public function zonemsg($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $myq = $db->select('mcms_quan','id',[
              'AND'=>[
                'utype'=>0,
                'author'=>$s['id']
              ]
            ]);
      $list = $db->select('mcms_comments','*',[
            'AND'=>[
              'iamread'=>1,
              'targetId'=>$myq,
            ],'ORDER'=>['id'=>'DESC']
      ]);

      $list2 = $db->select('mcms_zhan','*',[
            'AND'=>[
              'iamread'=>1,
              'targetId'=>$myq,
            ],'ORDER'=>['id'=>'DESC']
      ]);

      $as = [
        's'=>$s,
        'list'=>$list,
        'list2'=>$list2,
      ];
      return $this->app->renderer->render($response, './finder-zones-msg.php', $as);
    }

    public function zonemsgs($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $myq = $db->select('mcms_quan','id',[
              'AND'=>[
                'utype'=>0,
                'author'=>$s['id']
              ]
            ]);
      $list = $db->select('mcms_comments','*',[
            'AND'=>[
              'targetId'=>$myq,
            ],
            'ORDER'=>['id'=>'DESC']
      ]);

      $as = [
        's'=>$s,
        'list'=>$list,
      ];
      return $this->app->renderer->render($response, './finder-zones-msgall.php', $as);
    }

    public function staffs($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      //获取URL传入参数
      $p = isset($request->getQueryParams()['p']) ? $request->getQueryParams()['p'] : 1;//分页
     
      $keyword = isset($request->getQueryParams()['keyword']) ? $request->getQueryParams()['keyword'] : '';//关键词
      $row = ($p * 32) - 32;


        $list = $db->select('member','*',[
          'AND'=>[
             'status'=>1,
            'OR'=>[
              // 'company[~]'=>$keyword,
              'name[~]'=>$keyword,
              'mobile[~]'=>$keyword
            ]
          ],
          'ORDER'=>[
            'views'=>'DESC'
          ],
          'LIMIT'=>[$row,32]
        ]);
        //统计总条数
        $count = $db->count('member',[
          'AND'=>[
             'status'=>1,
            'OR'=>[
              // 'company[~]'=>$keyword,
              'name[~]'=>$keyword,
              'mobile[~]'=>$keyword
            ]
          ]
        ]);
        $allp = ceil($count/32);
        $as = [
        's'=>$s,
        'list'=>$list,
        'p'=>$p,
        'allp'=>$allp,
        'count'=>$count,
        'keyword'=>$keyword,
      ];
      return $this->app->renderer->render($response, './finder-staffs.php', $as);
    }

    public function staffsNocheck($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      //获取URL传入参数
      $p = isset($request->getQueryParams()['p']) ? $request->getQueryParams()['p'] : 1;//分页
     
      $keyword = isset($request->getQueryParams()['keyword']) ? $request->getQueryParams()['keyword'] : '';//关键词
      $row = ($p * 32) - 32;


        $list = $db->select('member','*',[
          'AND'=>[
            'status[>]'=>1,
            // 'OR'=>[
            //   // 'company[~]'=>$keyword,
            //   'name[~]'=>$keyword,
            //   'mobile[~]'=>$keyword
            // ]
          ],
          'ORDER'=>[
            'views'=>'DESC'
          ],
          'LIMIT'=>[$row,32]
        ]);
        //统计总条数
        $count = $db->count('member',[
          'AND'=>[
            'status[>]'=>1,
            // 'OR'=>[
            //   // 'company[~]'=>$keyword,
            //   'name[~]'=>$keyword,
            //   'mobile[~]'=>$keyword
            // ]
          ]
        ]);
        $allp = ceil($count/32);
        $as = [
        's'=>$s,
        'list'=>$list,
        'p'=>$p,
        'allp'=>$allp,
        'count'=>$count,
        'keyword'=>$keyword,
      ];
      return $this->app->renderer->render($response, './finder-staffs-nocheck.php', $as);
    }

    public function staffDetailzone($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
    }

    public function staffDetailcomments($request, $response, $args){
       global $db;
      $s = $request->getAttribute('s');//服务者
      $id = $args['id'];
      
      if(isset($_GET['backuri']) && $_GET['backuri']!=''){
        $backuri = $_GET['backuri'];
      }else{
        $backuri = '/zone.html';
      }
      
      //根据服务者ID查询基本信息
      $member = $db->get('member','*',['id'=>$id]);
      $as = [
        's'=>$s,
        'member'=>$member
      ];
      return $this->app->renderer->render($response, './staff-detail-comments.php', $as);

    }
    public function staffDetailredpaper($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $id = $args['id'];
      
      if(isset($_GET['backuri']) && $_GET['backuri']!=''){
        $backuri = $_GET['backuri'];
      }else{
        $backuri = '/zone.html';
      }
      
      //根据服务者ID查询基本信息
      $member = $db->get('member','*',['id'=>$id]);
      $as = [
        's'=>$s,
        'member'=>$member
      ];
      return $this->app->renderer->render($response, './staff-detail-redp.php', $as);
    }
    public function staffDetailjl($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
    }
    public function staffDetailhy($request, $response, $args){
       global $db;
      $s = $request->getAttribute('s');//服务者
      $id = $args['id'];
      
      if(isset($_GET['backuri']) && $_GET['backuri']!=''){
        $backuri = $_GET['backuri'];
      }else{
        $backuri = '/zone.html';
      }
      
      //根据服务者ID查询基本信息
      $member = $db->get('member','*',['id'=>$id]);
      $as = [
        's'=>$s,
        'member'=>$member
      ];
      return $this->app->renderer->render($response, './staff-detail-hy.php', $as);
    }
    public function staffDetailinfo($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $id = $args['id'];
      
      if(isset($_GET['backuri']) && $_GET['backuri']!=''){
        $backuri = $_GET['backuri'];
      }else{
        $backuri = '/zone.html';
      }
      
      //根据服务者ID查询基本信息
      $member = $db->get('member','*',['id'=>$id]);
      $as = [
        's'=>$s,
        'member'=>$member
      ];
      return $this->app->renderer->render($response, './staff-detail-info.php', $as);
    }

    public function zuitop($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $day = isset($request->getQueryParams()['day']) ? $request->getQueryParams()['day'] : date('Y-m-d');//分页
      $as = [
        's'=>$s,
        'day'=>$day,
      ];
      return $this->app->renderer->render($response, './finder-zuitop.php', $as);
    }

    
    public function zuitopdetail($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $day = isset($request->getQueryParams()['day']) ? $request->getQueryParams()['day'] : date('Y-m-d');//分页
      $type = isset($request->getQueryParams()['type']) ? $request->getQueryParams()['type'] :1;//分页
      $month = date('Y-m',strtotime($day));
      // var_dump($month);
      $count = [];
      $countmonty = [];
      $member = $db->select('member','*',[
          'AND'=>[
            'subcompany[>]'=>0,
            'id[!]'=>[1,99]
          ]
          
        ]);
      if($type == 1){
        //服务者邀请排行榜
 
    
        $count['all'] = $db->count('member');
      
        $count['month'] = $db->count('member',[
            'AND'=>[
              'invoceMember[!]'=>[0,1,99],
              'creattime[>=]'=>date($month.'-1 00:00:00'),
              'creattime[<=]'=>date($month.'-31 23:59:59'),
            ]
          ]);

        $count['day'] = $db->count('member',[
            'AND'=>[
              'invoceMember[!]'=>[0,1,99],
              'creattime[>=]'=>date($day.' 00:00:00'),
              'creattime[<=]'=>date($day.' 23:59:59'),
            ]
          ]); 

        
        $i=0;
        foreach ($member as $key => $value) {
          $countmonty[$i]['id']=$value['id'];
          $countmonty[$i]['name']=$value['name'];
          $countmonty[$i]['avatar']=$value['avatar'];
          $countmonty[$i]['count'] = $db->count('member',[
            'AND'=>[
              'invoceMember'=>$value['id'],
              'creattime[>=]'=>date($month.'-1 00:00:00'),
              'creattime[<=]'=>date($month.'-31 23:59:59'),
            ]
          ]);
          $i++;
        }
        $listmonth = my_sort($countmonty,'count',SORT_DESC,SORT_NUMERIC);  
        
      }

      if($type == 2){
        //发掘销售机会排行榜
       
        $count['all'] = $db->count('boppo',[]);
      
        $count['month'] = $db->count('takenow',[
            'AND'=>[
              'class'=>1,
              'creattime[>=]'=>date($month.'-1 00:00:00'),
              'creattime[<=]'=>date($month.'-31 23:59:59'),
            ]
          ]);

        $count['day'] = $db->count('takenow',[
            'AND'=>[
              'class'=>1,
              'creattime[>=]'=>date($day.' 00:00:00'),
              'creattime[<=]'=>date($day.' 23:59:59'),
            ]
          ]);


        
        $i=0;
        foreach ($member as $key => $value) {
          $countmonty[$i]['id']=$value['id'];
          $countmonty[$i]['name']=$value['name'];
          $countmonty[$i]['avatar']=$value['avatar'];
          $countmonty[$i]['count'] = $db->count('takenow',[
            'AND'=>[
              'uid'=>$value['id'],
              'type'=>1,
              'class'=>1,
              'creattime[>=]'=>date($month.'-1 00:00:00'),
              'creattime[<=]'=>date($month.'-31 23:59:59'),
            ]
          ]);
          $i++;
        }
        $listmonth = my_sort($countmonty,'count',SORT_DESC,SORT_NUMERIC);  
 
      }

      if($type == 3){
        //职位推荐排行榜
        

        $count['all'] = $db->count('member_job',[
            'AND'=>[
              'ywuid[!]'=>[0,1,99],
            ]
          ]);
      
          $count['month'] = $db->count('member_job',[
            'AND'=>[
              'ywuid[!]'=>[0,1,99],
              'creattime[>=]'=>date($month.'-1 00:00:00'),
              'creattime[<=]'=>date($month.'-31 23:59:59'),
            ]
          ]);

          $count['day'] = $db->count('member_job',[
            'AND'=>[
              'ywuid[!]'=>[0,1,99],
              'creattime[>=]'=>date($day.' 00:00:00'),
              'creattime[<=]'=>date($day.' 23:59:59'),
            ]
          ]);

        
        $i=0;
        foreach ($member as $key => $value) {
          $countmonty[$i]['id']=$value['id'];
          $countmonty[$i]['name']=$value['name'];
          $countmonty[$i]['avatar']=$value['avatar'];
          $countmonty[$i]['count'] = $db->count('member_job',[
            'AND'=>[
              'ywuid'=>$value['id'],
              'creattime[>=]'=>date($month.'-1 00:00:00'),
              'creattime[<=]'=>date($month.'-31 23:59:59'),
            ]
          ]);
          $i++;
        }
        $listmonth = my_sort($countmonty,'count',SORT_DESC,SORT_NUMERIC);  
 
      }

      if($type == 4){
        //服务者服务排行榜
      
          $count['all'] = $db->count('contract',[
            'AND'=>[
              'status'=>[4,5],
            ]
          ]);
      
          $count['month'] = $db->count('contract',[
            'AND'=>[
              'status'=>[4,5],
            ]
          ]);

          $count['day'] = $db->count('contract',[
            'AND'=>[
              'status'=>[4,5],
            ]
          ]);
   
       

        
        $i=0;
        foreach ($member as $key => $value) {
          $countmonty[$i]['id']=$value['id'];
          $countmonty[$i]['name']=$value['name'];
          $countmonty[$i]['avatar']=$value['avatar'];
          $countmonty[$i]['count'] = $db->count('contract',[
            'AND'=>[
              'staffId'=>$value['id'],
              'status'=>[4,5],
            ]
          ]);
          $i++;
        }
        $listmonth = my_sort($countmonty,'count',SORT_DESC,SORT_NUMERIC);  
 
      }

      if($type == 5){
        //服务者服务排行榜
      
          $count['all'] = $db->count('boppo_go_log',['type'=>1]);
      
          $count['month'] = $db->count('boppo_go_log',[
            'AND'=>[
              'type'=>1,
              'creatTime[>=]'=>date($month.'-1 00:00:00'),
              'creatTime[<=]'=>date($month.'-31 23:59:59'),
            ]
          ]);

          $count['day'] = $db->count('boppo_go_log',[
            'AND'=>[
              'type'=>1,
              'creatTime[>=]'=>date($day.' 00:00:00'),
              'creatTime[<=]'=>date($day.' 23:59:59'),
            ]
          ]);
   
       
          $member2 = $db->select('member','*',[
          'AND'=>[
            'status'=>1,
            'id[!]'=>[1,99]
          ]
          
        ]);
        
        $i=0;
        foreach ($member2 as $key => $value) {
          $countmonty[$i]['id']=$value['id'];
          $countmonty[$i]['name']=$value['name'];
          $countmonty[$i]['avatar']=$value['avatar'];
          $countmonty[$i]['count'] = $db->count('boppo_go_log',[
            'AND'=>[
              'uid'=>$value['id'],
              'type'=>1,
              'creatTime[>=]'=>date($month.'-1 00:00:00'),
              'creatTime[<=]'=>date($month.'-31 23:59:59'),
            ]
          ]);
          $i++;
        }
        $listmonth = my_sort($countmonty,'count',SORT_DESC,SORT_NUMERIC);  
 
      }

      if($type == 6){
        //服务者小能手排行榜
      
          $count['all'] = $db->count('contract_speed',['type'=>'end']);
      
          $count['month'] = $db->count('contract_speed',[
            'AND'=>[
              'type'=>'end',
              'creattime[>=]'=>date($month.'-1  00:00:00'),
              'creattime[<=]'=>date($month.'-31  00:00:00'),
            ]
          ]);

          $count['day'] = $db->count('contract_speed',[
            'AND'=>[
              'type'=>'end',
              'creattime[>=]'=>date($day.' 00:00:00'),
              'creattime[<=]'=>date($day.' 23:59:59'),
            ]
          ]);
   
       
          $member2 = $db->select('member','*',[
          'AND'=>[
            'status'=>1,
            'id[!]'=>[1,99]
          ]
          
        ]);
        
        $i=0;
        foreach ($member2 as $key => $value) {
          $countmonty[$i]['id']=$value['id'];
          $countmonty[$i]['name']=$value['name'];
          $countmonty[$i]['avatar']=$value['avatar'];
          $countmonty[$i]['count'] = $db->count('contract_speed',[
            'AND'=>[
              'uid'=>$value['id'],
              'type'=>'end',
              'creattime[>=]'=>date($month.'-1  00:00:00'),
              'creattime[<=]'=>date($month.'-31  00:00:00'),
            ]
          ]);
          $i++;
        }
        $listmonth = my_sort($countmonty,'count',SORT_DESC,SORT_NUMERIC);  
 
      }

      if($type == 7){
        //服务者服务报价排行榜
      
          $count['all'] = $db->count('mcms_service_price',['member_id[!]'=>0]);
      
          $count['month'] = $db->count('mcms_service_price',[
            'AND'=>[
              'time[>=]'=>strtotime(date($month.'-1 00:00:00')),
              'time[<=]'=>strtotime(date($month.'-31 23:59:59')),
              'member_id[!]'=>0
            ]
          ]);

          $count['day'] = $db->count('mcms_service_price',[
            'AND'=>[
              'time[>=]'=>strtotime(date($day.' 00:00:00')),
              'time[<=]'=>strtotime(date($day.' 23:59:59')),
              'member_id[!]'=>0
            ]
          ]);
   
       
          $member2 = $db->select('member','*',[
          'AND'=>[
            'status'=>1,
            'id[!]'=>[1,99]
          ]
          
        ]);
        
        $i=0;
        foreach ($member2 as $key => $value) {
          $countmonty[$i]['id']=$value['id'];
          $countmonty[$i]['name']=$value['name'];
          $countmonty[$i]['avatar']=$value['avatar'];
          $countmonty[$i]['count'] = $db->count('mcms_service_price',[
            'AND'=>[
              'member_id'=>$value['id'],
              'time[>=]'=>strtotime(date($month.'-1 00:00:00')),
              'time[<=]'=>strtotime(date($month.'-31 23:59:59')),
            ]
          ]);
          $i++;
        }
        $listmonth = my_sort($countmonty,'count',SORT_DESC,SORT_NUMERIC);  
 
      }



      $as = [
        's'=>$s,
        'day'=>$day,
        'month'=>$month,
        'type'=>$type,
        'count'=>$count,
        'listmonth'=>$listmonth
      ];
      return $this->app->renderer->render($response, './finder-zuitop-detail.php', $as);
    }

    public function rankingday($request, $response, $args){
      global $db;
      $day = isset($request->getQueryParams()['day']) ? $request->getQueryParams()['day'] : date('Y-m-d');
      $month = date('Y-m',strtotime($day));
      $list = $db->select('orders','*',[
        'AND'=>[
                  'paytime[>=]'=>date($day.' 0:0:0'),
                  'paytime[<=]'=>date($day.' 23:59:59'),
                  'status'=>[1,2,3],
                  ],
                  'ORDER'=>[
            'paytime'=>'DESC'
          ]
        ]);
      //var_dump($list);
      $as = [
        'day'=>$day,
        'month'=>$month,
        'list'=>$list
      ];
      return $this->app->renderer->render($response, './finder-rankingday.php', $as);
    }

    public function rankingRechangeday($request, $response, $args){
      global $db;
      $day = isset($request->getQueryParams()['day']) ? $request->getQueryParams()['day'] : date('Y-m-d');
      $month = date('Y-m',strtotime($day));
      $list = $db->select('wallets_recharge','*',[
        'AND'=>[
          'utype'=>1,
          'status'=>1,
          'paytime[>=]'=>date($month.'-1 00:00:00'),
          'paytime[<=]'=>date($month.'-31 23:59:59'),
        ],
        'ORDER'=>[
          'paytime'=>'DESC'
        ]
      ]);
      $as = [
        'day'=>$day,
        'month'=>$month,
        'list'=>$list
      ];
      return $this->app->renderer->render($response, './finder-rankingRechangeday.php', $as);
    }

    public function rankingRenewday($request, $response, $args){
      global $db;
      $day = isset($request->getQueryParams()['day']) ? $request->getQueryParams()['day'] : date('Y-m-d');
      $month = date('Y-m',strtotime($day));
      $list = $db->select('contract_renew','*',[
            'AND'=>[
              'status'=>0,
              'creattime[>=]'=>date($month.'-1 0:0:0'),
              'creattime[<=]'=>date($month.'-31 23:59:59'),
            ],'ORDER'=>[
              'id'=>'DESC'
            ]
          ]);
      $as = [
        'day'=>$day,
        'month'=>$month,
        'list'=>$list
      ];
      return $this->app->renderer->render($response, './finder-rankingRenewday.php', $as);
    }

    public function toutiaoForm($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $u = $request->getAttribute('u');//客户
      if($s['id']==''){
        return $response->withRedirect('/loginassms.html');
      }
      $id = isset($request->getQueryParams()['id']) ? $request->getQueryParams()['id'] : '';
      if($id!=''){
        $detail = $db->get('mcms_posts','*',['id'=>$id]);
      }else{
        $detail = false;
      }
      $as = [
        'id'=>$id,
        'detail'=>$detail,
      ];
      return $this->app->renderer->render($response, './finder-toutiao-form.php', $as);
    }
    public function toutiaoSave($request, $response, $args){
      global $db;

      $s = $request->getAttribute('s');//服务者
      $u = $request->getAttribute('u');//客户
      
      $id = $_POST['id'];
      $title = $_POST['title'];
      $content = $_POST['content'];
      $data = [];

      if($id == ''){
        $db->insert('mcms_posts',[
          'title'=>$title,
          'content'=>$content,
          'author'=>$s['id'],
          'status'=>1,
          'creatTime'=>date('Y-m-d H:i:s')
        ]);
        $data['id'] = $db->id();
      }else{
        $db->update('mcms_posts',[
          'title'=>$title,
          'author'=>$s['id'],
          'content'=>$content,
          'status'=>1,
          'creatTime'=>date('Y-m-d H:i:s')
        ],[
          'id'=>$id
        ]);
        $data['id'] = $id;
      }

      $json = array('flag' => 200,'msg' => '已保存','data'=>$data);
      return $response->withJson($json);
    }

    public function toutiaoFormhavepic($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      $id = isset($request->getQueryParams()['id']) ? $request->getQueryParams()['id'] : '';
      if($id!=''){
        $detail = $db->get('mcms_posts','*',['id'=>$id]);
      }else{
        $detail = false;
      }
      $as = [
        'id'=>$id,
        'detail'=>$detail,
      ];
      return $this->app->renderer->render($response, './finder-toutiao-form-have.php', $as);
    }

    public function toutiaoSavehavapic($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
      
      $id = $_POST['id'];
      $tags = $_POST['tags'];
      $thumbnail = $_POST['thumbnail'];
      $data = [];

      
        $db->update('mcms_posts',[
          'tags'=>$tags,
          'thumbnail'=>$thumbnail,
        ],[
          'id'=>$id
        ]);
        $data['id'] = $id;
 

      $json = array('flag' => 200,'msg' => '已保存','data'=>$data);
      return $response->withJson($json);
    }

    public function todayJslist($request, $response, $args){
      global $db;
      $s = $request->getAttribute('s');//服务者
       $day = isset($request->getQueryParams()['day']) ? $request->getQueryParams()['day'] : date('Y-m-d');
      $list = $db->select('takenow','*',[
        'AND'=>[
          'class'=>1,
          'type'=>1,
          'creattime[>=]'=>date($day.' 0:0:0'),
          'creattime[<=]'=>date($day.' 23:59:59'),
        ],
        'ORDER'=>['uid'=>'ASC']
      ]);

      $as = [
        'day'=>$day,
        'list'=>$list,
      ];
      return $this->app->renderer->render($response, './finder-todayJslist.php', $as);
    }
}
