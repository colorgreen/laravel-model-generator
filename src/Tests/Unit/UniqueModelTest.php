<?php
/**
 * Created by PhpStorm.
 * User: Korneliusz Szymański
 * Email: colorgreen19@gmail.com
 * Date: 2019-10-04
 * Time: 01:43
 */

namespace Colorgreen\Generator\Tests\Unit;


use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniqueModelTest extends TestCase
{
    use RefreshDatabase;

    public function testSave()
    {
        $page = Car::make( [
            'id' => 'A',
            'email' => 'aaa@aa.com'
        ] );

        $this->assertFalse( $page->exists );

        $page->save();

        if( !empty( $page->getErrors() ) )
            dd( $page->getErrors() );

        $this->assertTrue( $page->exists );


        $page2 = Car::make( [
            'id' => 'B',
            'email' => 'bbb@aa.com'
        ] );
        $this->assertFalse( $page2->exists );

        $page2->save();

        if( !empty( $page2->getErrors() ) )
            dd( $page2->getErrors() );

        $this->assertTrue( $page2->exists );

    }

}