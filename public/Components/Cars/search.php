<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Auth/Session.php';
require_once __DIR__ . '/../../../src/Services/SessionService.php';
require_once __DIR__ . '/../../../src/Classes/Cars/CarListing.php';
require_once __DIR__ . '/../../../src/Classes/Cars/SearchFilter.php';

// Add TranslationManager since we're using it in the template
use Classes\Language\TranslationManager;
use Components\Header\Header;
use Classes\Auth\Session;
use Services\SessionService;
use Classes\Cars\CarListing;
use Classes\Cars\SearchFilter;

// Initialize TranslationManager
$translationManager = TranslationManager::getInstance();
$session = Session::getInstance()->start();
$sessionService = new SessionService();

// Get search parameters
$query = $_GET['q'] ?? '';
$brand = $_GET['brand'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$yearFrom = $_GET['year_from'] ?? '';
$yearTo = $_GET['year_to'] ?? '';
$location = $_GET['location'] ?? '';
$sort = $_GET['sort'] ?? 'latest';
$features = $_GET['features'] ?? [];

// Initialize search filter
$searchFilter = new SearchFilter();

// Get filtered results
$results = $searchFilter->filter([
    'q' => $query,
    'brand' => $brand,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'year_from' => $yearFrom,
    'year_to' => $yearTo,
    'location' => $location,
    'features' => $features,
    'sort' => $sort
]);

// Get available options for filters
$availableBrands = $searchFilter->getAvailableBrands();
$availableLocations = $searchFilter->getAvailableLocations();
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
    <!-- Search Header -->
    <div class="bg-primary text-primary-content py-8">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Find Your Perfect Car</h1>
            <form action="" method="GET" class="w-full">
                <div class="join w-full max-w-2xl">
                    <input type="text" 
                           name="q" 
                           value="<?php echo htmlspecialchars($query); ?>"
                           class="input input-bordered join-item w-full" 
                           placeholder="Search by make, model, or keyword..."/>
                    <button class="btn btn-accent join-item">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Filters Sidebar -->
            <div class="lg:col-span-1">
                <form id="filterForm" action="" method="GET" class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">Filters</h2>
                        
                        <!-- Brand -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Brand</span>
                            </label>
                            <select name="brand" class="select select-bordered">
                                <option value="">All Brands</option>
                                <option value="toyota" <?php echo $brand === 'toyota' ? 'selected' : ''; ?>>Toyota</option>
                                <option value="honda" <?php echo $brand === 'honda' ? 'selected' : ''; ?>>Honda</option>
                                <option value="nissan" <?php echo $brand === 'nissan' ? 'selected' : ''; ?>>Nissan</option>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Price Range</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" 
                                       name="min_price" 
                                       value="<?php echo htmlspecialchars($minPrice); ?>"
                                       class="input input-bordered" 
                                       placeholder="Min"/>
                                <input type="number" 
                                       name="max_price" 
                                       value="<?php echo htmlspecialchars($maxPrice); ?>"
                                       class="input input-bordered" 
                                       placeholder="Max"/>
                            </div>
                        </div>

                        <!-- Year Range -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Year</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <select name="year_from" class="select select-bordered">
                                    <option value="">From</option>
                                    <?php for($i = 2023; $i >= 2000; $i--): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $yearFrom == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select name="year_to" class="select select-bordered">
                                    <option value="">To</option>
                                    <?php for($i = 2023; $i >= 2000; $i--): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $yearTo == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Location</span>
                            </label>
                            <select name="location" class="select select-bordered">
                                <option value="">All Locations</option>
                                <option value="dubai" <?php echo $location === 'dubai' ? 'selected' : ''; ?>>Dubai</option>
                                <option value="japan" <?php echo $location === 'japan' ? 'selected' : ''; ?>>Japan</option>
                            </select>
                        </div>

                        <!-- Additional Filters -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Features</span>
                            </label>
                            <div class="space-y-2">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="features[]" value="leather" class="checkbox" />
                                    <span class="label-text">Leather Seats</span>
                                </label>
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="features[]" value="navigation" class="checkbox" />
                                    <span class="label-text">Navigation System</span>
                                </label>
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="features[]" value="sunroof" class="checkbox" />
                                    <span class="label-text">Sunroof</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary w-full">Apply Filters</button>
                            <a href="search.php" class="btn btn-outline w-full mt-2">Clear Filters</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Search Results -->
            <div class="lg:col-span-3">
                <!-- Sort Options -->
                <div class="flex justify-between items-center mb-6">
                    <p class="text-base-content/70" id="totalResults">
                        Showing <?php echo count($results); ?> results
                    </p>
                    <select 
                        id="sortSelect"
                        name="sort"
                        class="select select-bordered w-full max-w-xs">
                        <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest First</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="year_new" <?php echo $sort === 'year_new' ? 'selected' : ''; ?>>Year: New to Old</option>
                        <option value="year_old" <?php echo $sort === 'year_old' ? 'selected' : ''; ?>>Year: Old to New</option>
                    </select>
                </div>

                <!-- Results Grid -->
                <div id="searchResults" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($results as $car): ?>
                    <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow">
                        <figure class="px-4 pt-4">
                            <img src="/car-project/public/uploads/car_images/<?php echo htmlspecialchars($car['images'][0] ?? 'default.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($car['title']); ?>"
                                 class="rounded-xl h-48 w-full object-cover" />
                        </figure>
                        <div class="card-body">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h2 class="card-title"><?php echo htmlspecialchars($car['title']); ?></h2>
                                    <p class="text-accent font-bold">$<?php echo number_format($car['price'], 2); ?></p>
                                </div>
                                <button onclick="toggleFavorite('<?php echo $car['id']; ?>')"
                                        class="btn btn-circle btn-outline favorite-btn"
                                        data-listing-id="<?php echo $car['id']; ?>">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex gap-2 my-2">
                                <div class="badge badge-outline"><?php echo htmlspecialchars($car['transmission']); ?></div>
                                <div class="badge badge-outline"><?php echo htmlspecialchars($car['fuel_type']); ?></div>
                            </div>
                            <div class="flex justify-between items-center mt-4">
                                <a href="view.php?id=<?php echo $car['id']; ?>" class="btn btn-primary">View Details</a>
                                <span class="text-sm text-base-content/70 capitalize">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $car['location'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div class="flex justify-center mt-8">
                    <div class="join">
                        <button class="join-item btn">1</button>
                        <button class="join-item btn btn-active">2</button>
                        <button class="join-item btn">3</button>
                        <button class="join-item btn">4</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/car-project/public/js/components/favorites.js"></script>
    <script src="/car-project/public/js/components/search-filter.js"></script>
</body>
</html>
