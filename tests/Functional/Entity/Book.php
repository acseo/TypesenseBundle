<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Tests\Functional\Entity;

class Book
{
    private $id;
    private $title;
    private $author;
    private $publishedAt;

    public function __construct($id, string $title, $author, \Datetime $publishedAt)
    {
        $this->id          = $id;
        $this->title       = $title;
        $this->author      = $author;
        $this->publishedAt = $publishedAt;
    }

    /**
     * Get the value of id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id.
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of title.
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title.
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the value of author.
     */
    public function setAuthor($author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get the value of author.
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Get the value of publishedAt.
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * Set the value of publishedAt.
     */
    public function setPublishedAt($publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }
}
