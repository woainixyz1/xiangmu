<?php 
	/**
	* 微信相关控制器
	*/
	namespace Home\Controller;
	use Think\Controller;
	use Think\Model;
	class WechatController extends Controller
	{
		//oauth参数
		private $code;
		private $state;
		private $acces_stoken;
		private $openid;
		//消息回复参数
		private $post_data;				//微信原始post数据   index.php/home/Wechat/msgRouting
		private $post_data_obj;			//微信post数据对象
		private $toUserName;			//消息接收方 
		private $fromUserName;			//消息发送方
		private $createTime;			//消息创建时间
		private $msgType;				//消息类型
		private $domain = 'http://www.oy1688.com';
		/**
		 * 微信消息路由
		 */
		public function msgRouting(){
			$signature = I("get.signature");	//获取签名
			$timestamp = I("get.timestamp");	//获取时间戳
			$nonce = I("get.nonce");
			$token = "2alsx2czbgnbx5qzac42sjhrvrtveoex"; //通讯TOKEN////tmpvhb5wyixqjz23qirffrbytgpbkpio/

			$tmpArr = array($token, $timestamp, $nonce);
			sort( $tmpArr );					//进行升降排序	sort();
			$tmpStr = implode( "",$tmpArr);		//把数组元素组合为字符串 implode();
			$tmpStr = sha1( $tmpStr );			//计算$tmpstr的sha1散列	sha1();
			
			if( $tmpStr == $signature && I('get.echostr')){
				echo $_GET['echostr'];//token验证
				exit;
			}
			
			$this->post_data   = $GLOBALS['HTTP_RAW_POST_DATA'];
			//消息路由
			$this->post_data_obj = simplexml_load_string($this->post_data);
			$this->toUserName    = $this->post_data_obj->ToUserName;
			$this->fromUserName  = $this->post_data_obj->FromUserName;
			$this->createTime    = $this->post_data_obj->CreateTime;
			$this->msgType       = strtolower($this->post_data_obj->MsgType);
			settype($this->toUserName, "string");		//改变类型为string字符串类型
			settype($this->fromUserName, "string");	
			settype($this->createTime, "string");
			settype($this->msgType, "string");
			switch ($this->msgType) {	//判断消息类型
					//普通消息
				case 'text':		//文本
				case 'image':		//图片
				case 'voice':		//声音	
				case 'video':		//视频
				case 'shortvideo':	//短视频
				case 'location':	//位置
				case 'link':		//链接
				echo $this->reNormalMsg();	//跳转
				break;				
					//事件消息
				case 'event':		//事件
				echo $this->reEventMsg();	//跳转
				break;
				default:			
				$this->unReply();
				break;
			}
		}
		/**
		 * 微信普通消息处理
		 * @param String $this->msgType 微信消息类型
		 */
		protected function reNormalMsg(){
			if($this->msgType=="text"){		//文本消息回复
				switch ($this->post_data_obj->Content) {	//根据内容判断回复信息
					case '你好':
					$content = "你好，欢迎光临黄埔微商学院";
					break;
					case '我的学籍卡':
						//$content = "你好，我的学籍卡";
					return $this->sendMyPoster();			//返回我的海报
					break;
					default:
					$this->unReply();
					break;
				}
			}else if($this->msgType=="image"){//回复图片消息
				$this->unReply();
			}else if($this->msgType=="voice"){//回复语音消息
				$this->unReply();
			}else if($this->msgType=="video"){//回复视频消息
				$this->unReply();
			}else if($this->msgType=="music"){//回复音乐消息
				$this->unReply();
			}else if($this->msgType=="news"){//回复图文消息
				$this->unReply();
			}
			return $this->reTextMsg($content);
		}
		/**
		 * 微信事件消息处理
		 * @return [type]
		 */
		protected function reEventMsg(){
			switch (strtolower($this->post_data_obj->Event)) {	//根据事件判断回复
				case 'subscribe':						//订阅
				return $this->reSubscribe();
				break;
				case 'unsubscribe':						//取消订阅
				$this->unsubscribe();
				break;
				case 'scan':							//扫描
				return $this->reSubscribe();
				break;
				case 'click':							//点击
				return $this->menuclick();
				break;
				default:
				$this->unReply();
				break;
			}
		}
		//没看懂
		private function menuclick(){				//菜单按钮
			if($this->post_data_obj->EventKey=='001'){
				$waittext = M('poster')->where(array('isdefault'=>1))->getField('waittext');	//获取字段
				$this->sendCustomNotice($this->fromUserName, htmlspecialchars_decode($waittext));	//解码
				return $this->sendMyPoster();	//\Think\Log::record('CLICK::::');
			}
		}
		/**
		 * 微信关注事件回复
		 */
		protected function reSubscribe(){
			//判断是否二维码扫描关注
			$this->recordScan();

			if($this->post_data_obj->EventKey!=""){
				return $this->QRSubscribe();
			}else{
				if(!M("member")->where("openid='{$this->fromUserName}'")->select()){
					$this->createMember(0);
				}
				$this->saveUserInfo($this->fromUserName);
				$this->sendfollowNotic();
				return $this->unReply();//直接关注
			}
		}

		/**
		 * 二维码扫描事件
		 * @param String $this->post_data_obj->EventKey 二维码参数
		 */
		protected function QRSubscribe(){
			$agentid = $this->getAgentid();

			if(!intval($agentid)){
				$this->sendfollowNotic();
				return $this->unReply();
			}

			if (strstr($agentid, 'article_')) {
				$this->articleSubscribe($agentid);
				return $this->unReply();
			}

			$member = M("member");
			$res    = $member->where("id={$agentid}")->find();

			if(!$res){
				return $this->reTextMsg("推荐人不存在");
			}

			$map['openid'] = $this->fromUserName;
			$res = $member->where($map)->find();

			if($res){
				if($res['agentid']!=0){
					return $this->reTextMsg("你已经有推荐人");
				}

				if($res['id'] == $agentid){
					return $this->reTextMsg("你不能成为自己的推荐人");
				}

				$data['agentid'] = $agentid;
				$map['openid']   = $this->fromUserName;
				if($member->where($map)->data($data)->save()){
					return $this->sendNotic();
				}else{
					return $this->reTextMsg("操作失败");
				}
			}else{
				$result = $this->createMember($agentid);
				$info   = $this->saveUserInfo($this->fromUserName);
				if($result){
					return $this->sendNotic();
				}else{
					return $this->reTextMsg("操作失败");
				}
			}
		}

		private function articleSubscribe($sign){
			//扫描文章关注
			//判断该用户是否已关注
			if (M('member')->where(array('openid'=>$this->openid))->find()) {
				return false;
			}else{
				if ($res = M('article_qrcode')->where(array('sign'=>$sign))->find()) {
					if (M('article_subscribe_log')->where(array('openid'=>$this->openid,'article_openid'=>$res['openid'],'article_id'=>$res['id']))->find()) {
						return false;
					}else{
						$setting = M('article_setting')->find();
						M('article_subscribe_log')->data(array('openid'=>$this->openid,'article_openid'=>$res['openid'],'article_id'=>$res['id'],'sign'=>$sign,'time'=>time(),'money'=>$setting['subscribe']))->add();
					}
				}else{
					return false;
				}
			}
		}

		private function getAgentid(){
			if(strpos($this->post_data_obj->EventKey, "scene_")){
				$agentid  = substr($this->post_data_obj->EventKey, strpos($this->post_data_obj->EventKey, "qrscene_")+strlen("qrscene_"));//未关注
			}else{
				$agentid  = $this->post_data_obj->EventKey; //已关注
				settype($agentid, "string");
			}
			return $agentid;
		}

		private function recordScan(){
			$qr_record        = M('qr_record');
			$data['openid']   = $this->fromUserName;
			$data['agentid']  = $this->post_data_obj->EventKey."";
			$data['ticket']   = $this->post_data_obj->Ticket."";
			$data['add_time'] = time();
			$qr_record->data($data)->add();
		}

		private function createMember($agentid){
			$data['openid']   = $this->fromUserName;
			$data['agentid']  = $agentid;
			$data['username'] = getGuid();
			$data['app_key']  = getGuid();
			$data['add_time'] = time();
			return M("member")->add($data);
		}

		private function sendNotic(){
			$poster = M('poster')->where(array('isdefault'=>1))->find();
			if(!$poster){
				return '欢迎关注黄埔商学院';
			}
			
			$qrmember = M('member')->where(array('id'=>$this->getAgentid()))->find();
			$member   = M('member')->where(array('openid'=>$this->fromUserName))->find();

			if(!empty($poster['entrytext'])){
				$entrytext = $poster['entrytext'];
				$entrytext = str_replace("[nickname]", $qrmember['nickname'], $entrytext);
				$entrytext = str_replace("[uid]", intval($member['id'])+362000, $entrytext);
				$entrytext = str_replace("[openid]", $member['openid'], $entrytext);
				$this->sendCustomNotice($this->fromUserName, htmlspecialchars_decode($entrytext));
			}

			if (!empty($poster['subtext']) && $qrmember) {
				$subtext = $poster['subtext'];
				$subtext = str_replace("[nickname]", $member['nickname'], $subtext);
				$subtext = str_replace("[credit]", $poster['reccredit'], $subtext);
				$subtext = str_replace("[money]", $poster['recmoney'], $subtext);
				$subtext = str_replace("[uid]", intval($member['id'])+362000, $subtext);
				$this->sendCustomNotice($qrmember['openid'], htmlspecialchars_decode($subtext));
			}
			return $this->unReply();//如果还需要额外的信息，可以添加消息，比如给客服或管理员发送提醒消息。
		}

		private function sendfollowNotic(){
			$poster = M('poster')->where(array('isdefault'=>1))->find();
			if(!$poster){
				return '欢迎关注黄埔商学院';
			}
			$member   = M('member')->where(array('openid'=>$this->fromUserName))->find();
			if (!empty($poster['followtxt'])) {
				$followtxt = $poster['followtxt'];
				$followtxt = str_replace("[nickname]", $member['nickname'], $followtxt);
				$followtxt = str_replace("[credit]", $poster['reccredit'], $followtxt);
				$followtxt = str_replace("[money]", $poster['recmoney'], $followtxt);
				$followtxt = str_replace("[uid]", intval($member['id'])+362000, $followtxt);
				$followtxt = str_replace("[openid]", $member['openid'], $followtxt);
				$this->sendCustomNotice($member['openid'], htmlspecialchars_decode($followtxt));
			}
		}

		public function sendMyPoster(){
			$openid = $this->fromUserName;

			$index = A("Index");
			$url = $index->getQrImg($openid);

			$resp = $this->addNewsImg($url);

			$content = @json_decode($resp, true);
			$media_id  = $content['media_id'];
   			//发送图片
			$template = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[image]]></MsgType><Image><MediaId><![CDATA[%s]]></MediaId></Image></xml>";
			$s = sprintf($template, $this->fromUserName, $this->toUserName, time() , $media_id);
   			//\Think\Log::record($s);
			return $s;
		}

		public function addNewsImg($file){//添加文章中要用的图片，返回URL
			$access_token = $this->getAccessToken();
			$data=array("media"=>"@".$file);
			//$url="https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".$access_token;
			$url="https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$access_token}&type=image";
			return $this->curl_wechat($url, true, $data);
		}

		private function curl_wechat($url,$post=false,$poststr=""){//CURL微信发包封装
			$ch=curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			if($post==true){
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $poststr);
			}
			$result=curl_exec($ch);
			curl_close($ch);
				// echo $result;
			return $result;
		}

		/**
		 * 回复文本消息
		 */
		protected function reTextMsg($content){
			$template= "<xml> <ToUserName><![CDATA[%s]]></ToUserName> <FromUserName><![CDATA[%s]]></FromUserName> <CreateTime>%s</CreateTime> <MsgType><![CDATA[text]]></MsgType> <Content><![CDATA[%s]]></Content> </xml>";
			return sprintf($template,$this->fromUserName,$this->toUserName,time(),$content);
		}
		/**
		 * 不处理
		 */
		protected function unReply(){
			echo "success";
			exit;
		}

		private function unsubscribe(){
			M('member')->where(array('openid'=>$this->fromUserName))->save(array('follow'=>0, 'unfollowtime'=>time()));
			echo "success";
			exit;
		}

		/**
		 * 微信Oauth授权调用方法
		 * @param String $code GET传入code
		 * @param String $state 自定义参数
		 */
		public function OAuth($code, $state){
			$this->code  = $_GET['code'];
			$this->state = $_GET['state'];
			$result      = $this->getAccessTokenByCode();
			$result      = json_decode($result);

			if($result->access_token != null){
				$this->access_token = $result->access_token;
				$this->openid       = $result->openid;
				$result             = $this->getUserInfoByAccessToken();
				$user_info          = json_decode($result,true);

				if(M('oauth')->where("openid ='{$this->openid}'")->select()){
					if(M('oauth')->where("openid ='{$this->openid}'")->save($user_info)){
						$this->ajaxReturn(array("code" => 3, "msg" => "授权成功！已更新！"));
					}else{
						$this->ajaxReturn(array("code" => 4, "msg" => "授权成功！未更新！"));
					}
				}else{
					if(M('oauth')->data($user_info)->add()){
						$this->ajaxReturn(array("code" => 1, "msg" => "授权成功！添加信息成功！"));
					}else{
						$this->ajaxReturn(array("code" => 2, "msg" => "授权成功！添加信息失败！"));
					}
				}
			}else{
				$this->ajaxReturn(array("code"=>0, "msg"=>"授权失败：<br/>错误码：{$result->errcode}<br/>错误信息：{$result->errmsg}"));
			}
		}

		public function sendMsgByPost(){
			$content   = array("content"=>urlencode(I("post.content")));
			$id        = I("post.id");
			$map['id'] = $id;
			$res       = M("member")->where($map)->find();
			if($res['openid']!=null){
				$res = $this->sendCustomerServiceMsg($res['openid'], "text", $content);
				$res=json_decode($res);
				if($res->errcode==0){
					$this->ajaxReturn(array("code"=>1,"msg"=>"消息发送成功"));
				}else{
					$this->ajaxReturn(array("code"=>0,"msg"=>"消息发送失败\n错误码：{$res->errcode}\n错误提示：{$res->errmsg}"));
				}
			}else{
				$this->ajaxReturn(array("code"=>0,"msg"=>"openid不存在"));
			}
		}

		public function sendCustomNotice($openid, $content){
			$content   = array("content"=>urlencode($content));
			$res = $this->sendCustomerServiceMsg($openid, "text", $content);
		}

		public function sendMsg($id, $content){
			$content   = array("content"=>urlencode($content));
			$map['id'] = $id;
			$res       = M("member")->where($map)->find();
			if($res['openid']!=null){
				$res = $this->sendCustomerServiceMsg($res['openid'], "text", $content);
				$res=json_decode($res);
				if($res->errcode==0){
					return array("code"=>1,"msg"=>"消息发送成功");
				}else{
					return array("code"=>0,"msg"=>"消息发送失败\n错误码：{$res->errcode}\n错误提示：{$res->errmsg}");
				}
			}else{
				return array("code"=>0,"msg"=>"openid不存在");
			}
		}

		protected function sendCustomerServiceMsg($openid,$msgtype,$content){//发送客服消息
			$url="https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$this->getAccessToken();
			$arr=array("touser"=>$openid, "msgtype"=>$msgtype, $msgtype=>$content);
			$post=json_encode($arr);
			return curl_wechat($url,true,$post);
		}

		public function menu(){
			$appid = "";
			$redirect_uri = $this->domain."/index.php/home/api/wechatlogin/";
			$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=". C('appid') ."&redirect_uri=".urlencode($redirect_uri)."&response_type=code&scope=snsapi_userinfo&state=weixin#wechat_redirect";
			
			$sub_button =  array();
			$sub_button[] = array( "type"=>"click", "name"=>urlencode("我的学籍卡"), "key" => "001");

			$member_sub_button = array();
			$member_sub_button[] =  array( "type"=>"view", "name"=>urlencode("学员中心"), "url"=>$this->domain."/index.php/home/index/member");
			$member_sub_button[] =  array( "type"=>"view", "name"=>urlencode("微信营销"), "url"=>"http://www.oy1688.com/index.php/Home/index");
			$member_sub_button[] =  array( "type"=>"view", "name"=>urlencode("新手指南"), "url"=>"http://mp.weixin.qq.com/s?__biz=MzIyOTI0NDU4OA==&mid=100000018&idx=1&sn=8e3813bce820735ce9df575bcba4abb1&scene=0#wechat_redirect");

			$array  = array(
				array("name"=>urlencode("①立即报道"),"type"=>"view","url"=>$this->domain."/index.php/Home/index/showteam"),
				array("name"=>urlencode("②学籍卡"),"sub_button"=>$sub_button),
				array("name"=>urlencode("③学员中心"),"sub_button"=>$member_sub_button)
			);

			$res    = $this->createMenu($array);
			$res    = json_decode($res);

			if($res->errcode == 0){
				echo "创建菜单成功！";
			}else{
				echo "创建菜单失败！错误码：{$res->errcode}，错误信息：{$res->errmsg}";
			}
		}

		public function getMenu(){
			$url = "https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token=".$this->getAccessToken();
			$result=curl_wechat($url);
			dump($result);
		}

		protected function getAccessToken(){

			$url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".C("APPID")."&secret=".C("APPSECRET");
			$access_token=M("access_token");
			$res=$access_token->select();
			if($res){
				if(time()-intval($res[0]['time'])>7000){
					$result=curl_wechat($url);
					$obj=json_decode($result);
					if($obj->access_token!=null){
						$access_token->accesstoken=$obj->access_token;
						$access_token->time=time();
						$access_token->where("id={$res[0]['id']}")->save();
						return $obj->access_token;
					}else{
						return "";
					}
				}else{
					return $res[0]['accesstoken'];
				}
			}else{
				$result = curl_wechat($url);
				$obj=json_decode($result);
				if($obj->access_token!=null){
					$access_token->accesstoken=$obj->access_token;
					$access_token->time=time();
					$access_token->add();
					return $obj->access_token;
				}else{
					return "";
				}
			}
		}
		
		protected function saveUserInfo($openid){
			$userinfo = $this->getUserInfo($openid);
			$userinfo = json_decode($userinfo);
			if($userinfo->subscribe==1){
				$data['nickname'] = $userinfo->nickname;
				$data['avatar']   = $userinfo->headimgurl;
				$data['follow']	  = 1;
				$data['followtime'] = time();
				$map['openid'] = $openid;
				M("member")->where($map)->data($data)->save();
				return $data;
			}
			return false;
		}

		protected function getUserInfo($openid){//获取用户基本信息
			$url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->getAccessToken()."&openid=".$openid."&lang=zh_CN";
			return curl_wechat($url);
		}

		public function checkMenu(){//获取菜单
			$url="https://api.weixin.qq.com/cgi-bin/menu/get?access_token=".$this->getAccessToken();
			echo curl_wechat($url);
		}

		protected function createMenu($arr_menu){ //创建菜单
			$url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->getAccessToken();
			$menu=array("button"=>$arr_menu);
			$createstr=json_encode($menu);
			return curl_wechat($url,true,$createstr);
		}

		protected function getUserInfoByAccessToken(){
			$url="https://api.weixin.qq.com/sns/userinfo?access_token=".$this->access_token."&openid=".$this->openid."&lang=zh_CN";
			return curl_wechat($url);
		}

		protected function getAccessTokenByCode($grant_type=""){
			$url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".C("appid")."&secret=".C("appsecret")."&code=".$this->code."&grant_type=authorization_code";
			if($grant_type!=""){
				$url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".C("appid")."&secret=".C("appsecret")."&code=".$this->code."&grant_type=".$grant_type;
			}
			return curl_wechat($url);
		}

	}
	?>