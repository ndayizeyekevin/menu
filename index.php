<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover the KABC Hotel menu. Good food, warm company, and a table ready for you.">
    <meta name="theme-color" content="#102c24">
    <title>KABC Hotel | Gather well. Eat joyfully.</title>
    <link rel="icon" type="image/png" href="img/icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>
    <header class="site-header">
        <a class="site-brand" href="./" aria-label="KABC Hotel home">
            <img src="img/icon.png" alt="">
            <span>KABC <strong>HOTEL</strong></span>
        </a>
        <nav class="site-nav" aria-label="Main navigation">
            <a href="#experience">Our table</a>
            <a class="nav-menu-link" href="full_menu">Explore the menu <span aria-hidden="true">↗</span></a>
        </nav>
    </header>

    <main>
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-art" aria-hidden="true">
                <div class="hero-orbit orbit-one"></div>
                <div class="hero-orbit orbit-two"></div>
                <div class="hero-sun"></div>
                <div class="hero-horizon"></div>
            </div>
            <div class="hero-content">
                <p class="eyebrow"><span></span> KABC HOTEL</p>
                <h1 id="hero-title">Gather well.<br><em>Eat joyfully.</em></h1>
                <p class="hero-copy">Good food has a way of bringing us closer. Find your next favourite on the KABC Hotel menu.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="full_menu">Explore our menu <span aria-hidden="true">→</span></a>
                    <a class="text-link" href="#experience">A little about us <span aria-hidden="true">↓</span></a>
                </div>
                <div class="hero-note">
                    <span class="note-rule"></span>
                    <span>A good meal is even better together.</span>
                </div>
            </div>
            <div class="hero-index" aria-hidden="true"><span>01</span> / THE KABC TABLE</div>
        </section>

        <section class="experience" id="experience" aria-labelledby="experience-title">
            <div class="experience-heading">
                <p class="eyebrow eyebrow-dark"><span></span> COME AS YOU ARE</p>
                <h2 id="experience-title">Make room for<br><em>something good.</em></h2>
            </div>
            <div class="experience-copy">
                <p>From the first look at the menu to the last bite, we believe the best moments are the ones we share.</p>
                <a class="text-link text-link-dark" href="full_menu">Find something to love <span aria-hidden="true">→</span></a>
            </div>
            <div class="experience-mark" aria-hidden="true">
                <span>GOOD FOOD</span>
                <span class="mark-dot">·</span>
                <span>GOOD COMPANY</span>
                <span class="mark-dot">·</span>
                <span>KABC HOTEL</span>
            </div>
        </section>

        <section class="menu-invitation" aria-labelledby="menu-title">
            <div>
                <p class="eyebrow"><span></span> YOUR NEXT FAVOURITE</p>
                <h2 id="menu-title">Something for<br>every kind of craving.</h2>
            </div>
            <a class="button button-light" href="full_menu">See the full menu <span aria-hidden="true">↗</span></a>
            <span class="invitation-decoration" aria-hidden="true">K</span>
        </section>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
