<?php 
namespace Home\Controller;
use Think\Controller;
class ApiController extends	Controller{
	//token验证
	public function msgRouting(){
		$timestamp = $_GET['timestamp'];
		$nonce = $_GET['nonce'];
		$token = "weixin";
		$signature = $_GET['signature'];
		$array = array($timestamp,$nonce,$token);
		//排序
		sort($array);
		
		//2.将排序后的三个参数拼接后用sha1加密
		$tmpstr = implode('',$array);
		$tmpstr = sha1($tmpstr);
		
		//3. 将加密后的字符串与 signature 进行对比, 判断该请求是否来自微信
		if($tmpstr == $signature)
		{
			echo $_GET['echostr'];
			exit;
		}
	}
	
	//获取AccessToken
	public function getAccessToken(){
		$appid = "wx7f90a0e70710f7c7";
		$appsecret = "1d6d9f3050b67d6d6cfce0314dfd49b5 ";
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
		$res = myCurl($url);
		$jsoninfo = json_decode($res, true);
		return $jsoninfo['access_token'];
	}
	
	//创建自定义菜单
	public function creatMenu(){

$jsonmenu = '{
      "button":[
      {
            "name":"天气预报",
           "sub_button":[
            {
               "type":"click",
               "name":"北京天气",
               "key":"天气北京"
            },
            {
               "type":"click",
               "name":"上海天气",
               "key":"天气上海"
            },
            {
               "type":"click",
               "name":"广州天气",
               "key":"天气广州"
            },
            {
               "type":"click",
               "name":"深圳天气",
               "key":"天气深圳"
            },
            {
                "type":"view",
                "name":"本地天气",
                "url":"http://m.hao123.com/a/tianqi"
            }]
      

       },
       {
           "name":"方倍工作室",
           "sub_button":[
            {
               "type":"click",
               "name":"公司简介",
               "key":"company"
            },
            {
               "type":"click",
               "name":"趣味游戏",
               "key":"游戏"
            },
            {
                "type":"click",
                "name":"讲个笑话",
                "key":"笑话"
            }]
       

       }]
 }';


$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->getAccessToken();
$result = https_request($url, $jsonmenu);
var_dump($result);

function https_request($url,$data = null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}
	}
		

}