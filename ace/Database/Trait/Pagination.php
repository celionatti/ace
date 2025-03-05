<?php

declare(strict_types=1);

namespace Ace\ace\Database\Trait;

use Ace\ace\Database\Database;
use Ace\ace\Database\QueryBuilder\QueryBuilder;

trait Pagination
{
    /**
     * Paginate all records
     *
     * @param int $perPage Number of records per page
     * @param int $page Current page number
     * @return array Pagination result with data and metadata
     */
    // public static function paginate(int $perPage = 15, int $page = 1): array
    // {
    //     // Get the database connection
    //     $db = Database::getInstance();
    //     if (!$db) {
    //         return [
    //             'data' => [],
    //             'total' => 0,
    //             'per_page' => $perPage,
    //             'current_page' => $page,
    //             'last_page' => 0
    //         ];
    //     }

    //     // Create a query builder
    //     $builder = new QueryBuilder($db);

    //     // Get the called class
    //     $className = static::class;

    //     // Create a new instance
    //     $model = new $className();

    //     // Calculate offset
    //     $offset = ($page - 1) * $perPage;

    //     // Get total count
    //     $totalQuery = clone $builder;
    //     $totalCount = $totalQuery->table($model->table)
    //         ->select('COUNT(*) as total')
    //         ->first()['total'] ?? 0;

    //     // Calculate last page
    //     $lastPage = ceil($totalCount / $perPage);

    //     // Get paginated results
    //     $results = $builder->table($model->table)
    //         ->limit($perPage)
    //         ->offset($offset)
    //         ->get();

    //     // Convert results to model instances
    //     $data = array_map(function($result) use ($className) {
    //         $instance = new $className($result);
    //         $instance->exists = true;
    //         $instance->original = $result;
    //         return $instance;
    //     }, $results ?: []);

    //     return [
    //         'data' => $data,
    //         'total' => $totalCount,
    //         'per_page' => $perPage,
    //         'current_page' => $page,
    //         'last_page' => $lastPage
    //     ];
    // }

    /**
     * Paginate query results with advanced options
     *
     * @param array $options Pagination options
     * @return array Pagination result with data and metadata
     */
    public static function paginate(array $options = []): array
    {
        // Merge default options with provided options
        $defaultOptions = [
            'per_page' => 15,
            'page' => 1,
            'columns' => ['*'],
            'order_by' => null,
            'conditions' => [],
            'relations' => [],
            'search' => null
        ];
        $options = array_merge($defaultOptions, $options);

        // Validate and sanitize inputs
        $perPage = max(1, min(100, (int)$options['per_page'])); // Limit between 1-100
        $page = max(1, (int)$options['page']);

        // Get the database connection
        $db = Database::getInstance();
        if (!$db) {
            return self::emptyPaginationResult($perPage, $page);
        }

        // Create a query builder
        $builder = new QueryBuilder($db);

        // Get the called class
        $className = static::class;

        // Create a new instance
        /** @var Model $model */
        $model = new $className();

        // Start building the query
        $query = $builder->table($model->table)
            ->select($options['columns']);

        // Apply conditions
        if (!empty($options['conditions'])) {
            foreach ($options['conditions'] as $column => $value) {
                $query->where($column, $value);
            }
        }

        // Apply search if provided
        if ($options['search'] && method_exists($model, 'applySearch')) {
            $model->applySearch($query, $options['search']);
        }

        // Apply ordering
        if ($options['order_by']) {
            foreach ($options['order_by'] as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        try {
            // Count total results (before pagination)
            $countQuery = clone $query;
            $totalCount = $countQuery
                ->select('COUNT(*) as total')
                ->first()['total'] ?? 0;

            // Calculate last page
            $lastPage = max(1, ceil($totalCount / $perPage));

            // Ensure page is within valid range
            $page = min($page, $lastPage);

            // Get paginated results
            $results = $query
                ->limit($perPage)
                ->offset($offset)
                ->get();

            // Convert results to model instances
            $data = array_map(function($result) use ($className, $options) {
                /** @var Model $instance */
                $instance = new $className($result);
                $instance->exists = true;
                $instance->original = $result;

                // Load relations if specified
                if (!empty($options['relations'])) {
                    foreach ($options['relations'] as $relation) {
                        if (method_exists($instance, $relation)) {
                            $instance->$relation();
                        }
                    }
                }

                return $instance;
            }, $results ?: []);

            return [
                'data' => $data,
                'meta' => [
                    'total' => $totalCount,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'from' => ($page - 1) * $perPage + 1,
                    'to' => min($page * $perPage, $totalCount)
                ]
            ];
        } catch (\Exception $e) {
            // Log the error
            if (class_exists('\Ace\ace\Logger\Logger')) {
                \Ace\ace\Logger\Logger::exception($e);
            }

            return self::emptyPaginationResult($perPage, $page);
        }
    }

    /**
     * Create an empty pagination result
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return array Empty pagination result
     */
    private static function emptyPaginationResult(int $perPage, int $page): array
    {
        return [
            'data' => [],
            'meta' => [
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 0,
                'from' => 0,
                'to' => 0
            ]
        ];
    }

    /**
     * Paginate query results with flexible conditions
     *
     * @param callable $queryCallback Callback to modify the query builder
     * @param int $perPage Number of records per page
     * @param int $page Current page number
     * @return array Pagination result with data and metadata
     */
    public static function paginateQuery(callable $queryCallback, int $perPage = 15, int $page = 1): array
    {
        // Get the database connection
        $db = Database::getInstance();
        if (!$db) {
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 0
            ];
        }

        // Create a query builder
        $builder = new QueryBuilder($db);

        // Get the called class
        $className = static::class;

        // Create a new instance
        $model = new $className();

        // Allow the callback to modify the query
        $queryCallback($builder->table($model->table));

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Create a separate query for total count
        $totalQuery = clone $builder;
        $totalCount = $totalQuery
            ->select('COUNT(*) as total')
            ->first()['total'] ?? 0;

        // Calculate last page
        $lastPage = ceil($totalCount / $perPage);

        // Apply pagination to the original query
        $builder->limit($perPage)->offset($offset);

        // Get paginated results
        $results = $builder->get();

        // Convert results to model instances
        $data = array_map(function($result) use ($className) {
            $instance = new $className($result);
            $instance->exists = true;
            $instance->original = $result;
            return $instance;
        }, $results ?: []);

        return [
            'data' => $data,
            'total' => $totalCount,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage
        ];
    }
}