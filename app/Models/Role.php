<?php

namespace App\Models;

use Ace\Model;
use Ace\Application;

class Role extends Model
{
    public static function tableName(): string
    {
        return 'roles';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'slug' => 'required|unique:roles,slug',
        ];
    }

    /**
     * Get all permissions belonging to this role
     *
     * @return Permission[]
     */
    public function permissions(): array
    {
        $db = Application::$app->db;
        if (!$db) return [];

        $stmt = $db->prepare("
            SELECT p.* FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id
        ");
        $stmt->execute(['role_id' => $this->id]);
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
     * Check if this role has a specific permission
     */
    public function hasPermission(string $slug): bool
    {
        $db = Application::$app->db;
        if (!$db) return false;

        $stmt = $db->prepare("
            SELECT COUNT(*) FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id AND p.slug = :slug
        ");
        $stmt->execute(['role_id' => $this->id, 'slug' => $slug]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Attach a permission to this role by slug
     */
    public function givePermission(string $slug): bool
    {
        $permission = Permission::findOne(['slug' => $slug]);
        if (!$permission) return false;

        $db = Application::$app->db;
        $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :perm_id)");
        return $stmt->execute(['role_id' => $this->id, 'perm_id' => $permission->id]);
    }

    /**
     * Revoke a permission from this role by slug
     */
    public function revokePermission(string $slug): bool
    {
        $permission = Permission::findOne(['slug' => $slug]);
        if (!$permission) return false;

        $db = Application::$app->db;
        $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = :role_id AND permission_id = :perm_id");
        return $stmt->execute(['role_id' => $this->id, 'perm_id' => $permission->id]);
    }
}

