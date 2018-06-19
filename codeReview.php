<?php
require 'vendor/autoload.php';

/* @var $loader Composer\Autoload\ClassLoader */
$project = '/Users/davidcallizaya/NetBeansProjects/processmaker';
$baseBranch = 'origin/release/4.0.0';
$project = '/Users/davidcallizaya/NetBeansProjects/nayra';
$baseBranch = 'origin/master';
$loader = addComposerLoader($project . '/vendor/autoload.php');

function find($regexp, $path, $filter, $except)
{
    if (in_array($path, $except)) {
        return;
    }
    foreach (glob("$path/$filter") as $filename) {
        $source = file_get_contents($filename);
        if (preg_match_all($regexp, $source, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($source, 0, $match[1]), "\n") + 1;
                echo $match[0], "\n";
                nbLogError("CR error", $filename, $line);
            }
        }
    }
    foreach (glob("$path/*", GLOB_ONLYDIR) as $dirname) {
        find($regexp, $dirname, $filter, $except);
    }
}

function findInList($regexp, $list)
{
    foreach ($list as $filename) {
        $content = file_get_contents($filename);
        if (preg_match_all($regexp, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                echo $match[0], "\n";
                nbLogError("CR error", $filename, $line);
            }
        }
    }
}
$map = $loader->getClassMap();
array_walk($map,
           function ($filename, $class) use ($project, $baseBranch) {
    /* @var $method \ReflectionMethod */
    if (!(
        strpos($class, 'ProcessMaker\\') === 0
        && strpos($class, 'ProcessMaker\Util') !== 0
        && strpos($class, 'ProcessMaker\Services') !== 0
        && strpos($class, 'ProcessMaker\Project') !== 0
        && strpos($class, 'ProcessMaker\Plugins') !== 0
        && strpos($class, 'ProcessMaker\Importer') !== 0
        && strpos($class, 'ProcessMaker\Exporter') !== 0
        && strpos($class, 'ProcessMaker\Core') !== 0
        && strpos($class, 'ProcessMaker\Console') !== 0
        && strpos($class, 'ProcessMaker\BusinessModel') !== 0
        && strpos($class, 'ProcessMaker\Application') !== 0
        && strpos($class, 'ProcessMaker\Policies\AccessControl') !== 0
        && strpos($class, 'ProcessMaker\Policies\ControlUnderUpdating') !== 0
        )) {
        return;
    }
    if (!gitHasChanged($filename, $project, $baseBranch)) {
        return;
    }
    $reflectionClass = new ReflectionClass($class);
    $docComment = $reflectionClass->getDocComment();
    if (empty($docComment)) {
        nbLogError("Missing class doc", $filename, 1);
    }
    foreach ($reflectionClass->getMethods() as $method) {
        $docComment = $method->getDocComment();
        if (empty($docComment)) {
            nbLogError("Missing method " . $method->getName() . " doc", realpath($method->getFileName()),
                                                                                 $method->getStartLine());
        }
    }
});

/**
 * Busca el uso de clases sin declarar use.
 *  excepto si se usan en tags de docblocks.
 *
 * @param array $list
 * @param array $classes
 */
function checkUseOfClasses(array $list, array $classes)
{
    foreach ($list as $filename) {
        if (substr($filename, -4) !== '.php') continue;
        if (substr($filename, -14) === 'routes/api.php') continue;
        $source = file_get_contents($filename);
        foreach ($classes as $class => $path) {
            $classTC = strpos($class, '\\') === false ? '\\' . $class : $class;
            $regexp = '/^((?!use |@see |@param |@return ).)*' . (substr($classTC, 0, 1) === '\\' ? '' : '\\?') . preg_quote($classTC,
                                                                                                                            '/') . '\b.*$/m';
            if (preg_match_all($regexp, $source, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($source, 0, $match[1]), "\n") + 1;
                    echo $match[0], "\n";
                    nbLogError("Missing use for $class ", $filename, $line);
                }
            }
        }
    }
}

function checkUseOrder(array $list)
{
    foreach ($list as $filename) {
        if (substr($filename, -4) !== '.php') continue;
        if (substr($filename, -14) === 'routes/api.php') continue;
        $source = file_get_contents($filename);
        $regexp = '/^use\s+(.+);$/m';
        $uses = [];
        if (preg_match_all($regexp, $source, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($source, 0, $match[1]), "\n") + 1;
                $uses[] = $match[0];
            }
        }
        $uses0 = $uses;
        sort($uses);
        if ($uses != $uses0) {
            nbLogError("Ordenar uses", $filename, $line);
        }
    }
}

function checkDocumentation(array $list)
{
    foreach ($list as $filename) {
        if (substr($filename, -4) !== '.php') continue;
        $source = file_get_contents($filename);
        $regexp = '/^namespace\s+(.+);$/m';
        if (!preg_match($regexp, $source, $match)) continue;
        $namespace = $match[1];
        $className = '\\' . $namespace . '\\' . basename($filename, '.php');
        //$reflection = new ReflectionParameter($className);
        $reflection = new ReflectionClass($className);
        checkDoc($reflection, $list, null);
        foreach ($reflection->getMethods() as $method) {
            checkDoc($method, $list, $reflection);
        }
    }
}

function checkDoc(Reflector $reflection, $list, ReflectionClass $owner = null)
{
    $filename = $reflection->getFileName();
    if (!in_array($filename, $list)) return;
    $line = $reflection->getStartLine();
    $doc = trim(preg_replace('/^\s+\*\s?/m', '', substr($reflection->getDocComment(), 4, -4)));
    if (empty($doc)) {
        nbLogError("Missing DocBlock ", $filename, $line);
    }
    if (is_callable([$reflection, 'getParameters'])) {
        /* @var $method ReflectionMethod */
        /* @var $reflection ReflectionMethod */
        /* @var $interface ReflectionClass */
        if ($reflection->getName() !== '__construct' && $reflection->isPublic() && $owner && $owner->isInstantiable()
            && !$owner->isSubclassOf('\PHPUnit\Framework\TestCase')) {
            //Verifica que el metodo esta declarado en una interfaz
            $foundDeclaration = null;
            foreach ($owner->getInterfaces() as $interface) {
                if ($interface->hasMethod($reflection->getName())) {
                    $foundDeclaration = $interface->getMethod($reflection->getName());
                    break;
                }
            }
            if (!$foundDeclaration) {
                nbLogError("Missing declaration of " . $reflection->getName() . " in interface ", $filename, $line);
                nbLogError("Missing declaration of " . $reflection->getName() . " in interface " . $owner->getName(),
                           $owner->getFileName(), $owner->getStartLine());
            }
        }
        /* @var $parameter ReflectionParameter */
        foreach ($reflection->getParameters() as $parameter) {
            $name = ($parameter->isVariadic() ? '\.\.\.' : '') . preg_quote('$' . $parameter->getName(), '/');
            $class = $parameter->getType() && strpos($parameter->getType(), '\\') !== false ? '\\' . $parameter->getType()
                    : $parameter->getType();
            $type = $class ?
                '(' . preg_quote($class, '/') . '|' . preg_quote(basename(str_replace('\\', '/', $class)), '/') . ')'
                . ($parameter->isDefaultValueAvailable() && $parameter->getDefaultValue() === null ? '\|null' : '') : '\w+';
            $regexp = '@param ' . $type . ' ' . $name;
            if (!preg_match("/$regexp/", $doc)) {
                nbLogError("Wrong parameters ", $filename, $line);
                echo '@param ' . $class . ($parameter->isDefaultValueAvailable() && $parameter->getDefaultValue() === null
                        ? '|null' : '') . ' ' . ($parameter->isVariadic() ? '\.\.\.' : '') . '$' . $parameter->getName(), "\n";
            }
        }
    }
}

function checkEndLine(array $list)
{
    foreach ($list as $filename) {
        if (!is_file($filename)) continue;
        $source = file_get_contents($filename);
        if (substr($source, -1, 1) !== "\n") {
            $line = substr_count($source, "\n") + 1;
            nbLogError("Falta linea en blanco al final del archivo", $filename, $line);
        }
    }
}

//checkea @param type
findInList('/@param\s+type|@return\s+type/', gitGetChangedFiles($project, $baseBranch));

//checkUseOfClasses(gitGetChangedFiles($project, $baseBranch), $map);
checkUseOrder(gitGetChangedFiles($project, $baseBranch));

checkEndLine(gitGetChangedFiles($project, $baseBranch));
checkDocumentation(gitGetChangedFiles($project, $baseBranch));

