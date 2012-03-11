<?php namespace Nassau\Acl;

class Acl {
  // flags
  // musi miec wyrazne pozwolenie na wszystkich etapach
  // iloczyn &&
  const RECURSIVE_INTERSECT = 0x01;
  // wystarczy, ze otrzyma pozwolenie w jednym etapie
  // suma ||
  const RECURSIVE_UNION     = 0x02;
  const RECURSIVE_NONE      = 0x00;
  const RECURSIVE           = 0x03;
  
  /// co z defaultami? jesli 
  
  private $_aclTable = array ();
  private $_mode = null;
  
  public function __construct($rules, $mode = ACL::RECURSIVE_UNION) {
    $this->_mode = $mode;
    
    $parser = new Parser();
    // TODO DI
    
    foreach ($rules as $subject => $list) {
      if (!is_array($list)) continue;

      $ruleset = new Ruleset();
      // TODO DI
      foreach ($list as $key => $value) {
        list ($rank, $name) = $parser->parseRank($key);

        $perms = $parser->parsePerms($value);
        foreach ($perms as $mode => $perm) {
          $ruleset->add(new Rule($rank, $name, $perm, $mode));
          // TODO DI
        }
      }
      $this->_aclTable[$subject] = $ruleset;
    }
  }

  public static function createTarget($user, $groups = array()) {
  	return new Target($user, $groups);
  }  

  public function getAccessLevel(Target $target, $subject, $mode = null) {
    $mode = is_null($mode) ? $this->_mode : $mode;
    
    $options = Array (
      'recurive' => true,
      'mode' => 'union/intersect', 
      'default' => 'all/none',
    );
    
    
    $subject = array(trim($subject, '/'));
    do {
      $key = array_slice(explode('/', end($subject)), 0, -1);
      array_push($subject, sizeof($key) ? implode('/', $key) : '*');
    } while (sizeof($key));
    
    $subject = array_reverse($subject);
    $perm = 0;
    
    foreach ($subject as $key) {
      if (!isset($this->_aclTable[$key])) continue;
      
      $ruleset = $this->_aclTable[$key];
      foreach ($ruleset as $rule) {
        $m = $rule->match($target);
        if ($m) {
          list ($val, $_) = $rule->getValue();
          if ($_ == Rule::MODE_GRANT)
            $perm |= $val;
          elseif ($_ == Rule::MODE_REVOKE)
            $perm &= ~$val;
        }
      }
    }
    
  	return $perm;  
  }
    
  public function hasAccess(Target $target, $subject, $mask = Rule::PERM_READ, $mode = null) {
  	$perm = $this->getAccessLevel($target, $subject, $mode);
    return ($perm & $mask) == $mask;
  }
}
