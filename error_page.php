<?php
$errorPages = [
    403 => [
        'eyebrow' => 'ACCESS RESTRICTED',
        'title' => 'This area is reserved.',
        'message' => 'You do not have permission to view this page. You can head back to the KABC Hotel home page or browse the menu.',
    ],
    404 => [
        'eyebrow' => 'PAGE NOT FOUND',
        'title' => 'Looks like this table is taken.',
        'message' => 'We could not find the page you were looking for. It may have moved, or the address may be incorrect.',
    ],
    500 => [
        'eyebrow' => 'TEMPORARY SETBACK',
        'title' => 'We are setting things right.',
        'message' => 'Something went wrong on our side. Please try again in a little while.',
    ],
    502 => [
        'eyebrow' => 'SERVICE TEMPORARILY UNAVAILABLE',
        'title' => 'We are having trouble connecting.',
        'message' => 'A service we rely on did not respond as expected. Please try again in a little while.',
    ],
    503 => [
        'eyebrow' => 'WE WILL BE RIGHT BACK',
        'title' => 'The kitchen is taking a short break.',
        'message' => 'The site is temporarily unavailable while we take care of a few things. Please try again soon.',
    ],
];

$statusCode = defined('ERROR_PAGE_CODE') ? ERROR_PAGE_CODE : 500;
$page = $errorPages[$statusCode] ?? $errorPages[500];
http_response_code($statusCode);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$basePath = $basePath === '' ? '' : $basePath;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102c24">
    <title><?= $statusCode ?> | KABC Hotel</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/fontawesome/css/solid.min.css">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/img/icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: light; --forest: #102c24; --leaf: #b6d49a; --cream: #f4f0e6; --paper: #fbf9f3; --ink: #1b3028; --muted: #64736b; --gold: #d8ae68; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; overflow: hidden; padding: 32px 22px; background: var(--forest); color: var(--paper); font-family: "DM Sans", Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .scene { position: fixed; inset: 0; overflow: hidden; pointer-events: none; }
        .glow { position: absolute; width: min(72vw, 760px); aspect-ratio: 1; top: -37%; right: -13%; border-radius: 50%; background: radial-gradient(circle, rgba(216,174,104,.2), rgba(216,174,104,0) 68%); }
        .ring { position: absolute; width: min(76vw, 780px); aspect-ratio: 1; right: -21%; bottom: -72%; border: 1px solid rgba(182,212,154,.18); border-radius: 50%; box-shadow: 0 0 0 54px rgba(182,212,154,.035), 0 0 0 108px rgba(182,212,154,.025); }
        main { position: relative; width: min(100%, 720px); padding: clamp(36px, 8vw, 76px); border: 1px solid rgba(244,240,230,.16); background: rgba(11,33,27,.62); box-shadow: 0 32px 100px rgba(0,0,0,.18); }
        .brand { display: inline-flex; align-items: center; gap: 12px; color: var(--paper); text-decoration: none; font-size: .8rem; font-weight: 700; letter-spacing: .14em; }
        .brand img { width: 48px; height: 38px; object-fit: contain; }
        .brand strong { color: var(--leaf); font-weight: 500; }
        .eyebrow { display: flex; align-items: center; gap: 11px; margin: clamp(48px, 8vh, 76px) 0 17px; color: var(--leaf); font-size: .72rem; font-weight: 700; letter-spacing: .18em; }
        .eyebrow::before { width: 26px; height: 1px; background: var(--gold); content: ""; }
        .code { position: absolute; top: 35px; right: clamp(28px, 6vw, 60px); color: rgba(244,240,230,.08); font-family: "Playfair Display", Georgia, serif; font-size: clamp(4rem, 12vw, 8rem); line-height: 1; }
        h1 { position: relative; max-width: 540px; margin: 0; font-family: "Playfair Display", Georgia, serif; font-size: clamp(2.5rem, 7vw, 4.7rem); font-weight: 500; letter-spacing: -.045em; line-height: 1.05; }
        .message { max-width: 450px; margin: 24px 0 34px; color: rgba(244,240,230,.72); font-size: 1rem; line-height: 1.75; }
        .actions { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; }
        .button { display: inline-flex; min-height: 50px; align-items: center; justify-content: center; padding: 0 21px; border: 1px solid transparent; border-radius: 2px; background: var(--leaf); color: var(--forest); font-size: .86rem; font-weight: 700; text-decoration: none; transition: transform .18s ease, background .18s ease; }
        .button:hover { transform: translateY(-2px); background: #c9e0b2; }
        .button-secondary { border-color: rgba(244,240,230,.3); background: transparent; color: var(--paper); }
        .button-secondary:hover { background: rgba(244,240,230,.08); }
        .footnote { margin: 54px 0 0; color: rgba(244,240,230,.42); font-size: .72rem; letter-spacing: .04em; }
        @media (max-width: 480px) { main { padding: 32px 26px; } .code { top: 30px; right: 24px; } .eyebrow { margin-top: 58px; } .footnote { margin-top: 42px; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
    <div class="scene" aria-hidden="true"><div class="glow"></div><div class="ring"></div></div>
    <main>
        <span class="code" aria-hidden="true"><?= $statusCode ?></span>
        <a class="brand" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/" aria-label="KABC Hotel home">
            <img src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/img/icon.png" alt="">
            <span>KABC <strong>HOTEL</strong></span>
        </a>
        <p class="eyebrow"><?= htmlspecialchars($page['eyebrow'], ENT_QUOTES, 'UTF-8') ?></p>
        <h1><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="message"><?= htmlspecialchars($page['message'], ENT_QUOTES, 'UTF-8') ?></p>
        <div class="actions">
            <a class="button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/">Back to home <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            <a class="button button-secondary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/full_menu">Explore the menu</a>
        </div>
        
        <p class="footnote">KABC HOTEL &nbsp;&middot;&nbsp; GATHER WELL. EAT JOYFULLY.</p>
    </main>
</body>
</html>
