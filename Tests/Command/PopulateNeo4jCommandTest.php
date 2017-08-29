<?php

namespace Headoo\ElasticSearchBundle\Tests\Command;

use Doctrine\ORM\EntityManager;
use Misteio\Neo4jBundle\Helper\Neo4jHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Misteio\Neo4jBundle\Tests\TestTrait;
use Symfony\Component\Console\Input\ArrayInput;

class PopulateNeo4jCommandTest extends KernelTestCase
{
    use TestTrait;

    /**
     * @var Neo4jHelper
     */
    private $_neo4jHelper;

    /**
     * @var EntityManager
     */
    private $_em;

    /**
     * @var Application
     */
    protected $application;


    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        self::bootKernel();

        $this->_em              = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->_neo4jHelper     = static::$kernel->getContainer()->get('misteio.neo4j.helper');
        $this->application      = new Application(self::$kernel);
        $this->application->setAutoExit(false);
        $this->loadFixtures();
    }

    public function testCommand1()
    {

        $options1['command'] = 'misteio:neo4j:populate';
        $this->application->run(new ArrayInput($options1));

        $client = $this->_neo4jHelper->getClient('graphenedb');
        $result = $client->run("Match (n:FakeEntity) RETURN n;");
        $this->assertEquals(100, count($result->getRecords()));
    }

    public function testCommand2()
    {
        $options2['command'] = 'misteio:neo4j:populate';
        $options2['--reset'] = true;
        $options2['--limit'] = 10;
        $this->application->run(new ArrayInput($options2));

        $client = $this->_neo4jHelper->getClient('graphenedb');
        $result = $client->run("Match (n:FakeEntity) RETURN n;");
        $this->assertEquals(10, count($result->getRecords()));
    }

    public function testCommand3()
    {
        $options3['command'] = 'misteio:neo4j:populate';
        $options3['--reset'] = true;
        $options3['--offset']= 10;
        $this->application->run(new ArrayInput($options3));

        $client = $this->_neo4jHelper->getClient('graphenedb');
        $result = $client->run("Match (n:FakeEntity) RETURN n;");
        $this->assertEquals(90, count($result->getRecords()));
    }

    public function testCommand4()
    {
        $options4['command'] = 'misteio:neo4j:populate';
        $options4['--reset'] = true;
        $options4['--type']  = 'FakeEntity';
        $this->application->run(new ArrayInput($options4));

        $client = $this->_neo4jHelper->getClient('graphenedb');
        $result = $client->run("Match (n:FakeEntity) RETURN n;");
        $this->assertEquals(100, count($result->getRecords()));
    }


    public function testCommandRunParallel()
    {
        $optionsRunParallel = [
            'command'   => 'misteio:neo4j:populate',
            '--reset'   => true,
            '--type'    => 'FakeEntity',
            '--batch'   => '10',
            '--threads' => '4',
        ];
        $this->application->run(new ArrayInput($optionsRunParallel));

        $client = $this->_neo4jHelper->getClient('graphenedb');
        $result = $client->run("Match (n:FakeEntity) RETURN n;");
        $this->assertGreaterThan(-1, count($result->getRecords()));
    }

    public function testCommandWrongType()
    {
        $optionsWrongType = [
            'command'  => 'misteio:neo4j:populate',
            '--type'   => 'UnknownType',
        ];
        $returnValue = $this->application->run(new ArrayInput($optionsWrongType));
        self::assertNotEquals(0, $returnValue, 'This command should failed: UNKNOWN TYPE');
    }
}
