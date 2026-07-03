<?php

class filerun {
	public $token;
	public $token_type;
	public $refresh_token;
	
	protected $path = "/adhoc";
	protected $username = "colorcom";
	protected $password = "4Qxnd@d54zAp";
	protected $client_id = "f5e135817404af09c3b1eb9d9b36385a";
	protected $client_secret = "nCafaHTBF8HFTPImGBulJ7zMX3cPzc7tGMja0LX4";
	
	function upload() {
		$url = "https://archive.colorcom.hu/api.php/files/upload/";
		
		}
	
	function refresh() {
		$url = "https://archive.colorcom.hu/oauth2/token/";
		$data = array(
			"grant_type" => "refresh_token",
			"client_id" => $this->client_id,
			"client_secret" => $this->client_secret,
			"refresh_token" => $this->refresh_token,
			);
			
		$response = $this->callCURL( $url, $data );
		$this->token = $response["access_token"];
		$this->refresh_token = $response["refresh_token"];
		}
	
	function login() {
		$url = "https://archive.colorcom.hu/oauth2/token/";
		$data = array(
			"grant_type" => "password",
			"client_id" => $this->client_id,
			"client_secret" => $this->client_secret,
			"username" => $this->username,
			"password" => $this->password,
			);
		
		$response = $this->callCURL( $url, $data );
		$this->token = $response["access_token"];
		$this->token_type = $response["token_type"];
		$this->refresh_token = $response["refresh_token"];
		}

	function callCURL( $url, $data, $query = "" ) {
		$headers = array(
			"Content-Type: multipart/form-data; charset=UTF-8",
			);
		
		if( !empty( $query ) ) $query = "?".$query;
			
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt($ch, CURLOPT_POST, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
		
		$response = curl_exec ($ch);
				
		return json_decode($response, true);
		}
		
	public function __construct() {
		$this->login();
		}	
	}
	
?>