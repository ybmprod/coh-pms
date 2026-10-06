<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <main class="container narrow">
        <section class="card error-box">
            <h1>404</h1>
            <p>The page you requested could not be found.</p>
            <a class="button primary" href="<?php echo e(BASE_URL); ?>">Return home</a>
        </section>
    </main>
</body>
</html>
