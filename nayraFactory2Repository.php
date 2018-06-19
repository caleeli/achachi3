<?php
require 'vendor/autoload.php';

/* @var $loader Composer\Autoload\ClassLoader */
$project = '/Users/davidcallizaya/NetBeansProjects/processmaker';
$baseBranch = 'origin/release/4.0.0';
$project = '/Users/davidcallizaya/NetBeansProjects/nayra';
$baseBranch = 'origin/master';
$loader = addComposerLoader($project . '/vendor/autoload.php');

eachFile(function($filename) {
    $code = preg_replace_callback(
        '/->createInstanceOf\((\w+)::class(.*)\);/',
        function ($match) {
            $class = $match[1];
            $params = trim(ltrim(trim($match[2]), ','));
            echo '->create' . substr($class, 0, -9) . '('.$params.');', "\n";
            return '->create' . substr($class, 0, -9) . '('.$params.');';
        },
        file_get_contents($filename)
    );
    file_put_contents($filename, $code);
}, $project, '*.php', ['/Users/davidcallizaya/NetBeansProjects/nayra/vendor']);
