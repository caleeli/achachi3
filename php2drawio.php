<?php
require 'vendor/autoload.php';

//addComposerLoader('/Users/davidcallizaya/NetBeansProjects/ProcessMakerRE/vendor/autoload.php');
addComposerLoader('/Users/davidcallizaya/NetBeansProjects/processmaker/vendor/autoload.php');

function class2drawio($array, DOMDocument $dom, $stereotype = 'trait')
{
    if($stereotype == 'trait') {
        $getNames = 'getTraitNames';
        $getAll = 'getAllTraits';
        $isStereotype = 'isTrait';
        //$isStereotype = 'isUserDefined';
    }
    if($stereotype == 'interface') {
        $getNames = 'getInterfaceNames';
        $getAll = 'getAllInterfaces';
        $isStereotype = 'isInterface';
    }
    $ref = [];
//Calculate Reference count
    array_walk($array,
               function (ReflectionClass $reflection) use ($dom, &$ref, $getNames, $isStereotype) {
        if (!$reflection->$isStereotype()) {
            return;
        }
        if (!isset($ref[$reflection->getName()])) $ref[$reflection->getName()] = ['id' => '', 'references' => []];
        foreach ($reflection->$getNames() as $trait) {
            if (!isset($ref[$trait])) $ref[$trait] = ['id' => '', 'references' => []];
            $ref[$trait]["references"][] = $reflection;
        }
    });
    foreach ($ref as $name => $def) {
        //if ($name != 'ProcessMaker\Bpmn\FlowNodeTrait') continue;
        $common = null;
        foreach ($def['references'] as $trait) {
            lastChildRef($trait, $ref, function ($trait) use (&$common, $getAll) {
                if ($common === null) {
                    $common = $getAll($trait);
                } else {
                    $common = array_intersect($common, $getAll($trait));
                }
            });
        }
        if ($common) echo $name . ':' . json_encode(array_values($common)), "\n";
    }
//Draw classes
    array_walk($array,
               function (ReflectionClass $reflection) use ($dom, &$ref, $stereotype, $isStereotype) {
        if (!$reflection->$isStereotype()) return;
        $namePath = explode('\\', $reflection->getName());
        $name = array_pop($namePath);
        $node = drawio_Interface($dom, $name, $stereotype, null, count($ref[$reflection->getName()]['references']) * -100);
        $dom->getElementsByTagName('root')->item(0)->appendChild($node);
        $ref[$reflection->getName()]['id'] = $node->getAttribute('id');
    });
//Draw relationships
    array_walk($array,
               function (ReflectionClass $reflection) use ($dom, &$ref, $getNames, $isStereotype) {
        if (!$reflection->$isStereotype()) return;
        $source = $ref[$reflection->getName()]['id'];
        foreach ($reflection->$getNames() as $trait) {
            $target = $ref[$trait]['id'];
            $node = drawio_Relationship($dom, $source, $target);
            $dom->getElementsByTagName('root')->item(0)->appendChild($node);
        }
    });
    return $dom;
}
$dom = new DOMDocument;
$dom->loadXML(file_get_contents('/Library/WebServer/Documents/AchachiX/data/ClassDiagram.xml'));
file_put_contents('output/diagram.xml',
                  class2drawio(findClasses('/Users/davidcallizaya/NetBeansProjects/ProcessMakerRE/src/ProcessMaker',
                                           '\ProcessMaker'), $dom, 'trait')->saveXML());

function getAllTraits(ReflectionClass $reflection, &$traits = [])
{
    foreach ($reflection->getTraits() as $trait) {
        $traits[] = $trait;
        getAllTraits($trait, $traits);
    }
    return array_unique($traits);
}

function getAllInterfaces(ReflectionClass $reflection, &$traits = [])
{
    foreach ($reflection->getInterfaces() as $trait) {
        $traits[] = $trait;
        getAllInterfaces($trait, $traits);
    }
    return array_unique($traits);
}

function lastChildRef(ReflectionClass $reflection, $ref, callable $callback)
{
    if (count($ref[$reflection->getName()]['references'])) {
        foreach ($ref[$reflection->getName()]['references'] as $trait) {
            lastChildRef($trait, $ref, $callback);
        }
    } else {
        $callback($reflection);
    }
}

