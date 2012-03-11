<?php

  include 'lib/Spl/SplClassLoader.php';
  $o = new SplClassLoader(null, __DIR__ . '/lib');
  $o -> register();

  $ctrl = new Kuna\Controller();
