<!DOCTYPE html>
<html>
<head>
    <!-- Meta tags -->
    <meta charset="<?= ($charset ?? ini_get('default_charset')); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php
    if (isset($meta_description)) : ?>
        <meta name="description" content="<?= HTML::chars($meta_description); ?>">
    <?php
    endif; ?>
    <?php
    if (isset($meta_keywords)) : ?>
        <meta name="keywords" content="<?= HTML::chars($meta_keywords); ?>">
    <?php
    endif; ?>
    <title><?= HTML::chars($title ?? ''); ?></title>
    <!-- Links -->
    <?= HTML::link('favicon.ico', ['rel' => 'shortcut icon', 'type' => 'image/x-icon']); ?>
    <!-- Styles -->
    <?php
    if (!empty($styles)) : ?>
        <?php
        foreach ($styles as $style) : ?>
            <?= HTML::style($style) . PHP_EOL; ?>
        <?php
        endforeach; ?>
    <?php
    endif; ?>
</head>
<body>
<!-- Header -->
<header id="header">
    <div class="container">

    </div>
</header>

<div class="container" id="content">
    <div class="row">
        <?php
        if (!empty($sidebar)) : ?>
            <?= (new View('snippets/sidebar', get_defined_vars())); ?>
        <?php
        endif ?>
        <!-- Main -->
        <main id="main" class="col-12<?= (!empty($sidebar) ? ' col-md-8 col-lg-9' : ''); ?>">
            <?php
            if (!empty($breadcrumb)) : ?>
                <?= (new View('snippets/breadcrumb', ['breadcrumbs' => $breadcrumb])); ?>
            <?php
            endif; ?>
            <!-- Main content -->
            <div id="main-content">
                <?= ($main_content ?? ''); ?>
            </div>
        </main>
    </div>
</div>

<!-- Footer -->
<footer id="footer">
    <div class="container">

    </div>
</footer>

<?php
if (!empty($scripts)) : ?>
    <!-- Scripts -->
    <?php
    foreach ($scripts as $script) : ?>
        <?= HTML::script($script) . PHP_EOL; ?>
    <?php
    endforeach; ?>
<?php
endif; ?>
</body>
</html>
