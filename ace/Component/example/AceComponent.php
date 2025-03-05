<?php

// Template Usage.

<!-- components/my-component.ace.php -->
<div class="component">
    <h1><?= $title ?></h1>
    <div class="content">
        <?= $component->slot('content', 'Default content') ?>
    </div>
    <?= $this->yieldSection('extra_content') ?>
</div>

<!-- Controller -->

// In controller
$component = new MyComponent(['title' => 'Dashboard'])
    ->withSlots(['content' => '<p>Welcome back!</p>']);

$html = $component->render();

<!-- Template Directive -->

<!-- In parent template -->
@component('my-component', ['title' => 'Dashboard'])
    @slot('content')
        <p>Welcome back!</p>
    @endslot
@endcomponent