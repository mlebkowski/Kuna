<?php namespace Kuna;

class Context {
	private $secret;
	private $params;
	public function __construct($secret, $params) {
		$this->params = $params;
		$this->secret = $secret;
	}
	public function getUser() {
		return $this->params['developer_id'];
	}
	public function getGroups() {
		return array(
			substr($this->secret, 1, 3)
		);
	}
}
