<?php

use Ace\Migration;

class m2026_06_15_000001_create_users_and_transactions_tables extends Migration
{
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `transactions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NULL,
                `reference` VARCHAR(255) NOT NULL UNIQUE,
                `amount` DECIMAL(10, 2) NOT NULL,
                `status` VARCHAR(50) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS `transactions`");
        $this->pdo->exec("DROP TABLE IF EXISTS `users`");
    }
}

