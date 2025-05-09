<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\BookRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\DeserializationContext;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\AuthorRepository;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class BookController extends AbstractController
{
    #[Route('/api/v1/books', name: 'app_api_book_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Access denied')]
    public function index(BookRepository $bookRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllBooks-" . $page . "-" . $limit;
        $bookList = $cachePool->get($idCache, function (ItemInterface $item) use ($bookRepository, $page, $limit) {
            $item->tag("booksCache")->expiresAfter(3600);
            return $bookRepository->findAllWithPagination($page, $limit);
        });
        
        $context = SerializationContext::create()->setGroups(['book:index']);
        $jsonBookList = $serializer->serialize($bookList, 'json', $context);

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [],true);
    }

    #[Route('/api/v1/books/{id}', name: 'app_api_book_read', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Access denied')]
    public function read(Book $book, BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        if ($book) {
            $context = SerializationContext::create()->setGroups(['book:read']);
            $jsonBook = $serializer->serialize($book, 'json', $context);
            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/v1/books', name: 'app_api_book_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Access denied')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse 
    {
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $errors = $validator->validate($book);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse($serializer->serialize($errorMessages, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();

        $idAuthor = $content['author'] ?? -1;
        if ($idAuthor == -1) {
            return new JsonResponse(['error' => 'Author not found'], Response::HTTP_NOT_FOUND);
        }
        $book->setAuthor($authorRepository->find($idAuthor));
     
        $cachePool->invalidateTags(['booksCache']);
        $em->persist($book);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['book:read']);
        $jsonBook = $serializer->serialize($book, 'json', $context);

        $location = $urlGenerator->generate('app_api_book_read', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/v1/books/{id}', name:"app_api_book_update", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Access denied')]
    public function update(Request $request, SerializerInterface $serializer, Book $currentBook, EntityManagerInterface $em, AuthorRepository $authorRepository, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse 
    {
        $content = $request->toArray();

        $title = $content['title'] ?? null;
        if ($title) $currentBook->setTitle($title);

        $coverText = $content['coverText'] ?? null;
        if ($coverText) $currentBook->setCoverText($coverText);
        
        $errors = $validator->validate($currentBook);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse($serializer->serialize($errorMessages, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $idAuthor = $content['author'] ?? null;
        if ($idAuthor) {
            $author = $authorRepository->find($idAuthor);
            if (!$author) {
                return new JsonResponse(['message' => 'Author not found'], Response::HTTP_BAD_REQUEST);
            }
            $currentBook->setAuthor($author);
        }
        
        $cachePool->invalidateTags(['booksCache']);
        $em->persist($currentBook);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
   }

    #[Route('/api/v1/books/{id}', name: 'app_api_book_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Access denied')]
    public function delete(Book $book, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
    {
        $cachePool->invalidateTags(['booksCache']);
        $em->remove($book);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}