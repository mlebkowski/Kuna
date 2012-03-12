<?php namespace Kuna\Manager;
use \Kuna\Manager;

class Game extends Manager {
	public function getCurrentGame($app) {
		return $this->query()->fetch();
	}
}
