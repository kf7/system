 <!-- Breadcrumb navigation -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
    <?php $currentUrl = array_key_last($breadcrumbs); ?>
    <?php foreach ($breadcrumbs as $url => $title) : ?>
        <?php if (empty($url) || $url === $currentUrl) : ?>
            <li class="breadcrumb-item active" aria-current="page">
                <?=HTML::chars($title);?>
            </li>
        <?php else : ?>
            <li class="breadcrumb-item">
                <a href="<?=$url;?>"><?=HTML::chars($title);?></a>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
    </ol>
</nav>