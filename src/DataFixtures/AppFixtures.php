<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Comment;
use App\Entity\Place;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppFixtures extends Fixture
{
    private $passwordHasherFactory;

    public function __construct(PasswordHasherFactoryInterface $passwordHasherFactory)
    {
        $this->passwordHasherFactory = $passwordHasherFactory;
    }

    public function load(ObjectManager $manager): void
    {
        $slanic = new Place();
        $slanic->setCity('Slanic');
        $slanic->setName('Slanic Prahova');
        $slanic->setSlug('slanic');
        $manager->persist($slanic);

        $peles = new Place();
        $peles->setCity('Sinaia');
        $peles->setName('Peles');
        $peles->setSlug('peles');
        $manager->persist($peles);

        $comment1 = new Comment();
        $comment1->setPlace($slanic);
        $comment1->setAuthor('Abc');
        $comment1->setEmail('abc@example.com');
        $comment1->setText('This was a great place.');
        $comment1->setState('published');
        $manager->persist($comment1);

        $comment2 = new Comment();
        $comment2->setPlace($peles);
        $comment2->setAuthor('Abc');
        $comment2->setEmail('abc@example.com');
        $comment2->setState('published');
        $comment2->setText('This was a great place.');
        $manager->persist($comment2);

        $admin = new Admin();
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setUsername('admin');
        $admin->setPassword($this->passwordHasherFactory->getPasswordHasher(Admin::class)->hash('admin', null));
        $manager->persist($admin);

        $manager->flush();
    }
}