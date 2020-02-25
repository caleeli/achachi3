<?php

namespace Achachi;

use Exception;
use ReflectionClass as ReflectionClassBase;
use ReflectionMethod as ReflectionMethodBase;

trait ReflectionTrait
{

    private $fileCode = '';
    private $fileLines = [];
    private $fileUses = [];

    /**
     * @var CodeParser $parser
     */
    private $parser = null;

    public function getFileLines()
    {
        return $this->fileLines;
    }

    protected function setFileCode($fileCode)
    {
        $this->fileCode = $fileCode;
        $this->fileLines = explode("\n", $this->fileCode);
        $this->parser = new CodeParser($this->fileCode);
        $this->parser->setFinalRow($this->getStartLine());
        $this->loadFileUses();
        $this->parser->setInitialRow($this->getStartLine());
        $this->parser->setFinalRow($this->getEndLine());
        return $this;
    }

    private function loadFileUses()
    {
        $this->fileUses = [];
        $pattern = [
            'use',
            T_WHITESPACE,
            [
                'name' => 'class',
                CodeParser::EXPRESSION,
            ],
            ';'
        ];
        $this->parser->forEachMatches($pattern,
            function ($match) {
            preg_match('/^(.+)\s+as\s+(.+)$|^(.+)$/', $match['class'], $ma);
            $class = $ma[2] ? $ma[1] : $ma[3];
            $arrayClass = explode('\\', $class);
            $name = $ma[2] ? $ma[2] : array_pop($arrayClass);
            $this->fileUses[$name] = substr($class, 0, 1) === '\\' ? substr($class,
                    1) : $class;
        });
    }

    public function getFullClassName($class)
    {
        $class = substr($class, 0, 1) === '\\' ? substr($class, 1) : $class;
        return isset($this->fileUses[$class]) ? $this->fileUses[$class] : $class;
    }

    public function getFileCode()
    {
        return $this->fileCode;
    }

    /**
     * Get the code of the class.
     *
     * @return string
     */
    public function getCode()
    {
        $res = array_slice($this->fileLines, $this->getStartLine() - 1,
            $this->getEndLine() - $this->getStartLine() + 1);
        return implode("\n", $res);
        throw new \Exception('sss');
        throw new ReflectionClassBase('sss');
    }

    /**
     * @return CodeParser
     */
    public function getParser()
    {
        return $this->parser;
    }

    public function getThrows()
    {
        $exceptions = [];
        $this->getParser()->forEachMatches([
            'throw',
            T_WHITESPACE,
            'new',
            T_WHITESPACE,
            [
                'name' => 'exception',
                CodeParser::EXPRESSION
            ]
            ],
            function($result, $self) use (&$exceptions) {
            if (preg_match('/[^(]+/', $result['exception'], $ma)) {
                $class = $this->getFullClassName($ma[0]);
                $exceptions[$class] = $class;
            }
        });
        return array_keys($exceptions);
    }
}

/**
 * Achachi ReflectionClass
 *
 * @author David Callizaya <davidcallizaya@gmail.com>
 */
class ReflectionClass extends ReflectionClassBase
{

    use ReflectionTrait;

    public function __construct($class)
    {
        parent::__construct($class);
        $this->setFileCode(file_get_contents($this->getFileName()));
    }

    public function getMethods($filter = null)
    {
        $arguments = func_get_args();
        $methods = [];
        foreach (parent::getMethods(...$arguments) as $method) {
            $methods[] = ReflectionMethod::convert($method, $this);
        }
        return $methods;
    }

    /**
     * Gets a <b>ReflectionMethod</b> for a class method.
     *
     * @param string $name <p>
     * The method name to reflect.
     * </p>
     *
     * @return ReflectionMethod A <b>ReflectionMethod</b>.
     */
    public function getMethod($name)
    {
        return ReflectionMethod::convert(parent::getMethod($name), $this);
    }
}

class ReflectionMethod extends ReflectionMethodBase
{

    use ReflectionTrait;

    /**
     * @var ReflectionClass $reflectionClass
     */
    public $reflectionClass;

    /**
     * @param ReflectionMethodBase $reflection
     * @param \Achachi\ReflectionClass $reflectionClass
     *
     * @return ReflectionMethod
     */
    public static function convert(ReflectionMethodBase $reflection, ReflectionClass $reflectionClass)
    {
        return new static($reflection->class, $reflection->name,
            $reflectionClass);
    }

    public function __construct($class, $name, ReflectionClass $reflectionClass)
    {
        parent::__construct($class, $name);
        $this->setFileCode($reflectionClass->getFileCode());
        $this->reflectionClass = $reflectionClass;
    }

    public function getNamespaceName()
    {
        return $this->reflectionClass->getNamespaceName();
    }
}

require 'CodeParser.php';

$reflection = new ReflectionClass(ReflectionClass::class);
var_dump($reflection->getMethod('getCode')->getCode());
var_dump($reflection
        ->getMethod('getCode')
        ->getThrows());
