<?php

namespace App\Entity;

use App\Repository\BookRatingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BookRatingRepository::class)
 */
class BookRating
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $bookId;

    /**
     * @ORM\Column(type="float")
     */
    private $rating;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBookId(): ?string
    {
        return $this->bookId;
    }

    public function setBookId(string $bookId): self
    {
        $this->bookId = $bookId;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(float $rating): self
    {
        $this->rating = $rating;

        return $this;
    }
}
