<?php

require_once dirname(__FILE__) . '/src/Implementation/AMIListener.php';
require_once dirname(__FILE__) . '/src/Implementation/AMIClient.php';

// Configure as required.
$options = array(
	'host'            => '192.168.1.250',
	'username'        => 'root',
	'password'        => 'toor',
	'port'            => 5038,
	'connect_timeout' => 10,
	'read_timeout'    => 100,
	'scheme'          => 'tcp://'
);

$AMIClient   = new AMIClient($options);
$AMIListener = new AMIListener();

$AMIClient->registerEventListener(array($AMIListener, 'handle'));

if ($AMIClient->open())
{
	$AMIClient->process();
}
