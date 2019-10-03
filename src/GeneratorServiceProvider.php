<?php

namespace Colorgreen\Generator;

use Colorgreen\Generator\Commands\GenerateModelCommand;
use Colorgreen\Generator\Validators\UniqueValidator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class GeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateModelCommand::class
            ]);
        }

        Validator::extend('unique_model', UniqueValidator::class, 'Value already exist in database');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
