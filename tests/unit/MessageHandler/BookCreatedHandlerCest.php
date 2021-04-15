<?php

namespace App\Tests\unit\MessageHandler;

use App\Message\BookCreated;
use App\MessageHandler\BookCreatedHandler;
use App\Tests\UnitTester;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\MessageBuilder;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Standalone\MockService\MockServerEnvConfig;

class BookCreatedHandlerCest
{
    protected Matcher $matcher;
    protected MockServerEnvConfig $config;
    protected InteractionBuilder $interactionBuilder;
    protected MessageBuilder $messageBuilder;
    protected array $review;
    protected array $book;
    protected string $uuid;

    public function _before(UnitTester $I)
    {
        $this->config = new MockServerEnvConfig();
        $this->interactionBuilder = new InteractionBuilder($this->config);
        $this->messageBuilder = new MessageBuilder($this->config);

        $this->matcher = new Matcher();

        $this->uuid = 'fb5a885f-f7e8-4a50-950f-c1a64a94d500';

        $this->setUpGeneratingCover();
        $this->setUpMessage(function (string $raw) {
            $uuid = json_decode($raw, true)['contents']['uuid'];

            $message = new BookCreated($uuid);
            $handler = new BookCreatedHandler();

            $handler->setBaseUrl($this->config->getBaseUri());
            $handler($message);
        });
    }

    public function testInvoke()
    {
        $this->messageBuilder->verify();
        $this->interactionBuilder->verify();
    }

    protected function setUpMessage(callable $callback): void
    {
        $contents       = new \stdClass();
        $contents->uuid = $this->uuid;

        $metadata = [
            'queue' => 'messenger-async',
            'routing_key' => 'messenger-async'
        ];

        $this->messageBuilder
            ->given('Book Fixtures Loaded')
            ->expectsToReceive('Created book event')
            ->withMetadata($metadata)
            ->withContent($contents);

        $this->messageBuilder->setCallback($callback);
    }

    protected function setUpGeneratingCover(): void
    {
        // build the request
        $request = new ConsumerRequest();
        $request
            ->setMethod('PUT')
            ->setPath("/api/books/{$this->uuid}/generate-cover")
            ->addHeader('Content-Type', 'application/json')
            ->setBody([]);

        // build the response
        $response = new ProviderResponse();
        $response
            ->setStatus(204);

        $this->interactionBuilder->given('Book Fixtures Loaded')
            ->uponReceiving('A PUT request to generate book cover')
            ->with($request)
            ->willRespondWith($response);
    }
}
