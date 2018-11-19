<?php
class Token
{
    // 入口方法 外面调用此接口是 先经过getToken()
    public static function getToken()
    {
        global $db;// 判断缓存的 合法
        $t = mktime();
        $has = $db->get('wx_token',['token','time']);
        if($has && $t - $has['time'] < 1800){
            return $has['token'];
        }else{
            // 请求token
            $res = self::requestToken();
            // 写入
            $db->update('wx_token',[
                'token'=>$res,
                'time'=>mktime(),
                'renewtime'=>date('Y-m-d H:i:s'),
                'app'=>'WECHAT'
                ],['id'=>1]);
            return $res;
        }
    }

    // 请求的方法
    public static function requestToken()
    {
        //至上会计服务号
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx9f2d1785175d240a&secret=2aacbdec8fbe702af7a4747cc838ea2a';

        $res = https_request($url,'');
        // json 转为数组
        $res = json_decode($res, true);
        $token = $res['access_token'];

        if (!empty($token)) {
            return $token;
        } else {
            return false;
        }
    }

}