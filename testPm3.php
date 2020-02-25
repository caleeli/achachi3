<?php
namespace ProcessMaker\PMTest;

use PHPUnit\Framework\TestCase;

$file = '/Users/davidcallizaya/Netbeans/processmaker/workflow/engine/classes/ActionsByEmailCoreClass.php';

require $file;

final class ActionsByEmailCoreClassTest extends PMTestCase
{
    use MockTranslationsTrait;

    public function testExceptionIfDataIsNull(): void
    {
        // Asserts: PMException should register an exception
        PMock::staticClassMock('PMException')
            ->shouldReceive('registerErrorLog')
            ->andReturnUsing(PMock::callback(function($exception) {
                $message = $exception->getMessage();
                    $this->assertSame('The parameter $data is null   .', $message);
                }));
        PMock::staticClassMock('G')
            ->allows()->outRes('ID_EXCEPTION_LOG_INTERFAZ');

        $a = new ActionsByEmailCoreClass();
        $a->sendActionsByEmail(null, []);
    }
    
    public function testExceptionIfDataIsNull2(): void
    {
        // Asserts: PMException should register an exception
        PMock::staticClassMock('PMException')
            ->shouldReceive('registerErrorLog')
            ->andReturnUsing(PMock::callback(function($exception) {
                $message = $exception->getMessage();
                    $this->assertSame('The parameter $data is null   .', $message);
                }));
        PMock::staticClassMock('G')
            ->allows()->outRes('ID_EXCEPTION_LOG_INTERFAZ');

        $a = new ActionsByEmailCoreClass();
        $a->sendActionsByEmail(null, []);
    }
}
