<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\BookRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Book;

final class BookController extends AbstractController
{
    #[Route('/api/v1/books', name: 'app_api_book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $bookList = $bookRepository->findAll();
        $jsonBookList = $serializer->serialize($bookList, 'json');

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [],true);
    }
    #[Route('/api/v1/books/{id}', name: 'app_api_book_read', methods: ['GET'])]
    public function read(Book $book, BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        if ($book) {
            $jsonBook = $serializer->serialize($book, 'json');
            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
    }
}