<?php

use Ace\Migration;

class m2026_06_15_000002_create_rbac_tables extends Migration
{
    public function up(): void
    {
        // 1. Create tables
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `roles` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` VARCHAR(255) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `permissions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` VARCHAR(255) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `role_permissions` (
                `role_id` INT NOT NULL,
                `permission_id` INT NOT NULL,
                PRIMARY KEY (`role_id`, `permission_id`),
                FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_roles` (
                `user_id` INT NOT NULL,
                `role_id` INT NOT NULL,
                PRIMARY KEY (`user_id`, `role_id`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Seed default roles
        $defaultRoles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Full system access'],
            ['name' => 'Editor',        'slug' => 'editor', 'description' => 'Can manage content'],
            ['name' => 'User',          'slug' => 'user', 'description' => 'Standard registered user'],
        ];

        $stmt = $this->pdo->prepare("INSERT IGNORE INTO `roles` (`name`, `slug`, `description`) VALUES (:name, :slug, :description)");
        foreach ($defaultRoles as $role) {
            $stmt->execute($role);
        }

        // 3. Seed default permissions
        $defaultPermissions = [
            ['name' => 'View Dashboard',   'slug' => 'view-dashboard',   'description' => 'Access the admin dashboard'],
            ['name' => 'Create Posts',     'slug' => 'create-posts',     'description' => 'Create new blog posts'],
            ['name' => 'Edit Posts',       'slug' => 'edit-posts',       'description' => 'Edit existing blog posts'],
            ['name' => 'Delete Posts',     'slug' => 'delete-posts',     'description' => 'Delete blog posts'],
            ['name' => 'Manage Users',     'slug' => 'manage-users',     'description' => 'View and manage user accounts'],
            ['name' => 'Manage Roles',     'slug' => 'manage-roles',     'description' => 'Assign and manage roles'],
            ['name' => 'Manage Comments',  'slug' => 'manage-comments',  'description' => 'Moderate user comments'],
        ];

        $stmt = $this->pdo->prepare("INSERT IGNORE INTO `permissions` (`name`, `slug`, `description`) VALUES (:name, :slug, :description)");
        foreach ($defaultPermissions as $perm) {
            $stmt->execute($perm);
        }

        // 4. Assign permissions to 'admin'
        $adminRole = $this->pdo->query("SELECT id FROM roles WHERE slug = 'admin'")->fetch();
        $allPerms = $this->pdo->query("SELECT id FROM permissions")->fetchAll();
        if ($adminRole) {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (:role_id, :perm_id)");
            foreach ($allPerms as $perm) {
                $stmt->execute(['role_id' => $adminRole['id'], 'perm_id' => $perm['id']]);
            }
        }

        // 5. Assign permissions to 'editor'
        $editorRole = $this->pdo->query("SELECT id FROM roles WHERE slug = 'editor'")->fetch();
        if ($editorRole) {
            $editorPerms = $this->pdo->query("SELECT id FROM permissions WHERE slug IN ('view-dashboard','create-posts','edit-posts','manage-comments')")->fetchAll();
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (:role_id, :perm_id)");
            foreach ($editorPerms as $perm) {
                $stmt->execute(['role_id' => $editorRole['id'], 'perm_id' => $perm['id']]);
            }
        }

        // 6. Assign roles to existing users
        $adminUser = $this->pdo->query("SELECT id FROM users WHERE email = 'admin@example.com'")->fetch();
        if ($adminUser && $adminRole) {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (:user_id, :role_id)");
            $stmt->execute(['user_id' => $adminUser['id'], 'role_id' => $adminRole['id']]);
        }

        $userRole = $this->pdo->query("SELECT id FROM roles WHERE slug = 'user'")->fetch();
        if ($userRole) {
            $usersWithoutRole = $this->pdo->query("SELECT id FROM users WHERE id NOT IN (SELECT user_id FROM user_roles)")->fetchAll();
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (:user_id, :role_id)");
            foreach ($usersWithoutRole as $u) {
                $stmt->execute(['user_id' => $u['id'], 'role_id' => $userRole['id']]);
            }
        }
    }

    public function down(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS `user_roles`");
        $this->pdo->exec("DROP TABLE IF EXISTS `role_permissions`");
        $this->pdo->exec("DROP TABLE IF EXISTS `permissions`");
        $this->pdo->exec("DROP TABLE IF EXISTS `roles`");
    }
}

