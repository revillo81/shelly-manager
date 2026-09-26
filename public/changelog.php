<?php
require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

function render_markdown_lite(string $md): string
{
    $lines = explode("\n", $md);
    $html = '';
    $inList = false;
    foreach ($lines as $line) {
        $line = rtrim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^## (.+)/', $line, $m)) {
            if ($inList) { $html .= "</ul>"; $inList = false; }
            $html .= '<h2>' . e($m[1]) . '</h2>';
        } elseif (preg_match('/^# (.+)/', $line, $m)) {
            if ($inList) { $html .= "</ul>"; $inList = false; }
            $html .= '<h1>' . e($m[1]) . '</h1>';
        } elseif (preg_match('/^- (.+)/', $line, $m)) {
            if (!$inList) { $html .= '<ul>'; $inList = true; }
            $html .= '<li>' . e($m[1]) . '</li>';
        } else {
            if ($inList) { $html .= "</ul>"; $inList = false; }
            $html .= '<p>' . e($line) . '</p>';
        }
    }
    if ($inList) { $html .= "</ul>"; }
    return $html;
}

$changelogPath = __DIR__ . '/../CHANGELOG.md';
$content = file_exists($changelogPath) ? file_get_contents($changelogPath) : '';

$pageTitle = t('changelog_title');
require __DIR__ . '/../templates/header.php';
?>
<h1><?= e(t('changelog_title')) ?></h1>
<section class="card">
    <?= render_markdown_lite($content) ?>
</section>
<?php require __DIR__ . '/../templates/footer.php'; ?>
