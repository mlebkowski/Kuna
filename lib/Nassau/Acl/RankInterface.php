<?php namespace Nassau\Acl;

interface RankInterface {
  public function test(Target $target, $params = null);
  public function getOrder();
}
