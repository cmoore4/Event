<?php

include("XMPP/XMPP.php");

/*  XMPP Command Line Messenger
 *  Invoke: php messenger.php "recipient1@example.com, recipient2@example.com" "Hello."
 *  
 *  CSM, 1-06-2011
 */



// Here we go...

// This script takes 2 parameter from the command line, plus the script name
if ($argc == 3){

	// The first param is a comma separated list of recipients: "sean@example.com,you@example.com" 
	$recips = explode(',', str_replace(' ', '', $argv[1]));

	// Some debug text
	print "Sending to ";
	foreach ($recips as $r) print $r . " ";
	print "\n\n";

	// The second param is our message.  "Hello." 
	$message = $argv[2];
}

// Create the parameters for the connection with maximum verbiage.  Warning, this is a lot of output.  Log it to a file.  Change "LEVEL_VERBOSE" to "LEVEL_ERROR" to not see output.
$conn = new XMPPHP_XMPP('talk.google.com', 5222, 'USER', 'PASSWORD', 'xmpphp', 'gmail.com', $printlog=True, $loglevel=XMPPHP_LOG::LEVEL_VERBOSE);
// Connect to the server
$conn->connect();
// Wait until we have a response from the server that the session is started to do anything else, or timeout after 5 seconds
$conn->processUntil('session_start', 5);
// Get the roster
$conn->getRoster();
// Wait until the roster has completed updating before doing anything else.
$conn->processUntil('roster_updated', 5);

// Loop through each email address from the command line, update the presence (status).
foreach ($recips as $address){
	// Verify they are in your roster, elsewise you won't be able to message them anyway
	if ($conn->roster->isContact($address)) {
		// Get the presence/status
		$conn->getPresence($address);
		// Wait for the server to respond, or timeout.
		$conn->processUntil('presence', 5);

		// If they were online
		if ($conn->roster->isOnline($address)){
			// Send the message.  No need to wait for a response from the server.
			$conn->message($address, $message);
			// Log it.
			$conn->log->log("SENT: $address -- $message");
		}
	}
}

$conn->disconnect();
$conn = null;
unset($conn);

//End of File