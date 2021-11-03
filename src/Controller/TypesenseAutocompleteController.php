<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TypesenseAutocompleteController
{
    private $routesConfig;

    public function __construct(array $routesConfig)
    {
        $this->routesConfig = $routesConfig;
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $finderName = $request->get('finder_name', null);
        $q          = $request->get('q', null);
        if (!isset($this->routesConfig[$finderName])) {
            throw new NotFoundHttpException('no autocomplete found with the name : '.$finderName);
        }

        $results = $this->routesConfig[$finderName]->search($q);

        return new JsonResponse($results->getRawResults());
    }
}
