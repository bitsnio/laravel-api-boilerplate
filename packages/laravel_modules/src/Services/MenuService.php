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

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get menus for specified module or all modules
     * Optionally filter by user permissions
     *
     * @param string|null $moduleName Optional module name to filter by
     * @param bool $filterByUserPermissions Whether to filter results by user permissions
     * @return array Array of menu structures
     */
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

            // If module name is specified, only process that module
            if ($moduleName) {
                $module = $this->repository->find($moduleName);

                if ($module) {
                    $menuPath = $module->getPath() . '/Config/menu.php';
                    if (file_exists($menuPath)) {
                        $menu = require $menuPath;
                        if (isset($menu['module'])) {
                            $processed = $this->processModuleMenu($menu['module']);

                            if ($filterByUserPermissions && $user) {
                                $processed = $this->filterMenuByUserPermissions($processed, $user);
                            }

                            if (!$filterByUserPermissions || !empty($processed)) {
                                $allMenus[$module->getName()] = $processed;
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
                            $processed = $this->processModuleMenu($menu['module']);

                            if ($filterByUserPermissions && $user) {
                                $processed = $this->filterMenuByUserPermissions($processed, $user);
                            }

                            if (!$filterByUserPermissions || !empty($processed)) {
                                $allMenus[$module->getName()] = $processed;
                            }
                        }
                    }
                }
            }

            return $allMenus;
        });
    }

    /**
     * Process a module menu structure
     * 
     * @param array $moduleMenu The module menu configuration
     * @return array Processed menu structure
     */
    protected function processModuleMenu(array $moduleMenu): array
    {
        // Return the menu structure as is, without adding permissions
        return $moduleMenu;
    }

    /**
     * Filter menu by user permissions
     * 
     * @param array $menu The menu structure
     * @param object $user The authenticated user
     * @return array Filtered menu structure
     */
    protected function filterMenuByUserPermissions(array $menu, $user): array
    {
        if (!$user) {
            return [];
        }
    
        $moduleName = strtolower($menu['name'] ?? '');
    
        // Module permission: inventory.inventory.view (all lowercase)
        $modulePermission = strtolower("{$moduleName}.{$moduleName}.view");
    
        if (!$user->can($modulePermission)) {
            return [];
        }
    
        if (isset($menu['sub_module']) && is_array($menu['sub_module'])) {
            foreach ($menu['sub_module'] as $key => $subModule) {
                $subModuleName = strtolower($subModule['name'] ?? '');
                $subModulePermission = strtolower("{$moduleName}.{$subModuleName}.view");
    
                if (!$user->can($subModulePermission)) {
                    unset($menu['sub_module'][$key]);
                    continue;
                }
    
                if (isset($subModule['actions']) && is_array($subModule['actions'])) {
                    foreach ($subModule['actions'] as $actionKey => $action) {
                        $actionName = strtolower($action['name'] ?? '');
                        $actionPermission = strtolower("{$moduleName}.{$subModuleName}.{$actionName}.view");
    
                        if (!$user->can($actionPermission)) {
                            unset($menu['sub_module'][$key]['actions'][$actionKey]);
                        }
                    }
                    if (isset($menu['sub_module'][$key]['actions'])) {
                        $menu['sub_module'][$key]['actions'] = array_values($menu['sub_module'][$key]['actions']);
                    }
                }
            }
            $menu['sub_module'] = array_values($menu['sub_module']);
        }
    
        return $menu;
    }




    public function allModules(): array
    {
        return array_keys($this->repository->all());
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

    /**
     * Get all sub-modules for a specific module
     * 
     * @param string $moduleName The name of the module
     * @return array Array of sub-modules or empty array if module not found
     */
    public function getSubModules(string $moduleName): array
    {
        $moduleMenus = $this->getMenus($moduleName);

        if (empty($moduleMenus['menus'][$moduleName]) || !isset($moduleMenus['menus'][$moduleName]['sub_module'])) {
            return [];
        }

        return $moduleMenus['menus'][$moduleName]['sub_module'];
    }

    /**
     * Get all actions for a specific sub-module within a module
     * 
     * @param string $moduleName The name of the module
     * @param string $subModuleName The name of the sub-module
     * @return array Array of actions or empty array if not found
     */
    public function getSubModuleActions(string $moduleName, string $subModuleName): array
    {
        $subModules = $this->getSubModules($moduleName);

        foreach ($subModules as $subModule) {
            if ($subModule['name'] === $subModuleName) {
                return $subModule['actions'] ?? [];
            }
        }

        return [];
    }

    /**
     * Get all modules structure with their sub-modules and actions
     * Optionally filter by user permissions
     * 
     * @param bool $filterByUserPermissions Whether to filter by user permissions
     * @return array Hierarchical structure of modules, sub-modules and actions
     */
    public function getModuleStructure(bool $filterByUserPermissions = false): array
    {
        $allData = $this->getMenus(null, $filterByUserPermissions);
        $structure = [];

        if (!isset($allData['menus']) || empty($allData['menus'])) {
            return [];
        }

        foreach ($allData['menus'] as $moduleName => $moduleData) {
            $module = [
                'name' => $moduleData['name'],
                'title' => $moduleData['title'] ?? $moduleData['name'],
                'icon' => $moduleData['icon'] ?? null,
                'order' => $moduleData['order'] ?? 999,
                'sub_modules' => []
            ];

            if (isset($moduleData['sub_module']) && is_array($moduleData['sub_module'])) {
                foreach ($moduleData['sub_module'] as $subModule) {
                    $subModuleData = [
                        'name' => $subModule['name'],
                        'title' => $subModule['title'] ?? $subModule['name'],
                        'icon' => $subModule['icon'] ?? null,
                        'order' => $subModule['order'] ?? 999,
                        'routes_type' => $subModule['routes_type'] ?? null,
                        'actions' => []
                    ];

                    if (isset($subModule['actions']) && is_array($subModule['actions'])) {
                        foreach ($subModule['actions'] as $action) {
                            $subModuleData['actions'][] = [
                                'name' => $action['name'],
                                'title' => $action['title'] ?? $action['name'],
                                'icon' => $action['icon'] ?? null,
                                'order' => $action['order'] ?? 999,
                                'routes_type' => $action['routes_type'] ?? null
                            ];
                        }

                        // Sort actions by order
                        usort($subModuleData['actions'], function ($a, $b) {
                            return $a['order'] <=> $b['order'];
                        });
                    }

                    $module['sub_modules'][] = $subModuleData;
                }

                // Sort sub-modules by order
                usort($module['sub_modules'], function ($a, $b) {
                    return $a['order'] <=> $b['order'];
                });
            }

            $structure[$moduleName] = $module;
        }

        return $structure;
    }
}
