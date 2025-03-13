<?php

namespace Phiki;

class Autoloader {
  public static function load($class) {
    $ret = str_replace("\\", "/", $class) . ".php";
    $ret = include "$ret";
    return $ret == 1 ? true : false;
  }
}
