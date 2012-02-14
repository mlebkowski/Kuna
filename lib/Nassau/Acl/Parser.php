<?php namespace Nassau\Acl;

class Parser {
  public function parseRank($str) {
    list ($rank, $name) = array_pad(explode(" ", $str, 2), 2, null);
        
    if (is_null($name)) switch (substr($rank, 0, 1)):
    case '%': 
      $name = substr($rank, 1);
      $rank = Rule::RANK_GROUP;
      break;
    endswitch;
          
        
    switch (strtolower($rank)): 
    case 'groups': case 'group':
                  $rank = Rule::RANK_GROUP; break;
    case 'user':  $rank = Rule::RANK_USER;  break;
    case 'default': case 'all':
      $rank = Rule::RANK_ALL;
      $name = null;
      break;
    default: 
      $name = "@". $rank;
      $rank = Rule::RANK_GROUP;
    endswitch;
        
    if ($rank == Rule::RANK_GROUP) {
      $name = array_filter(explode(" ", str_replace(',', '', $name)));
      // TODO: co z guid = 0?
    }
    
    return Array ($rank, $name);
  }

  public function parsePerms($str) {
    $stop = array ('allow', 'deny');
    $re = sprintf('/,?\s*\b(%s)\b\s*/', implode('|', $stop));

    $rules = Array ();

    $str = sprintf('%s %s', trim(strtolower($str)), end($stop));
    $data = preg_split($re, $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    
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
    
    if (empty($rules)) $rules = Array (reset($stop) => null);
    
    $ret = array ();
    foreach ($rules as $key => $val) {
      $key = ($key == reset($stop)) ? Rule::MODE_GRANT : Rule::MODE_REVOKE;
      $ret[$key] = $this->parseValue($val);
    }
    return $ret;
  }
  public function parseValue($val) {
    $bitmask = 0x00;
    
    $val = explode(',', implode(',', (array)$val));
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
  
}
