<?php

class Monitor{

	public $hosts = array();
	
	/**
	 * At this point, constructor is empty
	 * @return void
	 */
	public function __construct(){}
	
	/**
	 * Add a host to the monitor
	 * 
	 * @param string $ip The machine ip or host name
	 * @param string $name The name of the server as you identify it i.e. "My Desktop"
	 * @param string|int $port The port the webserver is running on
	 * @param string|void $user Username, if website needs auth
	 * @param string|void $password Password, if website needs auth
	 * @param string|void $url Additional url string i.e. "/index.php"
	 * @param bool $ssl Whether or not to use https:// in web address
	 * @return void
	 */
	public function add_host($ip, $name, $port=80, $user='', $password='', $url='', $ssl=FALSE){
		$this->hosts[] = array(
			'ip' => $ip,
			'port' => $port,
			'name' => $name,
			'url' => $url,
			'user' => $user,
			'pass' => $password,
			'ssl' => $ssl;
		);
	} 	

	/**
	 * Returns a url from an element in the Hosts array above.
	 * 
	 * @param array $host Host array needs params: ssl, user, pass, ip, port, url
	 * @return string
	 */
	public static function get_URL($host){
		$url = ($host['ssl']) ? 'https://' : 'http://';
		$url .= (!empty($host['user'])) ? $host['user'] : '';
		$url .= (!empty($host['pass'])) ? ':' . $host['pass'] . '@' : '';
		$url .= $host['ip'];
		$url .= ':' . $host['port'];
		$url .= (!empty($host['url'])) ? $host['url']: '';

		return $url;
	}

	/**
	 * Returns a list of server ips and names, formatted for output
	 * Mostly for debug purposes.
	 *
	 * @return string
	 */
	public function get_servers(){
		foreach($this->hosts as $host){
			$result = $result . ' \'' . $host['name'] . ' \' : ' . $host['ip'] . "\n";
		}

		return $result;
	}
}

// END OF FILE Monitor.php