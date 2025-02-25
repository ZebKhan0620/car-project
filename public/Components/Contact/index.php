<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/Contact.php';
require_once __DIR__ . '/../../../src/Classes/Language/TranslationManager.php';
require_once __DIR__ . '/../Header/Header.php';

use Components\Contact\Contact;
use Classes\Language\TranslationManager;
use Components\Header\Header;

// Get the current locale
$translationManager = TranslationManager::getInstance();

// Handle locale from URL or session
if (isset($_GET['locale'])) {
    $translationManager->setLocale($_GET['locale']);
    $_SESSION['locale'] = $_GET['locale'];
} elseif (isset($_SESSION['locale'])) {
    $translationManager->setLocale($_SESSION['locale']);
}

$locale = $translationManager->getLocale();
?>
<!DOCTYPE html>
<html lang="<?php echo $locale; ?>" data-theme="carmarket">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('contact.title'); ?></title>
    
    <!-- DaisyUI and Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Library for animations -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    
    <!-- Custom styles -->
    <style>
        /* Add any custom styles here */
        .business-status-badge {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="min-h-screen bg-base-200">
    <?php
    try {
        $header = new Header();
        echo $header->render();

        // Add scheduling section before contact form
        require_once __DIR__ . '/schedule-view.php';

        // Initialize and render the contact component
        $contact = new Contact();
        echo $contact->render();

    } catch (\Exception $e) {
        // Log the error
        error_log("Contact form error: " . $e->getMessage());
        
        // Show user-friendly error message
        echo '<div class="container mx-auto px-4 py-8">';
        echo '<div class="alert alert-error shadow-lg">';
        echo '<div>';
        echo '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">';
        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />';
        echo '</svg>';
        echo '<span>Sorry, there was a problem loading the contact form. Please try again later.</span>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    ?>

    <!-- AOS Library Script -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });
    </script>

    <!-- Contact Form Scripts -->
    <script src="/car-project/public/js/components/contact.js"></script>
</body>
</html>