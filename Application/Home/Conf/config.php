<?php
return array(
	    /* 数据库设置 */
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  'localhost', // 服务器地址
    'DB_NAME'               =>  'wechat',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  'root',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  'vy_',    // 数据库表前缀    
    'DB_DEBUG'  			=>  TRUE, // 数据库调试模式 开启后可以记录SQL日志
    'DB_FIELDS_CACHE'       =>  true,        // 启用字段缓存
    'DB_CHARSET'            =>  'utf8',      // 数据库编码默认采用utf8

    //模板替换常量
    'TMPL_PARSE_STRING'		=>array(
    		'__ADMIN__'	=>	'/Public/Admin'
    	),
	
	//
	'APPID'	=>	'wx7f90a0e70710f7c7',
	'APPSECRET'	=>	'1d6d9f3050b67d6d6cfce0314dfd49b5',

    // 开启路由
	'URL_ROUTER_ON'   => true,
);