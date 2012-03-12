<?php namespace Kuna;

class Api {

	private $db;
	public function __construct(\PDO $db) {
		$this->db = $db;
	}
	
	public function getManager($name) {
		$name = strtolower($name);
		
		
		switch ($name) {
			case 'settings':	
			case 'users':
			case 'users/answers':
			case 'questions':
			case 'questions/answers':
			case 'pages':
				//game_id
			case 'applications':
				//dev_id
			case 'themes':
			case 'games':
				//game_id
			
				$name = preg_replace_callback('#s/.#', function ($m) {
					return ucfirst(substr($m[0], -1)); 
				} , $name);
				$name = substr($name, 0, -1);
				$name = "\\Kuna\\Manager\\" . ucfirst($name);
				$manager = new $name($this->db);
				return $manager;
		}
	}
	public function __get($name) {
		$name = preg_replace_callback('/[A-Z]/', function ($x) {
			return '/' . strtolower($x);
		}, $name);
		return $this->getManager($name);
	}
}
