<?php

declare(strict_types=1);

/**
 * ==================================================
 * ==================               =================
 * Validator Class
 * ==================               =================
 * ==================================================
 */

namespace Ace\ace\Validation;

use Ace\ace\Database\Database;
use Ace\ace\Exception\AceException;

class Validator
{
    protected $data;
    protected $rules;
    protected $errors = [];
    protected static $customValidators = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function passes()
    {
        foreach ($this->rules as $field => $rules) {
            $rules = explode('|', $rules);
            foreach ($rules as $rule) {
                $parameters = explode(':', $rule);
                $method = 'validate' . ucfirst($parameters[0]);
                if (method_exists($this, $method)) {
                    $this->$method($field, $parameters[1] ?? null);
                } elseif (isset(self::$customValidators[$parameters[0]])) {
                    call_user_func(self::$customValidators[$parameters[0]], $this, $field, $parameters[1] ?? null);
                } else {
                    throw new AceException("Validation rule {$parameters[0]} does not exist.");
                }
            }
        }
        return empty($this->errors);
    }

    public function fails()
    {
        return !$this->passes();
    }

    public function errors()
    {
        return $this->errors;
    }

    protected function validateRequired($field)
    {

        if (!isset($this->data[$field]) || empty($this->data[$field])) {
            $this->errors[$field] = $this->formatFieldName($field) . " is required.";
        }
    }

    protected function validateEmail($field)
    {
        if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be a valid email address.";
        }
    }

    protected function validateString($field)
    {
        if (!is_string($this->data[$field])) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be a string.";
        }
    }

    protected function validateMin($field, $value)
    {
        if (!isset($this->data[$field]) || is_null($this->data[$field]) || strlen($this->data[$field]) < $value) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be at least {$value} characters.";
        }
    }

    protected function validateMax($field, $value)
    {
        if (!isset($this->data[$field]) || is_null($this->data[$field]) || strlen($this->data[$field]) > $value) {
            $this->errors[$field] = $this->formatFieldName($field) . " must not exceed {$value} characters.";
        }
    }

    protected function validateNumeric($field)
    {
        if (!is_numeric($this->data[$field])) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be a number.";
        }
    }

    protected function validateBoolean($field)
    {
        if (!is_bool($this->data[$field])) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be true or false.";
        }
    }

    protected function validateArray($field)
    {
        if (!is_array($this->data[$field])) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be an array.";
        }
    }

    protected function validateDatetime($field)
    {
        if (!strtotime($this->data[$field])) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be a valid datetime.";
        }
    }

    protected function validateConfirmed($field)
    {
        $confirmationField = "{$field}_confirm";

        if (!isset($this->data[$confirmationField]) || $this->data[$field] !== $this->data[$confirmationField]) {
            $this->errors[$field] = $this->formatFieldName($field) . " confirm does not match.";
        }
    }

    protected function validateUnique($field, $tableColumnCondition)
    {
        // Split table, column, and additional condition if provided
        $parts = explode(',', $tableColumnCondition);
        list($table, $column) = explode('.', $parts[0]);
        $value = $this->data[$field];

        // Build the base query
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value";
        $params = ['value' => $value];

        // Handle additional conditions (e.g., 'id != 1')
        if (isset($parts[1])) {
            // Parse additional condition(s), e.g., "id != 1"
            $additionalCondition = trim($parts[1]);

            // Assuming the format 'field != value' or 'field = value'
            if (preg_match('/(\w+)\s*(=|!=|<|>|<=|>=)\s*(.+)/', $additionalCondition, $matches)) {
                $conditionField = $matches[1];
                $conditionOperator = $matches[2];
                $conditionValue = $matches[3];

                // Add the additional condition to the query
                $query .= " AND {$conditionField} {$conditionOperator} :{$conditionField}";

                // Determine if the value is numeric or a string, then bind it correctly
                if (is_numeric($conditionValue)) {
                    $params[$conditionField] = $conditionValue;
                } else {
                    // Remove quotes around strings like 'John' to bind properly
                    $conditionValue = trim($conditionValue, "'");
                    $params[$conditionField] = $conditionValue;
                }
            } else {
                throw new AceException("Invalid condition format in unique validation.");
            }
        }

        // Execute the query with the prepared statement
        $stmt = Database::getInstance()->getConnection()->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);

        // Check if any records match
        if ($result->count > 0) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be unique.";
        }
    }

    protected function validateIn($field, $values)
    {
        $values = explode(',', $values);
        if (!in_array($this->data[$field], $values)) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be one of " . implode(', ', $values) . ".";
        }
    }

    public static function addCustomValidator($name, callable $callback)
    {
        self::$customValidators[$name] = $callback;
    }

    protected function formatFieldName(string $field): string
    {
        return ucwords(str_replace('_', ' ', $field));
    }
}
