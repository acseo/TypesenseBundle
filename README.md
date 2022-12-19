# ACSEOTypesenseBundle

This bundle provides integration with [Typesense](https://typesense.org/) with Symfony. 

It relies on the official [TypeSense PHP](https://github.com/typesense/typesense-php) package

Features include:

- Doctrine object transformer to Typesense indexable data
- Usefull services to search in collections 
- Listeners for Doctrine events for automatic indexing

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
TYPESENSE_URL=http://localhost:8108
TYPESENSE_KEY=123
```

```yaml
# config/packages/acseo_typesense.yml
acseo_typesense:
    # Typesense host settings
    typesense:
        url: '%env(resolve:TYPESENSE_URL)%'
        key: '%env(resolve:TYPESENSE_KEY)%'
        collection_prefix: 'test_'                 # Optional : add prefix to all collection 
                                                   #            names in Typesense
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
                    entity_attribute: id             # Entity attribute name forced
                    name: sortable_id                # Typesense field name
                    type: int32
                title: 
                    name: title
                    type: string
                author:
                     name: author
                     type: object                    # Object conversion with __toString()
                author.country:
                    name: author_country           
                    type: string
                    facet: true                      # Declare field as facet (required to use "group_by" query option)
                    entity_attribute: author.country # Equivalent of $book->getAuthor()->getCountry()
                genres:
                    name: genres
                    type: collection                 # Convert ArrayCollection to array of strings
                publishedAt: 
                    name: publishedAt
                    type: datetime
                    optional: true                   # Declare field as optional
            default_sorting_field: sortable_id       # Default sorting field. Must be int32 or float
            symbols_to_index: ['+']                  # Optional - You can add + to this list to make the word c++ indexable verbatim.
        users:
            entity: App\Entity\User
            fields:
                id:
                    name: id
                    type: primary
                sortable_id:
                    entity_attribute: id
                    name: sortable_id
                    type: int32
                email:
                    name: email
                    type: string
            default_sorting_field: sortable_id
            token_separators: ['+', '-', '@', '.']  # Optional - This will cause contact+docs-example@typesense.org to be indexed as contact, docs, example, typesense and org.
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

# Import collections with Doctrine entities
php bin/console typesense:import
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

You can create more complex queries using all the possible Typsense [search arguments](https://typesense.org/docs/0.21.0/api/documents.html#arguments)

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

### Use different kind of services

This bundles creates different services that you can use in your Controllers or anywhere you want.

* `typesense.client` : the basic client inherited from the official `typesense-php` package 
* `typesense.collection_client` : this service allows you to do basic actions on collections, and allows to perform `search` and `multisearch` action.
* `typesense.finder.*` : this generated service allows you to perform `query` or `rawQuery` on a specific collection. Example of a generated service : `typesense.finder.candidates`
* `typesense.specificfinder.*.*` : this generated service allows you to run pre-configured requests (declared in : `config/packages/acseo_typesense.yml`). Example of a generated service : `typesense.specificfinder.candidates.default`

Note : there a other services. You can use the `debug:container` command in order to see all of them.

### Doctrine Listeners

Doctrine listeners will update Typesense with Entity data during the following events :

* postPersist
* postUpdate
* preDelete

### Perform multisearch

You can create [multisearch](https://typesense.org/docs/0.21.0/api/documents.html#federated-multi-search) requests and get results using the `collectionClient` service.

```php
// Peform multisearch

$searchRequests = [
    (new TypesenseQuery('Jules'))->addParameter('collection', 'author'),
    (new TypesenseQuery('Paris'))->addParameter('collection', 'library')  
];

$commonParams = new TypesenseQuery()->addParameter('query_by', 'name');

$response = $this->collectionClient->multisearch($searchRequests, $commonParams);
```

## Cookbook 
----------------

* [Use Typesense to make an autocomplete field](doc/cookbook/autocomplete.md)


## Testing the Bundle

tests are written in the `tests` directory.

*  **Unit** tests doesn't require a running Typesense server
*  **Functional** tests require a running Typesense server

You can launch the tests with the following commands : 

```bash
# Unit test
$ php ./vendor/bin/phpunit tests/Unit

# Functional test
# First, start a Typesense server with Docker
$ composer run-script typesenseServer
$ php ./vendor/bin/phpunit tests/Functional
```



