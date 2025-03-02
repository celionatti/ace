<?php /* Source: C:\xampp\htdocs\ace/resources/views/home.ace.php */ ?>
<?php $this->extend("layouts/default"); ?>
<?php /* Line: $__line__ */ ?>

<?php /* Line: $__line__ */ ?>
<?php $this->startSection("title"); ?>
<?php /* Line: $__line__ */ ?>
    Home Page
<?php /* Line: $__line__ */ ?>
<?php $this->endSection(); ?>
<?php /* Line: $__line__ */ ?>

<?php /* Line: $__line__ */ ?>
<?php $this->startSection("content"); ?>
<?php /* Line: $__line__ */ ?>
    <?php if($isLoggedIn): ?>
<?php /* Line: $__line__ */ ?>
        <p>Welcome back, <?= htmlspecialchars($username, ENT_QUOTES) ?>!</p>
<?php /* Line: $__line__ */ ?>
    <?php endif; ?>
<?php /* Line: $__line__ */ ?>

<?php /* Line: $__line__ */ ?>
    <h2>Welcome to Ace Framework</h2>
<?php /* Line: $__line__ */ ?>
    <p>This is the content of the homepage.</p>
<?php /* Line: $__line__ */ ?>

<?php /* Line: $__line__ */ ?>
    <h3>Recent Articles</h3>
<?php /* Line: $__line__ */ ?>
    <ul>
<?php /* Line: $__line__ */ ?>
        <?php foreach($articles as $article): ?>
<?php /* Line: $__line__ */ ?>
            <li><?= htmlspecialchars($article, ENT_QUOTES) ?></li>
<?php /* Line: $__line__ */ ?>
        <?php endforeach; ?>
<?php /* Line: $__line__ */ ?>
    </ul>
<?php /* Line: $__line__ */ ?>

<?php /* Line: $__line__ */ ?>
    <p>Safe: <?= htmlspecialchars($content, ENT_QUOTES) ?></p>
<?php /* Line: $__line__ */ ?>
    <p>UnSafe: <?= $content ?></p>
<?php /* Line: $__line__ */ ?>

<?php /* Line: $__line__ */ ?>
    <form>
<?php /* Line: $__line__ */ ?>
        <?= $this->generateCsrfField() ?>
<?php /* Line: $__line__ */ ?>
    </form>
<?php /* Line: $__line__ */ ?>
<?php $this->endSection(); ?>
<?php /* Line: $__line__ */ ?>
