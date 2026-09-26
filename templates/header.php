<?php
/** @var string $pageTitle */
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? t('app_title')) ?> · <?= e(t('app_title')) ?></title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<?php if (Auth::check()): ?>
<header class="topbar">
    <div class="topbar-brand">
        <a href="<?= url('index.php') ?>"><span class="logo">⚡</span> <?= e(t('app_title')) ?></a>
    </div>
    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="<?= e(t('nav_menu')) ?>" aria-expanded="false">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    <div class="topbar-menu" id="topbar-menu">
        <nav class="topbar-nav">
            <a href="<?= url('index.php') ?>"><?= e(t('nav_dashboard')) ?></a>
            <a href="<?= url('add_device.php') ?>"><?= e(t('nav_add_device')) ?></a>
            <a href="<?= url('settings.php') ?>"><?= e(t('nav_settings')) ?></a>
            <a href="<?= url('help.php') ?>"><?= e(t('nav_help')) ?></a>
            <a href="<?= url('changelog.php') ?>"><?= e(t('nav_changelog')) ?></a>
        </nav>
        <div class="topbar-right">
            <span class="lang-switch">
                <a href="?lang=de" class="<?= Lang::current() === 'de' ? 'active' : '' ?>">DE</a> /
                <a href="?lang=en" class="<?= Lang::current() === 'en' ? 'active' : '' ?>">EN</a>
            </span>
            <span class="user"><?= e(Auth::username()) ?></span>
            <a href="<?= url('logout.php') ?>" class="btn-link"><?= e(t('nav_logout')) ?></a>
        </div>
    </div>
</header>
<?php endif; ?>
<main class="content">
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= e($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-error"><?= e($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
