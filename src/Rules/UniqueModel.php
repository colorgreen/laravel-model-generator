<?php
/**
 * Created by PhpStorm.
 * User: Korneliusz Szymański
 * Email: colorgreen19@gmail.com
 * Date: 2019-10-04
 * Time: 01:39
 */

namespace Colorgreen\Generator\Rules;


use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class UniqueModel implements Rule
{
    protected $model;

    /**
     * UniqueModel constructor.
     */
    public function __construct( $model )
    {
        $this->model = $model;
    }


    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes( $attribute, $value )
    {
        $table = $this->model->getTable();

        if( !$this->model->exists ) {
            $rules = [ $attribute => "unique:$table,$attribute" ];
        } else {
            $pk = $this->model->getPrimaryKey();
            $v = $this->model->$pk;
            $rules = [ $attribute => "unique:$table,$attribute,$v,$pk" ];
        }

        return Validator::make( [ $attribute => $value ], $rules, [] )->passes();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __( 'Value already exist in database.' );
    }
}