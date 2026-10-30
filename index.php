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
$contactFormValues = ['name' => '', 'email' => '', 'phone' => '', 'room' => '', 'arrival' => '', 'departure' => '', 'message' => ''];
$contactFormError = '';
$contactFormSent = isset($_GET['contact']) && $_GET['contact'] === 'sent';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'contact_enquiry') {
    $readContactField = static function (string $key): string {
        return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
    };
    foreach ($contactFormValues as $key => $_value) {
        $contactFormValues[$key] = $readContactField($key);
    }

    if (!adminVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $contactFormError = 'Your form session expired. Please refresh the page and try again.';
    } elseif ($readContactField('website') !== '') {
        header('Location: ./?contact=sent#contact');
        exit;
    } elseif (isset($_SESSION['last_contact_enquiry']) && time() - (int) $_SESSION['last_contact_enquiry'] < 30) {
        $contactFormError = 'Please wait a moment before sending another enquiry.';
    } elseif ($contactFormValues['name'] === '' || mb_strlen($contactFormValues['name']) > 120) {
        $contactFormError = 'Please enter your name (up to 120 characters).';
    } elseif ($contactFormValues['email'] !== '' && filter_var($contactFormValues['email'], FILTER_VALIDATE_EMAIL) === false) {
        $contactFormError = 'Please enter a valid email address.';
    } elseif (mb_strlen($contactFormValues['email']) > 254 || mb_strlen($contactFormValues['phone']) > 40) {
        $contactFormError = 'Please check your email or phone number and try again.';
    } elseif ($contactFormValues['email'] === '' && $contactFormValues['phone'] === '') {
        $contactFormError = 'Please provide an email address or phone number so reservations can reach you.';
    } elseif ($contactFormValues['room'] !== '' && !in_array($contactFormValues['room'], array_column($rooms, 0), true)) {
        $contactFormError = 'Please choose a room from the list.';
    } elseif (mb_strlen($contactFormValues['message']) > 2000) {
        $contactFormError = 'Please keep your message under 2,000 characters.';
    } else {
        $parseContactDate = static function (string $value) {
            if ($value === '') {
                return null;
            }
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            $errors = DateTimeImmutable::getLastErrors();
            return $date && ($errors === false || (!$errors['warning_count'] && !$errors['error_count'])) ? $date : false;
        };
        $arrivalDate = $parseContactDate($contactFormValues['arrival']);
        $departureDate = $parseContactDate($contactFormValues['departure']);
        $invalidArrival = $contactFormValues['arrival'] !== '' && $arrivalDate === false;
        $invalidDeparture = $contactFormValues['departure'] !== '' && $departureDate === false;

        if ($invalidArrival || $invalidDeparture) {
            $contactFormError = 'Please enter valid arrival and departure dates.';
        } elseif (($arrivalDate && $arrivalDate < new DateTimeImmutable('today')) || ($departureDate && $departureDate < new DateTimeImmutable('today'))) {
            $contactFormError = 'Please choose dates that are today or later.';
        } elseif ($arrivalDate && $departureDate && $departureDate <= $arrivalDate) {
            $contactFormError = 'Departure must be after arrival.';
        } else {
            try {
                require_once __DIR__ . '/includes/contact_enquiry_mailer.php';
                sendKabcRoomEnquiry($contactFormValues);
                $_SESSION['last_contact_enquiry'] = time();
                header('Location: ./?contact=sent#contact');
                exit;
            } catch (Throwable $exception) {
                error_log('KABC room enquiry could not be sent: ' . $exception->getMessage());
                $contactFormError = 'We couldn’t send your enquiry just now. Please call +250 794 095 885 or email reservations@kabchotel.rw.';
            }
        }
    }
}
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
            <div class="hero-photo-caption" aria-hidden="true"><span>KIGALI CITY · RWANDA</span><strong>A place to settle in</strong></div>
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

        <section class="contact-section" id="contact" aria-labelledby="contact-title">
            <div class="contact-heading">
                <p class="eyebrow eyebrow-dark"><span></span> LET'S PLAN YOUR STAY</p>
                <h2 id="contact-title">A room in Kigali<br>is just <em>a message away.</em></h2>
                <p>Tell us a little about your visit. Our reservations team will follow up to confirm availability and rates.</p>
                <a class="contact-direct-link" href="tel:+250794095885"><i class="fa-solid fa-phone" aria-hidden="true"></i> +250 794 095 885</a>
                <a class="contact-direct-link" href="mailto:reservations@kabchotel.rw"><i class="fa-solid fa-envelope" aria-hidden="true"></i> reservations@kabchotel.rw</a>
            </div>
            <div class="contact-grid">
                <div class="enquiry-panel">
                    <?php if ($contactFormSent): ?>
                        <div class="form-feedback form-feedback-success" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Thank you. Your enquiry has been sent to our reservations team.</div>
                    <?php elseif ($contactFormError !== ''): ?>
                        <div class="form-feedback form-feedback-error" role="alert"><?= htmlspecialchars($contactFormError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <form class="contact-form" action="#contact" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($landingCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="form_action" value="contact_enquiry">
                        <div class="contact-honey" aria-hidden="true"><label for="contactWebsite">Leave this field empty</label><input id="contactWebsite" type="text" name="website" tabindex="-1" autocomplete="off"></div>
                        <div class="form-row">
                            <div class="form-field"><label for="contactName">Your name <span>*</span></label><input id="contactName" name="name" type="text" maxlength="120" autocomplete="name" value="<?= htmlspecialchars($contactFormValues['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required></div>
                            <div class="form-field"><label for="contactRoom">Room preference</label><select id="contactRoom" name="room"><option value="">Choose a room (optional)</option><?php foreach ($rooms as $room): ?><option value="<?= htmlspecialchars($room[0], ENT_QUOTES, 'UTF-8') ?>" <?= $contactFormValues['room'] === $room[0] ? 'selected' : '' ?>><?= htmlspecialchars($room[0], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div class="form-row">
                            <div class="form-field"><label for="contactEmail">Email</label><input id="contactEmail" name="email" type="email" maxlength="254" autocomplete="email" value="<?= htmlspecialchars($contactFormValues['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="you@example.com"></div>
                            <div class="form-field"><label for="contactPhone">Phone</label><input id="contactPhone" name="phone" type="tel" maxlength="40" autocomplete="tel" value="<?= htmlspecialchars($contactFormValues['phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="+250 ..."></div>
                        </div>
                        <p class="contact-form-hint">Please provide an email address or phone number.</p>
                        <div class="form-row">
                            <div class="form-field"><label for="contactArrival">Arrival</label><input id="contactArrival" name="arrival" type="date" value="<?= htmlspecialchars($contactFormValues['arrival'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="form-field"><label for="contactDeparture">Departure</label><input id="contactDeparture" name="departure" type="date" value="<?= htmlspecialchars($contactFormValues['departure'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                        <div class="form-field"><label for="contactMessage">Message <span class="optional-label">Optional</span></label><textarea id="contactMessage" name="message" rows="3" maxlength="2000" placeholder="Anything else we should know?"><?= htmlspecialchars($contactFormValues['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea></div>
                        <button class="button button-primary contact-submit" type="submit">Send enquiry <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                    </form>
                </div>
                <aside class="map-panel" aria-label="KABC Hotel location">
                    <iframe title="Map showing KABC Hotel in Kigali" src="https://maps.google.com/maps?q=Kigali%20City%206%20KN%2084%20st&t=&z=15&ie=UTF8&iwloc=&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    <div class="map-details"><span class="map-pin"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span><div><strong>KABC Hotel</strong><span>Kigali City 6 KN 84 st, Rwanda</span></div><a href="https://maps.google.com/?q=Kigali+City+6+KN+84+st" target="_blank" rel="noopener noreferrer" aria-label="Open KABC Hotel in Google Maps"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
                </aside>
            </div>
        </section>
    </main>

    <dialog class="login-dialog" id="adminLoginDialog" aria-labelledby="loginDialogTitle"><div class="login-dialog-content"><button class="login-dialog-close" type="button" data-login-close aria-label="Close sign-in dialog">&times;</button><p class="eyebrow eyebrow-dark"><span></span> KABC HOTEL ADMIN</p><h2 id="loginDialogTitle">Welcome back.</h2><p class="login-dialog-copy">Sign in to manage menu items and images.</p><form class="landing-login-form" action="admin/" method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($landingCsrfToken, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="login"><label for="landingUsername">Username</label><input id="landingUsername" name="username" type="text" minlength="3" maxlength="64" placeholder="Enter username" autocomplete="username" required><label for="landingPassword">Password</label><input id="landingPassword" name="password" type="password" placeholder="Enter password" autocomplete="current-password" required><button class="button button-primary" type="submit">Sign in <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button></form><a class="login-recovery-link" href="admin/">Forgot your password?</a></div></dialog>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <script src="assets/js/landing.js" defer></script>
    <script src="assets/js/password_visibility.js?v=1" defer></script>
</body>
</html>
