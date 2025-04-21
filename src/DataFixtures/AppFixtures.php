<?php
// src\DataFixtures\AppFixtures.php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    public function __construct(private UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@project.dev');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
        $manager->persist($user);

        $user = new User();
        $user->setEmail('admin@project.dev');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
        $manager->persist($user);

        $authors = [];
        for ($i = 0; $i < 5; $i++) {
            $author = new Author;
            $author->setFirstname('Author ' . $i);
            $author->setLastname('Lastname ' . $i);
            $manager->persist($author);
            $authors[] = $author;
        }

        for ($i = 0; $i < 20; $i++) {
            $book = new Book;
            $book->setTitle('Book ' . $i);
            $book->setCoverText('Quatrième de couverture numéro : ' . $i);
            $book->setAuthor($authors[array_rand($authors)]);
            $manager->persist($book);
        }

        $manager->flush();
    }
}