<?php $this->extend("layouts/default"); ?>

<?php $this->startSection("title"); ?>
    Home Page
<?php $this->endSection(); ?>

<?php $this->startSection("content"); ?>
    <?php if ($isLoggedIn): ?>
        <p>Welcome back, <?= htmlspecialchars($username, ENT_QUOTES) ?>!</p>
    <?php endif; ?>

    <h2>Welcome to Ace Framework</h2>
    <p>This is the content of the homepage.</p>

    <h3>Recent Articles</h3>
    <ul>
        <?php foreach ($articles as $article): ?>
            <li><?= htmlspecialchars($article, ENT_QUOTES) ?></li>
        <?php endforeach; ?>
    </ul>
<?php $this->endSection(); ?>
