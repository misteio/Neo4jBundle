<?php
namespace Misteio\Neo4jBundle\Factory;
use Misteio\Neo4jBundle\Helper\Neo4jHelper;
use Symfony\Component\DependencyInjection\Container;


/**
 * Class Neo4jFactory
 * @package Misteio\ElasticSearchBundle\Handler
 */
class Neo4jFactory
{
    /** @var  Neo4jHelper */
    protected $neo4jHelper;

    /** @var  Container */
    protected $container;


    /**
     * @param Neo4jHelper $neo4jHelper
     */
    public function setNeo4jHelper(Neo4jHelper $neo4jHelper)
    {
        $this->neo4jHelper              = $neo4jHelper;
    }

    /**
     * @param $entity
     * @param $connectionName
     */
    public function removeFromNeo4j($entity, $connectionName){
        $type          = explode("\\",get_class($entity));
        $type          = end($type);
        $this->neo4jHelper->getClient($connectionName)->run('MATCH (n:' . $type . ' {id: {id}}) DETACH DELETE n;', ['id' => $entity->getId()]);
    }

    /**
     * @param $entityName
     * @param $connectionName
     */
    public function removeAllFromNeo4j($entityName, $connectionName){
        $this->neo4jHelper->getClient($connectionName)->run('MATCH (n: ' . $entityName .') DETACH DELETE n;');
    }


    /**
     * @param $entityName
     * @param $connectionName
     * @param $indexName
     */
    public function createIndex($entityName, $connectionName, $indexName){
        $this->neo4jHelper->getClient($connectionName)->run('CREATE INDEX ON :' . $entityName . '( ' . $indexName . ');');
    }

    /**
     * @param $entityName
     * @param $connectionName
     * @param $indexCompositeName
     */
    public function createCompositeIndex($entityName, $connectionName, $indexCompositeName){
        $this->neo4jHelper->getClient($connectionName)->run('CREATE INDEX ON :' . $entityName . '( ' . $indexCompositeName . ');');
    }

    /**
     * @param $entityName
     * @param $connectionName
     * @param $indexName
     */
    public function dropIndex($entityName, $connectionName, $indexName){
        $this->neo4jHelper->getClient($connectionName)->run('DROP INDEX ON :' . $entityName . '( ' . $indexName . ');');
    }

    /**
     * @param $entityName
     * @param $connectionName
     * @param $indexCompositeName
     */
    public function dropCompositeIndex($entityName, $connectionName, $indexCompositeName){
        $this->neo4jHelper->getClient($connectionName)->run('DROP INDEX ON :' . $entityName . '( ' . $indexCompositeName . ');');
    }
}
