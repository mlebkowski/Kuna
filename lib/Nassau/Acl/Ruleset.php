<?php namespace Nassau\Acl;

class RuleSet implements \IteratorAggregate {
  private $_iterator = array ();
  
  public function getIterator () {
    return new \ArrayIterator($this->_iterator);
  }
  
  public function get ($key) {
    return $this->_iterator[$key]; // issue a warning
    return $this->has($key) ? $this->_iterator[$key] : null;
  }
  public function set ($key, Rule $value) {
    $this->_iterator[$key] = $value;
  }
  public function has($key) {
    return array_key_exists($key, $this->_iterator);
  }
  public function all() {
    return $this->_iterator;
  }
  public function replace($key, $value) {
    if ($this->has($key)) $this->set($key, $value);
  }
  public function clear () {
    $this->_iterator = array ();
  }
  public function isEmpty () {
    return $this->count() == 0;
  }
  public function add(Rule $value) {
    array_push($this->_iterator, $value);
  }
  public function register () {
    throw new \Exception();
  }
  public function count() {
    return sizeof($this->_iterator);
  }
  public function keys() {
    return array_keys($this->_iterator);
  }
}
