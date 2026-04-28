<?php
/**
 * PropTXChange — Flash Messages
 *
 * One-time messages stored in $_SESSION and consumed on the
 * next page render. Bootstrapped by index.php.
 *
 * Usage:
 *   flash_set('success', 'Account created!');
 *   flash_set('error',   'Something went wrong.');
 *   flash_set('info',    'Please verify your email.');
 *
 *   // In a page template:
 *   echo flash_html();   // renders and clears the message
 */

function flash_set(string $type, string $text): void
{
    $_SESSION['_flash'] = ['type' => $type, 'text' => $text];
}

function flash_get(): ?array
{
    if (!empty($_SESSION['_flash'])) {
        $msg = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $msg;
    }
    return null;
}

function flash_html(): string
{
    $msg = flash_get();
    if (!$msg) return '';

    $styles = [
        'success' => 'background:rgba(76,175,124,0.1);border:1px solid rgba(76,175,124,0.3);color:var(--green)',
        'error'   => 'background:rgba(224,92,92,0.1);border:1px solid rgba(224,92,92,0.3);color:var(--red)',
        'info'    => 'background:var(--gold-dim);border:1px solid rgba(201,168,76,0.3);color:var(--gold)',
    ];
    $icons = [
        'success' => '&#10003;',
        'error'   => '&#9888;',
        'info'    => '&#8505;',
    ];

    $s = $styles[$msg['type']] ?? $styles['info'];
    $i = $icons[$msg['type']]  ?? $icons['info'];

    return '<div style="' . $s . ';border-radius:var(--radius);padding:0.85rem 1rem;'
         . 'margin-bottom:1.5rem;font-size:0.875rem;line-height:1.5;">'
         . $i . ' ' . htmlspecialchars($msg['text'])
         . '</div>';
}
