<?php
return array(
	//CAS配置
    'CLIENT_ID'=>'1000000102',
    'client_secret'=>'qnfk0xqkmm6xrwc6mk61tmc4xxw9vm',
    // 'client_secret'=>'cW5mazB4cWttbTZ4cndjNm1rNjF0bWM0eHh3OXZt',
   // 'CLIENT_ID'=>'1000000036',
	//'client_secret'=>'7G1iQamGZgvAW7Bnk0lxEvO1QyHqJk9j',
   'cas_host'=>'passport.crecgec.com',
    'cas_context'=>'CAS',
    'cas_port' => 443,
     'cas_server_ca_cert_path' => '/path/to/cachain.pem',
    //OAUTH接口配置
    'use_webservice'=>'https://passport.crecgec.com/webservice/userinfo?wsdl',
   'cascallback'=>'http://localhost:8088/cmfx-master/cmfx-master/admin',
    //'cascallback'=>'http://lbos.crecgec.cn/',
     'passport_interface'=>'https://passport.crecgec.com',

     'oauth_user_api'=>'http://passport.crecgec.com/userinfo_api.php',
	'oauth_client_api'=>'http://passport.crecgec.com/client_api.php',
	'oauth_log'=>'http://passport.crecgec.com/interface/nodeinterface.php',

	'oauth_gs_api'=>'http://passport.crecgec.com/interface/gsinterface.php',
	'oauth_token'=>'http://passport.crecgec.com/token.php',
     
);
   