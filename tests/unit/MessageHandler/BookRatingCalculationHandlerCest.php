<?php

namespace App\Tests\unit\Service;

use App\Message\BookRatingCalculation;
use App\MessageHandler\BookRatingCalculationHandler;
use App\Tests\UnitTester;
use Codeception\Example;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\MessageBuilder;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Standalone\MockService\MockServerEnvConfig;

class BookRatingCalculationHandlerCest
{
    protected Matcher $matcher;
    protected MockServerEnvConfig $config;
    protected InteractionBuilder $interactionBuilder;
    protected MessageBuilder $messageBuilder;
    protected object $review;
    protected object $book;
    protected string $reviewIri;
    protected static $reviewInteractionRegistered = false;

    public function _before(UnitTester $I)
    {
        $this->config = new MockServerEnvConfig();
        $this->interactionBuilder = new InteractionBuilder($this->config);
        $this->messageBuilder = new MessageBuilder($this->config);

        $this->matcher = new Matcher();

        $this->reviewIri = '/api/reviews/fb5a885f-f7e8-4a50-950f-c1a64a94d500';

        $review = new \stdClass();
        $review->{'@id'} = $this->matcher->term($this->reviewIri, '\/api\/reviews\/[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}');
        $review->{'@type'} = 'http://schema.org/Review';
        $review->body = $this->matcher->like('Necessitatibus eius commodi odio ut aliquid. Sit enim molestias in minus aliquid repudiandae qui. Distinctio modi officiis eos suscipit. Vel ut modi quia recusandae qui eligendi. Voluptas totam asperiores ab tenetur voluptatem repudiandae reiciendis.');

        $this->review = $review;

        $book = new \stdClass();
        $book->{'@id'} = $this->matcher->term('/api/books/0114b2a8-3347-49d8-ad99-0e792c5a30e6', '\/api\/books\/[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}');
        $book->{'@type'} = 'Book';
        $book->title = $this->matcher->like('Voluptas et tempora repellat corporis excepturi.');
        $book->description = $this->matcher->like('Quaerat odit quia nisi accusantium natus voluptatem. Explicabo corporis eligendi ut ut sapiente ut qui quidem. Optio amet velit aut delectus. Sed alias asperiores perspiciatis deserunt omnis. Mollitia unde id in.');
        $book->author = $this->matcher->like('Melisa Kassulke');
        $book->publicationDate = $this->matcher->dateTimeISO8601('1999-02-13T00:00:00+07:00');
        $book->reviews = $this->matcher->eachLike($review, 0);

        $this->book = $book;
    }

    /**
     * @example(page="1")
     * @example(page="2")
     * @example(page="3")
     * @example(page="4")
     */
    public function testInvoke(UnitTester $I, Example $example)
    {
        $page = (int) $example['page'];
        $this->setUpGettingBooks($page);
        $this->setUpGettingReview();
        $this->setUpMessage(function (string $raw) use ($I) {
            $page = json_decode($raw, true)['contents']['page'];

            $message = new BookRatingCalculation($page);
            $handler = new BookRatingCalculationHandler($I->grabService('doctrine')->getManager(), $I->grabService('messenger.default_bus'));

            $handler->setBaseUrl($this->config->getBaseUri());
            $handler($message);
        }, $page);

        $this->messageBuilder->verify();
        $this->interactionBuilder->verify();
    }

    protected function setUpGettingBooks(int $page): void
    {
        // build the request
        $request = new ConsumerRequest();
        $request
            ->setMethod('GET')
            ->setPath('/api/books')
            ->setQuery("page={$page}");

        // build the response
        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/ld+json; charset=utf-8')
            ->setBody([
                '@context' => '/api/contexts/Book',
                '@id' => '/api/books',
                '@type' => 'hydra:Collection',
                'hydra:member' => $this->matcher->eachLike((array) $this->book),
                'hydra:totalItems' => $this->matcher->like(8),
                'hydra:view' => [
                    '@id' => "/api/books?page={$page}",
                    '@type' => 'hydra:PartialCollectionView',
                    'hydra:first' => '/api/books?page=1',
                    'hydra:last' => '/api/books?page=4'
                ] +
                (
                    $page > 1 ? [
                        'hydra:previous' => '/api/books?page=' . ($page - 1),
                    ] : []
                ) +
                (
                    $page < 4 ? [
                        'hydra:next' => '/api/books?page=' . ($page + 1),
                    ] : []
                ),
            ]);

        $this->interactionBuilder->given('Book Fixtures Loaded')
            ->uponReceiving("A GET request to get books at page {$page}")
            ->with($request)
            ->willRespondWith($response);
    }

    protected function setUpGettingReview(): void
    {
        if (static::$reviewInteractionRegistered) {
            return;
        }

        // build the request
        $request = new ConsumerRequest();
        $request
            ->setMethod('GET')
            ->setPath($this->reviewIri);

        // build the response
        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/ld+json; charset=utf-8')
            ->setBody([
                '@context' => '/api/contexts/Review',
                'rating' => $this->matcher->like(4),
                'book' => [
                  '@id' => $this->matcher->term('/api/books/1b45e925-318a-4e8b-a53b-e5fe37a6454d', '\/api\/books\/[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}'),
                  '@type' => 'Book',
                  'title' => $this->matcher->like('Voluptas et tempora repellat corporis excepturi.')
                ],
                'author' => $this->matcher->like('Taya Paucek V'),
                'publicationDate' => $this->matcher->dateTimeISO8601('1971-05-06T04:06:57+08:00')
            ] + (array) $this->review);

        $this->interactionBuilder->given('Book Fixtures Loaded')
            ->uponReceiving('A GET request to get review')
            ->with($request)
            ->willRespondWith($response);

        static::$reviewInteractionRegistered = true;
    }

    protected function setUpMessage(callable $callback, int $page): void
    {
        $contents       = new \stdClass();
        $contents->page = "/api/books?page={$page}";

        $metadata = [
            'queue' => 'messenger-async',
            'routing_key' => 'messenger-async'
        ];

        $this->messageBuilder
            ->given('Book Fixtures Loaded')
            ->expectsToReceive('Event to calculate book rating')
            ->withMetadata($metadata)
            ->withContent($contents);

        $this->messageBuilder->setCallback($callback);
    }
}
