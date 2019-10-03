<?php
/**
 * Created by PhpStorm.
 * User: Korneliusz Szymański
 * Email: colorgreen19@gmail.com
 * Date: 2019-10-03
 * Time: 23:18
 */

namespace Colorgreen\Generator\Validators;

use Illuminate\Support\Facades\Validator;

class UniqueValidator
{
    public function validate( $attribute, $value, $parameters, $validator )
    {
        $table = $parameters[0];
        $field = !empty( $parameters[1] ) ? $parameters[1] : $attribute;

        if( request()->method() == "POST" ) {
            $rules = [ $attribute => "unique:$table,$field" ];
        } else {
            $v = $parameters[2];
            $pk = !empty( $parameters[3] ) ? $parameters[3] : 'id';
            $rules = [ $attribute => "unique:$table,$field,$v,$pk" ];
        }

        return Validator::make( [ $attribute => $value ], $rules, [] )->passes();
    }

}
