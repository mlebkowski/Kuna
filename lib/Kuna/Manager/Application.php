<?php namespace Kuna\Manager;
use \Kuna\Manager;

class Application extends Manager {
	public function getByDeveloperId($id) {
		return $this->query(array('developer_id' => $id))->fetchAll();
	}
}
