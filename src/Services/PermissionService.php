<?php

use Models\User;
use Classes\Storage\JsonStorage;
class PermissionService {
    private $storage;
    private $defaultRoles = [
        'admin' => [
            'description' => 'Full system access',
            'permissions' => ['*']  // Wildcard for all permissions
        ],
        'user' => [
            'description' => 'Standard user access',
            'permissions' => [
                'profile:read',
                'profile:update',
                'cars:read',
                'cars:create',
                'cars:update',
                'cars:delete'
            ]
        ],
        'guest' => [
            'description' => 'Limited access for non-registered users',
            'permissions' => [
                'cars:read'
            ]
        ]
    ];

    public function __construct() {
        $this->storage = new JsonStorage('roles.json');
        $this->initializeRoles();
    }

    private function initializeRoles() {
        foreach ($this->defaultRoles as $role => $config) {
            if (!$this->storage->findByField('name', $role)) {
                $this->storage->insert([
                    'name' => $role,
                    'description' => $config['description'],
                    'permissions' => $config['permissions'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'is_system' => true
                ]);
                error_log("[Permission] Initialized role: $role");
            }
        }
    }

    public function hasPermission($userId, $permission) {
        $user = (new User())->findById($userId);
        if (!$user) {
            error_log("[Permission] User not found: $userId");
            return false;
        }

        $role = $user['role'] ?? 'guest';
        $roleData = $this->storage->findByField('name', $role);
        
        if (!$roleData) {
            error_log("[Permission] Role not found: $role");
            return false;
        }

        // Admin has all permissions
        if (in_array('*', $roleData['permissions'])) {
            return true;
        }

        $hasPermission = in_array($permission, $roleData['permissions']);
        error_log("[Permission] Checking permission '$permission' for user $userId ($role): " . 
                 ($hasPermission ? 'granted' : 'denied'));
        
        return $hasPermission;
    }

    public function assignRole($userId, $role) {
        $roleData = $this->storage->findByField('name', $role);
        if (!$roleData) {
            error_log("[Permission] Role not found: $role");
            return false;
        }

        $user = new User();
        $updateResult = $user->update($userId, ['role' => $role]);
        
        if ($updateResult) {
            error_log("[Permission] Role '$role' assigned to user: $userId");
            return true;
        }

        error_log("[Permission] Failed to assign role '$role' to user: $userId");
        return false;
    }

    public function getRolePermissions($role) {
        $roleData = $this->storage->findByField('name', $role);
        if (!$roleData) {
            error_log("[Permission] Role not found: $role");
            return [];
        }
        return $roleData['permissions'];
    }

    public function createRole($name, $description, $permissions) {
        if ($this->storage->findByField('name', $name)) {
            error_log("[Permission] Role already exists: $name");
            return false;
        }

        $role = [
            'name' => $name,
            'description' => $description,
            'permissions' => $permissions,
            'created_at' => date('Y-m-d H:i:s'),
            'is_system' => false
        ];

        $result = $this->storage->insert($role);
        if ($result) {
            error_log("[Permission] Created new role: $name");
            return true;
        }

        error_log("[Permission] Failed to create role: $name");
        return false;
    }

    public function updateRole($name, $permissions) {
        $role = $this->storage->findByField('name', $name);
        if (!$role) {
            error_log("[Permission] Role not found: $name");
            return false;
        }

        // Don't allow modifying system roles
        if (isset($role['is_system']) && $role['is_system']) {
            error_log("[Permission] Cannot modify system role: $name");
            return false;
        }

        $result = $this->storage->update($role['id'], ['permissions' => $permissions]);
        if ($result) {
            error_log("[Permission] Updated permissions for role: $name");
            return true;
        }

        error_log("[Permission] Failed to update role: $name");
        return false;
    }

    public function getAllRoles() {
        return $this->storage->findByField('name', null, function($record) {
            return isset($record['name']);
        }) ?: [];
    }

    public function getUserRole($userId) {
        $user = (new User())->findById($userId);
        return $user['role'] ?? 'guest';
    }
} 