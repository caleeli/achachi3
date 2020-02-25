<?php
ini_set('display_errors', 'on');
error_reporting(E_ALL);
//
//$a = 0;
$b = 'b';
$c = 'c';
var_dump($a ?: $b ?: $c);
//
$a = 0;
$b = 2;
$c = 3;
var_dump($a ?: $b ?: $c);
//
$a = 1;
$b = 0;
$c = 3;
var_dump($a ?: $b ?: $c);
//
$a = 0;
$b = 0;
$c = 3;
var_dump($a ?: $b ?: $c);
