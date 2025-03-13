<?php

declare(strict_types=1);

namespace Ace\Command\Commands;

use Ace\Command\Command;
use Ace\Command\TermUI;

class ViewCommand extends Command
{
    // Templates directory
    protected $templatesDir;

    // Base directories
    protected $viewsDir;

    public function __construct()
    {
        $this->name = 'make:view';
        $this->description = 'Create a new view from template';

        // Set up directories - adjust these paths as needed
        $this->templatesDir = dirname(__DIR__) . '/templates';
        $this->viewsDir = BASE_PATH . '/resources/views';

        // Command configuration
        $this->addArgument('name', 'The name of the view to create (path/name)', true);
        $this->addOption('model', 'm', 'The model to use for creating a resource view set', true);
        $this->addOption('layout', 'l', 'The layout to extend (defaults to app)', true);
        $this->addOption('resource', 'r', 'Create a complete set of resource views (index, create, edit, show)', false);
    }

    public function handle($arguments, $options)
    {
        // Get view name
        $viewName = $arguments['name'];

        // Check if we're creating a resource view set
        $isResource = isset($options['resource']) && $options['resource'];

        if ($isResource) {
            // If creating resource views, model is required
            if (!isset($options['model']) || empty($options['model'])) {
                TermUI::error("Model name is required when creating resource views. Use --model option.");
                return 1;
            }

            $modelName = $this->formatClassName($options['model']);
            return $this->createResourceViews($modelName, $options);
        } else {
            // Create a single view
            return $this->createSingleView($viewName, $options);
        }
    }

    /**
     * Create a single view file
     */
    protected function createSingleView($viewName, $options)
    {
        // Normalize view path
        $viewPath = $this->normalizeViewPath($viewName);

        // Get layout name
        $layoutName = isset($options['layout']) && !empty($options['layout'])
            ? $options['layout']
            : 'app';

        // Create directory if it doesn't exist
        $viewDirectoryPath = dirname($this->viewsDir . '/' . $viewPath . '.php');
        $this->ensureDirectoryExists($viewDirectoryPath);

        // Check if view already exists
        $fullViewPath = $this->viewsDir . '/' . $viewPath . '.php';
        if (file_exists($fullViewPath)) {
            TermUI::error("View {$viewPath} already exists at {$fullViewPath}");
            return 1;
        }

        // Load view template
        $templatePath = $this->templatesDir . '/view.php.template';
        if (!file_exists($templatePath)) {
            $template = $this->getDefaultViewTemplate();
        } else {
            $template = file_get_contents($templatePath);
        }

        // Replace placeholders
        $content = str_replace(
            ['{{ViewName}}', '{{LayoutName}}'],
            [$viewPath, $layoutName],
            $template
        );

        // Write view file
        file_put_contents($fullViewPath, $content);

        TermUI::success("View {$viewPath} created successfully!");
        return 0;
    }

    /**
     * Create a set of resource views for a model
     */
    protected function createResourceViews($modelName, $options)
    {
        // Create a directory for the model views if it doesn't exist
        $viewDirectoryName = strtolower($this->pluralize($modelName));
        $viewDirectoryPath = $this->viewsDir . '/' . $viewDirectoryName;
        $this->ensureDirectoryExists($viewDirectoryPath);

        // Get layout name
        $layoutName = isset($options['layout']) && !empty($options['layout'])
            ? $options['layout']
            : 'app';

        // Create each view type
        $viewTypes = ['index', 'create', 'edit', 'show'];
        $successCount = 0;

        foreach ($viewTypes as $viewType) {
            $viewPath = $viewDirectoryName . '/' . $viewType;
            $fullViewPath = $this->viewsDir . '/' . $viewPath . '.php';

            // Skip if view already exists
            if (file_exists($fullViewPath)) {
                TermUI::warning("View {$viewPath} already exists at {$fullViewPath}. Skipping.");
                continue;
            }

            // Load appropriate template
            $templatePath = $this->templatesDir . '/view_' . $viewType . '.php.template';
            if (!file_exists($templatePath)) {
                // Use default template for this view type
                $templateMethod = 'getDefault' . ucfirst($viewType) . 'ViewTemplate';
                $template = $this->$templateMethod();
            } else {
                $template = file_get_contents($templatePath);
            }

            // Replace placeholders
            $content = str_replace(
                ['{{ViewName}}', '{{LayoutName}}', '{{ModelName}}', '{{modelName}}', '{{pluralModelName}}'],
                [
                    $viewPath,
                    $layoutName,
                    $modelName,
                    lcfirst($modelName),
                    strtolower($this->pluralize($modelName))
                ],
                $template
            );

            // Write view file
            file_put_contents($fullViewPath, $content);
            $successCount++;

            TermUI::info("Created {$viewType} view: {$fullViewPath}");
        }

        TermUI::success("Created {$successCount} views for {$modelName}!");
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
     * Normalize view path by converting dots to directory separators
     */
    protected function normalizeViewPath($path)
    {
        return str_replace('.', '/', $path);
    }

    /**
     * Return a default view template
     */
    protected function getDefaultViewTemplate()
    {
        return <<<'EOT'
<!-- View: {{ViewName}} -->
<extends layout="{{LayoutName}}">

<block name="content">
    <div class="container">
        <h1>{{ViewName}}</h1>
        <p>This is a new view.</p>
    </div>
</block>

</extends>
EOT;
    }

    /**
     * Return a default index view template for a resource
     */
    protected function getDefaultIndexViewTemplate()
    {
        return <<<'EOT'
<!-- View: {{ViewName}} -->
<extends layout="{{LayoutName}}">

<block name="content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>{{ModelName}} List</h1>
            <a href="/{{pluralModelName}}/create" class="btn btn-primary">Create New {{ModelName}}</a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <foreach items="$items" as="$item">
                        <tr>
                            <td>{$item->id}</td>
                            <td>{$item->name}</td>
                            <td>{$item->created_at}</td>
                            <td>
                                <a href="/{{pluralModelName}}/{$item->id}" class="btn btn-sm btn-info">View</a>
                                <a href="/{{pluralModelName}}/{$item->id}/edit" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" action="/{{pluralModelName}}/{$item->id}/delete" class="d-inline">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    </foreach>

                    <if condition="empty($items)">
                        <tr>
                            <td colspan="4" class="text-center">No {{ModelName}} records found.</td>
                        </tr>
                    </if>
                </tbody>
            </table>
        </div>
    </div>
</block>

</extends>
EOT;
    }

    /**
     * Return a default create view template for a resource
     */
    protected function getDefaultCreateViewTemplate()
    {
        return <<<'EOT'
<!-- View: {{ViewName}} -->
<extends layout="{{LayoutName}}">

<block name="content">
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h2>Create New {{ModelName}}</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/{{pluralModelName}}">
                            <!-- Example form fields - adjust as needed for your model -->
                            <div class="form-group mb-3">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{old('name')}" required>
                                <error field="name" />
                            </div>

                            <div class="form-group mb-3">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3">{old('description')}</textarea>
                                <error field="description" />
                            </div>

                            <div class="form-group mb-3">
                                <button type="submit" class="btn btn-primary">Create {{ModelName}}</button>
                                <a href="/{{pluralModelName}}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</block>

</extends>
EOT;
    }

    /**
     * Return a default edit view template for a resource
     */
    protected function getDefaultEditViewTemplate()
    {
        return <<<'EOT'
<!-- View: {{ViewName}} -->
<extends layout="{{LayoutName}}">

<block name="content">
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h2>Edit {{ModelName}}</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/{{pluralModelName}}/{$item->id}">
                            <input type="hidden" name="_method" value="PUT">

                            <!-- Example form fields - adjust as needed for your model -->
                            <div class="form-group mb-3">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{old('name', $item->name)}" required>
                                <error field="name" />
                            </div>

                            <div class="form-group mb-3">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3">{old('description', $item->description)}</textarea>
                                <error field="description" />
                            </div>

                            <div class="form-group mb-3">
                                <button type="submit" class="btn btn-primary">Update {{ModelName}}</button>
                                <a href="/{{pluralModelName}}/{$item->id}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</block>

</extends>
EOT;
    }

    /**
     * Return a default show view template for a resource
     */
    protected function getDefaultShowViewTemplate()
    {
        return <<<'EOT'
<!-- View: {{ViewName}} -->
<extends layout="{{LayoutName}}">

<block name="content">
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>{{ModelName}} Details</h2>
                            <div>
                                <a href="/{{pluralModelName}}/{$item->id}/edit" class="btn btn-warning">Edit</a>
                                <form method="POST" action="/{{pluralModelName}}/{$item->id}/delete" class="d-inline">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">ID</dt>
                            <dd class="col-sm-9">{$item->id}</dd>

                            <dt class="col-sm-3">Name</dt>
                            <dd class="col-sm-9">{$item->name}</dd>

                            <dt class="col-sm-3">Description</dt>
                            <dd class="col-sm-9">{$item->description}</dd>

                            <dt class="col-sm-3">Created At</dt>
                            <dd class="col-sm-9">{$item->created_at}</dd>

                            <dt class="col-sm-3">Updated At</dt>
                            <dd class="col-sm-9">{$item->updated_at}</dd>
                        </dl>

                        <div class="mt-4">
                            <a href="/{{pluralModelName}}" class="btn btn-secondary">Back to List</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</block>

</extends>
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

    /**
     * Simple singularization function
     */
    protected function singularize($word)
    {
        // If the word ends with 'ies', change to 'y'
        if (preg_match('/([^aeiou])ies$/i', $word)) {
            return preg_replace('/ies$/i', 'y', $word);
        }

        // If the word ends with 'es', check for special cases
        if (preg_match('/es$/i', $word)) {
            // Words ending with sh, ch, s, x, z
            if (preg_match('/(sh|ch|s|x|z)es$/i', $word)) {
                return preg_replace('/es$/i', '', $word);
            }
            // Default case for 'es' endings
            return preg_replace('/es$/i', '', $word);
        }

        // Default rule: remove trailing 's' if it exists
        if (preg_match('/s$/i', $word) && !preg_match('/(ss|us)$/i', $word)) {
            return preg_replace('/s$/i', '', $word);
        }

        return $word;
    }
}