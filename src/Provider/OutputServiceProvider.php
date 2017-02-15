<?php

namespace Caikeal\Providers;

use Illuminate\Support\ServiceProvider;
use Caikeal\Response\Response;
use Caikeal\Transformer\Factory as TransformerFactory;
use Caikeal\Transformer\Adapter\Fractal;
use Caikeal\Response\Factory as ResponseFactory;

class OutputServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setResponseStaticInstances();
    }

    protected function setResponseStaticInstances()
    {
        Response::setTransformer($this->app['output.transformer']);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerClassAliases();
        $this->registerTransformer();
        $this->registerResponseFactory();
    }

    /**
     * Register the transformer factory.
     *
     * @return void
     */
    protected function registerTransformer()
    {
        $this->app->singleton('output.transformer', function ($app) {
            return new TransformerFactory($app, $this->app->make(Fractal::class));
        });
    }

    /**
     * Register the response factory.
     *
     * @return void
     */
    protected function registerResponseFactory()
    {
        $this->app->singleton('output.response', function ($app) {
            return new ResponseFactory($app[TransformerFactory::class]);
        });
    }

    /**
     * Register the class aliases.
     *
     * @return void
     */
    protected function registerClassAliases()
    {
        $aliases = [
            'output.response' => 'Caikeal\Response\Factory',
            'output.transformer' => 'Caikeal\Transformer\Factory',
        ];
        foreach ($aliases as $key => $aliases) {
            foreach ((array) $aliases as $alias) {
                $this->app->alias($key, $alias);
            }
        }
    }
}
