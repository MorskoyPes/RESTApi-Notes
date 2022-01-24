<?php

namespace App\DataFixtures;

use App\Entity\Masters;
use App\Entity\Notes;
use App\Repository\MastersRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create();
//         $masters = (new Masters())
//             ->setName($faker->firstName())
//             ->setLastName($faker->lastName())
//         ;
//         var_dump($masters);
//         die;
//         $manager->persist($masters);
//        $masters = (new Masters())
//            ->setName($faker->firstName())
//            ->setLastName($faker->lastName())
//        ;
//        $manager->persist($masters);
//        $masters = (new Masters())
//            ->getName()
//            ->
//        ;
        $masters = (FixtureInterface::class)->
        load(MastersRepository::class)->getRepository(Masters::class)->find(7);
        var_dump($masters);
        die();
//        $manager->persist($masters);

        $note = (new Notes())
            ->setTime(\DateTime::createFromFormat('h:m:s', '10:00:00'))
            ->setDate(\DateTime::createFromFormat('Y-m-d', '2022-01-24'))
            ->setMaster($masters)
            ;
        $manager->persist($note);

        $manager->flush();
    }
}
