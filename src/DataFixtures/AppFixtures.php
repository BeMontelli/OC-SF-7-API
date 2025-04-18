<?php
// src\DataFixtures\AppFixtures.php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Author;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
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