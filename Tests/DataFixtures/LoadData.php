<?php

namespace Misteio\Neo4jBundle\Tests\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Misteio\Neo4jBundle\Tests\Entity\FakeEntity;

class LoadData implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 100; $i++) {
            $fake  = new FakeEntity();
            $fake->setName("My test {$i}");
            $manager->persist($fake);
        }
        
        $manager->flush();
    }
}
