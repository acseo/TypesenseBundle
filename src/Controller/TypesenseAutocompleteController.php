<?php

namespace ACSEO\TypesenseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TypesenseAutocompleteController //extends AbstractController
{
    private $routesConfig;

    public function __construct(array $routesConfig)
    {
        $this->routesConfig = $routesConfig;
    }
    public function autocomplete(Request $request): JsonResponse
    {
        $autocompleteName = $request->get('autocomplete_name', null);
        $q = $request->get('q', null);
        if (!isset($this->routesConfig[$autocompleteName])) {
            throw new NotFoundHttpException('no autocomplete found with the name : '.$autocompleteName);
        }

        $results = $this->routesConfig[$autocompleteName]->search($q);

        return new JsonResponse($results->getRawResults());
    }
}
