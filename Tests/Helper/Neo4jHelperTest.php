<?php

namespace Misteio\Neo4j\Tests\Helper;

use Misteio\Neo4jBundle\Helper\Neo4jHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Neo4jHelperTest extends KernelTestCase
{
    /**
     * @var Neo4jHelper
     */
    private $_neo4jHelper;


    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        self::bootKernel();
        $this->_neo4jHelper     = static::$kernel->getContainer()->get('misteio.neo4j.helper');

    }

    public function testClientConnection()
    {
        $client = $this->_neo4jHelper->getClient('graphenedb');
        $result = $client->run("MERGE (n:Person {name: 'Test'}) RETURN (n.name)");
        $this->assertEquals('Test' , $result->getRecord()->value("(n.name)"));
    }

}
