<?php

/**
  * wechat php test
  */
//define your token
//定义token密钥
define("TOKEN", "weixin");
//实例化对象
$wechatObj = new wechatCallbackapiTest();
//调用valid验证方法，只有验证时需要开启此函数，验证成功后，需要关闭此函数
//$wechatObj->valid();
//开启自动回复接口
$wechatObj->responseMsg();

//定义wechatCallbackapiTest类
class wechatCallbackapiTest
{
	//定义valid验证方法
	public function valid()
    {
		//接收随机字符串
        $echoStr = $_GET["echostr"];

        //valid signature , option
		//调用checkSignature方法，此方法返回布尔类型的值
        if($this->checkSignature()){
			//返回输出随机字符串
        	echo $echoStr;
			//强制中止代码段的执行
        	exit;
        }
    }

    //定义responseMsg自动回复接口
    public function responseMsg()
    {
		//get post data, May be due to the different environments
		//接收客户端传递过来的XML格式的数据
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

      	//extract post data
		if (!empty($postStr)){
                /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
                   the best way is to check the validity of xml by yourself */
				//解析XML时不解析XML实体，防止XXE攻击
                libxml_disable_entity_loader(true);
                //XML解析，生成SimpleXML对象
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
              	//获取客户端手机的openid(类似网卡的mac值，唯一的）
                $fromUsername = $postObj->FromUserName;
                //获取微信公众平台账号gh_微信码
                $toUsername = $postObj->ToUserName;
                //获取接收消息的类型MsgType（严格注意大小写）
                $msgType = $postObj->MsgType;
                //接收经纬度信息
                $latitude = $postObj->Location_X;
                $longitude = $postObj->Location_Y;
                //获取语音识别后的结果
                $rec = $postObj->Recognition;
                //获取用户发送过来的关键词
                $keyword = trim($postObj->Content);
                //时间戳
                $time = time();
                //定义文本回复模板
                $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";
                //定义音乐回复模板
                $musicTpl = "<xml>
							 <ToUserName><![CDATA[%s]]></ToUserName>
							 <FromUserName><![CDATA[%s]]></FromUserName>
							 <CreateTime>%s</CreateTime>
							 <MsgType><![CDATA[%s]]></MsgType>
							 <Music>
							 <Title><![CDATA[%s]]></Title>
							 <Description><![CDATA[%s]]></Description>
							 <MusicUrl><![CDATA[%s]]></MusicUrl>
							 <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
							 </Music>
							</xml>";
                //定义图文回复模板
                $newsTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<ArticleCount>%s</ArticleCount>
							%s
							</xml>";
                //判断用户发送的消息类型MsgType
				if($msgType=='text') {
					//判断用户发送的关键词是否为空
					if(!empty( $keyword ))
					{
						if($keyword=='?' || $keyword=='？') {
							//以text文本形式返回数据到客户端
							$msgType = "text";
							//定义回复内容
							$contentStr = "感谢您关注简易号码簿，请输入【】中的内容：\n【1】特种服务号码\n【2】通讯服务号码\n【3】银行服务号码\n【4】用户反馈";
							//格式化XML字符串
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							//输出返回XML数据到客户端
							echo $resultStr;
						} elseif($keyword=='1') {
							//以text文本形式返回数据到客户端
							$msgType = "text";
							//定义回复内容
							$contentStr = "常用特种服务号码：\n匪警：110\n火警：119\n急救：120";
							//格式化XML字符串
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							//输出返回XML数据到客户端
							echo $resultStr;
						} elseif($keyword=='2') {
							//以text文本形式返回数据到客户端
							$msgType = "text";
							//定义回复内容
							$contentStr = "常用通讯服务号码：\n中移动：10086\n中电信：10000\n中联通：10010";
							//格式化XML字符串
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							//输出返回XML数据到客户端
							echo $resultStr;
						} elseif($keyword=='3') {
							//以text文本形式返回数据到客户端
							$msgType = "text";
							//定义回复内容
							$contentStr = "常用银行服务号码：\n建设：95533\n工商：99588\n农业：95599";
							//格式化XML字符串
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							//输出返回XML数据到客户端
							echo $resultStr;
						} elseif($keyword=='4') {
							//以text文本形式返回数据到客户端
							$msgType = "text";
							//定义回复内容
							$contentStr = "尊敬的用户，为了更好的为您服务，请将系统的不足之处反馈给我们。\n反馈格式：@+建议内容\n例如：@希望增加***号码";
							//格式化XML字符串
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							//输出返回XML数据到客户端
							echo $resultStr;
						} elseif(strpos($keyword,'@')===0) {
							//以text文本形式返回数据到客户端
							$msgType = "text";
							//定义回复内容
							$contentStr = "感谢您的宝贵建议，我们会努力为您提供更好的服务！";
							//格式化XML字符串
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							//输出返回XML数据到客户端
							echo $resultStr;
						} elseif($keyword=='音乐') {
							//以音乐形式返回数据到客户端
							$msgType = "music";
							//定义音乐标题
							$title = "速度与激情7";
							//定义音乐描述
							$desc = "速度与激情原声大碟...";
							//定义音乐地址
							$url = "http://czbk888.duapp.com/music.mp3";
							//定义高清音乐地址
							$hqurl = "http://czbk888.duapp.com/music.mp3";
							//格式化XML格式的数据
							$resultStr = sprintf($musicTpl, $fromUsername, $toUsername, $time, $msgType, $title, $desc, $url, $hqurl);
							//输出返回字符串到客户端
							echo $resultStr;
						} elseif($keyword=='单图文') {
							//定义回复类型为图文回复
							$msgType = "news";
							//定义图文回复数量
							$count = 1;
							//组装第三个参数
							$str = '<Articles>';
							for($i=1;$i<=$count;$i++) {
								$str .= "<item>
										 <Title><![CDATA[微信开发教程]]></Title>
										 <Description><![CDATA[和龙哥学习微信开发...]]></Description>
										 <PicUrl><![CDATA[http://czbk.sinaapp.com/images/{$i}.jpg]]></PicUrl>
										 <Url><![CDATA[http://czbk.sinaapp.com/]]></Url>
										 </item>";
							}
							$str .= '</Articles>';
							//格式化XML格式的数据
							$resultStr = sprintf($newsTpl, $fromUsername, $toUsername, $time, $msgType, $count, $str);
							//返回输出格式化后的字符串到客户端
							echo $resultStr;
						} elseif($keyword=='多图文') {
							//定义回复类型为图文回复
							$msgType = "news";
							//定义图文回复数量
							$count = 4;
							//组装第三个参数
							$str = '<Articles>';
							for($i=1;$i<=$count;$i++) {
								$str .= "<item>
								<Title><![CDATA[微信开发教程]]></Title>
								<Description><![CDATA[和龙哥学习微信开发...]]></Description>
								<PicUrl><![CDATA[http://czbk.sinaapp.com/images/{$i}.jpg]]></PicUrl>
								<Url><![CDATA[http://czbk.sinaapp.com/]]></Url>
								</item>";
							}
							$str .= '</Articles>';
							//格式化XML格式的数据
							$resultStr = sprintf($newsTpl, $fromUsername, $toUsername, $time, $msgType, $count, $str);
							//返回输出格式化后的字符串到客户端
							echo $resultStr;
						} else {
							//以text文本形式返回数据到客户端
							$msgType = "text";

							//定义url地址
							$url = "http://www.tuling123.com/openapi/api?key=9009fc44f168cfc7055c8a469821ce9b&info={$keyword}";
							//通过file_get_contents发送get请求
							$str = file_get_contents($url);
							//获取json对象
							$json = json_decode($str);
							//获取返回内容
							$contentStr = $json->text;

							//定义回复内容

							//格式化XML字符串
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							//输出返回XML数据到客户端
							echo $resultStr;
						}
					}else{
						echo "Input something...";
					}
				} elseif($msgType=='image') {
					//以text文本形式返回数据到客户端
					$msgType = "text";
					//定义回复内容
					$contentStr = "您发送的是图片消息，图片真漂亮！";
					//格式化XML字符串
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					//输出返回XML数据到客户端
					echo $resultStr;
				} elseif($msgType=='voice') {
					//以text文本形式返回数据到客户端
					$msgType = "text";

					//定义url地址
					$url = "http://www.tuling123.com/openapi/api?key=9009fc44f168cfc7055c8a469821ce9b&info={$rec}";
					//通过file_get_contents发送get请求
					$str = file_get_contents($url);
					//获取json对象
					$json = json_decode($str);
					//获取返回内容
					$contentStr = str_replace("<br>", "", $json->text);

					//定义回复内容

					//格式化XML字符串
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					//输出返回XML数据到客户端
					echo $resultStr;
				} elseif($msgType=='video' || $msgType=='shortvideo') {
					//以text文本形式返回数据到客户端
					$msgType = "text";
					//定义回复内容
					$contentStr = "您发送的是视频消息，不会是大片吧！";
					//格式化XML字符串
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					//输出返回XML数据到客户端
					echo $resultStr;
				} elseif($msgType=='location') {
					//以text文本形式返回数据到客户端
					$msgType = "text";

					//定义一个请求的url地址
					$url = "http://api.map.baidu.com/telematics/v3/reverseGeocoding?location={$longitude},{$latitude}&coord_type=gcj02&output=json&ak=2pReiGS2nQV9Gi7tslO9r2UZ";
					//模拟发送get请求
					$str = file_get_contents($url);
					//把json格式字符串转化对象或数组
					$json = json_decode($str);
					//输出当前地理位置的详细信息
					$addr = $json->description;

					//定义回复内容
					$contentStr = "您发送的是地理位置消息，位置如下：{$addr}";
					//格式化XML字符串
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					//输出返回XML数据到客户端
					echo $resultStr;
				} elseif($msgType=='link') {
					//以text文本形式返回数据到客户端
					$msgType = "text";
					//定义回复内容
					$contentStr = "您发送的是链接消息，感谢分享，好人！";
					//格式化XML字符串
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					//输出返回XML数据到客户端
					echo $resultStr;
				}
        }else {
        	echo "";
        	exit;
        }
    }

	//定义checkSignature方法
	private function checkSignature()
	{
        // you must define TOKEN by yourself
		//如果没有定义TOKEN密钥，抛出一个异常
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

		//接收三个参数微信加密签名、时间戳、随机数
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

		//把密钥常量赋值给$token
		$token = TOKEN;
		//把以下三个参数组装成数组
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		//按字典法排序（a-z）
		sort($tmpArr, SORT_STRING);
		//把数组组装成字符串
		$tmpStr = implode( $tmpArr );
		//把字符串通过哈希算法进行加密（类似md5）
		$tmpStr = sha1( $tmpStr );
		//验证加密后的字符串与微信加密签名，如果一致返回true，否则返回false
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

?>