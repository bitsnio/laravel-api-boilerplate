<?php

namespace Bitsnio\Modules\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Bitsnio\Modules\Support\Config\GenerateConfigReader;
use Bitsnio\Modules\Support\Stub;
use Bitsnio\Modules\Traits\LoadAndWriteJson;
use Bitsnio\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeAllfilesCommand extends GeneratorCommand
{
    use ModuleCommandTrait, LoadAndWriteJson;

    /**
     * The name of argument name.
     *
     * @var string
     */
    protected $argumentName = 'model';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-All';
    private $file_exist = false;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create all files including model, migration, seeder, factory, controller and request file';

    public function handle(): int
    {
        if (parent::handle() === E_ERROR) {
            return E_ERROR;
        }
        if(file_exists('Request.json')) {
            $this->file_exist = true;
            $this->createJsonTemplete($this->getModelName());
        }
        $this->handleAllOption();
        if($this->file_exist) File::delete(['Request.json', 'Template.json']);

        return 0;
    }

    /**
     * Create a proper migration name:
     * ProductDetail: product_details
     * Product: products
     * @return string
     */
    private function createMigrationName()
    {
        $pieces = preg_split('/(?=[A-Z])/', $this->argument('model'), -1, PREG_SPLIT_NO_EMPTY);

        $string = '';
        foreach ($pieces as $i => $piece) {
            if ($i+1 < count($pieces)) {
                $string .= strtolower($piece) . '_';
            } else {
                $string .= Str::plural(strtolower($piece));
            }
        }

        return $string;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['model', InputArgument::REQUIRED, 'The name of model will be created.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['fillable', null, InputOption::VALUE_OPTIONAL, 'The fillable attributes.', null],
        ];
    }

    protected function handleAllOption(){
        //handle request options
        $requestName = "{$this->getModelName()}Request";
        $this->call('module:make-request', array_filter([
            'name' => $requestName,
            'module' => $this->argument('module')
        ]));

        //handle factory options
        $this->call('module:make-factory', array_filter([
            'name' => $this->getModelName(),
            'module' => $this->argument('module')
        ]));

        //handle seeder options
        $seedName = "{$this->getModelName()}Seeder";
        $this->call('module:make-seed', array_filter([
            'name' => $seedName,
            'module' => $this->argument('module')
        ]));

        //handle controller options
        $controllerName = "{$this->getModelName()}Controller";
        $this->call('module:make-controller', array_filter([
            'controller' => $controllerName,
            'module' => $this->argument('module'),
        ]));

        //handle migration options
        if($this->file_exist){
            $path = $this->laravel['modules']->getModulePath($this->getModuleName());
            $generatorPath = GenerateConfigReader::read('migration');
            $this->call('json:migrate', ['file' => 'Template.json', 'path' => $path . $generatorPath->getPath() . '/']);
        }
        else $this->call('module:make-migration', ['name' => $this->getModelName(), 'module' => $this->argument('module')]);

    }

    /**
     * @return mixed
     */
    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/model.stub', [
            'NAME'              => $this->getModelName(),
            'FILLABLE'          => $this->getFillable(),
            'NAMESPACE'         => $this->getClassNamespace($module),
            'CLASS'             => $this->getClass(),
            'LOWER_NAME'        => $module->getLowerName(),
            'MODULE'            => $this->getModuleName(),
            'STUDLY_NAME'       => $module->getStudlyName(),
            'MODULE_NAMESPACE'  => $this->laravel['modules']->config('namespace'),
        ]))->render();
    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $modelPath = GenerateConfigReader::read('model');

        return $path . $modelPath->getPath() . '/' . $this->getModelName() . '.php';
    }

    /**
     * @return mixed|string
     */
    private function getModelName()
    {
        return Str::studly($this->argument('model'));
    }

    /**
     * @return string
     */
    private function getFillable()
    {
        if($this->file_exist){
            $fillable = $this->getFillables($this->getModelName());
            if (!is_null($fillable)) {
                $arrays = (is_string($fillable)) ? explode(',', $fillable) : $fillable;
                return json_encode($arrays);
            }
        }
        return '[]';
    }

    /**
     * Get default namespace.
     *
     * @return string
     */
    public function getDefaultNamespace(): string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.model.namespace') ?: $module->config('paths.generator.model.path', 'Entities');
    }
   
}
