<?php

namespace App\MessageHandler;

use App\Message\BookCreated;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookCreatedHandler implements MessageHandlerInterface
{
    private HttpClientInterface $client;
    private string $baseUrl;

    public function __construct()
    {
        $this->client = HttpClient::create();
    }

    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function __invoke(BookCreated $message)
    {
        $this->generateCover($message->getUuid());
    }

    protected function generateCover(string $uuid): bool
    {
        $response = $this->client->request('PUT', "{$this->baseUrl}/api/books/{$uuid}/generate-cover", [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [],
        ]);

        return 204 === $response->getStatusCode();
    }
}
