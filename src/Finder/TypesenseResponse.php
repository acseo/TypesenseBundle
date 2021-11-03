<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Finder;

class TypesenseResponse
{
    private $facetCounts;
    private $found;
    private $hits;
    private $hydratedHits;
    private $isHydrated;
    private $page;
    private $searchTimeMs;

    public function __construct(?array $result)
    {
        $this->facetCounts  = $result['facet_counts']   ?? null;
        $this->found        = $result['found']          ?? null;
        $this->hits         = $result['hits']           ?? null;
        $this->page         = $result['page']           ?? null;
        $this->searchTimeMs = $result['search_time_ms'] ?? null;
        $this->isHydrated   = false;
        $this->hydratedHits = null;
    }

    /**
     * Get the value of facetCounts.
     */
    public function getFacetCounts()
    {
        return $this->facetCounts;
    }

    /**
     * Get the value of hits.
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
     * Get the value of page.
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Get total hits.
     */
    public function getFound()
    {
        return $this->found;
    }

    /**
     * Set the value of hydratedHits.
     */
    public function setHydratedHits($hydratedHits): self
    {
        $this->hydratedHits = $hydratedHits;

        return $this;
    }

    /**
     * Set the value of isHydrated.
     */
    public function setHydrated(bool $isHydrated): self
    {
        $this->isHydrated = $isHydrated;

        return $this;
    }
}
