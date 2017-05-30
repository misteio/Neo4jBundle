<?php

namespace Misteio\Neo4jBundle\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Misteio\Neo4jBundle\Event\Neo4jEvent;
use Misteio\Neo4jBundle\Factory\Neo4jFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


class Neo4jSubscriber implements EventSubscriber
{
    /** @var array  */
    protected $aTypes;

    /** @var EventDispatcherInterface  */
    protected $eventDispatcher;

    /** @var Container  */
    protected $container;

    /** @var array  */
    protected $mapping;

    /** @var Neo4jFactory  */
    protected $neo4jFactory;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @param $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
        foreach ($this->mapping as $key=>$mapping){
            $this->aTypes[] = $key;
        }
    }

    /**
     * @param Neo4jFactory $neo4jFactory
     */
    public function setNeo4jFactory(Neo4jFactory $neo4jFactory)
    {
        $this->neo4jFactory = $neo4jFactory;

    }


    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'postUpdate',
            'postRemove',
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->sendEvent($args, 'persist');
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $this->sendEvent($args, 'remove');
    }


    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->sendEvent($args, 'update');
    }

    /**
     * @param LifecycleEventArgs $args
     * @param $action
     */
    public function sendEvent(LifecycleEventArgs $args, $action)
    {
        $entity = $args->getEntity();
        $a      = explode("\\",get_class($entity));
        $type   = end($a);

        if(in_array($type, $this->aTypes)){
            if(array_key_exists('auto_event',$this->mapping[$type])){
                $this->_catchEvent( $entity,
                                    $this->mapping[$type]['transformer'],
                                    $this->mapping[$type]['connection'],
                                    $action);
            }else{
                $event = new Neo4jEvent($action, $entity);
                $this->eventDispatcher->dispatch("misteio.neo4j.event", $event);
            }
        }
    }

    /**
     * @param $entity
     * @param $transformer
     * @param $connectionName
     * @param $action
     */
    private function _catchEvent($entity,$transformer, $connectionName, $action)
    {
        if($action == 'persist' || $action == 'update'){
            $this->container->get($transformer)->transform($entity, $connectionName);
        }else{
            $this->neo4jFactory->removeFromNeo4j($entity, $connectionName);
        }
    }
}
