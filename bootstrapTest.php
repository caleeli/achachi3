<?php
namespace ProcessMaker\PMTest;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;



// --bootstrap bootstrapTest.php

require 'vendor/autoload.php';

spl_autoload_register(function ($clase) {
    // Skip phpunit classes
    if (strpos($clase, 'SebastianBergmann\\') === 0) {
        return;
    }
    if (strpos($clase, 'Mockery\\') === 0) {
        return;
    }
    echo ">>> Mock class $clase <<<\n";echo ("class $clase " . '{use ProcessMaker\\PMTest\\PMockTrait;}');
    eval("class $clase " . '{use \\ProcessMaker\\PMTest\\PMockTrait;}');
});

Mockery::globalHelpers();

class PMock
{

    private static $instances = [];
    private static $doubles = [];
    private static $statics = [];

    public static function registerInstance($instance)
    {
        self::$instances[] = $instance;
        self::$doubles[] = Mockery::mock(get_class($instance));
    }

    public static function staticClass($className)
    {
        return get_class(self::staticClassMock($className));
    }

    /**
     * 
     * @param string $className
     *
     * @return \Mockery\MockInterface
     */
    public static function staticClassMock($className)
    {
        return isset(self::$statics[$className]) ? self::$statics[$className] : self::$statics[$className] = Mockery::mock("alias:{$className}_Mock");
    }

    public static function callback($callback)
    {
        return new PMCallback($callback);
    }
}

trait PMockTrait
{

    public function __construct()
    {
        PMock::registerInstance($this);
    }

    public function __call($name, $arguments)
    {
        //echo "    Mock method $" . get_class($this) . "->$name\n";
    }

    public static function __callStatic($name, $arguments)
    {
        //echo "    Mock method " . static::class . "::$name\n";
        return call_user_func_array([PMock::staticClass(static::class), $name],
            $arguments);
    }
}

class PMCallback
{

    private static $callbacks = [];
    private $index;

    public function __construct($callback)
    {
        $this->index = count(static::$callbacks);
        static::$callbacks[] = $callback;
    }

    public function __invoke(...$arguments)
    {
        return call_user_func_array(static::$callbacks[$this->index], $arguments);
    }
}

class PMTestCase extends TestCase
{

    protected function setUp(): void
    {
        /* @var $method ReflectionMethod */
        $reflection = new ReflectionClass($this);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC);
        foreach($methods as $method) {
            $name = $method->getName();
            $nameLower = strtolower($name);
            if ($nameLower!=='setup' && strpos($nameLower, 'setup') === 0) {
                $closure = $method->getClosure($this);
                $closure();
            }
        }
    }
}

trait MockTranslationsTrait
{

    protected function setupMockTranslationsTrait()
    {
        // Return the same id when G::LoadTranslation
        PMock::staticClassMock('G')
            ->shouldReceive('LoadTranslation')
            ->andReturnUsing(PMock::callback(function($id) {
                    return $id;
                }));
    }
}
