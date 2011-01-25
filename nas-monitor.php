<?php
/**
 * This is a file that can be executed from the command line.
 * It pings a few of our servers on both IP and HTTP.
 * If you have a server_status.ini file set, it should IM you
 * about it's failure to reach 555.555.555.555.
 *
 * CSM
 */

include_once 'Event.php';
include_once 'Monitor.php';
// To install: pear install Net_Ping
require_once "Net/Ping.php";

$severity = 'im';
$event = "NAS Web Server Down";
// You'll need to create this ini file
$event_sender = new Event('server_status.ini');
$monitor = new Monitor();
$down = FALSE;
$ping = Net_Ping::factory();
if(PEAR::isError($ping)){
	die($ping->getMessage());
}   
$ping->setArgs(array('count' => 4, 'timeout' => 3));

// Arguments are IP, Name, Port, User, Password, URL, SSL
$monitor->add_host('129.81.82.212', 'Systems NAS', $port='8080', $user='', $password='', $url='/cgi-bin/html/login.html');
$monitor->add_host('129.81.185.207', 'Music NAS', $port='80', $user='', $password='');
$monitor->add_host('555.555.555.555', 'Dead machine');

foreach ($monitor->hosts as $host){
	
	// Check responding to ping
	if (!($ping->checkHost($host['ip']))){
		$args = array(
			'System' => $host['name'],
			'Status' => "Did not respond to ping",
			'IP' => $host['ip']
		);
		print $event_sender->send($severity, $event, $args);
		$down = TRUE;
	}

	// Check web servers responding
	$http = Monitor::get_URL($host);
	$opens = @file_get_contents($http);
	if ($opens === FALSE){
		$args = array(
			'System' => $host['name'],
			'Status' => "Could not open webpage",
			'URL' => $http
		);
		print $event_sender->send($severity, $event, $args);
		$down = TRUE;
	}
}

if (!$down){ 
	print "All servers appear to be up: \n";
	print $monitor->get_servers();	
}

// END OF FILE nas-monitor.php