<?php

declare(strict_types=1);

namespace Ace\ace\Command\Commands;

use Ace\ace\Command\Command;
use Ace\ace\Command\TermUI;

class ControllerCommand extends Command
{
    // Templates directory
    protected $templatesDir;

    // Base directories
    protected $controllersDir;

    public function __construct()
    {
        $this->name = 'make:controller';
        $this->description = 'Create a new controller from template';

        // Set up directories - adjust these paths as needed
        $this->templatesDir = dirname(__DIR__) . '/templates';
        $this->controllersDir = BASE_PATH . '/app/controllers';

        // Command configuration
        $this->addArgument('name', 'The name of the controller to create', true);
        $this->addOption('resource', 'r', 'Create a resource controller with CRUD actions', false);
        $this->addOption('model', 'm', 'The model that this controller will use', true);
        $this->addOption('api', 'a', 'Create an API controller', false);
    }

    public function handle($arguments, $options)
    {
        // Get controller name and ensure it's properly formatted
        $controllerName = $arguments['name'];

        // Add Controller suffix if not present
        if (!str_ends_with($controllerName, 'Controller')) {
            $controllerName .= 'Controller';
        }

        $controllerName = $this->formatClassName($controllerName);

        // Create directories if they don't exist
        $this->ensureDirectoryExists($this->controllersDir);

        // Check if controller already exists
        $controllerPath = $this->controllersDir . '/' . $controllerName . '.php';
        if (file_exists($controllerPath)) {
            TermUI::error("Controller {$controllerName} already exists at {$controllerPath}");
            return 1;
        }

        // Determine controller type and associated model
        $isResource = isset($options['resource']) && $options['resource'];
        $isApi = isset($options['api']) && $options['api'];
        $modelName = isset($options['model']) && !empty($options['model'])
            ? $this->formatClassName($options['model'])
            : $this->guessModelName($controllerName);

        // Create the controller file
        $this->createControllerFile($controllerName, $modelName, $isResource, $isApi);

        TermUI::success("Controller {$controllerName} created successfully!");
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
     * Guess the model name from the controller name
     */
    protected function guessModelName($controllerName)
    {
        // Remove "Controller" suffix and use singular form
        $baseName = str_replace('Controller', '', $controllerName);
        return $this->singularize($baseName);
    }

    /**
     * Create controller file from template
     */
    protected function createControllerFile($controllerName, $modelName, $isResource, $isApi)
    {
        // Determine which template to use
        $templateType = $isApi ? 'api_controller' : 'controller';
        $templateType = $isResource ? $templateType . '_resource' : $templateType . '_base';
        $templatePath = $this->templatesDir . '/' . $templateType . '.php.template';

        if (!file_exists($templatePath)) {
            // If template doesn't exist, create a appropriate template based on options
            if ($isResource && $isApi) {
                $template = $this->getApiResourceControllerTemplate();
            } elseif ($isResource) {
                $template = $this->getResourceControllerTemplate();
            } elseif ($isApi) {
                $template = $this->getApiControllerTemplate();
            } else {
                $template = $this->getBaseControllerTemplate();
            }
        } else {
            $template = file_get_contents($templatePath);
        }

        // Replace placeholders
        $content = str_replace(
            ['{{ControllerName}}', '{{ModelName}}'],
            [$controllerName, $modelName],
            $template
        );

        // Write controller file
        $controllerPath = $this->controllersDir . '/' . $controllerName . '.php';
        file_put_contents($controllerPath, $content);

        TermUI::info("Created controller file: {$controllerPath}");
    }

    /**
     * Return a base controller template
     */
    protected function getBaseControllerTemplate()
    {
        return <<<'EOT'
<?php

declare(strict_types=1);

namespace Ace\app\controllers;

use Ace\ace\Controller;
use Ace\ace\Http\Request;
use Ace\ace\Http\Response;

class {{ControllerName}} extends Controller
{
    /**
     * Display the index page
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->view('index');
    }
}
EOT;
    }

    /**
     * Return a resource controller template
     */
    protected function getResourceControllerTemplate()
    {
        return <<<'EOT'
<?php

declare(strict_types=1);

namespace Ace\app\controllers;

use Ace\ace\Controller;
use Ace\ace\Http\Request;
use Ace\ace\Http\Response;
use Ace\app\models\{{ModelName}};

class {{ControllerName}} extends Controller
{
    /**
     * Display a listing of the resource
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $items = {{ModelName}}::all();

        return $this->view('{{ModelName}}/index', [
            'items' => $items,
        ]);
    }

    /**
     * Show the form for creating a new resource
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        return $this->view('{{ModelName}}/create');
    }

    /**
     * Store a newly created resource in storage
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request): Response
    {
        $validated = $this->validate($request, [
            // Define validation rules here
        ]);

        $item = new {{ModelName}}();
        $item->fill($validated);
        $item->save();

        return $this->redirect('/' . strtolower($this->pluralize('{{ModelName}}')) . '/' . $item->id)
            ->withSuccess('{{ModelName}} created successfully!');
    }

    /**
     * Display the specified resource
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function show(Request $request, int $id): Response
    {
        $item = {{ModelName}}::find($id);

        if (!$item) {
            return $this->abort(404);
        }

        return $this->view('{{ModelName}}/show', [
            'item' => $item,
        ]);
    }

    /**
     * Show the form for editing the specified resource
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function edit(Request $request, int $id): Response
    {
        $item = {{ModelName}}::find($id);

        if (!$item) {
            return $this->abort(404);
        }

        return $this->view('{{ModelName}}/edit', [
            'item' => $item,
        ]);
    }

    /**
     * Update the specified resource in storage
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, int $id): Response
    {
        $item = {{ModelName}}::find($id);

        if (!$item) {
            return $this->abort(404);
        }

        $validated = $this->validate($request, [
            // Define validation rules here
        ]);

        $item->fill($validated);
        $item->save();

        return $this->redirect('/' . strtolower($this->pluralize('{{ModelName}}')) . '/' . $item->id)
            ->withSuccess('{{ModelName}} updated successfully!');
    }

    /**
     * Remove the specified resource from storage
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request, int $id): Response
    {
        $item = {{ModelName}}::find($id);

        if (!$item) {
            return $this->abort(404);
        }

        $item->delete();

        return $this->redirect('/' . strtolower($this->pluralize('{{ModelName}}')))
            ->withSuccess('{{ModelName}} deleted successfully!');
    }
}
EOT;
    }

    /**
     * Return an API controller template
     */
    protected function getApiControllerTemplate()
    {
        return <<<'EOT'
<?php

declare(strict_types=1);

namespace Ace\app\controllers;

use Ace\ace\Controller;
use Ace\ace\Http\Request;
use Ace\ace\Http\Response;

class {{ControllerName}} extends Controller
{
    /**
     * Get API resource
     *
     * @param Request $request
     * @return Response
     */
    public function get(Request $request): Response
    {
        return $this->json([
            'message' => 'API endpoint'
        ]);
    }

    /**
     * Process API request
     *
     * @param Request $request
     * @return Response
     */
    public function process(Request $request): Response
    {
        $data = $request->getJson();

        // Process the data

        return $this->json([
            'status' => 'success',
            'message' => 'Request processed',
            'data' => $data
        ]);
    }
}
EOT;
    }

    /**
     * Return an API resource controller template
     */
    protected function getApiResourceControllerTemplate()
    {
        return <<<'EOT'
<?php

declare(strict_types=1);

namespace Ace\app\controllers;

use Ace\ace\Controller;
use Ace\ace\Http\Request;
use Ace\ace\Http\Response;
use Ace\app\models\{{ModelName}};

class {{ControllerName}} extends Controller
{
    /**
     * Display a listing of the resource
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $items = {{ModelName}}::all();

        return $this->json([
            'data' => $items,
        ]);
    }

    /**
     * Store a newly created resource in storage
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request): Response
    {
        $data = $request->getJson();

        // Add validation here

        $item = new {{ModelName}}();
        $item->fill($data);
        $item->save();

        return $this->json([
            'status' => 'success',
            'message' => '{{ModelName}} created successfully!',
            'data' => $item
        ], 201);
    }

    /**
     * Display the specified resource
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function show(Request $request, int $id): Response
    {
        $item = {{ModelName}}::find($id);

        if (!$item) {
            return $this->json([
                'status' => 'error',
                'message' => '{{ModelName}} not found'
            ], 404);
        }

        return $this->json([
            'data' => $item,
        ]);
    }

    /**
     * Update the specified resource in storage
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, int $id): Response
    {
        $item = {{ModelName}}::find($id);

        if (!$item) {
            return $this->json([
                'status' => 'error',
                'message' => '{{ModelName}} not found'
            ], 404);
        }

        $data = $request->getJson();

        // Add validation here

        $item->fill($data);
        $item->save();

        return $this->json([
            'status' => 'success',
            'message' => '{{ModelName}} updated successfully!',
            'data' => $item
        ]);
    }

    /**
     * Remove the specified resource from storage
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request, int $id): Response
    {
        $item = {{ModelName}}::find($id);

        if (!$item) {
            return $this->json([
                'status' => 'error',
                'message' => '{{ModelName}} not found'
            ], 404);
        }

        $item->delete();

        return $this->json([
            'status' => 'success',
            'message' => '{{ModelName}} deleted successfully!'
        ]);
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