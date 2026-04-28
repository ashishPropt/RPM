<?php
/**
 * Flash messages — stored in session, consumed once on next render.
 *
 * flash('success', 'Saved!');
 * flash('error',   'Something went wrong.');
 * flash('info',    'Note: ...');
 *
 * In templates: echo flash_html();
 */
function flash(string $type, string $text): void
{
    $_SESSION['_flash'] = ['type' => $type, 'text' => $text];
}

function flash_html(): string
{
    if (empty($_SESSION['_flash'])) return '';
    $f = $_SESSION['_flash'];
    unset($_SESSION['_flash']);

    $colors = [
        'success' => '#2d6a4f',
        'error'   => '#9b2226',
        'info'    => '#7b6914',
    ];
    $bgs = [
        'success' => '#d8f3dc',
        'error'   => '#ffe3e3',
        'info'    => '#fff3cd',
    ];
    $icons = ['success' => '✓', 'error' => '⚠', 'info' => 'ℹ'];

    $c  = $colors[$f['type']] ?? $colors['info'];
    $bg = $bgs[$f['type']]   ?? $bgs['info'];
    $i  = $icons[$f['type']] ?? '•';

    return '<div class="flash" style="background:' . $bg . ';color:' . $c
         . ';border-left:4px solid ' . $c . ';padding:.75rem 1rem;border-radius:4px;'
         . 'margin-bottom:1.25rem;font-size:.9rem">'
         . $i . ' ' . htmlspecialchars($f['text']) . '</div>';
}

/** Shortcut redirect helper — keeps PRG clean. */
function redirect(string $page, string $extra = ''): never
{
    header('Location: index.php?page=' . $page . ($extra ? '&' . $extra : ''));
    exit;
}
