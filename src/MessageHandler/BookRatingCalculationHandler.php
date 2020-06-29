<?php

namespace App\MessageHandler;

use App\Entity\BookRating;
use App\Message\BookRatingCalculation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class BookRatingCalculationHandler implements MessageHandlerInterface
{
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MessageBusInterface
     */
    private $bus;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {
        $this->entityManager = $entityManager;
        $this->bus = $bus;
        $this->client = HttpClient::create();
    }

    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function __invoke(BookRatingCalculation $message)
    {
        $response = $this->client->request('GET', "{$this->baseUrl}{$message->getPage()}");

        if (200 == $response->getStatusCode()) {
            foreach ($response->toArray()['hydra:member'] as $book) {
                $bookId = $book['@id'];
                $rating = $this->calculateBookRating($book);

                $bookRating = $this->entityManager
                    ->getRepository(BookRating::class)
                    ->findBy(['bookId' => $bookId]);

                if (!$bookRating) {
                    $bookRating = new BookRating();
                    $bookRating->setBookId($bookId);

                    $this->entityManager->persist($bookRating);
                }

                $bookRating->setRating($rating);
                $this->entityManager->flush();
            }

            if ($response->toArray()['hydra:view']['hydra:next']) {
                $this->bus->dispatch(new BookRatingCalculation($response->toArray()['hydra:view']['hydra:next']));
            }
        }
    }

    protected function calculateBookRating(array $book): float
    {
        $totalRating = 0;
        $totalReviews = count($book['reviews']);
        foreach ($book['reviews'] as $review) {
            $reviewId = $review['@id'];
            $response = $this->client->request('GET', "{$this->baseUrl}{$reviewId}");

            if (200 == $response->getStatusCode()) {
                $totalRating += $response->toArray()['rating'];
            }
        }

        return $totalRating / $totalReviews;
    }
}
