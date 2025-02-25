<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Cars/SearchFilter.php';

use Classes\Cars\SearchFilter;

header('Content-Type: application/json');

try {
    $searchFilter = new SearchFilter();
    
    // Get all parameters from GET request
    $params = [
        'q' => $_GET['q'] ?? '',
        'brand' => $_GET['brand'] ?? '',
        'min_price' => $_GET['min_price'] ?? '',
        'max_price' => $_GET['max_price'] ?? '',
        'year_from' => $_GET['year_from'] ?? '',
        'year_to' => $_GET['year_to'] ?? '',
        'location' => $_GET['location'] ?? '',
        'features' => $_GET['features'] ?? [],
        'sort' => $_GET['sort'] ?? 'latest'
    ];

    // Get filtered results
    $results = $searchFilter->filter($params);
    
    // Use array_unique to remove duplicates
    $results = array_values(array_unique($results, SORT_REGULAR));

    // Start output buffering for HTML generation
    ob_start();
    
    foreach ($results as $car): ?>
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
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    <?php endforeach;
    
    $html = ob_get_clean();

    echo json_encode([
        'success' => true,
        'html' => $html,
        'total' => count($results)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}