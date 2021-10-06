<?php

namespace ACSEO\TypesenseBundle\Tests\Functional\Entity;

class Author
{
    private $name;
    private $country;

    public function __construct(string $name, string $country)
    {
        $this->name = $name;
        $this->country = $country;
    }

    public function __toString()
    {
        return $this->name;
    }
    
    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set the value of country
     *
     * @return  self
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }
}
