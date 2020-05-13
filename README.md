# ACSEOTypesenseBundle

This bundle provides integration with [Typesense](https://typesense.org/) with Symfony. 

Features include:

- Doctrine object transformer to Typesense indexable data
- Usefull services to search in collections 
- Listeners for Doctrine events for automatic indexing

**This Bundle is at his early stage and should be used with caution**

## Installation

Install the bundle using composer 

```bash
composer require acseo/typesense-bundle
````

Enable the bundle in you Symfony project

```php

<?php
// config/bundles.php

return [
    ACSEO\TypesenseBundle\ACSEOTypesenseBundle::class => ['all' => true],
```

## Configuration

Configure the Bundle

```
# .env
TYPESENSE_URL=localhost:8108
TYPESENSE_KEY=123
```

```
# config/packages/acseo_typesense.yml
acseo_typesense:
    # Typesense host settings
    typesense:
        host: '%env(resolve:TYPESENSE_URL)%'
        key: '%env(resolve:TYPESENSE_KEY)%'
    # Collection settings
    collections:
        books:                                     # Typesense collection name
            entity: 'App\Entity\Book'              # Doctrine Entity class
            fields: 
                #
                # Keeping Database and Typesense synchronized with ids
                #
                id:                                # Entity attribute name
                    name: id                       # Typesense attribute name
                    type: primary                  # Attribute type
                #
                # Using again id as a sortable field (int32 required)
                #
                sortable_id:
                    entity_attribute: id           # Entity attribute name forced
                    name: entity_id
                    type: int32
                title: 
                    name: title
                    type: string
                author:
                     name: author
                     type: object                  # Object conversion with __toString()
                author.country:
                    name : author_country          # equivalent of $book->getAuthor()->getCountry()
                    type: string
                genres:
                    name : genres
                    type: collection               # Convert ArrayCollection to array of strings
                publishedAt: 
                    name : published_at
                    type: datetime
            default_sorting_field: sortable_id    # Default sorting field. Must be int32 or float
```

You can use basic types supported by Typesense for your fields : string, int32, float, etc.
You can also use specific type names, such as : primary, collection, object

Data conversion from Doctrine entity to Typesense data is managed by `ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer`

## Usage

### Create index and populate data

This bundle comes with useful commands in order to create and index your data

```
# Creation collections structure
php bin/console typesense:create

# Populate collections with Doctrine entities
php bin/console typesense:populate
```

### Search documents

This bundle creates dynamic **finders** services that allows you to query Typesenses

The finder services are named like this  : typesense.finder.*collection_name*

You can inject the finders in your Controllers or into other services

```
# config/services.yaml
services:
    App\Controller\BookController:
        arguments:
            $bookFinder: '@typesense.finder.books'    
```

```
<?php

// src/Controller/BookController.php

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;

//
class BookController extends AbstractController
{
    private $bookFinder;

    public function __construct($bookFinder)
    {
        $this->bookFinder = $bookFinder;
    }

    public function search()
    {
        $query = new TypesenseQuery('Jules Vernes', 'author');

        // Get Doctrine Hydrated objects
        $results = $this->bookFinder->query($query)->getResults();
        
        // dump($results)
        // array:2 [▼
        //    0 => App\Entity\Book {#522 ▶}
        //    1 => App\Entity\Book {#525 ▶}
        //]
        
        // Get raw results from Typesence
        $rawResults = $this->bookFinder->rawQuery($query)->getResults();
        
        // dump($rawResults)
        // array:2 [▼
        //    0 => array:3 [▼
        //        "document" => array:4 [▼
        //        "author" => "Jules Vernes"
        //        "id" => "100"
        //        "published_at" => 1443744000
        //        "title" => "Voyage au centre de la Terre "
        //       ]
        //       "highlights" => array:1 [▶]
        //       "seq_id" => 4
        //    ]
        //    1 => array:3 [▼
        //        "document" => array:4 [▶]
        //        "highlights" => array:1 [▶]
        //        "seq_id" => 6
        //    ]
        // ]
    }
```

### Querying Typesense

The class `TypesenseQuery()` class takes 2 arguments :

* The search terme (`q`)
* The fields to search on (`queryBy`)

You can create more complex queries using all the possible Typsense [search arguments](https://typesense.org/docs/0.11.2/api/#search-collection)

```
<?php

use ACSEO\TypesenseBundle\Finder\TypesenseQuery;

$simpleQuery = new TypesenseQuery('search term', 'collection field to search in');

$complexQuery = new TypesenseQuery('search term', 'collection field to search in')
                      ->filterBy('theme: [adventure, thriller]')
                      ->sortBy('year:desc')
```

### Doctrine Listeners

Doctrine listeners will update Typesense with Entity data during the following events :

* postPersist
* postUpdate
* preDelete
