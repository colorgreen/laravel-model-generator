# Laravel model generator

Simple generator base on laracademy/generators, extending model generation. Generates model like in Yii framework. Create or update model base on existing table. 

Create BaseModel and Model. If there are changes on table schema, regenerating (the same command) process will change only BaseModel (new rules, fields, etc), so write your logic in Model class to prevent it from overriding.

Also base relations are generated to the models (relation ```hasOne()``` and ```belongsTo()``` ).

Column ```parent_id``` will generate relation to itself, available by ```parent()```

Base<xxx> extends Colorgreen\Generator\Models\BaseModel which provide on model validation.
```php
$model = new Model();
$model->email = "xxx";

if( !$model->save() )
    print_r( $model->getErrors() );

// if validation will fail, e.g. output:
// {"id":["The id field is required."],"email":["The email must be a valid email address."]}

```

or use model validation in api controller, example store action

```php
public function store(Request $request)
{
    $model = new Model();
    $model->fill($request->all());
    $model->getValidator()->validate();

    $model->save();

    return response()->json( [ 'message' => __('Success'), 'redirect' => route('model.edit', [$model] ) ] );
}
```

Example 'BaseModel'
```php
<?php

namespace App\Models\Base;

use Colorgreen\Generator\Models\BaseModel;

/**
 * Class BasePage
 * @property int id
 * @property string image
 * @property boolean active
 * @property int count
 * @property string email
 * @property string email
 
 * @property int related_model_id
 * @property \App\Models\RelatedModel related_model
 */
class BasePage extends BaseModel
{
    protected $attributes = [
		'email' => 'default_email', 
		'active' => 1, 
		'count' => 0
	];

    protected static $rules = [
		'id' => 'required|numeric|integer', 
		'image' => 'nullable|string|max:255', 
		'active' => 'nullable|boolean', 
		'count' => 'nullable|numeric|integer', 
		'email' => 'required|string|max:100|email'
	];
	
	
	public function related_model() {
		return $this->belongsTo( App\Models\RelatedModel::class, 'related_model_id' );
	}

    protected $table = 'pages';

    protected $fillable = ['image', 'active', 'count', 'email'];

    protected $hidden = [];

    protected $casts = [ 'active' => 'boolean', 'count' => 'integer', 'related_model_id' => 'integer' ];

    protected $dates = ['created_at', 'updated_at'];
}
```



## Usage

### Step 1: Install through Composer

```
composer require colorgreen/laravel-model-generator
```

### Step 2: Add the Service Provider
The easiest method is to add the following into your `config/app.php` file

```php
Colorgreen\Generator\GeneratorServiceProvider::class,
```

### Step 3: Artisan Command
Now that we have added the generator to our project the last thing to do is run Laravel's Arisan command

```
php artisan
```

You will see the following in the list

```
cgenerator:modelfromtable
```

## Commands

### generate:modelfromtable

This command will read your database table and generate a model based on that table structure. The fillable fields, casts, dates and even namespacing will be filled in automatically.

You can use this command to generate a single table, multiple tables or all of your tables at once.

* --name=
  * Use this for custom model name. Default is table name in studle_case and singular. E.g. --name=Page when table name is 'pages' and you want to name your model ```MyPage```.
  * If you use this command provide also one table in --table.
* --table=
  * This parameter if filled in will generate a model for the given table.
  * You can also pass in a list of tables using comma separated values.
* --base=
  * Use if you want to have custom BaseModel. E.g. --base=\App\Models\MyBaseModel.
* --prefix=
  * Set prefix of tables. E.g table 'cms_user_permissions' generate model 'UserPermission'
  * Note that using --prefix option with --all will generate models only for tables that starts from prefix
* --all
  * If this flag is present, then the table command will be ignored.
  * This will generate a model for **all** tables found in your database.
  * If --prefix is set relations will be made only within prefixed tables
* --connection=
  * by default if this option is omitted then the generate will use the default connection found in `config/database.php`
  * To specify a connection ensure that it exists in your `config/database.php` first.
* --folder=
  * by default all models are store in your _app/_ directory. If you wish to store them in another place you can provide the relative path from your base laravel application.
  * please see examples for more information
* --namespace=
  * by default all models will have the namespace of App
  * you can change the namespace by adding this option
* --debug
  * this shows some more information while running

## Examples

### Generating a single table

```
php artisan generate:modelfromtable --table=users

```
Will generate model with name User

### Generating a single table with custom model name

```
php artisan generate:modelfromtable --model_name=MyUser --table=users
```

### Generating a multiple tables

```
php artisan generate:modelfromtable --table=users,posts
```

### Generating all tables

```
php artisan generate:modelfromtable --all
```

For tables: blog_posts, blog_comments, shop_products, users, command ```php artisan generate:modelfromtable --all --prefix=blog_``` 
will generate models olny for blog_posts and blog_comments

### Changing to another connection found in `database.php` and generating models for all tables

```
php artisan generate:modelfromtable --connection=spark --all
```

### Changing the folder where to /app/Models

```
php artisan generate:modelfromtable --table=user --folder=app\Models
```

## Credits

Based on [Laracadeny Generators](https://github.com/laracademy/generators)


## License
Free to use