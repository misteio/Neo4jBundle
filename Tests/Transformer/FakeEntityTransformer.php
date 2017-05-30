<?php
namespace Misteio\Neo4jBundle\Tests\Transformer;
use Misteio\Neo4jBundle\Tests\Entity\FakeEntity;
use Misteio\Neo4jBundle\Helper\Neo4jHelper;

class FakeEntityTransformer
{
    /** @var  Neo4jHelper */
    private $neo4jHelper;

    /**
     * @param Neo4jHelper $neo4jHelper
     */
    public function setNeo4jHelper(Neo4jHelper $neo4jHelper)
    {
        $this->neo4jHelper = $neo4jHelper;
    }

    /**
     * @param FakeEntity $fake
     * @param $connectionName
     * @return bool
     */
    public function transform(FakeEntity $fake, $connectionName)
    {
        $this->neo4jHelper->getClient($connectionName)->run('MERGE (n:FakeEntity {id :{id}, name:{name}} )', ['id' => $fake->getId(), 'name' => $fake->getName()]);

        return true;
    }
}