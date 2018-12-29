<?php
/**
 * Created by PhpStorm.
 * User: Korneliusz Szymański
 * Email: colorgreen19@gmail.com
 * Date: 2018-07-15
 * Time: 17:00
 */

namespace Colorgreen\Generator\Commands;

use Laracademy\Generators\Commands\ModelFromTableCommand;
use Illuminate\Support\Facades\Schema;

class GenerateModelCommand extends ModelFromTableCommand
{
    protected $signature = 'cgenerator:modelfromtable
                            {--name= : Model name. If set, only 1 table is required in --table }
                            {--table= : a single table or a list of tables separated by a comma (,)}
                            {--base= : Base model name. Default Colorgreen\Generator\Models\BaseModel }
                            {--prefix= : Table prefix }
                            {--connection= : database connection to use, leave off and it will use the .env connection}
                            {--debug : turns on debugging}
                            {--folder= : by default models are stored in app, but you can change that}
                            {--namespace= : by default the namespace that will be applied to all models is App}
                            {--all : run for all tables}';

    public $defaults;
    public $rules;
    public $properties;
    public $modelRelations;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->options = [
            'name' => '',
            'connection' => '',
            'table' => '',
            'base' => '',
            'folder' => app()->path(),
            'debug' => false,
            'all' => false,
            'prefix' => '',
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->doComment( 'Starting Model Generate Command', true );
        $this->getOptions();

        $tables = [];
        $path = $this->options['folder'];
        $basepath = $path."\\Base";
        $modelStub = file_get_contents( $this->getStub() );
        $basemodelStub = file_get_contents( $this->getBaseStub() );

        // can we run?
        if( strlen( $this->options['table'] ) <= 0 && $this->options['all'] == false ) {
            $this->error( 'No --table specified or --all' );

            return;
        }

        if( strlen( $this->options['name'] ) > 0 && strpos( ",", $this->options['table'] ) !== false ) {
            $this->error( 'If name is set, pass only 1 table' );

            return;
        }

        // figure out if we need to create a folder or not
        if( $this->options['folder'] != app()->path() ) {
            if( !is_dir( $this->options['folder'] ) ) {
                mkdir( $this->options['folder'] );
            }
        }

        $this->makeDirectory( $basepath );

        // figure out if it is all tables
        if( $this->options['all'] ) {
            $tables = $this->getAllTables()->filter( function ( $v ) {
                return strpos( $v, $this->options['prefix'] ) !== false;
            } );
        } else {
            $tables = explode( ',', $this->options['table'] );
        }

        // cycle through each table
        foreach( $tables as $table ) {
            $this->rules = null;
            $this->properties = null;
            $this->modelRelations = null;

            // grab a fresh copy of our stub
            $stub = $modelStub;
            $basestub = $basemodelStub;

            $tablename = $this->options['name'] != '' ? $this->options['name'] : $table;
            $tablename = $this->getTableWithoutPrefix( $tablename );

            // generate the file name for the model based on the table name
            $classname = $this->options['name'] != '' ? $this->options['name'] : studly_case( str_singular( $tablename ) );
            $fullPath = "$path/$classname.php";
            $fullBasePath = "$basepath/Base$classname.php";

            $this->doComment( "Generating file: $classname.php" );
            $this->doComment( "Generating file: /Base/Base$classname.php" );

            // gather information on it
            $model = [
                'table' => $table,
                'fillable' => $this->getSchema( $table ),
                'guardable' => [],
                'hidden' => [],
                'casts' => [],
            ];

            // fix these up
            $columns = $this->describeTable( $table );

            // use a collection
            $this->columns = collect();

            foreach( $columns as $col ) {
                $this->columns->push( [
                    'field' => $col->Field,
                    'type' => $col->Type,
                    'null' => $col->Null == 'YES',
                    'default' => $col->Default,
                ] );
            }

            // reset fields
            $this->resetFields();

            $stub = $this->replaceClassName( $stub, $tablename );
            $stub = $this->replaceModuleInformation( $stub, $model );
            $stub = $this->replaceConnection( $stub, $this->options['connection'] );
            $stub = $this->replaceLabel( $stub, $columns );

            $basestub = $this->replaceClassName( $basestub, $tablename );
            $basestub = $this->replaceBaseClassName( $basestub, $this->options['base'] );
            $basestub = $this->replaceModuleInformation( $basestub, $model );
            $basestub = $this->replaceRulesAndProperties( $basestub, $this->columns, $tablename );
            $basestub = $this->replaceConnection( $basestub, $this->options['connection'] );

            // writing stub out

            if( !file_exists( $fullPath ) ) {
                $this->doComment( 'Writing model: '.$fullPath, true );
                file_put_contents( $fullPath, $stub );
            }

            if( !file_exists( $fullBasePath ) )
                $this->doComment( 'Writing base model: '.$fullBasePath, true );
            else
                $this->doComment( 'Updating base model: '.$fullBasePath, true );

            file_put_contents( $fullBasePath, $basestub );
        }

        $this->info( 'Complete' );
    }

    /**
     * replaces the module information.
     *
     * @param string $stub stub content
     * @param array $modelInformation array (key => value)
     *
     * @return string stub content
     */
    public function replaceModuleInformation( $stub, $modelInformation )
    {
        // replace table
        $stub = str_replace( '{{table}}', $modelInformation['table'], $stub );

        // replace fillable
        $this->fields = '';
        $this->fieldsHidden = '';
        $this->fieldsFillable = '';
        $this->fieldsCast = '';
        foreach( $modelInformation['fillable'] as $field ) {
            $this->fields .= ( strlen( $this->fields ) > 0 ? ', ' : '' )."'$field'";

            // fillable and hidden
            if( $field != 'id' ) {
                $this->fieldsFillable .= ( strlen( $this->fieldsFillable ) > 0 ? ', ' : '' )."'$field'";

                $fieldsFiltered = $this->columns->where( 'field', $field );
                if( $fieldsFiltered ) {
                    // check type
                    $type = strtolower( $fieldsFiltered->first()['type'] );
                    switch( $type ) {
                        case 'timestamp':
                            $this->fieldsDate .= ( strlen( $this->fieldsDate ) > 0 ? ', ' : '' )."'$field'";
                            break;
                        case 'datetime':
                            $this->fieldsDate .= ( strlen( $this->fieldsDate ) > 0 ? ', ' : '' )."'$field'";
                            break;
                        case 'date':
                            $this->fieldsDate .= ( strlen( $this->fieldsDate ) > 0 ? ', ' : '' )."'$field'";
                            break;
//                        case 'tinyint(1)':
//                            $this->fieldsCast .= (strlen($this->fieldsCast) > 0 ? ', ' : '')."'$field' => 'boolean'";
//                            break;
                    }

                    $cast = $this->getPhpType( $type );
                    if( $cast !== 'string' )
                        $this->fieldsCast .= ( strlen( $this->fieldsCast ) > 0 ? ', ' : '' )."'$field' => '".$cast."'";

                }
            } else {
                if( $field != 'id' && $field != 'created_at' && $field != 'updated_at' ) {
                    $this->fieldsHidden .= ( strlen( $this->fieldsHidden ) > 0 ? ', ' : '' )."'$field'";
                }
            }
        }

        // replace in stub
        $stub = str_replace( '{{fields}}', $this->fields, $stub );
        $stub = str_replace( '{{fillable}}', $this->fieldsFillable, $stub );
        $stub = str_replace( '{{hidden}}', $this->fieldsHidden, $stub );
        $stub = str_replace( '{{casts}}', $this->fieldsCast, $stub );
        $stub = str_replace( '{{dates}}', $this->fieldsDate, $stub );
        $stub = str_replace( '{{modelnamespace}}', $this->options['namespace'], $stub );

        return $stub;
    }

    /**
     * replaces the class name in the stub.
     *
     * @param string $stub stub content
     * @param string $tableName the name of the table to make as the class
     *
     * @return string stub content
     */
    public function replaceClassName( $stub, $tableName )
    {
        return str_replace( '{{class}}', studly_case( str_singular( $tableName ) ), $stub );
    }

    /**
     * replaces the class name in the stub.
     *
     * @param string $stub stub content
     * @param string $tableName the name of the table to make as the class
     *
     * @return string stub content
     */
    public function replaceBaseClassName( $stub, $baseclass )
    {
        return str_replace( '{{baseclass}}', studly_case( $baseclass ), $stub );
    }

    public function replaceRulesAndProperties( $stub, $columns, $tablename )
    {
        $this->rules = '';
        $this->defaults = '';
        $this->properties = '';
        foreach( $columns as $column ) {
            $field = $column['field'];

            $type = $this->getPhpType( $column['type'] );
            if( $column['default'] !== null )
                $this->defaults .= ( strlen( $this->defaults ) > 0 ? ', ' : '' )."\n\t\t'$field' => ".( $type == 'string' ? '\'' : '' ).$column['default'].( $type == 'string' ? '\'' : '' );

            $this->rules .= ( strlen( $this->rules ) > 0 ? ', ' : '' )."\n\t\t'$field' => '".$this->getRules( $column )."'";
            $this->properties .= "\n * @property ".$type." ".$field;
            $this->modelRelations .= $this->getRelationTemplate( $column, $this->properties, $tablename );
        }
        $this->defaults .= "\n\t";
        $this->rules .= "\n\t";

        $this->modelRelations .= $this->getRelationsForModel( $this->properties, $tablename );

        $stub = str_replace( '{{defaults}}', $this->defaults, $stub );
        $stub = str_replace( '{{rules}}', $this->rules, $stub );
        $stub = str_replace( '{{properties}}', $this->properties, $stub );
        $stub = str_replace( '{{relations}}', $this->modelRelations, $stub );
        return $stub;
    }

    public function getRelationsForModel( &$properties, $tablename )
    {
        $s = '';
        $searchedColumnName = snake_case( str_singular( $tablename )."_id" );

        foreach( $this->getAllTables() as $table ) {
            if( in_array( $searchedColumnName, $this->getTableColumns( $table ) ) ) {
                $table = $this->getTableWithoutPrefix( $table );

//                $name = str_singular($table);
                $name = $table;
                $relatedModel = $this->options['namespace']."\\".studly_case( str_singular( $table ) );

                $properties .= "\n * @property \\".$relatedModel."[] ".$name;

                $s .= "\t//TODO check if relation shouldn't be OneToOne ( hasOne() )\n".
                    "\tpublic function $name() {\n".
                    "\t\treturn \$this->hasMany('$relatedModel', '$searchedColumnName' );\n".
                    "\t}\n";
            }
        }

        return $s;
    }

    public function getPhpType( $columnType )
    {
        $length = $this->getLenght( $columnType );

        if( $this->isNumeric( $columnType ) != null ) {
            $type = $this->isInteger( $columnType );

            if( $length == '1' )
                return 'boolean';
            else if( $type != null )
                return 'integer';
            return 'float';
        }
        return 'string';
    }

    public function getRules( $info )
    {
        if( $info['field'] == 'id' )
            $rules = 'nullable';
        else $rules = $info["null"] ? 'nullable' : 'required';

        $length = $this->getLenght( $info['type'] );

        if( $this->isNumeric( $info['type'] ) != null ) {
            $type = $this->isInteger( $info['type'] );

            if( $length == '1' )
                $rules .= '|boolean';
            else if( $type != null )
                $rules .= '|numeric|integer';
            else
                $rules .= '|numeric';;
        } else if( $this->isDateTime( $info['type'] ) != null ) {
            $rules .= "|date";
        } else {
            $type = preg_match( "/\w+/", $info['type'], $output_array )[0];
            $rules .= "|string".( $length ? '|max:'.$length : '' );

            if( preg_match( "/email/", $info['field'] ) )
                $rules .= "|email";
        }

        return $rules;
    }

    public function getRelationTemplate( $column, &$properties, $currentTablename )
    {
        $foreignKey = $column['field'];

        if( strpos( $foreignKey, '_id' ) === false )
            return '';

        if( $foreignKey != 'id' ) {
            $tablename = $this->getTableNameByForeignKey( $foreignKey );
            if( $tablename != null ) {

                $tablename = $this->getTableWithoutPrefix( $tablename );

                if( $tablename !== null ) {
                    $modelname = str_singular( studly_case( $tablename ) );
                    $relatedModel = $this->options['namespace']."\\".$modelname;

                    $name = str_singular( $tablename );

                    $properties .= "\n * @property \\".$relatedModel." ".$name;

                    $s = "\tpublic function $name() {\n".
                        "\t\treturn \$this->belongsTo( \\$relatedModel::class, '$foreignKey' );\n".
                        "\t}\n";

                    return $s;
                }
            } else if( $foreignKey == 'parent_id' ) {
                $relatedModel = $this->options['namespace']."\\".str_singular( studly_case( $currentTablename) );

                $properties .= "\n * @property \\$relatedModel parent";

                return "\tpublic function parent() {\n".
                    "\t\treturn \$this->belongsTo( static::class, 'parent_id' );\n".
                    "\t}\n";
            }
        }


        return '';
    }

    protected function getTableNameByForeignKey( $foreignKey )
    {
        $tables = $this->getAllTables()->toArray();
        rsort( $tables );
        $tables = array_map( function ( $x ) {
            return $this->getTableWithoutPrefix( $x );
        }, $tables );

        $foreignKey = str_plural( str_replace( '_id', '', $foreignKey ) );
        $matches = preg_grep( "/".$foreignKey."/", $tables );

        if( $matches == null )
            return null;

        if( in_array( $foreignKey, $matches ) )
            return $foreignKey;

        if( array_values( $matches )[0] !== null )
            return array_values( $matches )[0];
        return null;
    }

    public function getTableColumns( $table )
    {
        return Schema::getColumnListing( $table );
    }

    protected function getLenght( $text )
    {
        preg_match( "/\d+/", $text, $output_array );
        return count( $output_array ) == 0 ? null : $output_array[0];
    }

    protected function isInteger( $text )
    {
        preg_match( "/tinyint|smallint|mediumint|bigint|int/", $text, $output_array );
        return count( $output_array ) == 0 ? null : $output_array[0];
    }

    protected function isNumeric( $text )
    {
        preg_match( "/tinyint|smallint|mediumint|bigint|int|decimal|float|double|real|bit|serial/", $text, $output_array );
        return count( $output_array ) == 0 ? null : $output_array[0];
    }

    protected function isDateTime( $text )
    {
        preg_match( "/datetime|timestamp|date|time|year/", $text, $output_array );
        return count( $output_array ) == 0 ? null : $output_array[0];
    }


    /**
     * returns all the options that the user specified.
     */
    public function getOptions()
    {
        // model name
        $this->options['name'] = ( $this->option( 'name' ) ) ?: '';

        // base model
        $this->options['base'] = $this->option( 'base' ) ?: '\\Colorgreen\\Generator\\Models\\BaseModel';

        // debug
        $this->options['debug'] = ( $this->option( 'debug' ) ) ? true : false;

        // connection
        $this->options['connection'] = ( $this->option( 'connection' ) ) ? $this->option( 'connection' ) : '';

        // folder
        $this->options['folder'] = ( $this->option( 'folder' ) ) ? base_path( $this->option( 'folder' ) ) : app()->path()."\\Models";
        // trim trailing slashes
        $this->options['folder'] = rtrim( $this->options['folder'], '/' );

        // namespace
        $this->options['namespace'] = ( $this->option( 'namespace' ) ) ? str_replace( 'app', 'App', $this->option( 'namespace' ) ) : 'App\\Models';
        // remove trailing slash if exists
        $this->options['namespace'] = rtrim( $this->options['namespace'], '/' );
        // fix slashes
        $this->options['namespace'] = str_replace( '/', '\\', $this->options['namespace'] );

        // all tables
        $this->options['all'] = ( $this->option( 'all' ) ) ? true : false;

        // single or list of tables
        $this->options['table'] = ( $this->option( 'table' ) ) ? $this->option( 'table' ) : '';

        $this->options['prefix'] = ( $this->option( 'prefix' ) ) ? $this->option( 'prefix' ) : '';
    }

    protected function getTableWithoutPrefix( $table )
    {
        return preg_replace( "/^".$this->options['prefix']."/", '', $table );
    }

    protected function makeDirectory( $path )
    {
        if( !is_dir( $path ) ) {
            return mkdir( $path, 0755, true );
        }

        return $path;
    }


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    public function getStub()
    {
        return __DIR__.'/stubs/model.stub';
    }

    /**
     * Get the base stub file for the generator.
     *
     * @return string
     */
    public function getBaseStub()
    {
        return __DIR__.'/stubs/basemodel.stub';
    }

    public function replaceLabel( $stub, $columns )
    {
        $columns = array_map( function ( $c ) {
            return $c->Field;
        }, $columns );

        // replaces for name column
        foreach( [ 'title', 'name', 'key' ] as $p )
            if( in_array( $p, $columns ) )
                $stub = str_replace( '{{namecolumn}}', "protected static \$nameColumn = '$p';", $stub );
        $stub = str_replace( '{{namecolumn}}', '', $stub );

        $priorities = [ 'title', 'name', 'key', 'id' ];

        $first = null;
        foreach( $priorities as $p )
            if( in_array( $p, $columns ) ) {
                if( $first !== null )
                    return str_replace( '{{label}}', "\$this->$first ?: \$this->$p", $stub );
                else
                    $first = $p;
            }

        if( $first !== null )
            return str_replace( '{{label}}', "\$this->$first", $stub );
        return str_replace( '{{label}}', '', $stub );
    }
}