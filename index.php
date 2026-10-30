<?php
require_once __DIR__ . '/includes/admin_session.php';

$landingCsrfToken = adminCsrfToken();
$rooms = [
    ['Double Standard Room', 'A restful room with a working desk and a private mini balcony.', 'room-standard.webp', [['1 guest', '$60'], ['2 guests', '$80']]],
    ['Junior Studio Suite', 'A generous suite with separate living and dining spaces, plus a city-view balcony.', 'room-suite.webp', [['1 guest', '$120'], ['2 guests', '$140']]],
    ['Junior Room', 'A comfortable double room with its own living and dining area and mini balcony.', 'room-junior.webp', [['1 guest', '$100'], ['2 guests', '$120']]],
    ['Twin Room', 'Two separate beds and a dedicated working desk, made for travelling together.', 'room-twin.webp', [['Per night', '$80']]],
    ['Two Bedrooms in One', 'A spacious room with a separate smaller bedroom, suitable for up to three guests.', 'room-family-compact.webp', [['1 guest', '$70'], ['2 guests', '$90']]],
    ['Family Room', 'Two standard rooms connected together, giving everyone more room to settle in.', 'room-family.webp', [['Per night', '$110']]],
];
$gallery = [
    ['Reception', 'gallery-reception.webp'], ['Rooms', 'gallery-rooms.webp'], ['Breakfast buffet', 'gallery-buffet.webp'],
    ['The hotel by day', 'gallery-daytime.webp'], ['Conference room', 'gallery-conference.webp'], ['Restaurant', 'gallery-restaurant.webp'],
    ['Reception & lobby', 'gallery-lobby.webp'], ['Mini bar', 'gallery-minibar.webp'], ['The hotel at night', 'gallery-night.webp'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Stay at KABC Hotel in Kigali. Explore our rooms, facilities, dining and room rates, with breakfast included.">
    <meta name="theme-color" content="#18261e">
    <title>KABC Hotel | A considered stay in Kigali</title>
    <link rel="icon" type="image/png" href="./assets/images/icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css?v=4">
</head>
<body>
    <header class="site-header">
        <a class="site-brand" href="#home" aria-label="KABC Hotel home"><img src="assets/images/icon.png" alt=""><span>KABC <strong>HOTEL</strong></span></a>
        <nav class="site-nav" aria-label="Main navigation">
            <a href="#stay">Rooms</a><a href="#experience">The experience</a><a href="#gallery">Gallery</a>
            <a class="nav-menu-link" href="full_menu">Explore the menu <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
            <button class="nav-login-button" type="button" data-login-open>Admin sign in</button>
        </nav>
    </header>

    <main id="home">
        <section class="hero" aria-labelledby="hero-title">
            <img class="hero-photo" src="assets/hotel/gallery-daytime.webp" alt="KABC Hotel in Kigali" fetchpriority="high">
            <div class="hero-shade" aria-hidden="true"></div>
            <div class="hero-content">
                <p class="eyebrow"><span></span> KIGALI CITY · RWANDA</p>
                <h1 id="hero-title">Make Kigali<br><em>feel like yours.</em></h1>
                <p class="hero-copy">Settle into a comfortable room, enjoy breakfast on us, and explore the city from KABC Hotel.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="mailto:reservations@kabchotel.rw?subject=Room%20reservation">Enquire about a stay <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a class="text-link" href="#stay">Discover KABC <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
                </div>
                <div class="hero-note"><span class="note-rule"></span><span>A comfortable base in the heart of Kigali</span></div>
            </div>
            <div class="hero-index" aria-hidden="true"><span>01</span> / YOUR KIGALI STAY</div>
        </section>

        <section class="quick-facts" aria-label="KABC Hotel at a glance">
            <div><span class="fact-label">FIND US</span><strong>Kigali City, Rwanda</strong><a href="https://maps.google.com/?q=Kigali+City+6+KN+84+st" target="_blank" rel="noopener noreferrer">6 KN 84 st <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
            <div><span class="fact-label">ROOMS FROM</span><strong>$60 <small>/ night</small></strong><a href="#stay">See rooms & rates <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
            <div><span class="fact-label">INCLUDED WITH EVERY ROOM</span><strong>Breakfast & Wi-Fi</strong><span class="fact-note">Plus air conditioning and room service</span></div>
            <div><span class="fact-label">SPEAK TO OUR TEAM</span><strong>+250 794 095 885</strong><a href="tel:+250794095885">Call KABC Hotel <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
        </section>

        <section class="experience" id="experience" aria-labelledby="experience-title">
            <div class="experience-heading"><p class="eyebrow eyebrow-dark"><span></span> ABOUT KABC</p><h2 id="experience-title">Where hospitality<br>meets <em>the details.</em></h2></div>
            <div class="experience-copy"><p>At KABC Hotel, a warm welcome is just the beginning. From a seamless check-in to thoughtful service throughout your stay, our team is here to make every moment feel easy.</p><p>Settle into a calm, comfortable space and enjoy Kigali at your own pace. Whether you’re here for work or time away, we’ll help make it a stay to remember.</p><a class="text-link text-link-dark" href="#stay">Find your room <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
            <div class="experience-mark" aria-label="KABC Hotel in Kigali"><span>MADE FOR YOUR COMFORT</span><span class="mark-dot">·</span><span>KIGALI, RWANDA</span><span class="mark-dot">·</span><span>KABC HOTEL</span></div>
        </section>

        <section class="rooms-section" id="stay" aria-labelledby="rooms-title">
            <div class="section-heading"><div><p class="eyebrow eyebrow-dark"><span></span> FIND YOUR SPACE</p><h2 id="rooms-title">Rooms for the way<br>you <em>like to travel.</em></h2></div><p class="section-intro">Every room includes breakfast, Wi-Fi, air conditioning, a flat-screen TV, mini bar and room service.</p></div>
            <div class="room-grid">
                <?php foreach ($rooms as $room): ?>
                    <article class="room-card">
                        <div class="room-image"><img src="assets/hotel/<?= htmlspecialchars($room[2], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($room[0], ENT_QUOTES, 'UTF-8') ?> at KABC Hotel" loading="lazy"><span class="room-tag">Breakfast included</span></div>
                        <div class="room-info"><h3><?= htmlspecialchars($room[0], ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($room[1], ENT_QUOTES, 'UTF-8') ?></p><div class="room-rates"><?php foreach ($room[3] as $rate): ?><span><?= htmlspecialchars($rate[0], ENT_QUOTES, 'UTF-8') ?><strong><?= htmlspecialchars($rate[1], ENT_QUOTES, 'UTF-8') ?><small> / night</small></strong></span><?php endforeach; ?></div><a href="mailto:reservations@kabchotel.rw?subject=<?= rawurlencode('Reservation enquiry: ' . $room[0]) ?>" class="room-link">Enquire about this room <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="rates-note">Rates shown are from KABC Hotel’s published room rates. Please contact reservations to confirm availability and current pricing.</p>
        </section>

        <section class="menu-invitation" aria-labelledby="menu-title">
            <div class="menu-image"><img src="assets/hotel/gallery-buffet.webp" alt="Breakfast buffet at KABC Hotel" loading="lazy"></div>
            <div class="menu-copy"><p class="eyebrow"><span></span> A TASTE OF KABC</p><h2 id="menu-title">Make time for<br><em>something delicious.</em></h2><p>From breakfast to beverages, discover the dishes and drinks waiting for you at KABC Hotel.</p><a class="button button-light" href="full_menu">Explore our menu <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
        </section>

        <section class="features-section" aria-labelledby="features-title">
            <div class="section-heading"><div><p class="eyebrow eyebrow-dark"><span></span> THE KABC EXPERIENCE</p><h2 id="features-title">The little things<br>make <em>the difference.</em></h2></div><p class="section-intro">A comfortable base in Kigali, with thoughtful services to make your stay smoother.</p></div>
            <div class="feature-grid">
                <article class="feature-card"><span class="feature-icon"><i class="fa-solid fa-star"></i></span><h3>Thoughtful hospitality</h3><p>A welcoming team ready to help make your stay comfortable.</p></article>
                <article class="feature-card"><span class="feature-icon"><i class="fa-solid fa-moon"></i></span><h3>Restful, quiet rooms</h3><p>A calm place to unwind after a day in the city.</p></article>
                <article class="feature-card"><span class="feature-icon"><i class="fa-solid fa-location-dot"></i></span><h3>In the heart of Kigali</h3><p>Find us at 6 KN 84 Street, Kigali City.</p></article>
                <article class="feature-card"><span class="feature-icon"><i class="fa-solid fa-utensils"></i></span><h3>Breakfast included</h3><p>Start each morning with breakfast, included in every listed rate.</p></article>
                <article class="feature-card"><span class="feature-icon"><i class="fa-solid fa-bell-concierge"></i></span><h3>Here when you need us</h3><p>24/7 front desk, room service, concierge and customer support.</p></article>
                <article class="feature-card"><span class="feature-icon"><i class="fa-solid fa-shirt"></i></span><h3>More ways to feel at home</h3><p>Enjoy laundry service and the comforts of your own room.</p></article>
            </div>
            <div class="amenities-strip"><span>Free Wi-Fi</span><i>·</i><span>Air conditioning</span><i>·</i><span>Flat-screen TV</span><i>·</i><span>Mini bar</span><i>·</i><span>Room service</span></div>
        </section>

        <section class="gallery-section" id="gallery" aria-labelledby="gallery-title">
            <div class="section-heading"><div><p class="eyebrow eyebrow-dark"><span></span> A CLOSER LOOK</p><h2 id="gallery-title">A glimpse of <em>KABC.</em></h2></div><p class="section-intro">From a warm welcome to a relaxed evening, take a look around.</p></div>
            <div class="gallery-grid"><?php foreach ($gallery as $index => $image): ?><figure class="gallery-item gallery-item-<?= $index + 1 ?>"><img src="assets/hotel/<?= htmlspecialchars($image[1], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($image[0], ENT_QUOTES, 'UTF-8') ?> at KABC Hotel" loading="lazy"><figcaption><?= htmlspecialchars($image[0], ENT_QUOTES, 'UTF-8') ?></figcaption></figure><?php endforeach; ?></div>
        </section>

        <section class="contact-section" id="contact" aria-labelledby="contact-title"><div><p class="eyebrow"><span></span> WE’D LOVE TO WELCOME YOU</p><h2 id="contact-title">Your Kigali stay<br>starts <em>here.</em></h2><p>Talk to our team about a room, a meal or anything that will make your visit more comfortable.</p></div><div class="contact-actions"><a class="button button-primary" href="mailto:reservations@kabchotel.rw?subject=Room%20reservation">Contact reservations <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><a href="tel:+250794095885">Call +250 794 095 885</a><a href="https://maps.google.com/?q=Kigali+City+6+KN+84+st" target="_blank" rel="noopener noreferrer">Kigali City 6 KN 84 st <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div></section>
    </main>

    <dialog class="login-dialog" id="adminLoginDialog" aria-labelledby="loginDialogTitle"><div class="login-dialog-content"><button class="login-dialog-close" type="button" data-login-close aria-label="Close sign-in dialog">&times;</button><p class="eyebrow eyebrow-dark"><span></span> KABC HOTEL ADMIN</p><h2 id="loginDialogTitle">Welcome back.</h2><p class="login-dialog-copy">Sign in to manage menu items and images.</p><form class="landing-login-form" action="admin/" method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($landingCsrfToken, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="login"><label for="landingUsername">Username</label><input id="landingUsername" name="username" type="text" minlength="3" maxlength="64" placeholder="Enter username" autocomplete="username" required><label for="landingPassword">Password</label><input id="landingPassword" name="password" type="password" placeholder="Enter password" autocomplete="current-password" required><button class="button button-primary" type="submit">Sign in <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button></form><a class="login-recovery-link" href="admin/">Forgot your password?</a></div></dialog>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <script src="assets/js/landing.js" defer></script>
    <script src="assets/js/password_visibility.js?v=1" defer></script>
</body>
</html>
