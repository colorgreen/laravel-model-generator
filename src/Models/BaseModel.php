<?php

namespace Colorgreen\Generator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Colorgreen\Generator\Traits\ExtendableModelTrait;

/**
 * Class BaseModel
 * @package Colorgreen\Generator\Models
 */
class BaseModel extends Model
{
    use ExtendableModelTrait;

    protected static $fields = [];

    protected static $rules = [];

    protected static $messages = [];

    protected $validation = true;

    /**
     * Name column that will identifies items in data grid
     *
     * @var string
     */
    protected static $nameColumn = 'id';

    /**
     * Returns validator which validates model with $rules.
     * @param null $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function getValidator( $data = null )
    {
        return Validator::make( $data ?: $this->attributes, static::$rules, static::$messages );
    }

    /**
     * Validates model. If validation fails false is returned and sets errors with messages in $error variable.
     * Errors can be received by getErrors() function
     * @param null $data
     * @return bool
     */
    public function validate( $data = null )
    {
        $v = $this->getValidator( $data );

        if( $v->fails() ) {
            $this->setErrors( $v->messages() );
            return false;
        }

        return true;
    }

    /**
     * Saves model to database. Before saving model is validated. If validation fails false is returned.
     * @param array $options
     * @return bool
     */
    public function save( array $options = [] )
    {
        if( $this->validation && !$this->validate() )
            return false;
        return parent::save( $options );
    }


    protected function setErrors( $errors )
    {
        $this->errors = $errors;
    }

    /**
     * Returns errors from validation.
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty( $this->errors );
    }

    /**
     * @return array
     */
    public function getCasts()
    {
        return $this->casts;
    }


    /**
     * @return array
     */
    public static function getRules()
    {
        return static::$rules;
    }

    /**
     * @return array
     */
    public static function getFields()
    {
        return static::$fields;
    }

    /**
     * @return string
     */
    public static function getNameColumn()
    {
        return static::$nameColumn;
    }

    /**
     * @param bool $validation
     */
    public function setValidation( bool $validation ): void
    {
        $this->validation = $validation;
    }

}
