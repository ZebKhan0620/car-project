<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Classes\Auth\Session;
use Services\SessionService;
use Classes\Language\TranslationManager;

// Initialize session
$session = Session::getInstance()->start();
$sessionService = new SessionService();
$translationManager = TranslationManager::getInstance();

// Require authentication
$sessionService->requireAuth();
$userId = $session->get('user_id');

// Read favorites from JSON file
$favoritesFile = __DIR__ . '/../../../data/favorites.json';
$favorites = [];

if (file_exists($favoritesFile)) {
    $favoritesData = json_decode(file_get_contents($favoritesFile), true);
    $favorites = $favoritesData['items'] ?? [];
    
    // Filter favorites for current user
    $favorites = array_filter($favorites, function($fav) use ($userId) {
        return $fav['user_id'] == $userId;
    });
}

// Get car details for each favorite
$carListings = [];
if (!empty($favorites)) {
    $carListingsFile = __DIR__ . '/../../../data/car_listings.json';
    if (file_exists($carListingsFile)) {
        $allListings = json_decode(file_get_contents($carListingsFile), true)['items'] ?? [];
        foreach ($favorites as $favorite) {
            foreach ($allListings as $listing) {
                if ($listing['id'] === $favorite['car_id']) {
                    // Merge favorite metadata with car listing
                    $listing['favorited_at'] = $favorite['favorited_at'];
                    $carListings[] = $listing;
                    break;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translationManager->getLocale(); ?>" data-theme="carmarket">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('cars.favorites'); ?> - <?php echo __('common.welcome'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Pass favorite data to JavaScript
        window.favorites = <?php echo json_encode(array_column($favorites, 'car_id')); ?>;
    </script>
    <script src="/car-project/public/js/components/favorites.js"></script>
</head>
<body class="min-h-screen bg-base-200">
    <!-- Breadcrumb -->
    <div class="bg-base-100 border-b">
        <div class="container mx-auto px-4 py-3">
            <div class="text-sm breadcrumbs">
                <ul>
                    <li><a href="/car-project/public/index.php"><?php echo __('common.home'); ?></a></li>
                    <li><a href="/car-project/public/Components/Cars/index.php"><?php echo __('cars.search'); ?></a></li>
                    <li><?php echo __('cars.favorites'); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8"><?php echo __('cars.favorites'); ?></h1>

        <?php if (empty($carListings)): ?>
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body text-center py-16">
                    <svg class="mx-auto h-16 w-16 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold"><?php echo __('cars.favorites_empty.title'); ?></h3>
                    <p class="mt-2 text-base-content/70"><?php echo __('cars.favorites_empty.description'); ?></p>
                    <div class="mt-6">
                        <a href="index.php" class="btn btn-primary"><?php echo __('cars.browse_cars'); ?></a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($carListings as $car): ?>
                    <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow">
                        <!-- Car Image -->
                        <figure class="px-4 pt-4">
                            <img src="/car-project/public/uploads/car_images/<?php echo htmlspecialchars($car['images'][0] ?? 'default.jpg'); ?>" 
                                 alt="<?php echo __('cars.gallery_image.title') . ': ' . htmlspecialchars($car['title']); ?>"
                                 class="rounded-xl h-48 w-full object-cover" />
                        </figure>
                        
                        <div class="card-body">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h2 class="card-title"><?php echo htmlspecialchars($car['title']); ?></h2>
                                    <p class="text-accent font-bold">$<?php echo number_format($car['price'], 2); ?></p>
                                </div>
                                <button onclick="toggleFavorite('<?php echo $car['id']; ?>')"
                                        class="btn btn-circle btn-ghost favorite-btn"
                                        data-listing-id="<?php echo $car['id']; ?>"
                                        title="<?php echo __('cars.favorites'); ?>">
                                    <svg class="w-6 h-6 text-red-500 fill-current" 
                                         viewBox="0 0 24 24">
                                        <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Car Details -->
                            <div class="flex gap-2 my-2">
                                <div class="badge badge-outline"><?php echo __("cars.transmissions.{$car['transmission']}"); ?></div>
                                <div class="badge badge-outline"><?php echo __("cars.fuel_types.{$car['fuel_type']}"); ?></div>
                            </div>

                            <!-- Added Date -->
                            <div class="text-sm text-base-content/70 mt-2">
                                <?php echo __('cars.favorites_added_date'); ?>: <?php 
                                    echo $car['favorited_at'] ? 
                                        date(__('common.date_format'), strtotime($car['favorited_at'])) : 
                                        __('cars.favorites_added_recently'); 
                                ?>
                            </div>

                            <div class="flex justify-between items-center mt-4">
                                <a href="view.php?id=<?php echo $car['id']; ?>" class="btn btn-primary"><?php echo __('cars.view_full_details'); ?></a>
                                <span class="text-sm text-base-content/70 capitalize">
                                    <?php echo __("cars.locations.{$car['location']}"); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="/car-project/public/js/components/favorites.js"></script>
</body>
</html>