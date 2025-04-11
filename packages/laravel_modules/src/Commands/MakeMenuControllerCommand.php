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

        // Generate main module controller and route
        $this->handleControllerGeneration($module, $moduleName);

        // Start with the main module route
        $routeGroups = [];

        // Get middleware from module config if available, otherwise use empty array
        $mainMiddleware = $menu['module']['middleware'] ?? [];

        // Only add routes to groups if middleware is defined, otherwise create a special "no_middleware" key
        $middlewareKey = !empty($mainMiddleware) ? $this->getMiddlewareKey($mainMiddleware) : "no_middleware";
        $routeGroups[$middlewareKey] = [
            'middleware' => $mainMiddleware,
            'routes' => [$this->generateResourceRoute($moduleName)]
        ];

        // Then process sub modules
        $this->generateControllersAndRoutes($module, $menu['module']['sub_module'], $routeGroups);
    }

    protected function loadExistingControllers($module): void
    {
        $controllersPath = $module->getPath() . '/App/Http/Controllers';
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

    protected function generateControllersAndRoutes($module, array $subModules, array &$routeGroups): void
    {
        foreach ($subModules as $subModule) {
            // Generate or check main sub-module controller
            $this->handleControllerGeneration($module, $subModule['name']);

            $middlewareKey = !empty($subModule['middleware']) ? $this->getMiddlewareKey($subModule['middleware']) : "no_middleware";

            if (!isset($routeGroups[$middlewareKey])) {
                $routeGroups[$middlewareKey] = [
                    'middleware' => $subModule['middleware'] ?? [],
                    'routes' => []
                ];
            }

            $routeGroups[$middlewareKey]['routes'][] = $this->generateResourceRoute($subModule['name']);

            // Handle actions
            if (isset($subModule['actions']) && is_array($subModule['actions'])) {
                foreach ($subModule['actions'] as $action) {
                    $this->handleControllerGeneration($module, $action['name'], $subModule['name']);

                    $actionMiddlewareKey = !empty($action['middleware']) ? $this->getMiddlewareKey($action['middleware']) : "no_middleware";

                    if (!isset($routeGroups[$actionMiddlewareKey])) {
                        $routeGroups[$actionMiddlewareKey] = [
                            'middleware' => $action['middleware'] ?? [],
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
        // Apply StudlyCase to controller name
        $controllerName = Str::studly($name) . 'Controller';

        // Apply StudlyCase to subfolder as well for PSR-4 compliance
        $subFolderStudly = $subFolder ? Str::studly($subFolder) : null;
        $controllerPath = $subFolderStudly ? "{$subFolderStudly}/{$controllerName}" : $controllerName;

        // Check if controller needs to be generated
        if (
            !isset($this->existingControllers[$controllerPath]) ||
            $this->existingControllers[$controllerPath] < $this->menuLastModified
        ) {
            $this->generateController($module, $name, $subFolderStudly);
            $this->info("Controller [{$controllerPath}] " .
                (isset($this->existingControllers[$controllerPath]) ? "updated" : "created") . ".");
        } else {
            $this->info("Controller [{$controllerPath}] already exists, skipping.");
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

        // Include module name as prefix (convert to kebab case)
        $modulePrefix = Str::kebab($this->argument('module'));

        // Combine paths without extra hyphens
        // Special case for main module controller - don't duplicate module name in route
        if ($routeName === $modulePrefix && !$parentName) {
            $routePath = $modulePrefix;
        } else {
            $routePath = $modulePrefix . '_' . ($parentPath
                ? $parentPath . '_' . $routeName
                : $routeName);
        }

        // Generate controller class name with full namespace
        $controllerName = Str::studly($name) . 'Controller';
        $controllerPath = $parentName
            ? Str::studly($parentName) . '\\' . $controllerName
            : $controllerName;

        // Create the full controller class path with consistent App/Http/Controllers path
        $controllerClass = "Modules\\" . $this->argument('module') . "\\App\\Http\\Controllers\\" . $controllerPath;

        // Return a temporary format that will be parsed later
        return "    Route::apiResource('{$routePath}', {$controllerClass}::class);";
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
        $content .= "use Illuminate\Support\Facades\Route;\n";

        // Collect all controllers that will be used
        $controllers = [];

        // Transform the route groups to use the new array-based apiResources style
        foreach ($groups as $key => $group) {
            $groups[$key]['resources'] = [];

            foreach ($group['routes'] as $route) {
                // Extract route path and controller from the original format
                preg_match('/Route::apiResource\(\'(.*?)\', (.*?)\)/', $route, $matches);

                if (count($matches) >= 3) {
                    $path = $matches[1];
                    $controller = trim($matches[2], "';");

                    // For the new format, we need just the controller class without quotes
                    $controllerClass = str_replace("::class", "", $controller);
                    $controllers[] = $controllerClass;

                    // Get just the class name (not the full namespace)
                    $classParts = explode('\\', $controllerClass);
                    $className = end($classParts);

                    // Store the route path and controller for later use in apiResources array
                    $groups[$key]['resources'][$path] = $className . "::class";
                }
            }
        }

        // Add use statements for all controllers
        $uniqueControllers = array_unique($controllers);
        foreach ($uniqueControllers as $controller) {
            $content .= "use $controller;\n";
        }
        $content .= "\n";

        // Generate route groups using apiResources array syntax
        foreach ($groups as $key => $group) {
            if (empty($group['resources'])) {
                continue;
            }

            if ($key === "no_middleware") {
                // No middleware, just define routes directly
                $content .= "Route::apiResources([\n";

                foreach ($group['resources'] as $path => $controller) {
                    $content .= "    '$path' => $controller,\n";
                }

                $content .= "]);\n\n";
            } else {
                // With middleware
                $middlewareString = implode("', '", $group['middleware']);
                $content .= "Route::middleware(['{$middlewareString}'])->group(function () {\n";

                // Use the apiResources method with array notation
                $content .= "    Route::apiResources([\n";

                foreach ($group['resources'] as $path => $controller) {
                    $content .= "        '$path' => $controller,\n";
                }

                $content .= "    ]);\n";
                $content .= "});\n\n";
            }
        }

        file_put_contents($routePath, $content);
    }
}
