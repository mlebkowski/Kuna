<?php namespace Kuna;

abstract class Manager {
	protected $db;
	protected $primaryKey;
	
	protected $primaryKeyName = 'id';
	protected $allowedFilterKeys = array ();
	protected $map = array ();
	protected $limits = array ();
	protected $keys = array ();
	
	public function __construct(\PDO $db) {
		$this->db = $db;
	}
	public function getById($id) {
		return $this->query(array('id' => $id))->fetch();
	}
	public function getByParams($params) {
		return $this->query($params)->fetchAll();
	}
	public function query($where = null, $limit = null, $order = null) {
		$where = (array)$where;
		foreach ($this->limits as $limit) {
			$where[$this->getForeignKeyName($limit->getType())] = $limit->id;
		}
		if ($this->primaryKey) {
			$where['id'] = $this->primaryKey;
		}
		
		// co z kluczami, po ktorych nie mozna filtrowac?
		foreach ($where as $col => &$value) {
			$value = is_array($value) 
				? sprintf('`%s` in (%s)', $col, implode(',', $this->db->quote($value)))
				: sprintf('`%s` = %s', $col, $this->db->quote($value))
			;;
		}
		$where = array_values($where);
		
		$sql = sprintf('select * from `%s` WHERE %s ORDER BY %s',
			$this->getTableName(),
			$where ? implode(' AND ', $where) : 1,
			$order ? implode(', ', array_map(array ($this->db, 'quote'), $order)) : 'id'
		);
		$q = $this->db->query($sql);
		$q -> setFetchMode(\PDO::FETCH_CLASS, $this->getModelClass());
		
		return $q;
	}
	public function getTableName() {
		return trim(strtolower(preg_replace('/[A-Z]/g', function ($x) {
			return '_' . strtolower($x[0]);
		}, __CLASS__)), '_');
	}
	public function getModelClass() {
		return '\\Kuna\\Model\\' . get_class($this);
	}
	public function getType() {
		return $this->getTableName();
	}
	public function getPrimaryKeyName() {
		return $this->primaryKeyName;
	}
	public function getAllowedFilterKeys() {
		return $this->allowedFilterKeys;
	}
	public function getKeys() {
		return array_keys($this->keys);
	}
	public function getRequiredKeys() {
		return array_keys(array_filter($this->keys));
	}
	public function getForeignKeyName($name) {
		return isset($this->map[$name]) ? $this->map[$name] : $name . "_id";
	}
	public function limit(Model $model) {
		$type = $model->getType();
		$this->limits[$type] = $model;
	}
	
}
