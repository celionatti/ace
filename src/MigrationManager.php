<?php

namespace Ace;

class MigrationManager
{
    private \PDO $pdo;
    private string $migrationsDir;

    public function __construct(\PDO $pdo, string $migrationsDir)
    {
        $this->pdo = $pdo;
        $this->migrationsDir = $migrationsDir;
        
        if (!is_dir($this->migrationsDir)) {
            mkdir($this->migrationsDir, 0755, true);
        }
    }

    /**
     * Log messages only if running in CLI mode
     */
    private function log(string $message): void
    {
        if (php_sapi_name() === 'cli') {
            echo $message;
        }
    }

    /**
     * Create the log tracking migrations table if it doesn't exist.
     */
    private function createMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    /**
     * Get list of applied migrations
     */
    private function getAppliedMigrations(): array
    {
        $this->createMigrationsTable();
        $stmt = $this->pdo->query("SELECT `migration` FROM `migrations`");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Scan migrations directory and return sorted list of migration files
     */
    private function getMigrationFiles(): array
    {
        $files = scandir($this->migrationsDir);
        $migrationFiles = [];
        foreach ($files as $file) {
            if (preg_match('/^m.*\.php$/', $file)) {
                $migrationFiles[] = $file;
            }
        }
        sort($migrationFiles);
        return $migrationFiles;
    }

    /**
     * Apply all pending migrations
     */
    public function applyMigrations(): void
    {
        $appliedMigrations = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles();
        
        $pending = [];
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (!in_array($name, $appliedMigrations)) {
                $pending[] = $file;
            }
        }

        if (empty($pending)) {
            $this->log("✅ Nothing to migrate. Database is up to date.\n");
            return;
        }

        // Get max batch number to increment
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM migrations");
        $maxBatch = (int)$stmt->fetchColumn();
        $batch = $maxBatch + 1;

        $this->log("🚀 Found " . count($pending) . " pending migration(s). Running batch $batch...\n\n");

        foreach ($pending as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            require_once $this->migrationsDir . '/' . $file;
            
            $this->log("  - Migrating: $name...\n");
            
            try {
                $instance = new $name($this->pdo);
                $instance->up();
                
                $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)");
                $stmt->execute(['migration' => $name, 'batch' => $batch]);
                
                $this->log("    ✅ Done.\n");
            } catch (\Throwable $e) {
                $this->log("    ❌ Error: " . $e->getMessage() . "\n");
                $this->log("🔥 Migration batch aborted.\n");
                return;
            }
        }
        
        $this->log("\n🎉 All migrations completed successfully.\n");
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback(): void
    {
        $this->createMigrationsTable();
        
        // Find last batch number
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM migrations");
        $lastBatch = $stmt->fetchColumn();
        
        if (!$lastBatch) {
            $this->log("✅ Nothing to rollback. No migrations found.\n");
            return;
        }

        // Get migrations in last batch, ordered by ID descending
        $stmt = $this->pdo->prepare("SELECT * FROM migrations WHERE batch = :batch ORDER BY id DESC");
        $stmt->execute(['batch' => $lastBatch]);
        $migrations = $stmt->fetchAll();

        $this->log("⏪ Rolling back batch $lastBatch (" . count($migrations) . " migrations)...\n\n");

        foreach ($migrations as $m) {
            $name = $m['migration'];
            $file = $name . '.php';
            $filePath = $this->migrationsDir . '/' . $file;
            
            if (!file_exists($filePath)) {
                $this->log("    ❌ Error: Migration file '$file' not found at '$filePath'.\n");
                return;
            }
            
            require_once $filePath;
            $this->log("  - Rolling back: $name...\n");
            
            try {
                $instance = new $name($this->pdo);
                $instance->down();
                
                $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE id = :id");
                $stmt->execute(['id' => $m['id']]);
                
                $this->log("    ✅ Done.\n");
            } catch (\Throwable $e) {
                $this->log("    ❌ Error: " . $e->getMessage() . "\n");
                $this->log("🔥 Rollback aborted.\n");
                return;
            }
        }
        
        $this->log("\n🎉 Rollback completed successfully.\n");
    }

    /**
     * Display status of all migrations
     */
    public function status(): void
    {
        $this->createMigrationsTable();
        
        $files = $this->getMigrationFiles();
        
        // Get applied migrations mapping to batch and created_at
        $stmt = $this->pdo->query("SELECT migration, batch, created_at FROM migrations");
        $appliedRows = $stmt->fetchAll();
        
        $appliedMap = [];
        foreach ($appliedRows as $row) {
            $appliedMap[$row['migration']] = [
                'batch' => $row['batch'],
                'created_at' => $row['created_at']
            ];
        }

        $this->log("\n");
        $this->log(str_pad("Migration Name", 60) . " | " . str_pad("Status", 10) . " | " . str_pad("Batch", 6) . " | Applied At\n");
        $this->log(str_repeat("-", 100) . "\n");

        if (empty($files)) {
            $this->log("No migrations found in migrations/ folder.\n");
            return;
        }

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (isset($appliedMap[$name])) {
                $status = "Applied";
                $batch = $appliedMap[$name]['batch'];
                $appliedAt = $appliedMap[$name]['created_at'];
            } else {
                $status = "Pending";
                $batch = "-";
                $appliedAt = "-";
            }
            
            $this->log(str_pad($name, 60) . " | " . str_pad($status, 10) . " | " . str_pad($batch, 6) . " | $appliedAt\n");
        }
        $this->log("\n");
    }

    /**
     * Generate a new migration boilerplate file
     */
    public function makeMigration(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9_]/', '', str_replace('-', '_', $name));
        
        $timestamp = date('Y_m_d_His');
        $filename = "m{$timestamp}_{$name}";
        $filePath = $this->migrationsDir . "/{$filename}.php";
        
        $template = "<?php

use Ace\Migration;

class {$filename} extends Migration
{
    public function up(): void
    {
        // \$this->pdo->exec(\"CREATE TABLE ...\");
    }

    public function down(): void
    {
        // \$this->pdo->exec(\"DROP TABLE ...\");
    }
}
";

        file_put_contents($filePath, $template);
        return $filePath;
    }
}

