<?php

/**
 * The most basic example. Sends all notifications for a 
 * sample critical error.
 *
 */

include 'Event.php';

// You can define where your ini file is by passing it in as an argument.
// Elsewise, it just assumes event.ini from the folder that contains this.
$sender = new Event('event.ini');

// Default severities are:
// info - db
// low - db and email
// moderate - db, email, and im
// critical - db, email, im, and sms
$severity = "critical";
$event = "Event #123";
$flags = array(
	'System Status' => 'Down', 
	'System Name' => 'Webserver 1', 
	'Contact Person' => 'Sysadmin (504-111-1111)'
);

print $sender->send($severity, $event, $flags);

// END OF FILE sample.php