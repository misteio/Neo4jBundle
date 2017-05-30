Neo4jSearchBundle
=========

[![Build Status](https://travis-ci.org/Misteio/Neo4jBundle.svg?branch=master)](https://travis-ci.org/Misteio/Neo4jBundle)
[![Code Climate](https://codeclimate.com/github/Misteio/Neo4jBundle/badges/gpa.svg)](https://codeclimate.com/github/Misteio/Neo4jBundle)
[![Latest Stable Version](https://poser.pugx.org/misteio/neo4j-bundle/v/stable)](https://packagist.org/packages/misteio/neo4j-bundle)
[![codecov](https://codecov.io/gh/Misteio/Neo4jBundle/branch/master/graph/badge.svg)](https://codecov.io/gh/misteio/Neo4jBundle)

Neo4jBundle is a Symfony Bundle designed for simply use Neo4J 3.x with Doctrine 2.x

## Installation

Via Composer

``` bash
$ composer require misteio/neo4jbundle-bundle
```
or in composer.json file
``` bash
"misteio/neo4jbundle-bundle": "dev-master"
```

Register the bundle in `app/AppKernel.php`:

``` php
public function registerBundles()
{
    return array(
        // ...
        new Misteio\Neo4jBundle\Neo4jBundle(),
        // ...
    );
}
```

Configuration
-------------

Configure your connections and mappings in `app/config/config.yml` :

``` yaml
imports:
    - { resource: parameters.yml }


misteio_neo4j:
    connections:
      %neo4j.hosts%
    mappings:
      %neo4j.mappings%
```

An then create a file named parameters.yml in `app/config`

``` yaml
parameters:
    neo4j.hosts:
      graphenedb:
             host: 'yourHostWithoutScheme'
             port: yourPort
             user: 'username'
             password: 'yourPassword'

    neo4j.mappings:
      FakeEntity:
        class: '\Misteio\Neo4jBundle\Tests\Entity\FakeEntity'
        transformer: 'neo4j.fakeentity.transformer'
        auto_event: true
        connection: 'graphenedb'
        indexes:
          - 'name'
        # only available for neo4j 3.2 and above  
        composite_indexes: 
          - 'id,name'


```

Example of entity


```php
<?php

namespace Name\NameBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class YourEntityClassName
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;


    /**
     * Set id
     *
     * @param integer $id
     * @return Id
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }


    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;



    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return City
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

```

As you can see you have to create a Transformer for your Entities.


``` php
<?php
namespace Name\NameBundle\Entity\Transformer\EntityTransformer;
use Misteio\Neo4jBundle\Helper\Neo4jHelper;
use Name\NameBundle\Entity\YourEntityClassName;

class YourTransformerClassName
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
            $this->neo4jHelper->getClient($connectionName)->run('CREATE (n:FakeEntity {id :{id}, name:{name}} )', ['id' => $fake->getId(), 'name' => $fake->getName()]);
    
            return true;
        }
}
```



## Usage

If auto_event is set, you have nothing to do for creation, update and deletion of your entities.

You can begin to call Neo4j with MisteioNeo4jHelper and Graphaware. Example in a Controller.

```php

    $client     = $this->getContainer()->get('misteio.neo4j.helper')->getClient('graphenedb');
    $result     = $client->run("Match (n:FakeEntity) RETURN n;");
    $nodes      = $result->getRecords();
```
For more information about querying Neo4j, look at [GraphAware Neo4j PHP Client](https://github.com/graphaware/neo4j-php-client)


If auto_event is not set, you can listen `misteio.neo4j.event` like this :
```yaml
name.neo4j.subscriber:
    class: Name\NameBundle\Subscriber\Neo4jSubscriber
    tags:
        - { name: kernel.event_listener, event: misteio.neo4j.event, method: onNeo4jEntityAction }
``` 

And in your EventListener Class
```php
<?php

namespace Name\NameBundle\EventListener;

use Misteio\Neo4jBundle\Event\Neo4jEvent;

class Neo4jListener
{
    /**
     * @param Neo4jEvent $event
     */
    public function onNeo4jEntityAction(Neo4jEvent $event)
    {
        //Action can be persist, update and delete
        $action = $event->getAction();
        //Your Doctrine Entity
        $entity = $event->getEntity();
    }
}
``` 

## Command for populate
After configuration of your entities, you maybe want make them available on Neo4j. You have to use `php app/console misteio:neo4j:populate`. Differents options are available :

* --limit=int : Limit of your collection
* --offset=int : Offset of your collection 
* --type=string : Name of your Object (in our example it's YourEntityClassName)
* --threads=int : Number of threads you want to use for. If you use it, limit will not be available, and you have to set a batch.
* --reset : For reset your indexes. BE CAREFULL, all your data will be lost in your Neo4j Cluster
* --batch=int : Length of collection per threads. Use this only with threads



## Security
If you discover a security vulnerability , please email instead of using the issue tracker. All security vulnerabilities will be promptly addressed.

## Standalone Test

### How to test

1. clone repo : `$ sudo git clone https://github.com/Misteio/Neo4jBundle.git`
2. go into directory : `$ cd Neo4jBundle/`
3. install composer as explained here : https://getcomposer.org/download/
4. launch composer update : `$ ./composer.phar update`
5. launch test : `$ ./vendor/bin/phpunit`

## License
This Bundle is open-sourced software licensed under the MIT license