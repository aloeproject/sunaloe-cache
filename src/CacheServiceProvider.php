<?php
/**
 * Created by PhpStorm.
 * User: lkboy
 * Date: 2020/5/14
 * Time: 17:08
 */

namespace Sunaloe\Cache;

use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sunaloe-cache', function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('sunaloe-cache.store', function ($app) {
            return $app['sunaloe-cache']->driver();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'sunaloe-cache',
        ];
    }
}