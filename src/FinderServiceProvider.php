<?php

namespace Hrmshandy\Finder;

use League\Glide\Server;
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
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(Router $router)
    {
        $this->glideServer();
        $this->defineRoute($router);
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
     * @param  Router $router [description]
     * @return [type]         [description]
     */
    private function defineRoute(Router $router)
    {
         $router->get('finder/img/{path}', function(Server $server, Request $request, $path) {

            $server->outputImage($path, $request->all());

        })->where('path', '[A-Za-z0-9\/\.\-\_]+');
    }
}
