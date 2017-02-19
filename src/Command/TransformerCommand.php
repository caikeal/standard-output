<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2017/2/16
 * Time: 下午7:27
 */

namespace Caikeal\Command;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command;

class TransformerCommand extends Command
{
    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var string
     */
    protected $path = 'app' . DIRECTORY_SEPARATOR . 'Transformers';

    /**
     * @var string
     */
    protected $namespace = 'App\Transformers';

    /**
     * @var
     */
    protected $transformer;

    /**
     * @var
     */
    protected $modelOption;

    /**
     * @var
     */
    protected $composer;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:transformer {name : Transformer name} {--m|model= : Binding Model name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a transformer file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;

        // Set composer.
        $this->composer = app()['composer'];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Write transformer.
        $this->writeTransformer();

        // Dump autoload.
        $this->composer->dumpAutoloads();
    }

    /**
     * write transformer.
     */
    protected function writeTransformer()
    {
        // get argument.
        $transformer = $this->argument('name');
        $modelOption = $this->option('model');

        // Create the repository.
        if($this->create($transformer, $modelOption))
        {
            // Information message.
            $this->info("Transformer file created successfully.");
        }
    }

    /**
     * Create the transformer.
     *
     * @param $transformer
     * @param $modelOption
     * @return int
     */
    public function create($transformer, $modelOption)
    {
        // Set the transformer.
        $this->setTransformer($transformer, $modelOption);

        // Create the directory.
        $this->createDirectory();

        // Return result.
        return $this->createClass();
    }

    /**
     * Set transformer.
     *
     * @param mixed $transformer
     * @param mixed $modelOption
     */
    public function setTransformer($transformer, $modelOption)
    {
        $this->transformer = $transformer;
        $this->modelOption = $modelOption;
    }

    /**
     * Get transformer.
     *
     * @return mixed
     */
    public function getTransformer()
    {
        return $this->transformer;
    }

    /**
     * Get model.
     *
     * @return mixed
     */
    public function getModelOption()
    {
        return $this->modelOption ?:'';
    }

    /**
     * Create directory.
     */
    protected function createDirectory()
    {
        // Directory.
        $directory = $this->getDirectory();
        // Check if the directory exists.
        if(!$this->files->isDirectory($directory))
        {
            // Create the directory if not.
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Get the transformer directory.
     *
     * @return mixed
     */
    protected function getDirectory()
    {
        // Get the directory from the config file.
        $directory = $this->path;

        // Return the directory.
        return $directory;
    }

    /**
     * @return int
     */
    protected function createClass()
    {
        // Result.
        $result = $this->files->put($this->getPath(), $this->populateStub());
        // Return the result.
        return $result;
    }


    /**
     * Get the path.
     *
     * @return string
     */
    protected function getPath()
    {
        // Path.
        $path = $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getTransformerName() . '.php';
        // return path.
        return $path;
    }

    /**
     * Get the transformer name.
     *
     * @return mixed|string
     */
    protected function getTransformerName()
    {
        // Get the transformer.
        $transformer_name = $this->getTransformer();

        // Check if the transformer ends with 'Transformer'.
        if(!strpos($transformer_name, 'Transformer') !== false)
        {
            // Append 'Transformer' if not.
            $transformer_name .= 'Transformer';
        }
        // Return transformer name.
        return $transformer_name;
    }

    /**
     * Get the model name.
     *
     * @return mixed|string
     */
    public function getModelName()
    {
        // Get the model.
        $model_name = $this->getModelOption();

        // Check if the model is set.
        if (!$model_name) return '';

        // Check if the model has dir path.
        $modelPath = explode('/', $model_name);

        // Return model name.
        return end($modelPath);
    }

    /**
     * Get the model namespace.
     *
     * @return mixed|string
     */
    public function getModelNamespace()
    {
        // Get the model.
        $model_name = $this->getModelOption();

        // Check if the model is set.
        if (!$model_name) return '';

        // Check if the model has dir path.
        $modelPath = explode('/', $model_name);

        // Give namespace.
        $modelPath = implode("\\", $modelPath);

        $modelPath = 'App\\'.$modelPath;

        // Return model namespace.
        return $modelPath;
    }

    /**
     * Populate the stub.
     *
     * @return mixed
     */
    protected function populateStub()
    {
        // Populate data
        $populate_data = $this->getPopulateData();
        // Stub
        $stub = $this->getStub();
        // Loop through the populate data.
        foreach ($populate_data as $key => $value)
        {
            // Populate the stub.
            $stub = str_replace($key, $value, $stub);
        }
        // Return the stub.
        return $stub;
    }

    /**
     * Get the populate data.
     *
     * @return array
     */
    protected function getPopulateData()
    {
        // Transformer namespace.
        $transformer_namespace = $this->namespace;
        // Transformer class.
        $transformer_class     = $this->getTransformerName();
        // Model namespace
        $model_namespace = $this->getModelNamespace();
        // Model class.
        $model_class     = $this->getModelName();
        // Populate data.
        $populate_data = [
            'transformer_namespace' => $transformer_namespace,
            'transformer_class'     => $transformer_class,
            'model_namespace_use'   => $model_namespace ? "use {$model_namespace};" : '',
            'model_namespace'       => $model_namespace,
            '[model_class]'         => $model_class ? "{$model_class} " : ''
        ];
        // Return populate data.
        return $populate_data;
    }

    /**
     * Get the stub.
     *
     * @return string
     */
    protected function getStub()
    {
        // Stub
        $stub = $this->files->get($this->getStubPath() . "transformer.stub");
        // Return stub.
        return $stub;
    }

    /**
     * Get the stub path.
     *
     * @return string
     */
    protected function getStubPath()
    {
        // Stub path.
        $stub_path = __DIR__ . '/../../resources/stubs/';
        // Return the stub path.
        return $stub_path;
    }
}