<?php
namespace Bitsnio\Modules\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Bitsnio\Modules\Contracts\RepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;


class MenuService
{
    protected $repository;
    protected $cacheKey = 'module_menu_permissions';
    protected $cacheDuration = 1440; // 24 hours

    protected $methodPermissionMap = [
        'GET' => 'view',
        'POST' => 'create',
        'PUT' => 'update',
        'PATCH' => 'update',
        'DELETE' => 'delete'
    ];

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAllMenus(): array
    {
        return Cache::remember($this->cacheKey, $this->cacheDuration, function () {
            $modules = $this->repository->all();
            $allMenus = [];
            $allPermissions = [];

            foreach ($modules as $module) {
                $menuPath = $module->getPath() . '/Config/menu.php';
                if (file_exists($menuPath)) {
                    $menu = require $menuPath;
                    if (isset($menu['module'])) {
                        $processed = $this->processModuleMenu($menu['module'], $module->getName());
                        $allMenus[$module->getName()] = $processed['menu'];
                        $allPermissions[$module->getName()] = $processed['permissions'];
                    }
                }
            }

            // // Sync permissions with database
            // $this->syncPermissions($allPermissions);

            return [
                'menus' => $allMenus,
                'permissions' => $allPermissions
            ];
        });
    }

    protected function processModuleMenu(array $moduleMenu, string $moduleName): array
    {
        $permissions = [];
        $processedMenu = $moduleMenu;

        if (isset($moduleMenu['sub_module']) && is_array($moduleMenu['sub_module'])) {
            foreach ($moduleMenu['sub_module'] as $key => $subModule) {
                $subModulePermissions = $this->generateModulePermissions(
                    $moduleName,
                    $subModule['name']
                );
                
                $permissions = array_merge($permissions, $subModulePermissions);
                $processedMenu['sub_module'][$key]['permissions'] = $subModulePermissions;

                // Process actions
                if (isset($subModule['actions'])) {
                    $actions = is_array($subModule['actions']) && isset($subModule['actions']['name'])
                        ? [$subModule['actions']]
                        : $subModule['actions'];

                    if (is_array($actions)) {
                        foreach ($actions as $actionKey => $action) {
                            if (!is_array($action) || !isset($action['name'])) {
                                continue;
                            }

                            $actionPermissions = $this->generateModulePermissions(
                                $moduleName,
                                $subModule['name'] . '.' . $action['name']
                            );
                            
                            $permissions = array_merge($permissions, $actionPermissions);
                            
                            if (isset($processedMenu['sub_module'][$key]['actions'][$actionKey])) {
                                $processedMenu['sub_module'][$key]['actions'][$actionKey]['permissions'] = 
                                    $actionPermissions;
                            }
                        }
                    }
                }
            }
        }

        return [
            'menu' => $processedMenu,
            'permissions' => $permissions
        ];
    }

    protected function generateModulePermissions(string $moduleName, string $name): array
    {
        $identifier = Str::slug($moduleName . ' ' . $name, '.');
        
        return [
            'view' => $identifier . '.view',
            'create' => $identifier . '.create',
            'update' => $identifier . '.update',
            'delete' => $identifier . '.delete'
        ];
    }

    protected function syncPermissions(array $modulePermissions): void
    {
        $permissions = [];
        
        foreach ($modulePermissions as $module => $perms) {
            foreach ($perms as $group) {
                $permissions = array_merge($permissions, array_values($group));
            }
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }

    public function getRequiredPermission(string $moduleName, string $route, string $method): ?string
    {
        $method = strtoupper($method);
        $permissionType = $this->methodPermissionMap[$method] ?? 'view';
        
        $routeParts = collect(explode('/', trim($route, '/')));
        
        $allMenus = $this->getAllMenus();
        if (!isset($allMenus['permissions'][$moduleName])) {
            return null;
        }

        $identifier = $routeParts->map(function ($part) {
            return Str::slug($part);
        })->implode('.');

        return $moduleName . '.' . $identifier . '.' . $permissionType;
    }

    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }
}