# Cookbook : Autocomplete

With Typsense and some Javascript, it can be pretty easy to create an autocomplete field based on an ajax request.

In order to do that, TypesenseBundle prodive a generic autocomplete controller that will allow to use a finder in order 
to search for data, and return a JSON Response that could be used to populate the autocomplete field

## Step 1 : declare a specific finder

First of all, we will need a finder in order to search for data. A specific finder can easily be declared.

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

## Step 2 : enable the autocomplete route

```yaml
# config/routes.yaml
autocomplete:
   path: /autocomplete
   controller: typesense.autocomplete_controller:autocomplete
```

## Step 3 : Create an ajax request that will populate your search field.

The example bellow is based on [bootstrap-autocomplete](https://github.com/xcash/bootstrap-autocomplete) but you can use any script you want once you understand how this works

```html
<!-- templates/base.html.twig -->
<html>
    <head>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            {% block body %}{% endblock %}
        </div>
        <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

        {% block javascripts %}{% endblock %}
    </body>
</html>
```

```html
<!-- templates/book/search.html.twig -->
{% extends 'base.html.twig' %}

{% block body %}
    <input class="form-control bookAutocomplete" type="text" autocomplete="off">
{% endblock %}

{% block javascripts %}
<script src="https://cdn.jsdelivr.net/gh/xcash/bootstrap-autocomplete@v2.3.7/dist/latest/bootstrap-autocomplete.min.js"></script>
<script type="text/javascript">

    $(document).ready(function(e) {
        $('.bookAutocomplete').autoComplete({
            minLength: 2,
            resolverSettings: {
                // Route to the autocomplete Controller, and name of the finder to use
                url: "{{path('autocomplete', {'finder_name' : 'books.books_autocomplete'})}}",
            },
            events: {
                searchPost: function (resultFromServer) {
                    // Manipulate returned data in order to display title in the search result
                    results = new Array();
                    resultFromServer.forEach(element => {
                        results.push(element.document.title);
                    });
                    return results;
                }
            }
        });
    });
</script>
{% endblock %}
```