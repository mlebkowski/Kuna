<?php

  use \Symfony\Component\HttpFoundation\Request;

  include 'lib/Spl/SplClassLoader.php';
  $o = new SplClassLoader(null, __DIR__ . '/lib');
  $o -> register();

	$db = new PDO('sqlite:db.sqlite');
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	$aclRules = new \Nassau\Config\Config('etc/acl.yaml');
	$acl = new \Nassau\Acl\Acl($aclRules);
	
	$auth = new Kuna\AuthManager($db);
  $ctrl = new Kuna\Controller($db, $auth, $acl);
  
	$secret = '$dev$7124ed5f12743e1a67d6443cddfbeb07ff78f427';
  $client = new Kuna\Client($developer_id = 1, $secret, $url = null); 

	$method = 'GET';
	$rest = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
  $params = $client->prepareParams(array ('data' => 'whatever'));
  $sig = $client->signRequest($rest, $method, $params);
  
  $params['sig'] = $sig;
  
  $r = Request::create($rest, $method, $params, array(), array(), array (
  	'HTTP_Accept' => 'text/plain'
  )); 
  
  $rsp = $ctrl->handle($r);
  echo $rsp . "\n\n";
