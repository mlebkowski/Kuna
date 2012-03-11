<?php namespace Kuna;

class AuthManager {
	const ERR_MISSING_PARAM = 0x01;
	const ERR_EXPIRED_NONCE = 0x02;
	const ERR_DEVELOPER_SIG = 0x03;
	const ERR_BAD_SIGNATURE = 0x04;
	
	const NONCE_EXPIRE = 180;
	
	private $db;
	private $lastError = null;
	public function __construct(\PDO $db) {
		$this->db = $db;
	}
	
  public function validateMessage($params) {
  	$dev_id = $params['developer_id'];
  	$sig = $params['sig'];
  	$nonce = $params['nonce'];
  	
  	unset($params['sig']);
  	
  	$this->lastError = null;
  	
  	if (empty($dev_id) || empty($sig) || empty($nonce)) {
  		$this->lastError = self::ERR_MISSING_PARAM;
  		return false;
  	}
  	if ($nonce + self::NONCE_EXPIRE < time()) {
  		$this->lastError = self::ERR_EXPIRED_NONCE;
  		return false;
  	}
  	
  	ksort($params);
  	$params = http_build_query($params);
  	
  	$secret = $this->getDeveloperSecret($dev_id);
  	if (!$secret) {
  		$this->lastError = self::ERR_DEVELOPER_SIG;
  		return false;
  	}
  	
  	return (sha1($params . $secret) == $sig) 
  		? new Context($secret, $params)
  		: ($this->lastError = self::ERR_BAD_SIGNATURE) && false;
  }
  public function getError() {
  	return $this->lastError;
  }
  
  private function getDeveloperSecret($id) {
  	return $this->db->query(sprintf('SELECT secret FROM developers WHERE id = %d',
  		(int)$id), PDO::FETCH_COLUMN, 0)->fetch();
  }
}
