<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\BookRepository;

final class BookController extends AbstractController
{
    #[Route('/api/v1/books', name: 'app_api_book', methods: ['GET'])]
    public function index(BookRepository $bookRepository): JsonResponse
    {
        $bookList = $bookRepository->findAll();

        return new JsonResponse([
            'books' => $bookList,
        ]);
    }
}