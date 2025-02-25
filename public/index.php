<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Component imports
require_once __DIR__ . '/Components/Header/Header.php';
require_once __DIR__ . '/Components/Hero/Hero.php';
require_once __DIR__ . '/Components/FeaturedCars/FeaturedCars.php';
require_once __DIR__ . '/Components/AboutUs/AboutUs.php';
require_once __DIR__ . '/Components/Testimonials/Testimonials.php';
require_once __DIR__ . '/Components/TrustedBrands/TrustedBrands.php';
require_once __DIR__ . '/Components/Contact/Contact.php';
require_once __DIR__ . '/Components/Footer/Footer.php';

// Required classes for FeaturedCars
require_once __DIR__ . '/../src/Classes/Cars/CarListing.php';
require_once __DIR__ . '/../src/Classes/Cars/SearchFilter.php';
require_once __DIR__ . '/../src/Classes/User/Favorites.php';
require_once __DIR__ . '/../src/Classes/Cars/ImageUploader.php';

use Classes\Auth\Session;
use Classes\Language\TranslationManager;
use Components\Header\Header;
use Components\Hero\Hero;
use Components\FeaturedCars\FeaturedCars;
use Components\AboutUs\AboutUs;
use Components\Testimonials\Testimonials;
use Components\TrustedBrands\TrustedBrands;
use Components\Contact\Contact;
// Initialize session and get translation manager
$session = Session::getInstance()->start();
$translationManager = TranslationManager::getInstance();
$currentLocale = $translationManager->getLocale();
$isAuthenticated = $session->get('user_id') ? true : false;

// Initialize components
$header = new Header();
$hero = new Hero();
$featuredCars = new FeaturedCars();
$aboutUs = new AboutUs();
$testimonials = new Testimonials();
$trustedBrands = new TrustedBrands();
$contact = new Contact();
$footer = new Footer();

// Get current page
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLocale; ?>" data-theme="carmarket">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo __('common.meta.description'); ?>">
    <title><?php echo __('common.meta.title'); ?></title>
    
    <!-- Styles -->
    <link href="/car-project/public/css/dist/styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link href="/car-project/public/css/components/trusted-brands.css" rel="stylesheet">
    <link href="/car-project/public/css/components/footer.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Animation Libraries -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollToPlugin.min.js"></script>
    <script src="https://unpkg.com/vanilla-tilt@1.7.0/dist/vanilla-tilt.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <!-- Make authentication status available to JavaScript -->
    <script>
        const isAuthenticated = <?php echo $isAuthenticated ? 'true' : 'false'; ?>;
        const baseUrl = '<?php echo '/car-project/public'; ?>';
        const currentLocale = '<?php echo $currentLocale; ?>';
    </script>
</head>
<body class="min-h-screen bg-base-200">
    <!-- Header -->
    <?php echo $header->render(); ?>
    
    <!-- Main Content -->
    <main>
        <?php echo $hero->render(); ?>
        <?php echo $featuredCars->render(); ?>
        <?php echo $aboutUs->render(); ?>
        <?php echo $testimonials->render(); ?>
        <?php echo $contact->render(); ?>
    </main>

    <!-- Footer -->
    <?php echo $footer->render(); ?>

    <!-- Scripts -->
    <script src="/car-project/public/js/components/featured-cars.js"></script>
    <script src="/car-project/public/js/components/hero-search.js"></script>
    <script src="/car-project/public/js/components/about-us.js"></script>
    <script src="/car-project/public/js/components/testimonials.js"></script>
    <script src="/car-project/public/js/components/trusted-brands.js"></script>
    <script src="/car-project/public/js/components/contact.js"></script>
    <script src="/car-project/public/js/components/newsletter.js"></script>
    <!-- Only include search-filter.js on search pages -->
    <?php if ($page === 'search'): ?>
    <script src="/car-project/public/js/components/search-filter.js"></script>
    <?php endif; ?>
    
    <!-- Initialize AOS -->
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    </script>
</body>
</html>