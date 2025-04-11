<?php

namespace Bitsnio\Modules\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Bitsnio\Modules\Contracts\RepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

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

    public function allModules(): array
    {
        return array_keys($this->repository->all());
    }

    public function getMenus(?string $moduleName = null, bool $filterByUserPermissions = false): array
    {
        $user = null;
        $userId = 'guest';

        if ($filterByUserPermissions) {
            try {
                $user = JWTAuth::parseToken()->authenticate();
                $userId = $user->id ?? 'guest';
            } catch (\Exception $e) {
                // Token invalid or not present
            }
        }

        $cacheKey = $filterByUserPermissions
            ? $this->cacheKey . ($moduleName ? '_' . $moduleName : '') . '_user_' . $userId
            : $this->cacheKey . ($moduleName ? '_' . $moduleName : '');

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($moduleName, $filterByUserPermissions, $user) {
            $allMenus = [];
            $allPermissions = [];

            // If module name is specified, only process that module
            if ($moduleName) {
                $module = $this->repository->find($moduleName);

                if ($module) {
                    $menuPath = $module->getPath() . '/Config/menu.php';
                    if (file_exists($menuPath)) {
                        $menu = require $menuPath;
                        if (isset($menu['module'])) {
                            $processed = $this->processModuleMenu($menu['module'], $module->getName());

                            if ($filterByUserPermissions && $user) {
                                $processed = $this->filterMenuByUserPermissions($processed, $user);
                            }

                            if (!$filterByUserPermissions || !empty($processed['menu'])) {
                                $allMenus[$module->getName()] = $processed['menu'];
                                $allPermissions[$module->getName()] = $processed['permissions'];
                            }
                        }
                    }
                }
            }
            // Otherwise process all modules
            else {
                $modules = $this->repository->all();

                foreach ($modules as $module) {
                    $menuPath = $module->getPath() . '/Config/menu.php';
                    if (file_exists($menuPath)) {
                        $menu = require $menuPath;
                        if (isset($menu['module'])) {
                            $processed = $this->processModuleMenu($menu['module'], $module->getName());

                            if ($filterByUserPermissions && $user) {
                                $processed = $this->filterMenuByUserPermissions($processed, $user);
                            }

                            if (!$filterByUserPermissions || !empty($processed['menu'])) {
                                $allMenus[$module->getName()] = $processed['menu'];
                                $allPermissions[$module->getName()] = $processed['permissions'];
                            }
                        }
                    }
                }
            }

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

        // Generate module-level permissions
        $modulePermissions = $this->generateModulePermissions(
            $moduleName,
            $moduleMenu['name']
        );
        $permissions[$moduleMenu['name']] = $modulePermissions;
        $processedMenu['permissions'] = $modulePermissions;

        if (isset($moduleMenu['sub_module']) && is_array($moduleMenu['sub_module'])) {
            foreach ($moduleMenu['sub_module'] as $key => $subModule) {
                $subModulePermissions = $this->generateModulePermissions(
                    $moduleName,
                    $subModule['name']
                );

                $permissions[$subModule['name']] = $subModulePermissions;
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

                            $permissions[$subModule['name'] . '.' . $action['name']] = $actionPermissions;

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

    public function syncPermissions(array $modulePermissions = null): void
    {
        if ($modulePermissions === null) {
            $allMenus = $this->getMenus(false);
            $modulePermissions = $allMenus['permissions'] ?? [];
        }

        $permissions = [];

        foreach ($modulePermissions as $module => $perms) {
            foreach ($perms as $section => $actions) {
                $permissions = array_merge($permissions, array_values($actions));
            }
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }

    public function createRoleWithPermissions(string $roleName, array $selectedPermissions): Role
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        // Sync the selected permissions to the role
        $permissions = Permission::whereIn('name', $selectedPermissions)->get();
        $role->syncPermissions($permissions);

        return $role;
    }

    public function getRequiredPermission(string $route, string $method): ?string
    {
        $method = strtoupper($method);
        $permissionType = $this->methodPermissionMap[$method] ?? 'view';

        $routeParts = collect(explode('/', trim($route, '/')));

        // First part is usually the module name
        if ($routeParts->isEmpty()) {
            return null;
        }

        $moduleName = $routeParts->first();
        $allMenus = $this->getMenus();

        // Extract the route parts after the module name
        $routePath = $routeParts->slice(1)->values()->implode('.');
        if (empty($routePath)) {
            $routePath = $moduleName;
        }

        // Format the identifier
        $identifier = Str::slug($moduleName) . '.' . Str::slug($routePath, '.');

        return $identifier . '.' . $permissionType;
    }

    protected function filterMenuByUserPermissions(array $processed, $user): array
    {
        if (!$user) {
            return [
                'menu' => [],
                'permissions' => []
            ];
        }

        $processedMenu = $processed['menu'];
        $filteredPermissions = $processed['permissions'];

        // Check if user has any permissions for this module
        $modulePermissions = $processed['menu']['permissions'] ?? [];
        $hasModulePermission = false;

        foreach ($modulePermissions as $permission) {
            if ($user->can($permission)) {
                $hasModulePermission = true;
                break;
            }
        }

        if (!$hasModulePermission) {
            // No permissions for this module at all
            return [
                'menu' => [],
                'permissions' => []
            ];
        }

        // Filter sub-modules based on permissions
        if (isset($processedMenu['sub_module']) && is_array($processedMenu['sub_module'])) {
            foreach ($processedMenu['sub_module'] as $key => $subModule) {
                $hasSubModulePermission = false;

                // Check sub-module permissions
                if (isset($subModule['permissions'])) {
                    foreach ($subModule['permissions'] as $permission) {
                        if ($user->can($permission)) {
                            $hasSubModulePermission = true;
                            break;
                        }
                    }
                }

                // If no permission, remove this sub-module
                if (!$hasSubModulePermission) {
                    unset($processedMenu['sub_module'][$key]);
                    continue;
                }

                // Filter actions based on permissions
                if (isset($subModule['actions']) && is_array($subModule['actions'])) {
                    foreach ($subModule['actions'] as $actionKey => $action) {
                        $hasActionPermission = false;

                        if (isset($action['permissions'])) {
                            foreach ($action['permissions'] as $permission) {
                                if ($user->can($permission)) {
                                    $hasActionPermission = true;
                                    break;
                                }
                            }
                        }

                        // If no permission, remove this action
                        if (!$hasActionPermission) {
                            unset($processedMenu['sub_module'][$key]['actions'][$actionKey]);
                        }
                    }

                    // Reset array keys
                    if (isset($processedMenu['sub_module'][$key]['actions'])) {
                        $processedMenu['sub_module'][$key]['actions'] = array_values($processedMenu['sub_module'][$key]['actions']);
                    }
                }
            }

            // Reset array keys for sub-modules
            $processedMenu['sub_module'] = array_values($processedMenu['sub_module']);
        }

        return [
            'menu' => $processedMenu,
            'permissions' => $filteredPermissions
        ];
    }

    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);

        // Also clear any user-specific caches when possible
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if ($user) {
                Cache::forget($this->cacheKey . '_user_' . $user->id);
            }
        } catch (\Exception $e) {
            // Token invalid or not present, which is fine
        }
    }
}
