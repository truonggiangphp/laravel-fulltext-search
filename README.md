# Laravel fulltext index and search
This package creates a MySQL fulltext index for models and enables you to search through those.

## Install

1. Install with composer ``composer require webikevn/laravel-fulltext-search``.
2. Publish migrations and config ``php artisan vendor:publish --tag=laravel-fulltext``
3. Migrate the database ``php artisan migrate``


## Usage

The package uses a model observer to update the index when models change. If you want to run a full index you can use the console commands.

### Models

Add the ``Indexable`` trait to the model you want to have indexed and define the columns you'd like to index as title and content.

#### Example
```
class Country extends Model
{

    use \Webike\Laravel\Fulltext\Indexable;

    protected $indexContentColumns = ['biographies.name', 'political_situation', 'elections'];
    protected $indexTitleColumns = ['name', 'governmental_type'];

}
```

You can use a dot notation to query relationships for the model, like ``biographies.name``.


### Searching 

You can search using the Search class.

```
$search = new \Webike\Laravel\Fulltext\Search();
$search->run('europe');
```

This will return a Collection of ``\Webike\Laravel\Fulltext\IndexedRecord`` which contain the models in the Polymorphic relation ``indexable``.

If you only want to search a certain model you can use ``$search->runForClass('europe', Country::class);``. This will only return results from that model.


### Commands


#### laravel-fulltext:all

Index all models for a certain class
```
 php artisan  laravel-fulltext:all
 
Usage:
  laravel-fulltext:all <model_class>

Arguments:
  model_class           Classname of the model to index

```

#### Example

``php artisan  laravel-fulltext:all \\App\\Models\\Country``

#### laravel-fulltext:one

```

Usage:
  laravel-fulltext:one <model_class> <id>

Arguments:
  model_class           Classname of the model to index
  id                    ID of the model to index

```

#### Example

`` php artisan  laravel-fulltext:one \\App\\Models\\Country 4 ``


## Options

### db_connection

Choose the database connection to use, defaults to the default database connection. When you are NOT using the default database connection, this MUST be set before running the migration to work correctly.

### weight.title weight.content

Results on ``title`` or ``content`` are weighted in the results. Search result score is multiplied by the weight in this config 

### enable_wildcards

Enable wildcard after words. So when searching for for example  ``car`` it will also match ``carbon``. 

### exclude_feature_enabled

This feature excludes some rows from being returned. Enable this when you have a flag in your model which determines whether this record must be returned in search queries or not. By default this feature is disabled.

### exclude_records_column_name

The column name for that property (which acts as a flag). This must match the exact column name at the table.

#### An example of using this feature

Think about when you have a blog and then you add this search functionality to your blogging system to search through your blog posts. Sometimes you do not want some posts to be appeared in search result, for example when a post is not published yet. This feature helps you to do it.

## Testing

``` bash
$ composer test
```

## Security

If you discover any security related issues, please email nguyen.giang@rivercrane.vn instead of using the issue tracker.