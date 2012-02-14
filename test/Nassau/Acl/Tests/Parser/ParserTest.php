<?php

use \Nassau\Acl\Rule, \Nassau\Acl\Parser;

class Acl_Tests_ParserTest extends PHPUnit_Framework_TestCase {
  /** 
   * @dataProvider keywordsProvider
   **/
  public function testKeywordToValue($key, $expected) {
    $parser = new Parser();
    $value = $parser->parseValue($key);
    $this->assertEquals($expected, $value);
  }
  
  /**
   * @dataProvider keywordsCombinedProvider
   **/
  public function testKeywordCombined($key, $expected) {
    $parser = new Parser();
    $value = $parser->parseValue($key);
    $this->assertEquals($expected, $value);
  }
  
  /** 
   * @dataProvider permsProvider
   **/
  public function testPerms($str, $expected, $message = '') {
    $parser = new Parser();
    $result = $parser->parsePerms($str);
    $this->assertEquals($expected, $result, $message);
  }
  
  public function keywordsProvider() {
    return Array(
      array ('read', Rule::PERM_READ),
      array ('r', Rule::PERM_READ),
      array ('write', Rule::PERM_WRITE),
      array ('w', Rule::PERM_WRITE),
      array ('delete', Rule::PERM_DELETE),
      array ('d', Rule::PERM_DELETE),
      array ('undelete', Rule::PERM_UNDELETE),
      array ('u', Rule::PERM_UNDELETE),
      array ('operator', Rule::PERM_OPERATE),
      array ('operate', Rule::PERM_OPERATE),
      array ('grant', Rule::PERM_OPERATE),
      array ('g', Rule::PERM_OPERATE),
      array ('o', Rule::PERM_OPERATE),
      array ('master', Rule::PERM_MASTER),
      array ('m', Rule::PERM_MASTER),
      array ('root', Rule::PERM_MASTER),
      array ('admin', Rule::PERM_MASTER),
      array ('a', Rule::PERM_ALL),
      array ('all', Rule::PERM_ALL),
    );
  }
  public function keywordsCombinedProvider() {
    return array (
      array ('rwdug', Rule::PERM_OPERATE),
    );
  }
  
  public function permsProvider() {
    $set = Array ();
    
    foreach (array (
      "" => Rule::MODE_GRANT,
      "allow" => Rule::MODE_GRANT,
      "deny" => Rule::MODE_REVOKE,
    ) as $prefix => $mode):
    
      $set[] = array ($prefix, array ($mode => $prefix ? Rule::PERM_ALL : Rule::PERM_NONE));
    
      foreach ($this->keywordsProvider() as $data):
        $set[] = array ($prefix . " " . $data[0], array ($mode => $data[1]));
      endforeach;
    endforeach;
    
    return $set;
    
      array(
" allow ",
" deny ",
"allow test",
" allow test",
" allow test ",
"allow test xyzzy",
"allow deny",
"allow test deny",
"allow test deny test",
"allow test eglebegle deny xyzzy",
"deny test allow eglebegle",
"deny test allow xyzzy deny abc",
"deny test1 deny test2 deny test3",
"allow alpha, deny beta",
"xyzzy eglebegle",
"xyzzy allow eglebegle",
"xyzzy allow eglebegle deny test",
);
  }
}
