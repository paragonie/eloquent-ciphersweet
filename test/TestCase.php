<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ParagonIE\EloquentCipherSweet\CipherSweetServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            CipherSweetServiceProvider::class,
        ];
    }

    /**
     * Set up the database.
     *
     * @return void
     */
    protected function setUpDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_bi');
            $table->string('email');
            $table->string('email_bi');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('title_bi');
            $table->string('body');
            $table->string('body_bi');
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_bi');
            $table->string('email');
            $table->string('my_custom_email_index');
            $table->timestamps();
        });
    }
}
