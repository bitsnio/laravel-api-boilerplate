<?php
namespace Bitsnio\Modules\Generators;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MenuGenerator 
{
 
    protected $filesystem;
    protected $module;
    protected $moduleName;

    public function __construct($module, $moduleName)
    {
        $this->filesystem = new Filesystem();
        $this->module = $module;
        $this->moduleName = $moduleName;
    }

    /**
     * Generate menu configuration file
     */
    public function generate(): void
    {
        $path = $this->module->getModulePath($this->moduleName) . '/Config/menu.php';

        if (!$this->filesystem->isDirectory(dirname($path))) {
            $this->filesystem->makeDirectory(dirname($path), 0755, true);
        }

        $contents = $this->getStubContents();

        $this->filesystem->put($path, $contents);
    }

       /**
     * Get menu stub contents
     */
    protected function getStubContents(): string
    {
        $stub = $this->filesystem->get(__DIR__ . '/../commands/stubs/menu.stub');

        return $this->replaceStubPlaceholders($stub);
    }

    /**
     * Replace stub placeholders
     */
    protected function replaceStubPlaceholders(string $stub): string
    {
        $replacements = [
            '$MODULE_NAME$' => $this->moduleName,
            '$LOWER_NAME$' => strtolower($this->moduleName),
            '$STUDLY_NAME$' => Str::studly($this->moduleName),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );
    }
}