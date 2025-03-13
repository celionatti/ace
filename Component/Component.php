<?php
declare(strict_types=1);

namespace Ace\ace\Component;

use Ace\ace\Ace;
use Ace\ace\Exception\AceException;
use Ace\ace\View\View;
use ReflectionClass;

abstract class Component
{
    /**
     * Component data storage
     */
    protected array $data = [];

    /**
     * Component slots storage
     */
    protected array $slots = [];

    /**
     * Ace singleton instance
     */
    protected Ace $ace;

    /**
     * View instance
     */
    protected View $view;

    /**
     * Constructor for the component
     *
     * @param array $data Initial data for the component
     * @param array $slots Initial slots for the component
     */
    public function __construct(array $data = [], array $slots = [])
    {
        // Retrieve the Ace singleton instance
        $this->ace = Ace::getInstance();

        // Retrieve the View instance from the container
        $this->view = $this->ace->getContainer()->get(View::class);

        // Initialize component data and slots
        $this->data = $this->sanitizeData($data);
        $this->slots = $this->sanitizeSlots($slots);

        // Call mount method for additional initialization
        $this->mount();
    }

    /**
     * Lifecycle method called when the component is instantiated.
     * Can be overridden by child components.
     */
    protected function mount(): void
    {
        // Default mount logic, override in child components as needed
    }

    /**
     * Merge additional data into the component's data array
     *
     * @param array $data New data to merge
     * @return static
     */
    public function withData(array $data): static
    {
        $this->data = array_merge($this->data, $this->sanitizeData($data));
        return $this;
    }

    /**
     * Merge additional slots into the component's slots array
     *
     * @param array $slots New slots to merge
     * @return static
     */
    public function withSlots(array $slots): static
    {
        $this->slots = array_merge($this->slots, $this->sanitizeSlots($slots));
        return $this;
    }

    /**
     * Render the component using the View class
     *
     * @throws AceException If view file is not found
     * @return string Rendered component content
     */
    public function render(): string
    {
        // Get the view path
        $viewPath = $this->getViewPath();

        // Prepare data for rendering
        $renderData = array_merge($this->data, [
            'slots' => $this->slots
        ]);

        // Use View class to render the component
        return $this->view->render($viewPath, $renderData);
    }

    /**
     * Retrieve the slot content by name, with an optional default value
     *
     * @param string $name Slot name
     * @param string $default Default content if slot is not found
     * @return string Slot content
     */
    protected function slot(string $name, string $default = ''): string
    {
        return $this->slots[$name] ?? $default;
    }

    /**
     * Generate the view name for the component based on the class name
     *
     * @return string View name (without .ace.php extension)
     */
    protected function getViewPath(): string
    {
        // Use ReflectionClass to get the short class name
        $className = (new ReflectionClass($this))->getShortName();

        // Convert camelCase to kebab-case for filename
        return 'components/' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $className));
    }

    /**
     * Sanitize input data to prevent potential issues
     *
     * @param array $data Input data to sanitize
     * @return array Sanitized data
     */
    protected function sanitizeData(array $data): array
    {
        // Remove null values and trim string values
        return array_filter(array_map(function($value) {
            return is_string($value) ? trim($value) : $value;
        }, $data));
    }

    /**
     * Sanitize input slots to prevent potential issues
     *
     * @param array $slots Input slots to sanitize
     * @return array Sanitized slots
     */
    protected function sanitizeSlots(array $slots): array
    {
        // Remove null values and trim string values
        return array_filter(array_map('trim', $slots));
    }
}