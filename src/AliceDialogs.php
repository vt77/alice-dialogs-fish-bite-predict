<?php
namespace AliceDialogs;

use Monolog;

$logger = new Monolog\Logger(__NAMESPACE__);

class AliceDialogs{

	var $session = False;

	const REJECT = 1;
	const CONFIRM = 2;
	const ERROR = 3;
	const EMPTY = 4;

	var $_request = null;

	function __construct($request)
	{
		$this->_request = $request;
	}


	public static function parse($request)
	{
		// takes raw data from the request 
		# $json = file_get_contents('php://input');
		// Converts it into a PHP object 
		$data = json_decode($request, true);
		return new AliceDialogs($data); 
	}


	public function user(){
		return $this->_request['session']['user']['user_id'];
	}


	public function session(){
		return $this->_request['state']['session'];
	}

	public function build_response($answer, $session=false)
	{
		$response = [
			'response' => [
				'text' => $answer,
				'end_session'=> ($session == false),
			],
			'version' => '1.0'
		];
	
		if(is_array($session))
			$response['session_state'] = $session;
	
		return $response;	
	}

	public function intenet($keys)
	{

		global $logger;

		if($this->_request == null)
			return null;

		$intents = $this->_request['request']['nlu']['intents'];
		$logger->debug("[DIALOG][INTENTS]",$intents);
		if ( key_exists('YANDEX.REJECT',$intents) )
		{
			$logger->info("[DIALOG][INTENTS] Reject");
			return $this::REJECT;
		}

		if ( key_exists('YANDEX.CONFIRM',$intents) )
		{
			$logger->info("[DIALOG][INTENTS] Confirm");
			return $this::CONFIRM;
		}

		foreach($keys as $intent_type)
			if(key_exists($intent_type,$intents))
					return $intents[$intent_type]['slots'];

		return $this::EMPTY;
	}
};

