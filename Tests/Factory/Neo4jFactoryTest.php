<?php
namespace Misteio\Neo4jBundle\Tests\Factory;


use Doctrine\ORM\EntityManager;
use Misteio\Neo4jBundle\Factory\Neo4jFactory;
use Misteio\Neo4jBundle\Helper\Neo4jHelper;
use Misteio\Neo4jBundle\Tests\TestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;


class Neo4jFactoryTest extends KernelTestCase
{
    use TestTrait;

    /**
     * @var Neo4jFactory
     */
    private $_neo4jFactory;

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
        $this->_neo4jFactory        = static::$kernel->getContainer()->get('misteio.neo4j.factory');
        $this->_neo4jHelper         = static::$kernel->getContainer()->get('misteio.neo4j.helper');
        $this->_em                  = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->application          = new Application(self::$kernel);
        $this->application->setAutoExit(false);
        $this->loadFixtures();
    }


    public function testRemoveFromNeo4j(){
        $client = $this->_neo4jHelper->getClient('graphenedb');
        $result = $client->run("Match (n:FakeEntity {name: 'My test 1'}) RETURN (n.name);");
        $this->assertEquals('My test 1' , $result->getRecord()->value("(n.name)"));


        $entity = $this->_em->getRepository('\Misteio\Neo4jBundle\Tests\Entity\FakeEntity')->find(1);
        $this->_neo4jFactory->removeFromNeo4j($entity, 'graphenedb');

        $result = $client->run("Match (n:FakeEntity {name: 'My test 1'}) RETURN (n.name);");
        $this->assertEquals(0 , count($result->getRecords()));
    }

    public function testRemoveAllFromNeo4j(){
        $client = $this->_neo4jHelper->getClient('graphenedb');
        $result = $client->run("Match (n:FakeEntity) RETURN n;");
        $this->assertGreaterThan(1 , count($result->getRecords()));

        $this->_neo4jFactory->removeAllFromNeo4j('FakeEntity', 'graphenedb');

        $result = $client->run("Match (n:FakeEntity) RETURN n;");
        $this->assertEquals(0 , count($result->getRecords()));
    }
}