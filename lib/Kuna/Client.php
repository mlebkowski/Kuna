<?php namespace Kuna;

class Client {

	protected $id;
	protected $secret;
	protected $endpoint;
	public function __construct($id, $secret, $endpoint) {
		$this->id = $id;
		$this->secret = $secret;
		$this->endpoint = $endpoint;
	}
	public function prepareParams($params) {
		$params = array_merge($params, array (
			'nonce' => time(),
			'developer_id' => $this->id,
		));
		return $params;
	}
	public function signRequest($request, $method = 'GET', array $params = array()) {
		$params = array_merge($params, array (
			'rest' => ltrim($request, '/'),
			'method' => strtoupper($method),
		));
		unset($params['sig']);
		ksort($params);
		$params = http_build_query($params);
		
		$sig = sha1($params . $this->secret);
		return $sig;
	}
}
