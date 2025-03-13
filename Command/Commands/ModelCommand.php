<?php

declare(strict_types=1);

namespace Ace\ace\Command\Commands;

use Ace\ace\Command\Command;
use Ace\ace\Command\TermUI;

class ModelCommand extends Command
{
    // Templates directory
    protected $templatesDir;

    // Base directories
    protected $modelsDir;
    protected $migrationsDir;

    public function __construct()
    {
        $this->name = 'make:model';
        $this->description = 'Create a new model from template';

        // Set up directories - adjust these paths as needed
        $this->templatesDir = dirname(__DIR__) . '/templates';
        $this->modelsDir = BASE_PATH . '/app/models';
        $this->migrationsDir = BASE_PATH . '/database/migrations';

        // Command configuration
        $this->addArgument('name', 'The name of the model to create', true);
        $this->addOption('migration', 'm', 'Create a migration file for the model', false);
        $this->addOption('fields', 'f', 'Database fields in format name:type,name:type', true);
        $this->addOption('table', 't', 'Table name (defaults to plural of model name)', true);
    }

    public function handle($arguments, $options)
    {
        // Get model name and ensure it's properly formatted
        $modelName = $arguments['name'];
        $modelName = $this->formatClassName($modelName);

        // Create directories if they don't exist
        $this->ensureDirectoryExists($this->modelsDir);

        // Check if model already exists
        $modelPath = $this->modelsDir . '/' . $modelName . '.php';
        if (file_exists($modelPath)) {
            TermUI::error("Model {$modelName} already exists at {$modelPath}");
            return 1;
        }

        // Parse fields if provided
        $fields = [];
        if (isset($options['fields']) && !empty($options['fields'])) {
            $fields = $this->parseFields($options['fields']);
        } else {
            // Interactive field creation
            $fields = $this->promptForFields();
        }

        // Determine table name
        $tableName = isset($options['table']) && !empty($options['table'])
            ? $options['table']
            : $this->pluralize(strtolower($modelName));

        // Create the model file
        $this->createModelFile($modelName, $tableName, $fields);

        // Create migration if requested
        if (isset($options['migration']) && $options['migration']) {
            $this->createMigrationFile($modelName, $tableName, $fields);
        }

        TermUI::success("Model {$modelName} created successfully!");
        return 0;
    }

    /**
     * Format the class name (CamelCase)
     */
    protected function formatClassName($name)
    {
        // Remove any non-alphanumeric characters
        $name = preg_replace('/[^a-zA-Z0-9]/', ' ', $name);
        // Convert to CamelCase
        $name = str_replace(' ', '', ucwords($name));
        return $name;
    }

    /**
     * Ensure a directory exists
     */
    protected function ensureDirectoryExists($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Parse fields from string format name:type,name:type
     */
    protected function parseFields($fieldsString)
    {
        $fields = [];
        $pairs = explode(',', $fieldsString);

        foreach ($pairs as $pair) {
            $parts = explode(':', trim($pair));
            if (count($parts) >= 2) {
                $fields[$parts[0]] = $parts[1];
            }
        }

        return $fields;
    }

    /**
     * Interactive field creation
     */
    protected function promptForFields()
    {
        $fields = [];
        TermUI::info("Let's add fields to your model. Enter a blank field name to finish.");

        while (true) {
            $fieldName = TermUI::prompt("Field name (or leave blank to finish)");
            if (empty($fieldName)) {
                break;
            }

            $fieldType = TermUI::select("Field type", [
                'string' => 'String (VARCHAR)',
                'integer' => 'Integer',
                'text' => 'Text (LONGTEXT)',
                'boolean' => 'Boolean',
                'date' => 'Date',
                'datetime' => 'DateTime',
                'decimal' => 'Decimal'
            ]);

            $fields[$fieldName] = $fieldType;
        }

        return $fields;
    }

    /**
     * Create model file from template
     */
    protected function createModelFile($modelName, $tableName, $fields)
    {
        // Try to load template
        $templatePath = $this->templatesDir . '/model.php.template';

        if (!file_exists($templatePath)) {
            // If template doesn't exist, create a basic model template
            $template = $this->getDefaultModelTemplate();
        } else {
            $template = file_get_contents($templatePath);
        }

        // Generate fields definition
        $fieldsDefinition = '';
        foreach ($fields as $field => $type) {
            $fieldsDefinition .= "        '{$field}',\n";
        }

        // Replace placeholders
        $content = str_replace(
            ['{{ModelName}}', '{{TableName}}', '{{Fields}}'],
            [$modelName, $tableName, $fieldsDefinition],
            $template
        );

        // Write model file
        $modelPath = $this->modelsDir . '/' . $modelName . '.php';
        file_put_contents($modelPath, $content);

        TermUI::info("Created model file: {$modelPath}");
    }

    /**
     * Create migration file
     */
    protected function createMigrationFile($modelName, $tableName, $fields)
    {
        $this->ensureDirectoryExists($this->migrationsDir);

        // Try to load template
        $templatePath = $this->templatesDir . '/migration.php.template';

        if (!file_exists($templatePath)) {
            // If template doesn't exist, create a basic migration template
            $template = $this->getDefaultMigrationTemplate();
        } else {
            $template = file_get_contents($templatePath);
        }

        // Generate migration filename
        $timestamp = date('Y_m_d_His');
        $migrationName = "Create{$modelName}Table";
        $migrationFileName = "{$timestamp}_{$migrationName}.php";

        // Generate fields for migration
        $fieldsDefinition = '';
        foreach ($fields as $field => $type) {
            $fieldsDefinition .= "            \$table->{$type}('{$field}');\n";
        }

        // Replace placeholders
        $content = str_replace(
            ['{{MigrationName}}', '{{TableName}}', '{{Fields}}'],
            [$migrationName, $tableName, $fieldsDefinition],
            $template
        );

        // Write migration file
        $migrationPath = $this->migrationsDir . '/' . $migrationFileName;
        file_put_contents($migrationPath, $content);

        TermUI::info("Created migration file: {$migrationPath}");
    }

    /**
     * Return a default model template if the template file doesn't exist
     */
    protected function getDefaultModelTemplate()
    {
        return <<<'EOT'
<?php

declare(strict_types=1);

namespace Ace\app\models;

use Ace\ace\Database\Model\Model;
use Ace\ace\Database\Interface\ModelInterface;
use Ace\ace\Database\QueryBuilder\QueryBuilder;

class {{ModelName}} extends Model implements ModelInterface
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = '{{TableName}}';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [
{{Fields}}
    ];

    /**
     * @var array Hidden attributes
     */
    protected array $hidden = [
        'password'
    ];

    public function applySearch(QueryBuilder $query, string $searchTerm): void
    {
        $query->whereRaw(
            '(first_name LIKE :search OR email LIKE :search)',
            ['search' => "%$searchTerm%"]
        );
    }

    /**
     * Find a user by email
     *
     * @param string $email User email
     * @return static|null The found user or null
     */
    public static function findByEmail(string $email): ?self
    {
        $results = static::where(['email' => $email]);
        return $results ? $results[0] : null;
    }
}
EOT;
    }

    /**
     * Return a default migration template if the template file doesn't exist
     */
    protected function getDefaultMigrationTemplate()
    {
        return <<<'EOT'
<?php

declare(strict_types=1);

namespace Ace\database\migrations;

use Ace\ace\Database\Migration\Migration;
use Ace\ace\Database\Schema\Schema;

class {{MigrationName}} extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('{{TableName}}', function ($table) {
            $table->id();
{{Fields}}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('{{TableName}}');
    }
}
EOT;
    }

    /**
     * Simple pluralization function
     */
    protected function pluralize($word)
    {
        // List of singular words that end in "s" but require "es" to form the plural.
        $singularExceptions = ['bus', 'kiss', 'class', 'glass', 'quiz'];

        // If the word ends with 's' and is not in the exceptions,
        // assume it's already plural.
        if (strtolower(substr($word, -1)) === 's' && !in_array(strtolower($word), $singularExceptions)) {
            return $word;
        }

        // If the word ends with a consonant followed by 'y', change 'y' to 'ies'
        if (preg_match('/([^aeiou])y$/i', $word)) {
            return preg_replace('/y$/i', 'ies', $word);
        }

        // If the word ends with s, x, z, ch, or sh, or is one of our singular exceptions, append 'es'
        if (preg_match('/(s|x|z|ch|sh)$/i', $word) || in_array(strtolower($word), $singularExceptions)) {
            return $word . 'es';
        }

        // Default rule: append 's'
        return $word . 's';
    }
}