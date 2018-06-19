<?php
require 'vendor/autoload.php';

/* @var $loader Composer\Autoload\ClassLoader */
$project = '/Users/davidcallizaya/NetBeansProjects/processmaker';
$baseBranch = 'origin/release/4.0.0';
$project = '/Users/davidcallizaya/NetBeansProjects/nayra';
$baseBranch = 'origin/master';
$loader = addComposerLoader($project . '/vendor/autoload.php');

eachFile(function($filename) {
    $code = file_get_contents($filename);
    $tokens = token_get_all($code);
    $pos = 0;
    $tokenIndex = [];
    foreach ($tokens as $i => $token) {
        $tokenIndex[$pos] = $i;
        if (is_array($token)) {
            $token[0] = token_name($token[0]);
            $pos += strlen($token[1]);
        } else {
            $pos += strlen($token);
        }
    }
    if (preg_match_all('/function\s*\(/', $code, $matches, PREG_OFFSET_CAPTURE)) {
        $final = '';
        $u = 0;
        $callbacks = [];
        foreach ($matches[0] as $match) {
            $pos = $match[1];
            $final .= substr($code, $u, $pos - $u);
            if (!isset($tokenIndex[$pos])) {
                throw new Exception("$filename offset $pos");
            }
            $callable = tokens_get_block($tokens, $tokenIndex[$pos]);
            $name = '';
            //eventName
            if (preg_match('/::(\w+)/', $callable, $ma)) {
                $name = preg_replace_callback('/_(\w)/',
                                              function ($ma) {
                    return strtoupper($ma[1]);
                }, strtolower($ma[1]));
            }
            //prevName
            if (is_array($tokens[$tokenIndex[$pos]-4]) && $tokens[$tokenIndex[$pos]-4][1]==='::' && preg_match('/(\w+)/', $tokens[$tokenIndex[$pos]-3][1], $ma)) {
                $name = preg_replace_callback('/_(\w)/',
                                              function ($ma) {
                    return strtoupper($ma[1]);
                }, strtolower($ma[1]));
            }
            if ($name) {
                $v = $u;
                $u = $pos + strlen($callable);
                $nameBase = $name; $nameI = 0;
                while(isset($callbacks[$name])) {
                    $nameI++;
                    $name = $nameBase . $nameI;
                }
                $final .= '[$this, \'' . $name . '\']';
                $callbacks[$name]= '    /**' . "\n"
                    . '     * '.$ma[1].' event' . "\n"
                    . '     *' . "\n"
                    . '     */' . "\n"
                    . '    public function ' . $name . trim(substr($callable, 8)) . "\n";
            } else {
                $u = $pos;
            }
        }
        $final .= substr(rtrim(substr($code, $u)), 0 , -1);
        if ($callbacks) {
            $callbacks = implode('', $callbacks);
            file_put_contents($filename, $final . $callbacks . "}\n");
        }
    }
}, $project, '*.php',
                         ['/Users/davidcallizaya/NetBeansProjects/nayra/vendor', '/Users/davidcallizaya/NetBeansProjects/nayra/tests']);

function tokens_get_block(array $tokens, $index, $blockStarted = false)
{
    $level = 0;
    for ($i = $index, $l = count($tokens); $i < $l; $i++) {
        if ($tokens[$i] === '{') {
            $level++;
            $blockStarted = true;
        }
        if ($tokens[$i] === '}') $level--;
        if ($blockStarted && $level === 0) {
            $block = '';
            for ($j = $index; $j <= $i; $j++) {
                $block .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
            }
            return $block;
        }
    }
}
