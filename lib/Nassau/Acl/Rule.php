<?php namespace Nassau\Acl;

class Rule {
  // first, match all, set some defaults
  const RANK_ALL      = 100;
  // then apply group rules
  const RANK_GROUP    = 200;
  // and user rules are more specific
  const RANK_USER     = 300;
  // at last, apply those if none off the above matched
  const RANK_OTHER    = 400;
  
  const PERM_NONE     = 0x00;
  const PERM_READ     = 0x01;
  const PERM_WRITE    = 0x02;
  const PERM_DELETE   = 0x04;
  const PERM_UNDELETE = 0x08;
  const PERM_OPERATE  = 0x0F;  // RWDU
  const PERM_MASTER   = 0x1F;  // RWDU and GRANT
  const PERM_ALL      = 0xFF;  // RWDU and GRANT MASTER
  
  const MODE_GRANT    = "allow";
  const MODE_REVOKE   = "deny";
  
  private $_perm;
  private $_name;
  private $_rank;
  private $_mode;
  
  /** 
   * @param $type defines the type of target to match
   * @param $name name of the target
   * @param $perm specific permissions
   * @param $mode is it an allow or a deny rule?
   **/
  public function __construct($rank, $name, $perm, $mode = self::PERM_GRANT) {
    $this->_name = array_map('strtolower', (array)$name);
    $this->_rank = $rank;

    if ($perm === true) $perm = self::PERM_ALL;

    switch (strtolower($perm)):
      case 'allow': $perm = self::PERM_ALL; $mode = self::MODE_GRANT;  break;
      case 'deny':  $perm = self::PERM_ALL; $mode = self::MODE_REVOKE; break;
    endswitch;
    
    $this->_perm = $perm;
    $this->_mode = $mode;

  }

  public function match (Target $target, $params = null) {
    $match = false;

    switch ($this->_rank):
    case self::RANK_ALL:
      $match = true;
      break;
      
    case self::RANK_GROUP:
      $match = $target->hasGroup($this->_name);
      break;
      
    case self::RANK_USER:
      $match = $target->isUser($this->_name);
      break;
      
    default:
      if ($this->_rank instanceof RankInterface) $this->_rank->test($target, $params);
      
    endswitch;
    
    return $match;

  }
  
  public function getValue() {
    return Array ($this->_perm, $this->_mode);
  }
  
  public function getRank() {
    return $this->_rank;
  }
  public function getName() {
    return $this->_name;
  }
}
