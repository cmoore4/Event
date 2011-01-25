Event Alert System
==================

This is a simple alert system to send notifications to people when an event occurs.  It utilizes the XMPPHP library, phpmailer, and PDO.  The former two are packaged here, the later should be present in PHP versions >= 5.0.

Usage
-----

1. Create an ini file based on event.ini (or simply edit event.ini directly if you only want to have one alert system set up).  
2. Include Event.php in the file or project you wish to have an alert set on.
3. Create an Event object, optionally provide it with an ini, and set your severity level.
4. When you catch an event that needs an alert sent, provide your Event object with the alert level, the message, and optionally an array of key:values to send along.

Example
-------

<?php
  include 'Event.php';
  $alerter = new Event();
  $resposes = $alerter->send('db', 'Test Event', array('Event' => 'Happened'));
?>