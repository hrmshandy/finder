<?php

namespace Hrmshandy\Finder;

use League\Glide\Server;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use League\Glide\ServerFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Filesystem\Filesystem;

class FinderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../resources/config/finder.php' => config_path('finder.php'),
        ], 'config');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'finder');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/finder'),
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->glideServer();
        $this->registerRoute();
    }

    /**
     * [glideServer description]
     * @return [type] [description]
     */
    private function glideServer()
    {
        $this->app->singleton(Server::class, function ($app) {
            $filesystem = $app->make(Filesystem::class);

            return ServerFactory::create([
                'source' => $filesystem->getDriver(),
                'source_path_prefix' => '',
                'cache' => $filesystem->getDriver(),
                'cache_path_prefix' => '.cache',
                //'base_url' => // Base URL of the images
            ]);
        });
    }

    /**
     * [defineRoute description]
     * @return [type] [description]
     */
    private function registerRoute()
    {
        $router = $this->app['router'];

        $router->group(['namespace' => 'Hrmshandy\Finder\Controllers'], function($router){
            $router->get('finder/file/{path}', 'FinderController@downloadFile')->where('folder', '[A-Za-z0-9\/\.\-\_]+');
            $router->post('finder/file', 'FinderController@saveFile');
            $router->delete('finder/file/{path}', 'FinderController@deleteFile')->where('path', '[A-Za-z0-9\/\.\-\_]+');
            $router->get('finder/images/{path}', 'FinderController@getFile')->where('path', '[A-Za-z0-9\/\.\-\_]+');
            $router->get('finder/{folder?}', 'FinderController@index')->where('folder', '[A-Za-z0-9\/\.\-\_]+');
        });
    }
}
