<?php
require 'vendor/autoload.php';

/* @var $loader Composer\Autoload\ClassLoader */

$project = '/Users/davidcallizaya/NetBeansProjects/nayra';
$baseBranch = 'origin/master';
$loader = addComposerLoader($project . '/vendor/autoload.php');

foreach (glob('/Users/davidcallizaya/NetBeansProjects/nayra/src/ProcessMaker/Nayra/Contracts/Bpmn/*.php') as $file) {
    $class = basename($file, '.php');
    if (strpos($class, 'CollectionInterface') !== false) continue;
    if (strpos($class, 'Interface') === false) continue;
    $name = substr($class, 0, -9);
    $fullClass = '\ProcessMaker\Nayra\Contracts\Bpmn\\' . $class;
    if (!is_subclass_of($fullClass, '\ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface')) continue;
    $code = '
use ProcessMaker\Nayra\Bpmn\Models\\'.$name.';';
    echo $code;
}

