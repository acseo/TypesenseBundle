<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Tests\Functional\Entity;

class Tag
{
    private $id;
    private $label;

    public function __construct(int $id, ?string $label = null)
    {
        $this->id = $id;
        $this->label = $label;
    }

    public function __toString()
    {
        return $this->label;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}