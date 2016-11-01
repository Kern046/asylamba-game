<?php

namespace Tests\Asylamba\Modules\Manager;

use Asylamba\Modules\Ares\Manager\CommanderManager;

class CommanderManagerTest extends \PHPUnit_Framework_TestCase {
    protected $manager;
    
    public function setUp()
    {
        $this->manager = new CommanderManager();
    }
    
    public function testLoad()
    {
        
    }
    
    public function testSave()
    {
        
    }
    
    public function testAdd()
    {
        
    }
    
    public function getDatabaseMock()
    {
        $databaseMock = $this
            ->getMockBuilder('Asylamba\Classes\Database\Database')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $databaseMock
            ->expects($this->any())
            ->method('prepare')
            ->willReturnCallback([$this, 'getStatementMock'])
        ;
        return $databaseMock;
    }
    
    public function getStatementMock()
    {
        $statementMock = $this
            ->getMockBuilder('PDOStatement')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        return $statementMock;
    }
}