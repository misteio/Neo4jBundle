<?php

namespace Misteio\Neo4jBundle\Tests;


use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Misteio\Neo4jBundle\Tests\DataFixtures\LoadData;
use Symfony\Component\Console\Input\ArrayInput;

trait TestTrait
{
    public function loadFixtures(array $options = [])
    {
        $options['command'] = 'doctrine:database:create';
        $this->application->run(new ArrayInput($options));

        $options['command'] = 'doctrine:schema:create';
        $this->application->run(new ArrayInput($options));


        $loader = new Loader();
        $loader->addFixture(new LoadData());

        $purger = new ORMPurger($this->_em);
        $executor = new ORMExecutor($this->_em, $purger);
        $executor->execute($loader->getFixtures());
    }
}
