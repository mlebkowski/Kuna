<?php namespace Kuna;

class Model {
	protected $manager;
	public function __construct(Manager $manager) {
		$this->manager = $manager;
	}
	public function getType() {
		list ($type) = array_reverse(explode('\\', get_class($this)));
		$type = preg_replace('/[A-Z]/', '_$0', $type);
		$type = strtolower($type);
		return $type;
	}
	public function __set($name, $value) {
		$this->$name = $value;
		if ($value && (substr($name, -3) == "_id")) {
			$name = substr($name, 0, -3);
			$this->$name = str_replace('_', 's/', $name) . 's/' . $value;
		}
	}
}
