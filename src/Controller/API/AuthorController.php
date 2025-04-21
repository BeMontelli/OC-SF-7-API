<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Book;
use App\Entity\Author;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthorController extends AbstractController
{
    #[Route('/api/v1/authors', name: 'app_api_author_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Access denied')]
    public function index(AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        $authorList = $authorRepository->findAll();
        $jsonAuthorList = $serializer->serialize($authorList, 'json', ['groups' => ['author:index']]);

        return new JsonResponse($jsonAuthorList, Response::HTTP_OK, [],true);
    }

    #[Route('/api/v1/authors/{id}', name: 'app_api_author_read', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Access denied')]
    public function read(Author $author, AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        if ($author) {
            $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => ['author:read']]);
            return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/v1/authors', name: 'app_api_author_create', methods: ['POST'])]   
    #[IsGranted('ROLE_ADMIN', message: 'Access denied')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, BookRepository $bookRepository, ValidatorInterface $validator): JsonResponse 
    {
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $errors = $validator->validate($author);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse($serializer->serialize($errorMessages, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();

        $idBooks = $content['books'] ?? [];

        foreach ($author->getBooks() as $book) {
            $author->removeBook($book);
        }

        foreach ($idBooks as $idBook) {
            $book = $bookRepository->find($idBook);
            if ($book) {
                $author->addBook($book);
            } else {
                throw new \Exception("Book with ID $idBook not found");
            }
        }
     
        $em->persist($author);
        $em->flush();

        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'author:read']);

        $location = $urlGenerator->generate('app_api_author_read', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/v1/authors/{id}', name:"app_api_author_update", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Access denied')]
    public function update(Request $request, SerializerInterface $serializer, Author $currentAuthor, EntityManagerInterface $em, BookRepository $bookRepository, ValidatorInterface $validator): JsonResponse 
    {
        $updatedAuthor = $serializer->deserialize($request->getContent(), 
        Author::class, 
                'json', 
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);

        $errors = $validator->validate($updatedAuthor);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse($serializer->serialize($errorMessages, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
                
        $content = $request->toArray();
        $idBooks = $content['books'] ?? [];

        foreach ($updatedAuthor->getBooks() as $book) {
            $updatedAuthor->removeBook($book);
        }

        foreach ($idBooks as $idBook) {
            $book = $bookRepository->find($idBook);
            if ($book) {
                $updatedAuthor->addBook($book);
            } else {
                throw new \Exception("Book with ID $idBook not found");
            }
        }
        
        $em->persist($updatedAuthor);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
   }

    #[Route('/api/v1/authors/{id}', name: 'app_api_author_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Access denied')]
    public function delete(Author $author, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($author);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}