<?php namespace Nassau\Acl;

class Target {
  protected $_user;
  protected $_id;
  protected $_groups;
  
  public function __construct($user, $groups = array()) {
    if (is_array($user)) {
      list ($this->_id, $this->_user) = each ($user);
    } else {
      $this->_user = $user;
    }
    
    $groups = is_array($groups) ? $groups : explode(" ", $groups);
    $this->_groups = array_map("strtolower", $groups);
  }
  
  public function isUser($names) {
    return in_array($this->_user, $names);
  }
  
  public function hasGroup($names) {
    $names = array_filter($names, array($this, '_reduce'));
    return sizeof($names) > 0;
    
  }
  
  private function _reduce($name) {
    return is_int($name)
      ? array_key_exists($name, $this->_groups)
      : in_array(strtolower($name), $this->_groups, true);
  }
}
