<?php namespace Datashaman\ElasticModel\Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Mockery;
use Orchestra\Testbench\TestCase as Orchestra_Testbench_TestCase;

class TestCase extends Orchestra_Testbench_TestCase
{
    protected $indexName;

    public function setUp()
    {
        parent::setUp();

        Models\Thing::resetElasticModel();
        Models\Thing::bootIndexing();

        $this->createDatabase();
        $this->createData();
    }

    protected function createDatabase()
    {
        Eloquent::unguard();

        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();

        $this->schema()->create('categories', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        $this->schema()->create('things', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('offline');
            $table->integer('category_id');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories');
        });
    }

    protected function createData()
    {
        Models\Category::create([ 'title' => 'Category #1' ]);
        Models\Category::create([ 'title' => 'Category #2' ]);

        Models\Thing::create([
            'category_id' => Models\Category::first()->id,
            'title' => 'Existing Thing',
            'description' => 'This is the best thing.',
            'status' => 'online',
        ]);
    }

    public function tearDown()
    {
        Mockery::close();

        $this->schema()->drop('things');
        $this->schema()->drop('categories');

        parent::tearDown();
    }

    protected function setClient($expectations)
    {
        $client = Mockery::mock('Elasticsearch\Client')
            ->shouldReceive($expectations)
            ->mock();

        Models\Thing::client($client);

        return $client;
    }

    /**
     * Schema Helpers.
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }
}
