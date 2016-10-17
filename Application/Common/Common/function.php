<?php
//判断有无内容
function myCurl($url,$data=null){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if(!$data==null){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}
	$output = curl_exec($ch);
	//关闭curl
	curl_close($ch);
	return $output;
}

function hex2rgb($colour)
{
    if ($colour[0] == '#') {
        $colour = substr($colour, 1);
    }
    if (strlen($colour) == 6) {
        list($r, $g, $b) = array(
            $colour[0] . $colour[1],
            $colour[2] . $colour[3],
            $colour[4] . $colour[5]
        );
    } elseif (strlen($colour) == 3) {
        list($r, $g, $b) = array(
            $colour[0] . $colour[0],
            $colour[1] . $colour[1],
            $colour[2] . $colour[2]
        );
    } else {
        return false;
    }
    $r = hexdec($r);
    $g = hexdec($g);
    $b = hexdec($b);
    return array(
        'red' => $r,
        'green' => $g,
        'blue' => $b
    );
}

function ihttp_request($url, $post = '', $extra = array(), $timeout = 60) {
   $urlset = parse_url($url);
   if (empty($urlset['path'])) {
       $urlset['path'] = '/';
   }
   if (!empty($urlset['query'])) {
       $urlset['query'] = "?{$urlset['query']}";
   }
   if (empty($urlset['port'])) {
       $urlset['port'] = $urlset['scheme'] == 'https' ? '443' : '80';
   }
   if (strexists($url, 'https://') && !extension_loaded('openssl')) {
       if (!extension_loaded("openssl")) {
           message('请开启您PHP环境的openssl');
       }
   }
   if (function_exists('curl_init') && function_exists('curl_exec')) {
       $ch = curl_init();
               if (ver_compare(phpversion(), '5.6') >= 0) {
           curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
       }
       if (!empty($extra['ip'])) {
           $extra['Host'] = $urlset['host'];
           $urlset['host'] = $extra['ip'];
           unset($extra['ip']);
       }
       curl_setopt($ch, CURLOPT_URL, $urlset['scheme'] . '://' . $urlset['host'] . ($urlset['port'] == '80' ? '' : ':' . $urlset['port']) . $urlset['path'] . $urlset['query']);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
       curl_setopt($ch, CURLOPT_HEADER, 1);
       if ($post) {
           if (is_array($post)) {
               $filepost = false;
               foreach ($post as $name => $value) {
                   if (substr($value, 0, 1) == '@' || (class_exists('CURLFile') && $value instanceof CURLFile)) {
                       $filepost = true;
                       break;
                   }
               }
               if (!$filepost) {
                   $post = http_build_query($post);
               }
           }
           curl_setopt($ch, CURLOPT_POST, 1);
           curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
       }
       if (!empty($GLOBALS['_W']['config']['setting']['proxy'])) {
           $urls = parse_url($GLOBALS['_W']['config']['setting']['proxy']['host']);
           if (!empty($urls['host'])) {
               curl_setopt($ch, CURLOPT_PROXY, "{$urls['host']}:{$urls['port']}");
               $proxytype = 'CURLPROXY_' . strtoupper($urls['scheme']);
               if (!empty($urls['scheme']) && defined($proxytype)) {
                   curl_setopt($ch, CURLOPT_PROXYTYPE, constant($proxytype));
               } else {
                   curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                   curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
               }
               if (!empty($GLOBALS['_W']['config']['setting']['proxy']['auth'])) {
                   curl_setopt($ch, CURLOPT_PROXYUSERPWD, $GLOBALS['_W']['config']['setting']['proxy']['auth']);
               }
           }
       }
       curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
       curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
       curl_setopt($ch, CURLOPT_SSLVERSION, 1);
       if (defined('CURL_SSLVERSION_TLSv1')) {
           curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
       }
       curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');
       if (!empty($extra) && is_array($extra)) {
           $headers = array();
           foreach ($extra as $opt => $value) {
               if (strexists($opt, 'CURLOPT_')) {
                   curl_setopt($ch, constant($opt), $value);
               } elseif (is_numeric($opt)) {
                   curl_setopt($ch, $opt, $value);
               } else {
                   $headers[] = "{$opt}: {$value}";
               }
           }
           if (!empty($headers)) {
               curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
           }
       }
       $data = curl_exec($ch);
       $status = curl_getinfo($ch);
       $errno = curl_errno($ch);
       $error = curl_error($ch);
       curl_close($ch);
       if ($errno || empty($data)) {
           return $errno;
       } else {
           return ihttp_response_parse($data);
       }
   }
   $method = empty($post) ? 'GET' : 'POST';
   $fdata = "{$method} {$urlset['path']}{$urlset['query']} HTTP/1.1\r\n";
   $fdata .= "Host: {$urlset['host']}\r\n";
   if (function_exists('gzdecode')) {
       $fdata .= "Accept-Encoding: gzip, deflate\r\n";
   }
   $fdata .= "Connection: close\r\n";
   if (!empty($extra) && is_array($extra)) {
       foreach ($extra as $opt => $value) {
           if (!strexists($opt, 'CURLOPT_')) {
               $fdata .= "{$opt}: {$value}\r\n";
           }
       }
   }
   $body = '';
   if ($post) {
       if (is_array($post)) {
           $body = http_build_query($post);
       } else {
           $body = urlencode($post);
       }
       $fdata .= 'Content-Length: ' . strlen($body) . "\r\n\r\n{$body}";
   } else {
       $fdata .= "\r\n";
   }
   if ($urlset['scheme'] == 'https') {
       $fp = fsockopen('ssl://' . $urlset['host'], $urlset['port'], $errno, $error);
   } else {
       $fp = fsockopen($urlset['host'], $urlset['port'], $errno, $error);
   }
   stream_set_blocking($fp, true);
   stream_set_timeout($fp, $timeout);
   if (!$fp) {
       return false;
   } else {
       fwrite($fp, $fdata);
       $content = '';
       while (!feof($fp))
           $content .= fgets($fp, 512);
       fclose($fp);
       return ihttp_response_parse($content, true);
   }
}


function ihttp_response_parse($data, $chunked = false) {
   $rlt = array();
   $headermeta = explode('HTTP/', $data);
   if (count($headermeta) > 2) {
       $data = 'HTTP/' . array_pop($headermeta);
   }
   $pos = strpos($data, "\r\n\r\n");
   $split1[0] = substr($data, 0, $pos);
   $split1[1] = substr($data, $pos + 4, strlen($data));
   
   $split2 = explode("\r\n", $split1[0], 2);
   preg_match('/^(\S+) (\S+) (\S+)$/', $split2[0], $matches);
   $rlt['code'] = $matches[2];
   $rlt['status'] = $matches[3];
   $rlt['responseline'] = $split2[0];
   $header = explode("\r\n", $split2[1]);
   $isgzip = false;
   $ischunk = false;
   foreach ($header as $v) {
       $pos = strpos($v, ':');
       $key = substr($v, 0, $pos);
       $value = trim(substr($v, $pos + 1));
       if (is_array($rlt['headers'][$key])) {
           $rlt['headers'][$key][] = $value;
       } elseif (!empty($rlt['headers'][$key])) {
           $temp = $rlt['headers'][$key];
           unset($rlt['headers'][$key]);
           $rlt['headers'][$key][] = $temp;
           $rlt['headers'][$key][] = $value;
       } else {
           $rlt['headers'][$key] = $value;
       }
       if(!$isgzip && strtolower($key) == 'content-encoding' && strtolower($value) == 'gzip') {
           $isgzip = true;
       }
       if(!$ischunk && strtolower($key) == 'transfer-encoding' && strtolower($value) == 'chunked') {
           $ischunk = true;
       }
   }
   if($chunked && $ischunk) {
       $rlt['content'] = ihttp_response_parse_unchunk($split1[1]);
   } else {
       $rlt['content'] = $split1[1];
   }
   if($isgzip && function_exists('gzdecode')) {
       $rlt['content'] = gzdecode($rlt['content']);
   }

   $rlt['meta'] = $data;
   if($rlt['code'] == '100') {
       return ihttp_response_parse($rlt['content']);
   }
   return $rlt;
}

function ihttp_response_parse_unchunk($str = null) {
   if(!is_string($str) or strlen($str) < 1) {
       return false; 
   }
   $eol = "\r\n";
   $add = strlen($eol);
   $tmp = $str;
   $str = '';
   do {
       $tmp = ltrim($tmp);
       $pos = strpos($tmp, $eol);
       if($pos === false) {
           return false;
       }
       $len = hexdec(substr($tmp, 0, $pos));
       if(!is_numeric($len) or $len < 0) {
           return false;
       }
       $str .= substr($tmp, ($pos + $add), $len);
       $tmp  = substr($tmp, ($len + $pos + $add));
       $check = trim($tmp);
   } while(!empty($check));
   unset($tmp);
   return $str;
}


function ihttp_get($url) {
   return ihttp_request($url);
}

function ihttp_post($url, $data) {
   $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
   return ihttp_request($url, $data, $headers);
}
function strexists($string, $find) {
    return !(strpos($string, $find) === FALSE);
}
function ver_compare($version1, $version2) {
    $version1 = str_replace('.', '', $version1);
    $version2 = str_replace('.', '', $version2);
    $oldLength = istrlen($version1);
    $newLength = istrlen($version2);
    if ($oldLength > $newLength) {
        $version2 .= str_repeat('0', $oldLength - $newLength);
    }
    if ($newLength > $oldLength) {
        $version1 .= str_repeat('0', $newLength - $oldLength);
    }
    $version1 = intval($version1);
    $version2 = intval($version2);
    return version_compare($version1, $version2);
}
function istrlen($string, $charset = '') {
    global $_W;
    if (empty($charset)) {
        $charset = $_W['charset'];
    }
    if (strtolower($charset) == 'gbk') {
        $charset = 'gbk';
    } else {
        $charset = 'utf8';
    }
    if (function_exists('mb_strlen')) {
        return mb_strlen($string, $charset);
    } else {
        $n = $noc = 0;
        $strlen = strlen($string);

        if ($charset == 'utf8') {

            while ($n < $strlen) {
                $t = ord($string[$n]);
                if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $n++;
                    $noc++;
                } elseif (194 <= $t && $t <= 223) {
                    $n += 2;
                    $noc++;
                } elseif (224 <= $t && $t <= 239) {
                    $n += 3;
                    $noc++;
                } elseif (240 <= $t && $t <= 247) {
                    $n += 4;
                    $noc++;
                } elseif (248 <= $t && $t <= 251) {
                    $n += 5;
                    $noc++;
                } elseif ($t == 252 || $t == 253) {
                    $n += 6;
                    $noc++;
                } else {
                    $n++;
                }
            }

        } else {

            while ($n < $strlen) {
                $t = ord($string[$n]);
                if ($t > 127) {
                    $n += 2;
                    $noc++;
                } else {
                    $n++;
                    $noc++;
                }
            }

        }

        return $noc;
    }
}

//过滤用户表情符号方法。
function filterFieldEmoji($str){
    $str = json_encode($str);
    $str = preg_replace("#(\\\ud[0-9a-f]{3})#ie","", $str);
    return json_decode($str);
}



function random($length, $numeric = FALSE) {
  $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
  $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
  if ($numeric) {
    $hash = '';
  } else {
    $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
    $length--;
  }
  $max = strlen($seed) - 1;
  for ($i = 0; $i < $length; $i++) {
    $hash .= $seed{mt_rand(0, $max)};
  }
  return $hash;
}

function curl_wechat($url, $post=false, $poststr=""){//CURL微信发包封装
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    if($post==true){
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode($poststr));
    }
    $result=curl_exec($ch);
    curl_close($ch);
    return $result;
}

function getGuid(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);
        $uuid = chr(123)
        .substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid,12, 4).$hyphen
        .substr($charid,16, 4).$hyphen
        .substr($charid,20,12).chr(125);
        return $uuid;
    }
}

//余额减去
function cashDec($userId, $cashNum, $type, $log, $ordersn=''){
    if(!intval($userId)){
        return 0;
    }
    $cash = getCash($userId);
    if($cashNum > 0 && $cash > $cashNum){
        $result = M('member')->where(array('id'=>$userId))->setDec('cash', $cashNum);
        if($result){
            $userinfo = session('userinfo');
            $suerinfo['cash'] = $cash-$cashNum;
            session('userinfo', $userinfo);
            setCashLog($userId, $cashNum, 0, $log, $ordersn);
            return true;
        }else{
            return false;
        }
    }
}

//余额增加
function cashInc($userId, $cashNum, $type, $log, $ordersn=''){
    if(!intval($userId)){
        return 0;
    }
    
    $cash = getCash($userId);
    if($cashNum > 0){
        $result = M('member')->where(array('id'=>$userId))->setInc('cash', $cashNum);
        if($result){
            $userinfo = session('userinfo');
            $suerinfo['cash'] = $cash + $cashNum;
            session('userinfo', $userinfo);
            setCashLog($userId, $cashNum, 1, $log, $ordersn);
            return true;
        }else{
            return false;
        }
    }
}

//获取当前余额数量
function getCash($userId){
    return M('member')->where(array('id'=>$userId))->getField('cash');
}

function setCashLog($userId, $cashNum, $type, $log, $ordersn){
    $data             = array();
    $data['user_id']  = $userId;
    $data['type']     = $type;
    $data['cash']     = $cashNum;
    $data['ordersn']  = $ordersn;
    $data['remark']   = $log;
    $data['add_time'] = time();
    M('recharge_log')->add($data);
}

function getIPAderss(){
    global $ip; 
    if (getenv("HTTP_CLIENT_IP")) 
        $ip = getenv("HTTP_CLIENT_IP"); 
    else if(getenv("HTTP_X_FORWARDED_FOR"))
        $ip = getenv("HTTP_X_FORWARDED_FOR"); 
    else if(getenv("REMOTE_ADDR"))
        $ip = getenv("REMOTE_ADDR"); 
    else 
        $ip = "Unknow";
    return $ip;
}

function createNoncestr( $length = 32 ) {
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str ="";
    for ( $i = 0; $i < $length; $i++ )  {  
        $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }  
    return $str;
}

function getRandomNum( $length = 10 ){
    $chars = "0123456789";
    $str ="";
    for ( $i = 0; $i < $length; $i++ )  {  
        $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }  
    return $str;
}

function toXml($data) {
    $template1="<%s><![CDATA[%s]]></%s>";
    $template2="<%s>%s</%s>";
    $str="";
    foreach ($data as $key => $value) {
        $str.=sprintf($template1,$key,$value,$key);
    }
    $str=sprintf($template2,"xml",$str,"xml");
    return $str;
}

function curl_post_ssl($url, $vars, $second=30, $aHeader=array())
{
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_TIMEOUT,$second);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
    
    curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
    curl_setopt($ch,CURLOPT_SSLCERT, C('pemcerPath'));
    curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
    curl_setopt($ch,CURLOPT_SSLKEY, C('pemkeyPath'));
 
    if( count($aHeader) >= 1 ){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
    }
 
    curl_setopt($ch,CURLOPT_POST, 1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
    $data = curl_exec($ch);
    if($data){
        curl_close($ch);
        return $data;
    }
    else { 
        $error = curl_errno($ch);
        echo "call faild, errorCode:$error\n"; 
        curl_close($ch);
        return false;
    }
}

function getArticleClassName($classid){
    $name = M('article_class')->where(array('id'=>$classid))->getField('name');
    if(empty($name)){
        $name = '全部内容';
    }   
    return $name;
}

?>