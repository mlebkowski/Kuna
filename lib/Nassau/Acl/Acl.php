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
  private function parseRule($txt) {
    $stop = array ('allow', 'deny');
    $re = sprintf('/,?\s*\b(%s)\b\s*/', implode('|', $stop));

    $rules = Array ();

    $txt = sprintf('%s %s', strtolower($txt), end($stop));
    $data = preg_split($re, $txt, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    $key = reset($stop);
    $buff = array();
    foreach ($data as $token):
      if (in_array($token, $stop)):
        if (sizeof($buff)) $rules[$key] = array_key_exists($key, $rules)
          ? array_merge($rules[$key], $buff)
          : $buff;
          
        $key  = $token;
        $buff = array(-1 => 'all');
      else:
        unset($buff[-1]); // default
        $buff = array_merge($buff, explode(' ', $token));
      endif;

    endforeach;
    
    $ret = array ();
    foreach ($rules as $key => $val) {
      $key = ($key == reset($stop)) ? Rule::MODE_GRANT : Rule::MODE_REVOKE;
      $ret[$key] = $this->parsePerms($val);    
    }
    return $ret;
  }
  public function parsePerms($val) {
    $bitmask = 0x00;
    
    $val = explode(',', implode(',', $val));
    while ($str = array_shift($val)) switch (strtolower($str)):
    case 'read': case 'r': case 'usage':
      $bitmask |= Rule::PERM_READ;
      break;
      
    case 'write': case 'w':
      $bitmask |= Rule::PERM_WRITE;
      break;

    case 'delete': case 'd':
      $bitmask |= Rule::PERM_DELETE;
      break;
      
    case 'undelete': case 'u':
      $bitmask |= Rule::PERM_UNDELETE;
      break;
      
    case 'operate': case 'operator': case 'o': case 'grant': case 'g':
      $bitmask |= Rule::PERM_OPERATE;
      break;
      
    case 'master': case 'm': case 'root': case 'admin':
      $bitmask |= Rule::PERM_MASTER;
      break;
    
    case 'all': case 'a':
      $bitmask |= Rule::PERM_ALL;
      break;

    default:
      if (strlen($str) > 1) {
        $val = array_merge($val, explode(' ', wordwrap($str, 1, ' ', true)));
      }
      
    endswitch;
    
    return $bitmask;
  }
  
  public function hasAccess(Target $target, $subject, $mask = Rule::PERM_READ, $mode = null) {
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
    
    return ($perm & $mask) == $mask;
  }
}
