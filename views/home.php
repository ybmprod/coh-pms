<?php
declare(strict_types=1);

$flashMessage = $flash ?? null;
$loggedIn = is_logged_in('customer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>

    <?php if ($flashMessage): ?>
        <div class="flash flash-<?php echo e($flashMessage['type']); ?>" role="alert">
            <?php echo e($flashMessage['message']); ?>
        </div>
    <?php endif; ?>

    <main id="main-content" class="container home-main">
        <section class="hero" aria-labelledby="hero-heading">
            <div class="hero-grid">
                <div>
                    <span class="hero-eyebrow">City of Harare venue bookings</span>
                    <h1 id="hero-heading">Find the right venue for your next event.</h1>
                    <p>
                        Browse community halls, centres, stadiums and open spaces. Check availability,
                        see the configurable charge before you book, and follow your request through to payment verification.
                    </p>
                    <div class="button-row">
                        <?php if ($loggedIn): ?>
                            <a href="<?php echo e(BASE_URL . '/?r=dashboard/customer'); ?>" class="button primary">
                                Go to my dashboard
                            </a>
                            <a href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>" class="button secondary">
                                Browse venues
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(BASE_URL . '/?r=auth/register'); ?>" class="button primary">
                                Create an account
                            </a>
                            <a href="<?php echo e(BASE_URL . '/?r=auth/login'); ?>" class="button secondary">
                                Log in
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="hero-aside" aria-label="Facility categories">
                    <h2>Municipal Properties</h2>
                    <ul class="hero-aside-list">
                        <li><span>Community Halls &amp; Centres</span><strong>Civic Events</strong></li>
                        <li><span>Municipal Stadia</span><strong>Sports &amp; Rallies</strong></li>
                        <li><span>Public Open Spaces</span><strong>Exhibitions</strong></li>
                        <li><span>Transparent Tariff Rules</span><strong>Instant Quote</strong></li>
                    </ul>
                </aside>
            </div>
        </section>

        <section class="home-steps" aria-labelledby="steps-title">
            <header class="section-heading">
                <div>
                    <span class="eyebrow">A simple process</span>
                    <h2 id="steps-title">Book with confidence</h2>
                </div>
            </header>

            <div class="asymmetric-steps-grid">
                <article class="card step-card step-card--featured">
                    <div>
                        <span class="step-number" aria-hidden="true">01</span>
                        <h3>Choose a venue and time</h3>
                        <p>Compare capacity and facilities across Harare municipal wards, then select your date and event times with real-time conflict checking.</p>
                    </div>
                </article>

                <article class="card step-card">
                    <div>
                        <span class="step-number" aria-hidden="true">02</span>
                        <h3>See the price upfront</h3>
                        <p>Availability and applicable pricing rules are checked before you submit.</p>
                    </div>
                </article>

                <article class="card step-card">
                    <div>
                        <span class="step-number" aria-hidden="true">03</span>
                        <h3>Pay and receive verification</h3>
                        <p>After approval, submit payment details to receive your payment verification reference.</p>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
