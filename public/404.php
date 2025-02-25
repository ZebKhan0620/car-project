<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/Components/Header/Header.php';

use Components\Header\Header;
use Classes\Language\TranslationManager;

$header = new Header();
$translationManager = TranslationManager::getInstance();
?>
<!DOCTYPE html>
<html lang="<?php echo $translationManager->getLocale(); ?>" data-theme="carmarket">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('auth.errors.404.title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200">
    <?php echo $header->render(); ?>
    <div class="container mx-auto px-4 py-16 text-center">
        <h1 class="text-4xl font-bold mb-4"><?php echo __('auth.errors.404.heading'); ?></h1>
        <p class="mb-8"><?php echo __('auth.errors.404.message'); ?></p>
        <a href="<?php echo BASE_URL; ?>" class="btn btn-primary"><?php echo __('auth.errors.404.back_home'); ?></a>
        <a href="<?php echo LOGIN_PATH; ?>" class="btn btn-primary"><?php echo __('auth.login.submit_button'); ?></a>
    </div>
</body>
</html> 