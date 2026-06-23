<?php

use Ace\Migration;

class m2026_06_15_000003_create_auth_extensions_tables extends Migration
{
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `password_resets` (
                `email` VARCHAR(255) NOT NULL,
                `token` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`email`, `token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `remember_tokens` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `token_hash` VARCHAR(255) NOT NULL UNIQUE,
                `expires_at` TIMESTAMP NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS `remember_tokens`");
        $this->pdo->exec("DROP TABLE IF EXISTS `password_resets`");
    }
}

