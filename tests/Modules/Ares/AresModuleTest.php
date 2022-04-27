<?php

namespace Tests\App\Modules\Ares;

use App\Classes\DependencyInjection\Container;
use App\Classes\Worker\Application;
use App\Modules\Ares\AresModule;

class AresModuleTest extends \PHPUnit\Framework\TestCase
{
    /** @var AresModule * */
    protected $module;

    public function setUp(): void
    {
        $this->module = new AresModule($this->getApplicationMock());
    }

    public function testGetName()
    {
        $this->assertEquals('Ares', $this->module->getName());
    }

    public function getApplicationMock()
    {
        $applicationMock = $this
            ->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $applicationMock
            ->expects($this->any())
            ->method('getContainer')
            ->willReturnCallback([$this, 'getContainerMock'])
        ;

        return $applicationMock;
    }

    public function getContainerMock()
    {
        $containerMock = $this
            ->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $containerMock
            ->expects($this->any())
            ->method('getParameter')
            ->willReturn(realpath('.'))
        ;

        return $containerMock;
    }
}
