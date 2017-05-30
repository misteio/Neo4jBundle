<?php

namespace Misteio\Neo4jBundle\Helper;

use GraphAware\Neo4j\Client\ClientBuilder;

class Neo4jHelper
{
    private $neo4jConnections;

    /**
     * Neo4jHelper constructor.
     * @param $neo4jConnections
     */
    public function __construct($neo4jConnections)
    {
        $this->neo4jConnections = $neo4jConnections;
    }


    /**
     * @return ClientBuilder
     */
    public function getClient($connectionName)
    {

        $host       = $this->neo4jConnections[$connectionName]['host'];
        $port       = $this->neo4jConnections[$connectionName]['port'];
        $user       = $this->neo4jConnections[$connectionName]['user'];
        $password   = $this->neo4jConnections[$connectionName]['password'];


        $neo4jClient = ClientBuilder::create()
            ->addConnection('default', "http://{$user}:{$password}@{$host}:{$port}")
            ->build();

        return $neo4jClient;
    }
}
