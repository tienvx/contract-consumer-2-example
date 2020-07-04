<?php

namespace App\Message;

class BookRatingCalculation
{
    private string $page;

    public function __construct(string $page)
    {
        $this->page = $page;
    }

    public function getPage(): string
    {
        return $this->page;
    }
}
