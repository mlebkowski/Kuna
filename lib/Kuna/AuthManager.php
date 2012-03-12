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
	
  public function validate($params) {
  	$dev_id = $params->get('developer_id');
  	$sig = $params->get('sig');
  	$nonce = $params->get('nonce');
  	
  	$params->remove('sig');
  	
  	$this->lastError = null;
  	
  	if (empty($dev_id) || empty($sig) || empty($nonce)) {
  		$this->lastError = self::ERR_MISSING_PARAM;
  		return false;
  	}
  	if ($nonce + self::NONCE_EXPIRE < time()) {
  		$this->lastError = self::ERR_EXPIRED_NONCE;
  		return false;
  	}
  	
  	$aparams = $params->all();
  	ksort($aparams);
  	$aparams = http_build_query($aparams);
  	
  	$secret = $this->getDeveloperSecret($dev_id);
  	if (!$secret) {
  		$this->lastError = self::ERR_DEVELOPER_SIG;
  		return false;
  	}
  	
  	$params->remove('nonce');
  	
  	return (sha1($aparams . $secret) == $sig) 
  		? new Context($secret, $params)
  		: ($this->lastError = self::ERR_BAD_SIGNATURE) && false;
  }
  public function getError() {
  	return $this->lastError;
  }
  
  private function getDeveloperSecret($id) {
  	return $this->db->query(sprintf('SELECT secret FROM developers WHERE id = %d',
  		(int)$id), \PDO::FETCH_COLUMN, 0)->fetch();
  }
}
