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

```yaml
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
                    name: sortable_id
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

```yaml
# Creation collections structure
php bin/console typesense:create

# Populate collections with Doctrine entities
php bin/console typesense:populate
```

### Search documents

This bundle creates dynamic generic **finders** services that allows you to query Typesense

The finder services are named like this  : typesense.finder.*collection_name*

You can inject the generic finder in your Controller or into other services. 

You can also create specific finder for a collection. See documentation below.

```yaml
# config/services.yaml
services:
    App\Controller\BookController:
        arguments:
            $bookFinder: '@typesense.finder.books'    
```

```php
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

You can create more complex queries using all the possible Typsense [search arguments](https://typesense.org/docs/0.14.0/api/#search-collection)

```php
<?php

use ACSEO\TypesenseBundle\Finder\TypesenseQuery;

$simpleQuery = new TypesenseQuery('search term', 'collection field to search in');

$complexQuery = new TypesenseQuery('search term', 'collection field to search in')
                      ->filterBy('theme: [adventure, thriller]')
                      ->addParameter('key', 'value')
                      ->sortBy('year:desc');
```

### Create specific finder for a collection

You can easily create specific finders for each collection that you declare.

```yaml
# config/packages/acseo_typesense.yml
acseo_typesense:
    # ...
    # Collection settings
    collections:
        books:                                       # Typesense collection name
            # ...                                    # Colleciton fields definition
            # ...
            finders:                                 # Declare your specific finder
                books_autocomplete:                  # Finder name
                    finder_parameters:               # Parameters used by the finder
                        query_by: title              #
                        limit: 10                    # You can add as key / valuesspecifications
                        prefix: true                 # based on Typesense Request 
                        num_typos: 1                 #
                        drop_tokens_threshold: 1     #
```

This configuration will create a service named `@typesense.finder.books.books_autocomplete`.  
You can inject the specific finder in your Controller or into other services

```yaml
# config/services.yaml
services:
    App\Controller\BookController:
        arguments:
            $autocompleteBookFinder: '@typesense.finder.books.books_autocomplete'
```

and then use it like this :

```php
<?php
// src/Controller/BookController.php

class BookController extends AbstractController
{
    private $autocompleteBookFinder;

    public function __construct($autocompleteBookFinder)
    {
        $this->autocompleteBookFinder = $autocompleteBookFinder;
    }

    public function autocomplete($term = '')
    {
        $results = $this->autocompleteBookFinder->search($term)->getResults();
        // or if you want raw results
        $rawResults = $this->autocompleteBookFinder->search($term)->getRawResults();
    }
```

### Doctrine Listeners

Doctrine listeners will update Typesense with Entity data during the following events :

* postPersist
* postUpdate
* preDelete


### Cookbook 
----------------

* [Use Typesense to make an autocomplete field](doc/cookbook/autocomplete.md)

