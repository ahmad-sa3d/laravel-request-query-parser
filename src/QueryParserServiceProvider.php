<?php

/**
 * @package  saad/request-query-parser
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\QueryParser;

use Illuminate\Support\ServiceProvider;
use Saad\QueryParser\Commands\MakePreparer;

class QueryParserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register Generator Command
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakePreparer::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
