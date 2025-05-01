<?php

namespace Bitsnio\Modules\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PermissionService
{
    protected $menuService;
    protected $cacheKey = 'module_permissions';
    protected $cacheDuration = 1440; // 24 hours

    protected $methodPermissionMap = [
        'GET' => 'view',
        'POST' => 'create',
        'PUT' => 'update',
        'PATCH' => 'update',
        'DELETE' => 'delete'
    ];

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * Generate permissions for a module, submodule, or action
     * 
     * @param string $moduleName Module name
     * @param string $name Submodule or action name
     * @return array Generated permissions
     */
    public function generateModulePermissions(string $moduleName, string $name): array
    {
        $identifier = Str::slug($moduleName . ' ' . $name, '.');

        return [
            'view' => $identifier . '.view',
            'create' => $identifier . '.create',
            'update' => $identifier . '.update',
            'delete' => $identifier . '.delete'
        ];
    }

    /**
     * Get all permissions for all modules or a specific module
     * 
     * @param string|null $moduleName Optional module name to filter by
     * @return array All permissions organized by module and section
     */
    public function getAllPermissions(?string $moduleName = null): array
    {
        $cacheKey = $this->cacheKey . ($moduleName ? '_' . $moduleName : '');

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($moduleName) {
            $allMenus = $this->menuService->getMenus($moduleName);
            $allPermissions = [];

            foreach ($allMenus as $module => $menuData) {
                $modulePermissions = [];
                
                // Module level permissions
                $modulePermissionSet = $this->generateModulePermissions($module, $menuData['name']);
                $modulePermissions[$menuData['name']] = $modulePermissionSet;

                // Process sub-modules
                if (isset($menuData['sub_module']) && is_array($menuData['sub_module'])) {
                    foreach ($menuData['sub_module'] as $subModule) {
                        $subModuleName = $subModule['name'];
                        $subModulePermissionSet = $this->generateModulePermissions($module, $subModuleName);
                        $modulePermissions[$subModuleName] = $subModulePermissionSet;

                        // Process actions
                        if (isset($subModule['actions']) && is_array($subModule['actions'])) {
                            foreach ($subModule['actions'] as $action) {
                                if (!isset($action['name'])) {
                                    continue;
                                }

                                $actionName = $subModuleName . '.' . $action['name'];
                                $actionPermissionSet = $this->generateModulePermissions($module, $actionName);
                                $modulePermissions[$actionName] = $actionPermissionSet;
                            }
                        }
                    }
                }

                $allPermissions[$module] = $modulePermissions;
            }

            return $allPermissions;
        });
    }

    /**
     * Define role with permissions
     *
     * @param array $config [
     *     'name' => 'role_name',          // Required
     *     'description' => 'Description', // Optional
     *     'modules' => ['Module1', 'Module2'], // All permissions for these modules
     *     'granular_modules' => [        // Specific permissions for these modules
     *         'Module1' => [
     *             'permissions' => ['view', 'create'], // Module-level
     *             'sub_modules' => [
     *                 'SubModule1' => [               // Sub-module level
     *                     'permissions' => ['view'],
     *                     'actions' => [
     *                         'Action1' => ['create'] // Action-level
     *                     ]
     *                 ]
     *             ]
     *         ]
     *     ]
     * ]
     * @return array
     */
    public function defineRoleWithPermissions(array $config): array
    {
        $this->validateRoleConfig($config);
        $allMenus = $this->menuService->getMenus();

        // Generate permissions for each module and create a case map
        $modulePermissions = [];
        $moduleNameMap = []; // Maps lowercase module names to original case
        foreach ($allMenus as $moduleName => $menuData) {
            $modulePermissions[$moduleName] = $this->generatePermissionsForModule($moduleName, $menuData);
            $moduleNameMap[strtolower($moduleName)] = $moduleName;
        }

        // Process permissions
        $allPermissions = $this->processModulesWithAllPermissions(
            $modulePermissions,
            $config['modules'] ?? [],
            $moduleNameMap
        );

        $granularPermissions = $this->processGranularModules(
            $modulePermissions,
            $config['granular_modules'] ?? [],
            $moduleNameMap
        );

        $permissions = array_merge($allPermissions, $granularPermissions);

        // Pass the correct guard name based on your role
        $guardName = $config['guard_name'] ?? 'api';

        $this->ensurePermissionsExist($permissions, $guardName);

        $role = Role::updateOrCreate(
            ['name' => $config['name'], 'guard_name' => $guardName],
            ['description' => $config['description'] ?? null]
        );

        $role->syncPermissions(
            Permission::whereIn('name', $permissions)
                ->where('guard_name', $guardName)
                ->get()
        );

        return [
            'role' => $role->name,
            'permissions_count' => count($permissions),
            'modules' => array_merge(
                $config['modules'] ?? [],
                array_keys($config['granular_modules'] ?? [])
            )
        ];
    }

    /**
     * Normalize module names array for case-insensitive comparison
     * 
     * @param array $moduleNames
     * @return array
     */
    protected function normalizeModuleNames(array $moduleNames): array
    {
        return array_map('strtolower', $moduleNames);
    }

    /**
     * Normalize granular module configuration for case-insensitive comparison
     * 
     * @param array $granularModules
     * @return array
     */
    protected function normalizeGranularModules(array $granularModules): array
    {
        $normalized = [];
        foreach ($granularModules as $moduleName => $moduleConfig) {
            $normalized[strtolower($moduleName)] = $moduleConfig;
        }
        return $normalized;
    }

    /**
     * Normalize module permissions keys for case-insensitive comparison
     * 
     * @param array $modulePermissions
     * @return array
     */
    protected function normalizeModulePermissionsKeys(array $modulePermissions): array
    {
        $normalized = [];
        foreach ($modulePermissions as $moduleName => $permissions) {
            $normalized[strtolower($moduleName)] = $permissions;
        }
        return $normalized;
    }

    /**
     * Generate complete permissions structure for a module
     * 
     * @param string $moduleName Module name
     * @param array $moduleData Module data
     * @return array Permissions structure
     */
    protected function generatePermissionsForModule(string $moduleName, array $moduleData): array
    {
        $result = [];
        
        // Module level permissions
        $modulePermissions = $this->generateModulePermissions($moduleName, $moduleData['name']);
        $result['module'] = [
            'permissions' => $modulePermissions
        ];
        
        // Sub-module permissions
        if (isset($moduleData['sub_module']) && is_array($moduleData['sub_module'])) {
            foreach ($moduleData['sub_module'] as $subModule) {
                $subModuleName = $subModule['name'];
                $subModulePermissions = $this->generateModulePermissions($moduleName, $subModuleName);
                
                $result[$subModuleName] = [
                    'permissions' => $subModulePermissions
                ];
                
                // Action permissions
                if (isset($subModule['actions']) && is_array($subModule['actions'])) {
                    foreach ($subModule['actions'] as $action) {
                        $actionName = $action['name'];
                        $actionIdentifier = $subModuleName . '.' . $actionName;
                        $actionPermissions = $this->generateModulePermissions($moduleName, $actionIdentifier);
                        
                        $result[$actionIdentifier] = [
                            'permissions' => $actionPermissions
                        ];
                    }
                }
            }
        }
        
        return $result;
    }

    protected function processModulesWithAllPermissions(array $modulePermissions, array $moduleNames, array $moduleNameMap): array
    {
        $permissions = [];

        foreach ($moduleNames as $moduleName) {
            // Find the actual module name using the case map
            $actualModuleName = $moduleNameMap[strtolower($moduleName)] ?? null;
            
            if (!$actualModuleName || !isset($modulePermissions[$actualModuleName])) {
                continue;
            }

            $permissions = array_merge(
                $permissions,
                $this->collectAllPermissions($modulePermissions[$actualModuleName])
            );
        }

        return $permissions;
    }

    protected function collectAllPermissions(array $moduleData): array
    {
        $permissions = [];

        foreach ($moduleData as $section) {
            if (isset($section['permissions'])) {
                $permissions = array_merge($permissions, array_values($section['permissions']));
            }
        }

        return $permissions;
    }

    protected function processGranularModules(array $modulePermissions, array $modulesConfig, array $moduleNameMap): array
    {
        $permissions = [];

        foreach ($modulesConfig as $moduleName => $moduleConfig) {
            // Find the actual module name using the case map
            $actualModuleName = $moduleNameMap[strtolower($moduleName)] ?? null;
            
            if (!$actualModuleName || !isset($modulePermissions[$actualModuleName])) {
                continue;
            }

            $moduleData = $modulePermissions[$actualModuleName];
            
            // Handle module-level permissions
            if (!empty($moduleConfig['permissions']) && isset($moduleData['module'])) {
                $permissions = array_merge(
                    $permissions,
                    $this->filterPermissions(
                        $moduleData['module']['permissions'],
                        $moduleConfig['permissions']
                    )
                );
            }
            
            // Handle sub-modules
            if (!empty($moduleConfig['sub_modules'])) {
                foreach ($moduleConfig['sub_modules'] as $subModuleName => $subModuleConfig) {
                    // Create a map of lowercase submodule names to actual keys
                    $subModuleMap = [];
                    foreach (array_keys($moduleData) as $key) {
                        $subModuleMap[strtolower($key)] = $key;
                    }
                    
                    // Find the actual submodule name
                    $actualSubModuleName = $subModuleMap[strtolower($subModuleName)] ?? null;
                    
                    // Sub-module level permissions
                    if (!empty($subModuleConfig['permissions']) && $actualSubModuleName && isset($moduleData[$actualSubModuleName])) {
                        $permissions = array_merge(
                            $permissions,
                            $this->filterPermissions(
                                $moduleData[$actualSubModuleName]['permissions'],
                                $subModuleConfig['permissions']
                            )
                        );
                    }
                    
                    // Action permissions
                    if (!empty($subModuleConfig['actions'])) {
                        foreach ($subModuleConfig['actions'] as $actionName => $actionPermTypes) {
                            $possibleActionKey = $subModuleName . '.' . $actionName;
                            
                            // Try to find the actual action key
                            $actualActionKey = null;
                            foreach (array_keys($moduleData) as $key) {
                                if (strtolower($key) === strtolower($possibleActionKey)) {
                                    $actualActionKey = $key;
                                    break;
                                }
                            }
                            
                            if ($actualActionKey && isset($moduleData[$actualActionKey])) {
                                $permissions = array_merge(
                                    $permissions,
                                    $this->filterPermissions(
                                        $moduleData[$actualActionKey]['permissions'],
                                        $actionPermTypes
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }

        return $permissions;
    }

    /**
     * Find key in array in a case-insensitive way
     * 
     * @param array $array
     * @param string $search
     * @return string|null
     */
    protected function findCaseInsensitiveKey(array $array, string $search): ?string
    {
        $lowerSearch = strtolower($search);
        foreach ($array as $key => $value) {
            if (strtolower($key) === $lowerSearch) {
                return $key;
            }
        }
        return null;
    }

    protected function filterPermissions(array $availablePermissions, array $requestedTypes): array
    {
        return array_intersect_key(
            $availablePermissions,
            array_flip($requestedTypes)
        );
    }

    protected function ensurePermissionsExist(array $permissions, string $guardName = 'api'): void
    {
        $existing = Permission::whereIn('name', $permissions)
            ->where('guard_name', $guardName)
            ->pluck('name');

        $missing = array_diff($permissions, $existing->toArray());

        foreach ($missing as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => $guardName
            ]);
        }
    }

    protected function validateRoleConfig(array $config): void
    {
        if (empty($config['name'])) {
            throw new \InvalidArgumentException('Role name is required');
        }

        if (empty($config['modules']) && empty($config['granular_modules'])) {
            throw new \InvalidArgumentException('At least one module must be specified');
        }
    }

    /**
     * Assign role to users
     *
     * @param string|array $roleNames
     * @param int|array $userIds
     * @param bool $replaceExisting
     * @return array
     */
    public function assignRoleToUsers($roleNames, $userIds, bool $replaceExisting = true): array
    {
        $roles = Role::whereIn('name', Arr::wrap($roleNames))->get();
        if ($roles->isEmpty()) {
            throw new \InvalidArgumentException('No matching roles found');
        }

        $results = [];
        foreach (Arr::wrap($userIds) as $userId) {
            $user = User::find($userId);
            if (!$user) {
                $results[$userId] = ['success' => false, 'message' => 'User not found'];
                continue;
            }

            $replaceExisting
                ? $user->syncRoles($roles)
                : $user->assignRole($roles);

            $results[$userId] = [
                'success' => true,
                'user_name' => $user->name,
                'assigned_roles' => $roles->pluck('name')->toArray()
            ];
        }

        return $results;
    }

    /**
     * Get permissions for a role
     *
     * @param string $roleName
     * @return Collection
     */
    public function getRolePermissions(string $roleName): Collection
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        return $role->permissions()->orderBy('name')->get();
    }

    /**
     * Update role permissions
     *
     * @param string $roleName
     * @param array $config (same structure as defineRoleWithPermissions)
     * @return array
     */
    public function updateRolePermissions(string $roleName, array $config): array
    {
        $config['name'] = $roleName;
        return $this->defineRoleWithPermissions($config);
    }

    /**
     * Sync permissions to the database
     * 
     * @return void
     */
    public function syncPermissions(): void
    {
        $allPermissions = $this->getAllPermissions();
        $permissions = [];

        foreach ($allPermissions as $module => $modulePerm) {
            foreach ($modulePerm as $section => $actions) {
                $permissions = array_merge($permissions, array_values($actions));
            }
        }

        foreach ($permissions as $permission) {
            // Check if the permission exists first
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission, 'guard_name' => 'api']);
            }
        }

        $this->clearCache();
    }

    /**
     * Get required permission for a route and HTTP method
     * 
     * @param string $route The route path
     * @param string $method HTTP method (GET, POST, etc.)
     * @return string|null Permission name or null if not found
     */
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
        
        // Extract the route parts after the module name
        $routePath = $routeParts->slice(1)->values()->implode('.');
        if (empty($routePath)) {
            $routePath = $moduleName;
        }

        // Format the identifier
        $identifier = Str::slug($moduleName) . '.' . Str::slug($routePath, '.');

        return $identifier . '.' . $permissionType;
    }

    /**
     * Clear permission cache
     * 
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
        
        // Clear cache for specific modules
        $moduleNames = $this->menuService->allModules();
        foreach ($moduleNames as $moduleName) {
            Cache::forget($this->cacheKey . '_' . $moduleName);
        }
    }
}