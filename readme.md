# Laravel model generator

Simple generator base on laracademy/generators, extending model generation. Generates model like in Yii framework. Create or update model base on existing table. 

Create BaseModel and Model. If there are changes on table schema, regenerating (the same command) process will change only BaseModel (new rules, fields, etc), so write your logic in Model class to prevent it from overriding.

Also base relations are generated to the models (relation ```hasOne()``` and ```belongsTo()``` ).

Base<xxx> extends Colorgreen\Generator\Models\BaseModel which provide on model validation.
```
$model = new Model();
$model->email = "xxx";

if( !$model->save() )
    print_r( $model->getErrors() );

// if validation will fail, e.g. output:
// {"id":["The id field is required."],"email":["The email must be a valid email address."]}

```

Example 'BaseModel'
```
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
 
 * @property int redirect_type_id
 * @property \App\Models\RedirectType redirect_type
 */
class BasePage extends BaseModel
{
    protected static $rules = [
		'id' => 'required|numeric|integer', 
		'image' => 'nullable|string|max:255', 
		'active' => 'nullable|boolean', 
		'count' => 'nullable|numeric|integer', 
		'email' => 'required|string|max:100|email'
	];
	
	
	public function redirect_type() {
		return $this->belongsTo('App\Models\RedirectType', 'redirect_type_id' );
	}

    protected $table = 'pages';

    protected $fillable = ['image', 'active', 'count', 'email'];

    protected $hidden = [];

    protected $casts = [];

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
* --all
  * If this flag is present, then the table command will be ignored.
  * This will generate a model for **all** tables found in your database.
  * _please note that this command will only ignore the `migrations` table and no model will be generate for it_
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