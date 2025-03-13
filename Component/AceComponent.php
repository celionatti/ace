<?php
declare(strict_types=1);

namespace Ace\Component;

use Ace\Ace;
use Ace\View\View;
use ReflectionClass;
use ReflectionException;

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

    protected string $viewName;

    public function __construct(array $data = [], array $slots = [])
    {
        // Retrieve the Ace singleton instance
        $this->ace = Ace::getInstance();

        $this->view = $this->ace->getContainer()->get(View::class);
        $this->data = $data;
        $this->slots = $slots;
        $this->viewName = $this->resolveViewName();
        $this->mount();
    }

    /**
     * Resolve view name based on component class name
     */
    protected function resolveViewName(): string
    {
        try {
            $className = (new ReflectionClass($this))->getShortName();
            return 'components/' . $this->kebabCase($className);
        } catch (ReflectionException $e) {
            throw new \RuntimeException('Error resolving component view name: ' . $e->getMessage());
        }
    }

    /**
     * Convert PascalCase to kebab-case
     */
    private function kebabCase(string $input): string
    {
        return strtolower(preg_replace(
            ['/([a-z])([A-Z])/', '/([^_])([A-Z][a-z])/'],
            '$1-$2',
            $input
        ));
    }

    /**
     * Create new instance with merged data
     */
    public function withData(array $data): static
    {
        return new static(
            array_merge($this->data, $data),
            $this->slots,
            $this->view
        );
    }

    /**
     * Create new instance with merged slots
     */
    public function withSlots(array $slots): static
    {
        return new static(
            $this->data,
            array_merge($this->slots, $slots),
            $this->view
        );
    }

    /**
     * Render component using View service
     */
    public function render(): string
    {
        return $this->view->render(
            $this->viewName,
            $this->prepareViewData()
        );
    }

    /**
     * Prepare data for view including slots and component reference
     */
    protected function prepareViewData(): array
    {
        return array_merge($this->data, [
            'slots' => $this->slots,
            'component' => $this,
        ]);
    }

    /**
     * Retrieve slot content with optional default value
     */
    protected function slot(string $name, string $default = ''): string
    {
        return $this->slots[$name] ?? $default;
    }

    /**
     * Lifecycle hook for initialization
     */
    protected function mount(): void
    {
        // Optional initialization logic for child components
    }
}