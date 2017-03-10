<?php

namespace Caikeal\Output\Provider;

use Caikeal\Output\Response\Format\Json;
use Illuminate\Support\ServiceProvider;
use Caikeal\Output\Response\Response;
use Caikeal\Output\Transformer\Factory as TransformerFactory;
use Caikeal\Output\Transformer\Adapter\Fractal;
use Caikeal\Output\Response\Factory as ResponseFactory;

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
        Response::setFormatters(['json'=>$this->app->make(Json::class)]);
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
        $this->registerCommand();
    }

    /**
     * Register the transformer command.
     *
     * @return void
     */
    protected function registerCommand()
    {
        if (class_exists('Illuminate\Foundation\Application', false)) {
            $this->commands([
                \Caikeal\Output\Command\TransformerCommand::class
            ]);
        }
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
            'output.response' => 'Caikeal\Output\Response\Factory',
            'output.transformer' => 'Caikeal\Output\Transformer\Factory',
        ];
        foreach ($aliases as $key => $aliases) {
            foreach ((array) $aliases as $alias) {
                $this->app->alias($key, $alias);
            }
        }
    }
}
