<?php

  include __DIR__ . '/../lib/Spl/SplClassLoader.php';
  
  foreach (Array (
    'Nassau' => __DIR__ . '/../lib',
    'Symfony\Component\Yaml' => __DIR__ . '/../lib',
  ) as $namespace => $path) {
    if (substr($path, 0, 1) !== '/') $path = __DIR__ . '/' . $path;
    $o = new SplClassLoader($namespace ?: null, $path);
    $o->register();
  }
  
