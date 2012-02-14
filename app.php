<?php

  use \Nassau\Acl\Acl, \Nassau\Acl\Rule;
  use \Nassau\Acl\Target;


  include __DIR__ . '/lib/Spl/SplClassLoader.php';
  
  foreach (Array (
    'Nassau' => 'lib',
    'Symfony\Component\Yaml' => 'lib',
//    null => 'lib',
  ) as $namespace => $path) {
    if (substr($path, 0, 1) !== '/') $path = __DIR__ . '/' . $path;
    $o = new SplClassLoader($namespace?:null, $path);
    $o->register();
  }
  
  $rules = Symfony\Component\Yaml\Yaml::parse("etc/acl.yaml");

  $acl = new Acl($rules);
  $user = new Target("puck", "authorized moderator");
  
  $page = Array (null, "test/name/space", "test/name", "test");
  
  
  
  $checks = Array (
    'read' => Rule::PERM_READ,
    'write' => Rule::PERM_WRITE,
    'delete' => Rule::PERM_DELETE,
    'grant' => Rule::PERM_OPERATE,
  );
    
  foreach ($page as $p) if ($p) 
    vprintf(" %-15s | %-5s | %-5s | %-6s | %-5s |\n", array_merge(array($p),
      array_map(function ($x) use ($acl, $user, $p) {
        return t($acl->hasAccess($user, $p, $x));
      }, $checks)
    ));
  else vprintf(" page \ perm:    | %s  | %s | %s | %s | \n %s\n", 
    array_merge(array_keys($checks), (array)str_repeat("-", 50))
  );

  function t($_) {return $_?"True":"False";}
