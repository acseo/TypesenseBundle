<?php

namespace ACSEO\TypesenseBundle\Manager;


class TypesenseResponse
{
    private $facetCounts;
    private $found;
    private $hits;
    private $hydratedHits;
    private $isHydrated;
    private $page;
    private $searchTimeMs;
    

    public function __construct(array $result)
    {
        $this->facetCounts = $result['facet_counts'];
        $this->found = $result['found'];
        $this->hits = $result['hits'];
        $this->page = $result['page'];
        $this->searchTimeMs = $result['search_time_ms'];
        $this->isHydrated = false;
        $this->hydratedHits = null;
    }

    /**
     * Get the value of facetCounts
     */ 
    public function getFacetCounts()
    {
        return $this->facetCounts;
    }

    /**
     * Get the value of hits
     */ 
    public function getResults()
    {
        if ($this->isHydrated) {
            return $this->hydratedHits;
        }
        return $this->hits;
    }

    public function getRawResults()
    {
        return $this->hits;
    }

    /**
     * Get the value of page
     */ 
    public function getPage()
    {
        return $this->page;
    }
  

    /**
     * Set the value of hydratedHits
     *
     * @return  self
     */ 
    public function setHydratedHits($hydratedHits)
    {
        $this->hydratedHits = $hydratedHits;

        return $this;
    }

    /**
     * Set the value of isHydrated
     *
     * @return  self
     */ 
    public function setHydrated(bool $isHydrated)
    {
        $this->isHydrated = $isHydrated;

        return $this;
    }
}