<?php

namespace Ace\Exceptions;

use RuntimeException;

/**
 * Thrown when a model record is not found in the database.
 */
class ModelNotFoundException extends RuntimeException
{
    protected string $model;
    protected mixed $id;

    public function __construct(string $model, mixed $id = null)
    {
        $this->model = $model;
        $this->id = $id;

        $basename = class_basename($model);
        $message = $id !== null
            ? "No query results for model [{$basename}] with ID [{$id}]."
            : "No query results for model [{$basename}].";

        parent::__construct($message, 404);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getModelId(): mixed
    {
        return $this->id;
    }
}

/**
 * Helper to get class basename without namespace.
 */
function class_basename(string $class): string
{
    return basename(str_replace('\\', '/', $class));
}
