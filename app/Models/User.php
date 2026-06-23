<?php

namespace App\Models;

use Ace\Model;
use Ace\Application;

class User extends Model
{
    public static function tableName(): string
    {
        return 'users';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'passwordConfirm' => 'required|match:password'
        ];
    }

    /**
     * Override save to hash user password before database insertion
     */
    public function save(): bool
    {
        if (isset($this->attributes['password'])) {
            // Only hash if not already hashed
            if (!str_starts_with($this->attributes['password'], '$2y$')) {
                $this->attributes['password'] = password_hash($this->attributes['password'], PASSWORD_BCRYPT);
            }
        }
        
        unset($this->attributes['passwordConfirm']);

        return parent::save();
    }

    // -------------------------------------------------------
    //  RBAC: Roles
    // -------------------------------------------------------

    /**
     * Get all roles assigned to this user
     *
     * @return Role[]
     */
    public function roles(): array
    {
        $db = Application::$app->db;
        if (!$db) return [];

        $stmt = $db->prepare("
            SELECT r.* FROM roles r
            INNER JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $this->id]);
        $records = $stmt->fetchAll();

        $models = [];
        foreach ($records as $record) {
            $model = new Role();
            $model->loadData($record);
            $models[] = $model;
        }
        return $models;
    }

    /**
     * Check if the user has a specific role by slug
     *
     * Usage: $user->hasRole('admin')
     */
    public function hasRole(string $slug): bool
    {
        $db = Application::$app->db;
        if (!$db) return false;

        $stmt = $db->prepare("
            SELECT COUNT(*) FROM roles r
            INNER JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = :user_id AND r.slug = :slug
        ");
        $stmt->execute(['user_id' => $this->id, 'slug' => $slug]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Check if the user has ANY of the given roles
     *
     * Usage: $user->hasAnyRole('admin', 'editor')
     */
    public function hasAnyRole(string ...$slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->hasRole($slug)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Assign a role to the user by slug
     *
     * Usage: $user->assignRole('editor')
     */
    public function assignRole(string $slug): bool
    {
        $role = Role::findOne(['slug' => $slug]);
        if (!$role) return false;

        $db = Application::$app->db;
        $stmt = $db->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
        return $stmt->execute(['user_id' => $this->id, 'role_id' => $role->id]);
    }

    /**
     * Remove a role from the user by slug
     *
     * Usage: $user->removeRole('editor')
     */
    public function removeRole(string $slug): bool
    {
        $role = Role::findOne(['slug' => $slug]);
        if (!$role) return false;

        $db = Application::$app->db;
        $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id AND role_id = :role_id");
        return $stmt->execute(['user_id' => $this->id, 'role_id' => $role->id]);
    }

    /**
     * Sync user roles — replaces all existing roles with the given set
     *
     * Usage: $user->syncRoles('admin', 'editor')
     */
    public function syncRoles(string ...$slugs): void
    {
        $db = Application::$app->db;
        // Remove all existing roles
        $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id")->execute(['user_id' => $this->id]);
        // Assign each new role
        foreach ($slugs as $slug) {
            $this->assignRole($slug);
        }
    }

    // -------------------------------------------------------
    //  RBAC: Permissions (derived through roles)
    // -------------------------------------------------------

    /**
     * Get all permissions for this user (aggregated from all roles)
     *
     * @return Permission[]
     */
    public function permissions(): array
    {
        $db = Application::$app->db;
        if (!$db) return [];

        $stmt = $db->prepare("
            SELECT DISTINCT p.* FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            INNER JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $this->id]);
        $records = $stmt->fetchAll();

        $models = [];
        foreach ($records as $record) {
            $model = new Permission();
            $model->loadData($record);
            $models[] = $model;
        }
        return $models;
    }

    /**
     * Check if user has a specific permission (through any of their roles)
     *
     * Usage: $user->hasPermission('edit-posts')
     */
    public function hasPermission(string $slug): bool
    {
        $db = Application::$app->db;
        if (!$db) return false;

        $stmt = $db->prepare("
            SELECT COUNT(*) FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            INNER JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = :user_id AND p.slug = :slug
        ");
        $stmt->execute(['user_id' => $this->id, 'slug' => $slug]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Check if user has ANY of the given permissions
     *
     * Usage: $user->hasAnyPermission('edit-posts', 'delete-posts')
     */
    public function hasAnyPermission(string ...$slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has ALL of the given permissions
     *
     * Usage: $user->hasAllPermissions('edit-posts', 'delete-posts')
     */
    public function hasAllPermissions(string ...$slugs): bool
    {
        foreach ($slugs as $slug) {
            if (!$this->hasPermission($slug)) {
                return false;
            }
        }
        return true;
    }

    // -------------------------------------------------------
    //  Convenience
    // -------------------------------------------------------

    /**
     * Check if user is an admin (via RBAC role)
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}

