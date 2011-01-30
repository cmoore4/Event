<?php

/**
 * Event Class
 *
 * Fires off a series of notifications, depending on the severity level.
 * Requires a configuration ini to get logins and recipients of messages.
 *
 * @version 1.0
 * @author Sean Moore <sean@csmooreinc.com>
 * @project Event
 */

class Event {
	/**
	 * @var array
	 */
	protected $severity = array(
		'db' => array('db'),
		'email' => array('email'),
		'im' => array('im'),
		'sms' => array('sms'),
		'info' => array('db'), 
		'low' => array('db', 'email'), 
		'moderate' => array('db', 'im', 'email'), 
		'critical' => array('db', 'im', 'sms', 'email')
		);

	/**
	 * @var string
	 */	 
	protected $db_string;

	/**
	 * @var string
	 */
	protected $db_user;

	/**
	 * @var string
	 */
	protected $db_pass;

	/**
	 * @var string
	 */
	protected $db_table;

	/**
	 * @var string
	 */
	protected $db_table2;

	/**
	 * @var string
	 */
	protected $im_user;

	/**
	 * @var string
	 */
	protected $im_pass;

	/**
	 * Constructor
	 * 
	 * Parses out config variables from the ini file.
	 * 
	 * @param string $ini The path to the config ini file
	 * @return void
	 */
	public function __construct($ini = 'event.ini'){
		
		if (!@file_exists($ini)){ return "Error: Invalid ini file name or path"; }
		$config = parse_ini_file($ini, True);
		
		$this->parse_db($config['database']);
		$this->parse_im($config['IM']);
		$this->parse_phone($config['SMS']);
		$this->parse_email($config['email']);

	}

	private function parse_db($config){
		$this->db_string = $config['driver'] .
        					':host=' . $config['host'] .
        					((!empty($config['port'])) ? (';port=' . $config['port']) : '') .
        					';dbname=' . $config['name'];
        $this->db_user = $config['user'];
        $this->db_pass = $config['password'];
        $this->db_table = $config['table'];
        $this->db_table2 = $config['table2'];
    }

    private function parse_im($config){
        $this->im_user = $config['sender'];
        $this->im_pass = $config['password'];
        $this->im_recips = explode(',', $config['recipients']);
        $this->im_recips = array_map('trim', $this->im_recips);
    }

    private function parse_phone($config){
        $this->phone_numbers = explode(',', $config['numbers']);
        $this->phone_numbers = array_map('trim', $this->phone_numbers);
    }

    private function parse_email($config){
        $this->email_recips = explode(',', $config['recipients']);
        $this->email_recips = array_map('trim', $this->email_recips);
        $this->smtp = $config['SMTP'];
        $this->email_prefix = $config['subject_prefix'];
        $this->email_user = $config['user'];
        $this->email_pass = $config['password'];
        $this->email_host = $config['host'];
        $this->email_from = $config['sender'];
        $this->email_from_name = $config['sender_name'];
        $this->email_port = $config['port'];
	}

	/**
	 * Fire off the events to send the messages.
	 *
	 * The main method used by implementing scripts.  It determines
	 * which methods to call to send alerts, based on the severity.
	 * 
	 * @param string $level The severity level (critical, info, etc...)
	 * @param string $event The name of the event that was triggered (Server Down, Event #123, etc..)
	 * @param array $args An array of key:value pairs ('Status'=>'Down', 'Level'=>'5')
	 * @return string $response
	 */
	public function send($level, $event, $args){
		
		$sev = $this->severity[$level];
		$response = '';

		if (in_array('db', $sev)){
			$response .= $this->db_send($event, $args, $level);
		}	

		if (in_array('email', $sev)){
			$response .= $this->email_send($event, $args);
		}

		if (in_array('im', $sev)){
			$response .= $this->im_send($event, $args);
		}

		if (in_array('sms', $sev)){
			$response .= $this->sms_send($event, $args);
		}

		return $response;
	}

	/**
	 * Log a message to the database
	 * 
	 * Logs the event to the database or returns an error.  As implemented,
	 * the event id, event, severity, and date are logged to one table.  The
	 * second table contains a list of event id, key, argument.  This allows 
	 * an arbitrary number of argumentsto be passed in.
	 *
	 * @param string $event The event name
	 * @param array $args Key:value pairs
	 * @param string $level Severity level, for logging in the database
	 * @return string $response Success or Failure messages
	 */
	private function db_send($event, $args, $level){

		$response = '';

		$conn = new PDO($this->db_string, $this->db_user, $this->db_pass);
		$insert = $conn->prepare("INSERT INTO {$this->db_table}(severity, event) VALUES(:level, :event)");
		$insert->bindValue(':level', $level);
		$insert->bindValue(':event', $event, PDO::PARAM_STR);
		$insert->execute();

		// PDO methods return '00000' or some variation thereof as first value of array if successful
		$pdo_error = $conn->errorInfo();
		$pdo_error = $pdo_error[0];
		$stmt_error = $insert->errorInfo();
		$stmt_error = $stmt_error[0];

		if ($pdo_error == '00000' && $stmt_error == '00000'){
			$response .= "Success: DB Write\n";
			// Return the last insert id from this session, to use as event_id in the second table
			$event_id = $conn->lastInsertId();
		} else {
			return "Error: DB Write events (PDO: $pdo_Error & $stmt_Error)\n";
		}

		$insert = $conn->prepare("INSERT INTO {$this->db_table2}(event_id, `key`, `value`) VALUES(:id, :key, :value)");
		$insert->bindValue(':id', $event_id);
		$insert->bindParam(':key', $k);
		$insert->bindParam(':value', $v);

		foreach ($args as $k => $v){
			$insert->execute();
		}

		$pdo_error = $conn->errorInfo();
		$pdo_error = $pdo_error[0];
		$stmt_error = $insert->errorInfo();
		$stmt_error = $stmt_error[0];

		if ($pdo_error == '00000' && $stmt_error == '00000'){
			$response .= "Success: DB Write event args\n";
		} else {
			$response .= "Error: DB Write event args\n";
		}

		return $response;
	}

	/**
	 * Send a message via XMPP (Jabber/GTalk/Facebook Chat)
	 * 
	 * Sends a message to the recipients list from the config file.
	 * Checks to make sure the user is both in the chat list, and online.
	 * Generally, this function takes the longest to run of all the methods
	 * in the class, due to the long-ish timeouts. 
	 *
	 * @param string $event The event name
	 * @param array $args Key:value pairs
	 * @return string $response Success or Failure messages
	 */
	private function im_send($event, $args){

		if (file_exists('XMPP/XMPP.php')){ include_once 'XMPP/XMPP.php'; }
		else { return "Error: IM XMPP library file not found\n"; }

		// Flatten the key:value pairs into one string in the array.
		// We'll be using implode later to flatten the array entirely.
		foreach ($args as $k=>&$v){
			$v = "$k: $v";
		}

		$response = '';
		$message = "*$event*\n" . implode("\n", $args);
		
		$conn = new XMPPHP_XMPP('talk.google.com', 5222, $this->im_user, $this->im_pass, 'xmpphp', 'gmail.com', $printlog=False, $loglevel=XMPPHP_LOG::LEVEL_VERBOSE);
		$conn->connect();
		$conn->processUntil('session_start', 3);
		$conn->getRoster();
		$conn->processUntil('roster_updated', 3);
		foreach ($this->im_recips as $address){

			if ($conn->roster->isContact($address)) {
				$conn->getPresence($address);
				$conn->processUntil('presence', 5);

				if ($conn->roster->isOnline($address)){
					$conn->message($address, $message);
					$response .= "Success: IM to $address\n";
				} else {
					$response .= "Error: IM to $address (user offline)\n";
				}
			} else {
				$response .= "Error: IM to $address (user not in roster)\n";
			}
		}

		$conn->disconnect();
		$conn = null;
		unset($conn);

		return $response;
	}

	/**
	 * Send an email, optionally via SMTP with Auth
	 * 
	 * Sends a message to the recipients list from the config file.
	 * 
	 * @param string $event The event name
	 * @param array $args Key:value pairs
	 * @return string $response Success or Failure messages
	 */
	private function email_send($event, $args){

		if (@file_exists('phpmailer/class.phpmailer.php')){ include_once 'phpmailer/class.phpmailer.php'; }
		else { return "Error: Email PHPMailer library file not found\n"; }
		$response = '';

		$mail = new phpmailer();
		if ($this->smtp == 'enabled') {
			$mail->IsSMTP();
			if (!empty($this->email_pass)){
				//$mail->SMTPDebug  = 2; 
				$mail->SMTPAuth = true;
				$mail->SMTPSecure = "ssl";  
				$mail->Username = $this->email_user;
				$mail->Password = $this->email_pass;
			}
		}

		$mail->From = $this->email_from;
		$mail->FromName = $this->email_from_name;
		$mail->Host = $this->email_host;
		$mail->Port = $this->email_port;
		foreach ($this->email_recips as &$email){
			if (filter_var($email, FILTER_VALIDATE_EMAIL)){
				$mail->AddAddress($email);
			} else {
				$response .= "Error: Email failed for $email (address not valid)\n";
				$email = '';
			}
		}
		$mail->IsHTML(false);
		$mail->Subject = $this->email_prefix . " $event";
		$mail->Body = "Event: $event occured on " . date(DATE_RFC822) . "\n\n";
		foreach ($args as $k=>$v){
			$mail->Body .= "$k: $v\n\n";
		}
		
		if (!$mail->Send()){
			return "Error: Email failed to send ({$mail->ErrorInfo})\n";
		}

		return "Success: Mail to " . implode(',', $this->email_recips) . "\n";
	}

	/**
	 * Send an SMS to defined carriers
	 * 
	 * Sends an SMS message to the recipients list from the config file.
	 * Uses the same method as the {@link email_send} method above. You can
	 * define more carriers by adding them to the switch statement below.
	 * 
	 * @see email_send()
	 * @param string $event The event name
	 * @param array $args Key:value pairs
	 * @return string $response Success or Failure messages
	 */
	private function sms_send($event, $args){

		if (@file_exists('phpmailer/class.phpmailer.php')){ include_once 'phpmailer/class.phpmailer.php'; }
		else { return "Error: Email PHPMailer library file not found\n"; }
		foreach ($args as $k=>&$v){
			$v = "$k: $v";
		}
		$response = '';

		$mail = new phpmailer();
		if ($this->smtp == 'enabled') {
			$mail->IsSMTP();
			if (!empty($this->email_pass)){
				$mail->SMTPAuth = true;
				$mail->SMTPSecure = "ssl";
				$mail->Username = $this->email_user;
				$mail->Password = $this->email_pass;
			}
		} 
		$mail->Host = $this->email_host;
		$mail->Port = $this->email_port;
		$mail->IsHTML(false);
		$mail->Subject = '';
		$mail->Body = "$event\n\n";
		$mail->Body .= implode("\n", $args);

		foreach ($this->phone_numbers as $number){
			$continue = false;
			$number = explode(':', $number);
			$carrier = $number[1];
			$number = $number[0];

			switch($carrier){
				case "ATT":
					$carrier_email = "@txt.att.net";
			        break;
			    case "Verizon":
			        $carrier_email = "@vtext.com";
			        break;
			    case "TMobile":
			        $carrier_email = "@tmomail.net";
			        break;
			    case "Sprint":
			        $carrier_email = "@messaging.sprintpcs.com";
			        break;
			    default:
			        $response .= "Error: SMS to $number failed ($carrier not supported)";
			        $continue = true;
        			break;
			}
			// Handles no carrier issues
			if ($continue) {continue;}

			$mail->AddAddress($number.$carrier_email); 
			$emails[] = $number.$carrier_email;
		}

		if (!$mail->Send()){
			$response .= "Error: Email failed to send ({$mail->ErrorInfo})\n";
		} else{
			$response .= "Success: Mail to " . implode(',', $emails) . "\n";
		}
		
		return $response;
	}
	
}