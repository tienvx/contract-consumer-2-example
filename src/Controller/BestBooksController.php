<?php

namespace App\Controller;

use App\Entity\BookRating;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class BestBooksController extends AbstractController
{
    /**
     * @Route("/best/books", name="best_books")
     */
    public function index()
    {
        return $this->getDoctrine()
            ->getRepository(BookRating::class)
            ->getMostRatingBooks(10);
    }
}
