<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Auth/Session.php';
require_once __DIR__ . '/../../../src/Services/SessionService.php';
require_once __DIR__ . '/../../../src/Classes/Cars/CarListing.php';
require_once __DIR__ . '/../../../src/Classes/Cars/SearchFilter.php';
require_once __DIR__ . '/../../../src/User/Favorites.php';
require_once __DIR__ . '/../Header/Header.php';

use Components\Header\Header;
use Classes\Auth\Session;
use Services\SessionService;
use Classes\Cars\CarListing;
use Classes\Cars\SearchFilter;
use User\Favorites;
use Classes\Language\TranslationManager;

$session = Session::getInstance()->start();
$sessionService = new SessionService();
$translationManager = TranslationManager::getInstance();

// Require authentication
$sessionService->requireAuth();
$userId = $session->get('user_id');

// Fetch car listings
$carListing = new CarListing();
$listings = $carListing->getAll();

// Get search query from quick search
$query = $_GET['q'] ?? '';

// Initialize search filter
$searchFilter = new SearchFilter();

// Get filtered results
$results = $searchFilter->filter([
    'q' => $query,
    'sort' => 'latest'
]);

// Get total count
$totalResults = count($results);

// Get user's favorites
$favorites = new Favorites();
$userFavorites = $favorites->getFavorites($userId);
$favoritedListingIds = array_column($userFavorites, 'car_id');
?>

<!DOCTYPE html>
<html lang="<?php echo $translationManager->getLocale(); ?>" data-theme="carmarket">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('cars.search'); ?> - <?php echo __('common.welcome'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200">
    <?php
    $header = new Header();
    echo $header->render();
    ?>

    <!-- Quick Search Section -->
    <div class="bg-base-100 border-b">
        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <form id="quickSearch" class="flex flex-wrap gap-4 items-end">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text"><?php echo __('cars.featured_cars.filters.brand'); ?></span>
                        </label>
                        <select name="brand" class="select select-bordered w-full min-w-[200px]">
                            <option value=""><?php echo __('cars.featured_cars.dropdowns.all_brands'); ?></option>
                            <?php foreach ($searchFilter->getAvailableBrands() as $brand): ?>
                                <option value="<?php echo htmlspecialchars($brand); ?>"
                                    <?php echo isset($_GET['brand']) && $_GET['brand'] === $brand ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($brand)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text"><?php echo __('cars.featured_cars.filters.price_range'); ?></span>
                        </label>
                        <div class="flex gap-2">
                            <input type="number" 
                                   name="min_price" 
                                   placeholder="<?php echo __('cars.featured_cars.filters.min_price'); ?>" 
                                   class="input input-bordered w-24" 
                                   value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
                            <input type="number" 
                                   name="max_price" 
                                   placeholder="<?php echo __('cars.featured_cars.filters.max_price'); ?>" 
                                   class="input input-bordered w-24"
                                   value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><?php echo __('forms.search'); ?></button>
                    <?php if (!empty($_GET['brand']) || !empty($_GET['min_price']) || !empty($_GET['max_price'])): ?>
                        <a href="index.php" class="btn btn-ghost"><?php echo __('cars.featured_cars.filters.reset'); ?></a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold"><?php echo __('cars.search'); ?></h1>
                    <?php if ($query): ?>
                        <p class="text-base-content/70">
                            <?php echo __('cars.featured_cars.stats.showing_results', ['count' => $totalResults]); ?>
                            "<?php echo htmlspecialchars($query); ?>"
                        </p>
                    <?php endif; ?>
                </div>
                <div class="flex gap-2">
                    <?php if ($session->get('user_id')): ?>
                        <a href="add-listing.php" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <?php echo __('cars.add_listing'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="search.php" class="btn btn-outline"><?php echo __('cars.search'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <?php if (empty($results)): ?>
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body text-center py-16">
                    <svg class="mx-auto h-16 w-16 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold"><?php echo __('messages.no_results'); ?></h3>
                    <?php if ($query): ?>
                        <p class="mt-2 text-base-content/70">
                            <?php echo __('cars.featured_cars.stats.no_results_query', ['query' => htmlspecialchars($query)]); ?>
                        </p>
                        <div class="mt-6">
                            <a href="index.php" class="btn btn-primary"><?php echo __('cars.featured_cars.filters.reset'); ?></a>
                            <a href="search.php?q=<?php echo urlencode($query); ?>" class="btn btn-outline ml-2">
                                <?php echo __('cars.search_advanced'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="mt-2 text-base-content/70"><?php echo __('cars.featured_cars.stats.no_listings'); ?></p>
                        <?php if ($session->get('user_id')): ?>
                            <div class="mt-6">
                                <a href="add-listing.php" class="btn btn-primary"><?php echo __('cars.add_first_listing'); ?></a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Results Count -->
            <p class="text-base-content/70 mb-6">
                <?php echo __('cars.featured_cars.stats.found_listings', ['count' => $totalResults]); ?>
            </p>

            <!-- Results Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($results as $car): ?>
                    <div class="card bg-base-100 shadow-xl">
                        <figure class="px-4 pt-4">
                            <img src="/car-project/public/uploads/car_images/<?php echo htmlspecialchars($car['images'][0] ?? 'default.jpg'); ?>" 
                                 alt="<?php echo __('cars.gallery_image.title') . ': ' . htmlspecialchars($car['title']); ?>"
                                 class="rounded-xl h-48 w-full object-cover" />
                        </figure>
                        <div class="card-body">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h2 class="card-title">
                                        <?php echo htmlspecialchars($car['title']); ?>
                                        <?php
                                        $status = $car['status'] ?? 'active';
                                        $statusClass = match($status) {
                                            'pending' => 'badge-warning',
                                            'sold' => 'badge-error',
                                            'active' => 'badge-success',
                                            default => 'badge-ghost'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?> ml-2 text-xs whitespace-nowrap">
                                            <?php echo __("cars.status.{$status}"); ?>
                                        </span>
                                    </h2>
                                    <?php if ($status === 'sold'): ?>
                                        <div class="text-error text-sm font-semibold whitespace-normal break-words">
                                            <?php echo __('cars.featured_cars.stats.item_sold'); ?>
                                        </div>
                                    <?php endif; ?>
                                    <p class="text-accent font-bold">$<?php echo number_format($car['price'], 2); ?></p>
                                </div>
                                <?php if ($session->get('user_id')): ?>
                                    <button onclick="toggleFavorite('<?php echo $car['id']; ?>')"
                                            class="btn btn-circle btn-ghost favorite-btn <?php echo in_array($car['id'], $favoritedListingIds) ? 'active' : ''; ?>"
                                            data-listing-id="<?php echo $car['id']; ?>"
                                            title="<?php echo __('cars.favorites'); ?>">
                                        <svg class="w-6 h-6 <?php echo in_array($car['id'], $favoritedListingIds) ? 'text-red-500 fill-current' : ''; ?>" 
                                             viewBox="0 0 24 24">
                                            <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-wrap gap-2 my-2">
                                <div class="badge badge-outline whitespace-nowrap"><?php echo __("cars.transmissions.{$car['transmission']}"); ?></div>
                                <div class="badge badge-outline whitespace-nowrap"><?php echo __("cars.fuel_types.{$car['fuel_type']}"); ?></div>
                            </div>
                            <div class="flex justify-between items-center mt-4 flex-wrap gap-2">
                                <a href="view.php?id=<?php echo $car['id']; ?>" class="btn btn-primary whitespace-nowrap"><?php echo __('cars.view_full_details'); ?></a>
                                <span class="text-sm text-base-content/70 capitalize whitespace-nowrap">
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
    <script src="/car-project/public/js/components/quick-search.js"></script>
    <script src="/car-project/public/js/components/image-loader.js"></script>
    <script src="/car-project/public/js/components/featured-cars.js"></script>
</body>
</html>
