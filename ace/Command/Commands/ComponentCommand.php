<?php

declare(strict_types=1);

namespace Ace\ace\Command\Commands;

use Ace\ace\Command\Command;
use Ace\ace\Command\TermUI;

class ComponentCommand extends Command
{
    // Templates directory
    protected $templatesDir;

    // Base directories
    protected $componentsDir;
    protected $componentsViewsDir;

    public function __construct()
    {
        $this->name = 'make:component';
        $this->description = 'Create a new UI component';

        // Set up directories - adjust these paths as needed
        $this->templatesDir = dirname(__DIR__) . '/templates';
        $this->componentsDir = BASE_PATH . '/app/components';
        $this->componentsViewsDir = BASE_PATH . '/resources/views/components';

        // Command configuration
        $this->addArgument('name', 'The name of the component to create', true);
        $this->addOption('view-only', 'v', 'Create only the view part without a class', false);
        $this->addOption('class-only', 'c', 'Create only the class part without a view', false);
        $this->addOption('inline', 'i', 'Create an inline component', false);
    }

    public function handle($arguments, $options)
    {
        // Get component name and ensure it's properly formatted
        $componentName = $arguments['name'];
        $className = $this->formatClassName($componentName);

        // Determine component type
        $viewOnly = isset($options['view-only']) && $options['view-only'];
        $classOnly = isset($options['class-only']) && $options['class-only'];
        $inline = isset($options['inline']) && $options['inline'];

        // Create directories if they don't exist
        $this->ensureDirectoryExists($this->componentsDir);
        $this->ensureDirectoryExists($this->componentsViewsDir);

        // Determine component paths
        $componentPath = $this->componentsDir . '/' . $className . '.php';
        $componentSlug = $this->kebabCase($componentName);
        $viewPath = $this->componentsViewsDir . '/' . $componentSlug . '.php';

        // Check if component already exists
        if (!$viewOnly && file_exists($componentPath)) {
            TermUI::error("Component class {$className} already exists at {$componentPath}");
            return 1;
        }

        if (!$classOnly && !$inline && file_exists($viewPath)) {
            TermUI::error("Component view already exists at {$viewPath}");
            return 1;
        }

        // Create component class if needed
        if (!$viewOnly) {
            $this->createComponentClass($className, $componentSlug, $inline, $componentPath);
        }

        // Create component view if needed
        if (!$classOnly && !$inline) {
            $this->createComponentView($componentSlug, $viewPath);
        }

        TermUI::success("Component {$className} created successfully!");
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
     * Convert a string to kebab-case
     */
    protected function kebabCase($string)
    {
        $string = preg_replace('/[^a-zA-Z0-9]/', ' ', $string);
        $string = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
        $string = preg_replace('/[\s-]+/', '-', $string);
        return trim($string, '-');
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
     * Create component class file
     */
    protected function createComponentClass($className, $componentSlug, $inline, $componentPath)
    {
        // Try to load template
        $templateType = $inline ? 'inline_component' : 'component';
        $templatePath = $this->templatesDir . '/' . $templateType . '.php.template';

        if (!file_exists($templatePath)) {
            // If template doesn't exist, create a basic component template
            $template = $inline
                ? $this->getDefaultInlineComponentTemplate()
                : $this->getDefaultComponentTemplate();
        } else {
            $template = file_get_contents($templatePath);
        }

        // Replace placeholders
        $content = str_replace(
            ['{{ComponentName}}', '{{ComponentView}}'],
            [$className, $componentSlug],
            $template
        );

        // Write component class file
        file_put_contents($componentPath, $content);

        TermUI::info("Created component class: {$componentPath}");
    }

    /**
     * Create component view file
     */
    protected function createComponentView($componentSlug, $viewPath)
    {
        // Try to load template
        $templatePath = $this->templatesDir . '/component_view.php.template';

        if (!file_exists($templatePath)) {
            // If template doesn't exist, create a basic component view template
            $template = $this->getDefaultComponentViewTemplate();
        } else {
            $template = file_get_contents($templatePath);
        }

        // Replace placeholders
        $content = str_replace(
            ['{{ComponentName}}'],
            [ucfirst(str_replace('-', ' ', $componentSlug))],
            $template
        );

        // Write component view file
        file_put_contents($viewPath, $content);

        TermUI::info("Created component view: {$viewPath}");
    }

    /**
     * Return a default component class template
     */
    protected function getDefaultComponentTemplate()
    {
        return <<<'EOT'
<?php

declare(strict_types=1);

namespace Ace\app\components;

use Ace\ace\Component\Component;

class {{ComponentName}} extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $title = '',
        public string $type = 'default'
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): string
    {
        return 'components.{{ComponentView}}';
    }
}
EOT;
    }

    /**
     * Return a default inline component class template
     */
    protected function getDefaultInlineComponentTemplate()
    {
        return <<<'EOT'
<?php

declare(strict_types=1);

namespace Ace\app\components;

use Ace\ace\Component\Component;

class {{ComponentName}} extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $type = 'default',
        public string $class = ''
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): string
    {
        $class = 'component-base';

        if ($this->type === 'primary') {
            $class .= ' component-primary';
        } elseif ($this->type === 'secondary') {
            $class .= ' component-secondary';
        }

        if ($this->class) {
            $class .= ' ' . $this->class;
        }

        return <<<HTML
        <div class="{$class}">
            {$this->slot}
        </div>
        HTML;
    }
}
EOT;
    }

    /**
     * Return a default component view template
     */
    protected function getDefaultComponentViewTemplate()
    {
        return <<<'EOT'
<div class="component {{ $class ?? '' }}" {{ $attributes }}>
    @if($title)
        <div class="component-header">
            <h3 class="component-title">{{ $title }}</h3>
        </div>
    @endif

    <div class="component-body">
        {{ $slot }}
    </div>
</div>
EOT;
    }
}