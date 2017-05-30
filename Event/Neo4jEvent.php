<?php

namespace Misteio\Neo4jBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class Neo4jEvent extends Event
{
    /** @var  string */
    protected $action;
    protected $entity;

    public function __construct($action, $entity)
    {
        $this->action          = $action;
        $this->entity          = $entity;
    }

    public function getAction()
    {
        return $this->action;
    }


    public function getEntity()
    {
        return $this->entity;
    }

}
