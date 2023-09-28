<?php
namespace AliceDialogs;

class AliceDialogs{

	var $session = False;

	const REJECT = 1;
	const CONFIRM = 2;
	const ERROR = 3;

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

	public function intenet($key=false)
	{
		$intents = $this->_request['request']['nlu']['intents'];
		if ( key_exists('YANDEX.REJECT',$intents) )
			return $this::REJECT;

		if ( key_exists('YANDEX.CONFIRM',$intents) )
			return $this::REJECT;

		if($key)
			return key_exists($key,$intents) ? $intents[$key]['slots'] : null;

		return  $intents;		
	}
};

