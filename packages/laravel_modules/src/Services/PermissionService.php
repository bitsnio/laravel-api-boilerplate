<?php

namespace Bitsnio\Modules\Services;

use Bitsnio\Modules\Services\MenuService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PermissionService
{
    protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
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

        // Process permissions
        $allPermissions = $this->processModulesWithAllPermissions(
            $allMenus['menus'],
            $config['modules'] ?? []
        );

        $granularPermissions = $this->processGranularModules(
            $allMenus['menus'],
            $config['granular_modules'] ?? []
        );

        $permissions = array_merge($allPermissions, $granularPermissions);

        // 👇 pass the correct guard name based on your role
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


    protected function processModulesWithAllPermissions(array $menus, array $moduleNames): array
    {
        $permissions = [];

        foreach ($moduleNames as $moduleName) {
            if (!isset($menus[$moduleName])) {
                continue;
            }

            $permissions = array_merge(
                $permissions,
                $this->collectAllPermissions($menus[$moduleName])
            );
        }

        return $permissions;
    }

    protected function collectAllPermissions(array $moduleData): array
    {
        $permissions = [];

        // Module-level permissions
        if (isset($moduleData['permissions'])) {
            $permissions = array_merge($permissions, array_values($moduleData['permissions']));
        }

        // Sub-module permissions
        foreach ($moduleData['sub_module'] ?? [] as $subModule) {
            if (isset($subModule['permissions'])) {
                $permissions = array_merge($permissions, array_values($subModule['permissions']));
            }

            // Action permissions
            foreach ($subModule['actions'] ?? [] as $action) {
                if (isset($action['permissions'])) {
                    $permissions = array_merge($permissions, array_values($action['permissions']));
                }
            }
        }

        return $permissions;
    }

    protected function processGranularModules(array $menus, array $modulesConfig): array
    {
        $permissions = [];

        foreach ($modulesConfig as $moduleName => $moduleConfig) {
            if (!isset($menus[$moduleName])) {
                continue;
            }

            $moduleData = $menus[$moduleName];
            $permissions = array_merge(
                $permissions,
                $this->handleModuleLevel($moduleData, $moduleConfig),
                $this->handleSubModules($moduleData, $moduleConfig)
            );
        }

        return $permissions;
    }

    protected function handleModuleLevel(array $moduleData, array $config): array
    {
        if (empty($moduleData['permissions'])) {
            return [];
        }

        if (empty($config['permissions'])) {
            return array_values($moduleData['permissions']);
        }

        return $this->filterPermissions(
            $moduleData['permissions'],
            $config['permissions']
        );
    }

    protected function handleSubModules(array $moduleData, array $config): array
    {
        $permissions = [];

        foreach ($moduleData['sub_module'] ?? [] as $subModule) {
            $subModuleName = $subModule['name'];
            $subModuleConfig = $config['sub_modules'][$subModuleName] ?? [];

            if (empty($subModuleConfig)) {
                continue;
            }

            // Sub-module level permissions
            if (!empty($subModule['permissions'])) {
                $permissions = array_merge(
                    $permissions,
                    $this->handleSubModuleLevel($subModule, $subModuleConfig)
                );
            }

            // Action permissions
            $permissions = array_merge(
                $permissions,
                $this->handleActions($subModule, $subModuleConfig)
            );
        }

        return $permissions;
    }

    protected function handleSubModuleLevel(array $subModule, array $config): array
    {
        if (empty($config['permissions'])) {
            return array_values($subModule['permissions']);
        }

        return $this->filterPermissions(
            $subModule['permissions'],
            $config['permissions']
        );
    }

    protected function handleActions(array $subModule, array $config): array
    {
        $permissions = [];

        foreach ($subModule['actions'] ?? [] as $action) {
            $actionName = $action['name'];
            $actionConfig = $config['actions'][$actionName] ?? [];

            if (empty($actionConfig)) {
                continue;
            }

            if (!empty($action['permissions'])) {
                $permissions = array_merge(
                    $permissions,
                    $this->filterPermissions(
                        $action['permissions'],
                        $actionConfig
                    )
                );
            }
        }

        return $permissions;
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
}
