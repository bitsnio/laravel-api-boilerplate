<?php

namespace Bitsnio\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Bitsnio\Modules\Contracts\RepositoryInterface;


class MakeMenuControllerCommand extends Command
{
    protected $signature = 'module:make-menu_controllers {module : The module name}';
    protected $description = 'Generate or update controllers and API routes based on module menu configuration';

    protected $repository;
    protected $existingControllers = [];
    protected $menuLastModified;

    public function __construct(RepositoryInterface $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    public function handle(): void
    {
        $moduleName = $this->argument('module');
        $module = $this->repository->find($moduleName);

        if (!$module) {
            $this->error("Module [{$moduleName}] does not exist!");
            return;
        }

        $menuPath = $module->getPath() . '/Config/menu.php';
        if (!file_exists($menuPath)) {
            $this->error("Menu configuration not found for module [{$moduleName}]!");
            return;
        }

        $this->menuLastModified = filemtime($menuPath);
        $this->loadExistingControllers($module);

        $menu = require $menuPath;
        $this->generateControllersAndRoutes($module, $menu['module']['sub_module']);
    }

    protected function loadExistingControllers($module): void
    {
        $controllersPath = $module->getPath() . '/Http/Controllers';
        if (!file_exists($controllersPath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllersPath));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($controllersPath . '/', '', $file->getPathname());
                $this->existingControllers[rtrim($relativePath, '.php')] = $file->getMTime();
            }
        }
    }

    protected function generateControllersAndRoutes($module, array $subModules): void
    {
        $routeGroups = [];

        foreach ($subModules as $subModule) {
            // Generate or check main sub-module controller
            $this->handleControllerGeneration($module, $subModule['name']);

            $middlewareKey = $this->getMiddlewareKey($subModule['middleware']);

            if (!isset($routeGroups[$middlewareKey])) {
                $routeGroups[$middlewareKey] = [
                    'middleware' => $subModule['middleware'],
                    'routes' => []
                ];
            }

            $routeGroups[$middlewareKey]['routes'][] = $this->generateResourceRoute($subModule['name']);

            // Handle actions
            if (isset($subModule['actions']) && is_array($subModule['actions'])) {
                foreach ($subModule['actions'] as $action) {
                    $this->handleControllerGeneration($module, $action['name'], $subModule['name']);

                    $actionMiddlewareKey = $this->getMiddlewareKey($action['middleware']);

                    if (!isset($routeGroups[$actionMiddlewareKey])) {
                        $routeGroups[$actionMiddlewareKey] = [
                            'middleware' => $action['middleware'],
                            'routes' => []
                        ];
                    }

                    $routeGroups[$actionMiddlewareKey]['routes'][] =
                        $this->generateResourceRoute($action['name'], $subModule['name']);
                }
            }
        }

        // Save optimized routes
        if (!empty($routeGroups)) {
            $this->saveOptimizedRoutes($module, $routeGroups);
        }
    }

    protected function handleControllerGeneration($module, string $name, ?string $subFolder = null): void
    {
        $controllerName = Str::studly($name) . 'Controller';
        $controllerPath = $subFolder ? "{$subFolder}/{$controllerName}" : $controllerName;

        // Check if controller needs to be generated
        if (
            !isset($this->existingControllers[$controllerPath]) ||
            $this->existingControllers[$controllerPath] < $this->menuLastModified
        ) {

            $this->generateController($module, $name, $subFolder);
            $this->info("Controller [{$controllerPath}] " .
                (isset($this->existingControllers[$controllerPath]) ? "updated" : "created") . ".");
        } else {
            $this->info("Controller [{$controllerPath}] is already Exist so Skiping.");
        }
    }

    protected function generateController($module, string $name, ?string $subFolder = null): void
    {
        $controllerName = Str::studly($name) . 'Controller';
        $controllerPath = $subFolder ? "{$subFolder}/{$controllerName}" : $controllerName;

        $this->call('module:make-controller', [
            'controller' => $controllerPath,
            'module' => $module->getName(),
            '--api' => true
        ]);
    }

    protected function generateResourceRoute(string $name, ?string $parentName = null): string
    {
        // Convert name and parentName to kebab case
        $routeName = Str::kebab($name);
        $parentPath = $parentName ? Str::kebab($parentName) : '';

        // Combine paths without extra hyphens
        $routePath = $parentPath
            ? $parentPath . '/' . $routeName
            : $routeName;

        // Generate controller path
        $controller = $parentName
            ? "{$parentName}\\{$name}Controller"
            : "{$name}Controller";

        return "    Route::apiResource('{$routePath}', '{$controller}');";
    }

    protected function getMiddlewareKey(array $middleware): string
    {
        sort($middleware);
        return implode(':', $middleware);
    }

    protected function saveOptimizedRoutes($module, array $groups): void
    {
        $routePath = $module->getPath() . "/Routes/api.php";
        $content = "<?php\n\n";
        $content .= "use Illuminate\Support\Facades\Route;\n\n";

        foreach ($groups as $group) {
            $middlewareString = implode("', '", $group['middleware']);
            $content .= "Route::middleware(['{$middlewareString}'])->group(function () {\n";
            $content .= implode("\n", $group['routes']);
            $content .= "\n});\n\n";
        }

        file_put_contents($routePath, $content);
    }
}
