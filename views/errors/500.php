<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>500 - Server Error</title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <main class="container narrow">
        <section class="card error-box">
            <h1>500</h1>
            <p>The system could not complete your request. Please try again later.</p>
            <a class="button primary" href="<?php echo e(BASE_URL . '/'); ?>">Return home</a>
        </section>
    </main>
</body>
</html>
