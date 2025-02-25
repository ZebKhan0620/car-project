<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/User/Favorites.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../Header/Header.php';

use Components\Header\Header;
use Classes\Auth\Session;
use Classes\Services\SessionService;
use User\Favorites;
use Classes\Cars\CarListing;
use Classes\Meeting\MeetingScheduler;
use Classes\Language\TranslationManager;

$session = Session::getInstance()->start();
$translationManager = TranslationManager::getInstance();

function __safe($key) {
    try {
        return __($key);
    } catch (Exception $e) {
        error_log("Translation error for key '$key': " . $e->getMessage());
        return $key;
    }
}

// Get the current locale
$locale = isset($_SESSION['locale']) ? $_SESSION['locale'] : 'en';

// Get car ID from URL
$carId = $_GET['id'] ?? null;
if (!$carId) {
    header('Location: index.php');
    exit;
}

// Get car listing
$carListing = new CarListing();
$listing = $carListing->getById($carId);

if (!$listing) {
    header('Location: index.php');
    exit;
}

// Format price and other values
$formattedPrice = number_format($listing['price'], 2);
$formattedMileage = number_format($listing['mileage']);

if ($listing) {
    error_log("Listing data for URL: " . json_encode($listing));
    $schedulingUrl = '/car-project/public/Components/Contact/index.php'
        . '?carId=' . urlencode($listing['id'])
        . '&carName=' . urlencode($listing['title']);
    error_log("Generated scheduling URL: " . $schedulingUrl);
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translationManager->getLocale(); ?>" data-theme="carmarket">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($listing['title']); ?> - <?php echo __('common.welcome'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($listing['description']); ?>">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-base-200">
    <?php
    $header = new Header();
    echo $header->render();
    ?>
    <!-- Breadcrumb -->
    <div class="bg-base-100 border-b">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="text-sm breadcrumbs">
                    <ul>
                        <li><a href="/car-project/public/index.php"><?php echo __('common.home'); ?></a></li>
                        <li><a href="index.php"><?php echo __('cars.search'); ?></a></li>
                        <?php if ($session->get('user_id')): ?>
                            <li><a href="favorites.php"><?php echo __('cars.favorites'); ?></a></li>
                        <?php endif; ?>
                        <li><?php echo htmlspecialchars($listing['title']); ?></li>
                    </ul>
                </div>
                <?php if ($session->get('user_id')): ?>
                    <div class="flex items-center gap-4">
                        <a href="favorites.php" class="btn btn-ghost btn-sm gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <?php echo __('cars.favorites'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Title and Price -->
        <div class="flex flex-wrap justify-between items-start gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($listing['title']); ?></h1>
                <div class="text-sm opacity-60"><?php echo __('cars.listed_time', ['time' => date('F j, Y', strtotime($listing['created_at']))]); ?></div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-primary">$<?php echo $formattedPrice; ?></div>
                <?php if (!empty($listing['negotiable'])): ?>
                    <div class="badge badge-accent"><?php echo __('cars.additional_options.negotiable'); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($listing['user_id'] === $session->get('user_id')): ?>
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title"><?php echo __('cars.manage_listing'); ?></h2>
                    <div class="flex gap-2">
                        <form action="update-status.php" method="POST" class="flex-1">
                            <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                            <select name="status" class="select select-bordered w-full" onchange="this.form.submit()">
                                <option value="pending" <?php echo $listing['status'] === 'pending' ? 'selected' : ''; ?>>
                                    <?php echo __('cars.status.pending'); ?>
                                </option>
                                <option value="active" <?php echo $listing['status'] === 'active' ? 'selected' : ''; ?>>
                                    <?php echo __('cars.status.active'); ?>
                                </option>
                                <option value="sold" <?php echo $listing['status'] === 'sold' ? 'selected' : ''; ?>>
                                    <?php echo __('cars.status.sold'); ?>
                                </option>
                                <option value="inactive" <?php echo $listing['status'] === 'inactive' ? 'selected' : ''; ?>>
                                    <?php echo __('cars.status.inactive'); ?>
                                </option>
                            </select>
                        </form>
                        <button class="btn btn-error" onclick="deleteListing()"><?php echo __('forms.delete'); ?></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Image Modal -->
                <dialog id="imageModal" class="modal">
                    <div class="modal-box max-w-5xl h-full flex flex-col p-0 bg-base-100">
                        <div class="relative flex-1 overflow-hidden">
                            <div id="modalImage" class="absolute inset-0 flex items-center justify-center">
                                <img src="" alt="<?php echo __('cars.gallery_image.title'); ?>" class="max-w-full max-h-full object-contain">
                            </div>
                            <div class="absolute top-0 left-0 right-0 bg-gradient-to-b from-base-100/80 to-transparent h-16 z-10"></div>
                            <div class="absolute bottom-4 left-4 z-20">
                                <div class="badge badge-neutral gap-2">
                                    <span id="imageSize"></span>
                                </div>
                            </div>
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-base-100/80 to-transparent h-16 z-10"></div>
                            <div class="absolute top-4 right-4 flex gap-2 z-20">
                                <button class="btn btn-circle btn-sm" onclick="rotateImage()" title="<?php echo __('cars.gallery_image.rotate'); ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                                <button class="btn btn-circle btn-sm" onclick="toggleFullscreen()" title="<?php echo __('cars.gallery_image.fullscreen'); ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0 0l-5-5m-7 11h4m-4 0v4m0 0l5-5m5 5v-4m0 4h-4m0 0l-5-5"/>
                                    </svg>
                                </button>
                                <button class="btn btn-circle btn-sm" onclick="closeImageModal()" title="<?php echo __('forms.close'); ?>">✕</button>
                            </div>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop bg-base-100/90">
                        <button><?php echo __('forms.close'); ?></button>
                    </form>
                </dialog>

                <!-- Image Gallery -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body p-0">
                        <?php if (!empty($listing['images'])): ?>
                            <div class="carousel w-full relative group overflow-hidden" style="height: 600px;">
                                <?php foreach ($listing['images'] as $index => $image): ?>
                                    <div id="slide<?php echo $index; ?>" class="carousel-item absolute inset-0 w-full transition-all duration-300 ease-out">
                                        <img src="/car-project/public/uploads/car_images/<?php echo htmlspecialchars($image); ?>" 
                                             class="w-full h-full object-cover hover:scale-[1.02] transition-transform cursor-zoom-in object-center" 
                                             style="object-position: center center;"
                                             onclick="openImageModal(this)"
                                             alt="<?php echo __('cars.gallery_image.title'); ?>">
                                        <?php if (count($listing['images']) > 1): ?>
                                            <div class="absolute flex justify-between transform -translate-y-1/2 left-5 right-5 top-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                                <button onclick="navigateSlider('prev')" 
                                                        class="btn btn-circle bg-base-100/80 hover:bg-base-100 btn-lg"
                                                        title="<?php echo __('cars.gallery_image.previous'); ?>">❮</button>
                                                <button onclick="navigateSlider('next')" 
                                                        class="btn btn-circle bg-base-100/80 hover:bg-base-100 btn-lg"
                                                        title="<?php echo __('cars.gallery_image.next'); ?>">❯</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- Thumbnails -->
                            <div class="flex gap-2 p-4 overflow-x-auto bg-base-100 scrollbar-thin scrollbar-thumb-base-300">
                                <?php foreach ($listing['images'] as $index => $image): ?>
                                    <div class="thumbnail-item flex-shrink-0 cursor-pointer transition-all hover:opacity-75
                                              <?php echo $index === 0 ? 'ring-2 ring-primary' : ''; ?>">
                                        <img src="/car-project/public/uploads/car_images/<?php echo htmlspecialchars($image); ?>" 
                                             alt="<?php echo __('cars.gallery_image.thumbnail'); ?>" 
                                             class="w-24 h-24 object-cover rounded-lg object-center"
                                             style="object-position: center center;"
                                             loading="lazy"
                                             onclick="showSlide(<?php echo $index; ?>)">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="aspect-video bg-base-200 flex items-center justify-center">
                                <div class="text-center p-8">
                                    <svg class="mx-auto h-16 w-16 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="mt-4 text-base-content/50 text-lg"><?php echo __('cars.gallery_image.no_images'); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="stat bg-base-100 rounded-box shadow-xl">
                        <div class="stat-title"><?php echo __('cars.mileage'); ?></div>
                        <div class="stat-value text-xl"><?php echo number_format($listing['mileage']); ?> km</div>
                    </div>
                    <div class="stat bg-base-100 rounded-box shadow-xl">
                        <div class="stat-title"><?php echo __('cars.year'); ?></div>
                        <div class="stat-value text-xl"><?php echo htmlspecialchars($listing['year']); ?></div>
                    </div>
                    <div class="stat bg-base-100 rounded-box shadow-xl">
                        <div class="stat-title"><?php echo __('cars.transmission'); ?></div>
                        <div class="stat-value text-xl"><?php echo __("cars.transmissions.{$listing['transmission']}"); ?></div>
                    </div>
                    <div class="stat bg-base-100 rounded-box shadow-xl">
                        <div class="stat-title"><?php echo __('cars.fuel_type'); ?></div>
                        <div class="stat-value text-xl"><?php echo __("cars.fuel_types.{$listing['fuel_type']}"); ?></div>
                    </div>
                </div>

                <!-- Description -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.description'); ?></h2>
                        <?php if (!empty($listing['description'])): ?>
                            <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
                        <?php else: ?>
                            <p class="text-base-content/70"><?php echo __('cars.no_description'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Features -->
                <?php if (!empty($listing['features'])): ?>
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.key_features'); ?></h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <?php foreach ($listing['features'] as $feature): ?>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span><?php echo __("cars.features.{$feature}"); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Additional Information -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.compare.additional_information'); ?></h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm opacity-70"><?php echo __('cars.status.title'); ?></span>
                                <div class="font-semibold">
                                    <?php if ($listing['status'] === 'active'): ?>
                                        <span class="badge badge-success"><?php echo __('cars.status.active'); ?></span>
                                    <?php elseif ($listing['status'] === 'pending'): ?>
                                        <span class="badge badge-warning"><?php echo __('cars.status.pending'); ?></span>
                                    <?php elseif ($listing['status'] === 'sold'): ?>
                                        <span class="badge badge-error"><?php echo __('cars.status.sold'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="text-sm opacity-70"><?php echo __('cars.warranty'); ?></span>
                                <div class="font-semibold">
                                    <?php echo $listing['warranty'] ? __('common.yes') : __('common.no'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <?php if (!empty($listing['contact_name']) || !empty($listing['contact_phone']) || !empty($listing['contact_email'])): ?>
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.contact_info.title'); ?></h2>
                        <div class="space-y-2">
                            <?php if (!empty($listing['contact_name'])): ?>
                                <div>
                                    <span class="text-sm opacity-70"><?php echo __('cars.contact_info.name'); ?></span>
                                    <div class="font-semibold"><?php echo htmlspecialchars($listing['contact_name']); ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($listing['contact_phone'])): ?>
                                <div>
                                    <span class="text-sm opacity-70"><?php echo __('cars.contact_info.phone'); ?></span>
                                    <div class="font-semibold"><?php echo htmlspecialchars($listing['contact_phone']); ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($listing['contact_email'])): ?>
                                <div>
                                    <span class="text-sm opacity-70"><?php echo __('cars.contact_info.email'); ?></span>
                                    <div class="font-semibold"><?php echo htmlspecialchars($listing['contact_email']); ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($listing['contact_method'])): ?>
                                <div>
                                    <span class="text-sm opacity-70"><?php echo __('cars.contact_info.preferred_method'); ?></span>
                                    <div class="font-semibold"><?php echo __("cars.contact_section.fields.preferred_method.options.{$listing['contact_method']}"); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="lg:col-span-1">
                <div class="sticky top-4 space-y-4">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <!-- Price Section -->
                            <div class="mb-6">
                                <div class="text-3xl font-bold text-primary">$<?php echo number_format($listing['price'], 2); ?></div>
                                <?php if (!empty($listing['negotiable'])): ?>
                                    <div class="badge badge-accent mt-1"><?php echo __('cars.additional_options.negotiable'); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Status and Warranty -->
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm opacity-70"><?php echo __('cars.status.title'); ?></span>
                                    <?php if ($listing['status'] === 'active'): ?>
                                        <span class="badge badge-success"><?php echo __('cars.status.active'); ?></span>
                                    <?php elseif ($listing['status'] === 'pending'): ?>
                                        <span class="badge badge-warning"><?php echo __('cars.status.pending'); ?></span>
                                    <?php elseif ($listing['status'] === 'sold'): ?>
                                        <span class="badge badge-error"><?php echo __('cars.status.sold'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($listing['warranty']): ?>
                                    <div class="flex items-center gap-2 text-success">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                        <span class="text-sm"><?php echo __('cars.warranty_active'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Contact Section -->
                            <div class="space-y-4 mb-6">
                                <h3 class="font-semibold text-lg"><?php echo __('cars.contact.title'); ?></h3>
                                <?php if (!empty($listing['contact_phone']) || !empty($listing['contact_email'])): ?>
                                    <?php if (!empty($listing['contact_phone'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($listing['contact_phone']); ?>" 
                                           class="btn btn-primary w-full">
                                            <?php echo __('cars.contact.call_seller'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($listing['contact_email'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($listing['contact_email']); ?>" 
                                           class="btn btn-outline w-full">
                                            <?php echo __('cars.contact.email_seller'); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-primary w-full"><?php echo __('cars.contact.contact_seller'); ?></button>
                                <?php endif; ?>
                            </div>

                            <!-- Schedule a Viewing -->
                            <div class="space-y-4 mb-6">
                                <h3 class="font-semibold text-lg"><?php echo __('cars.schedule_viewing.title'); ?></h3>
                                <?php if ($listing): ?>
                                    <a href="<?php echo htmlspecialchars($schedulingUrl); ?>" 
                                       class="btn btn-primary w-full flex items-center justify-center gap-2">
                                        <i class="fas fa-calendar-plus"></i>
                                        <?php echo __('cars.schedule_viewing.button'); ?>
                                    </a>
                                    
                                    <!-- Meetings Section -->
                                    <?php
                                    require_once __DIR__ . '/../../../src/Classes/Meeting/MeetingScheduler.php';
                                    $scheduler = new Classes\Meeting\MeetingScheduler();
                                    $meetings = $scheduler->getMeetingsForCar($listing['id']);
                                    
                                    if (!empty($meetings)): ?>
                                        <div class="divider"></div>
                                        <div class="space-y-4">
                                            <h3 class="font-semibold text-lg flex items-center gap-2">
                                                <i class="fas fa-calendar-check"></i>
                                                <?php echo __safe('cars.schedule_viewing.title'); ?>
                                            </h3>
                                            <div class="space-y-3">
                                                <?php foreach ($meetings as $meeting): ?>
                                                    <div class="bg-base-200 rounded-lg p-3">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="badge badge-sm" style="background-color: <?php 
                                                                echo strpos($meeting['type'], 'video_call_') === 0 ? '#4f46e5' : '#059669'; ?>">
                                                                <?php 
                                                                $meetingType = __safe('cars.schedule_viewing.meeting_types.' . $meeting['type']);
                                                                echo $meetingType;
                                                                ?>
                                                            </span>
                                                            <span class="badge badge-sm <?php 
                                                                echo $meeting['status'] === 'scheduled' ? 'badge-success' : 'badge-warning'; ?>">
                                                                <?php echo __safe('cars.schedule_viewing.status.' . $meeting['status']); ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <div class="text-sm space-y-1">
                                                            <div class="flex items-center gap-1">
                                                                <i class="fas fa-calendar-day opacity-70"></i>
                                                                <span><?php 
                                                                    $date = new DateTime($meeting['scheduled_date']);
                                                                    echo $date->format(__safe('common.date_format')); 
                                                                ?></span>
                                                            </div>
                                                            <div class="flex items-center gap-1">
                                                                <i class="fas fa-clock opacity-70"></i>
                                                                <span><?php 
                                                                    $time = DateTime::createFromFormat('h:i A', $meeting['scheduled_time']);
                                                                    echo $time ? $time->format(__safe('common.time_format')) : $meeting['scheduled_time'];
                                                                ?></span>
                                                            </div>
                                                        </div>

                                                        <?php if ($meeting['status'] === 'scheduled'): ?>
                                                            <div class="flex gap-2 mt-3">
                                                                <?php if (isset($meeting['join_url'])): ?>
                                                                    <a href="<?php echo htmlspecialchars($meeting['join_url']); ?>" 
                                                                       target="_blank"
                                                                       class="btn btn-xs btn-primary flex-1">
                                                                        <i class="fas fa-video mr-1"></i>
                                                                        <?php echo __('cars.schedule_viewing.join_meeting'); ?>
                                                                    </a>
                                                                <?php endif; ?>
                                                                <div class="dropdown dropdown-end">
                                                                    <label tabindex="0" class="btn btn-xs btn-ghost">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </label>
                                                                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                                                                        <li><a onclick="view_meeting_details('<?php echo $meeting['id']; ?>')">
                                                                            <i class="fas fa-info-circle"></i> <?php echo __('cars.schedule_viewing.details'); ?>
                                                                        </a></li>
                                                                        <li><a onclick="reschedule_meeting('<?php echo $meeting['id']; ?>')">
                                                                            <i class="fas fa-calendar-alt"></i> <?php echo __('cars.schedule_viewing.reschedule'); ?>
                                                                        </a></li>
                                                                        <li><a onclick="cancel_meeting('<?php echo $meeting['id']; ?>')" class="text-error">
                                                                            <i class="fas fa-times-circle"></i> <?php echo __('cars.schedule_viewing.cancel'); ?>
                                                                        </a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-primary w-full" disabled><?php echo __('cars.schedule_viewing.car_not_available'); ?></button>
                                <?php endif; ?>
                            </div>

                            <!-- Location Section -->
                            <div class="mb-6">
                                <h3 class="font-semibold text-lg mb-2"><?php echo __('cars.location'); ?></h3>
                                <p class="capitalize"><?php echo __("cars.locations.{$listing['location']}"); ?></p>
                            </div>

                            <!-- Share Section -->
                            <div class="divider"></div>
                            <div>
                                <h3 class="font-semibold text-lg mb-4"><?php echo __('cars.share.title'); ?></h3>
                                <div class="space-y-3">
                                    <!-- Copy Link -->
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="text-sm opacity-70"><?php echo __('cars.share.copy_link'); ?></div>
                                        <button class="btn btn-square btn-sm btn-outline" 
                                                onclick="copyLink()" 
                                                title="<?php echo __('cars.share.copy_link_tooltip'); ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Social Share -->
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="text-sm opacity-70"><?php echo __('cars.share.social_media'); ?></div>
                                        <div class="flex gap-2">
                                            <!-- WhatsApp -->
                                            <a href="https://wa.me/?text=<?php echo urlencode(__('cars.share.whatsapp_text', [
                                                'title' => $listing['title'], 
                                                'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"
                                            ])); ?>" 
                                               target="_blank" 
                                               class="btn btn-square btn-sm btn-outline"
                                               title="<?php echo __('cars.share.whatsapp'); ?>">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                </svg>
                                            </a>
                                            <!-- Facebook -->
                                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                                               target="_blank" 
                                               class="btn btn-square btn-sm btn-outline"
                                               title="<?php echo __('cars.share.facebook'); ?>">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                                                </svg>
                                            </a>
                                            <!-- Twitter -->
                                            <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode(__('cars.share.twitter_text', [
                                                'title' => $listing['title'], 
                                                'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"
                                            ])); ?>" 
                                               target="_blank" 
                                               class="btn btn-square btn-sm btn-outline"
                                               title="<?php echo __('cars.share.twitter'); ?>">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Favorite Button -->
                            <?php if ($session->get('user_id')): ?>
                                <?php 
                                    $favorites = new Favorites();
                                    $isFavorite = $favorites->isFavorite($session->get('user_id'), $listing['id']);
                                ?>
                                <div class="flex justify-end mb-4">
                                    <button onclick="toggleFavorite('<?php echo $listing['id']; ?>')"
                                            class="btn btn-circle btn-outline favorite-btn"
                                            data-listing-id="<?php echo $listing['id']; ?>">
                                        <svg class="w-6 h-6 <?php echo $isFavorite ? 'text-red-500 fill-current' : ''; ?>" 
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                    </button>
                                </div>
                            <?php endif; ?>

                            
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remove all other meeting sections -->
        </div>
    </div>

    <?php if (isset($_GET['scheduled']) && $_GET['scheduled'] === 'true'): ?>
        <div class="alert alert-success shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>
                    <?php 
                    $type = $_GET['type'] ?? '';
                    echo \Classes\Meeting\MeetingConfig::isVirtualMeeting($type) 
                        ? __('cars.schedule_viewing.success.virtual')
                        : __('cars.schedule_viewing.success.in_person');
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <script src="/../car-project/public/js/components/image-slider.js"></script>
    <script src="/../car-project/public/js/components/image-modal.js"></script>
    <script src="/../car-project/public/js/components/utils.js"></script>
    <script src="/../car-project/public/js/components/favorites.js"></script>
    <script src="/../car-project/public/assets/js/meeting-actions.js"></script>
    <script>
    async function view_meeting_details(meetingId) {
        try {
            const response = await fetch(`/car-project/public/api/meetings/details.php?id=${meetingId}`);
            const result = await response.json();
            
            if (result.success) {
                // Show meeting details in a modal
                const meeting = result.data;
                const modal = document.createElement('div');
                modal.className = 'modal modal-open';
                modal.innerHTML = `
                    <div class="modal-box">
                        <h3 class="font-bold text-lg">Meeting Details</h3>
                        <div class="py-4 space-y-2">
                            <p><strong>Date:</strong> ${formatDate(meeting.scheduled_date)}</p>
                            <p><strong>Time:</strong> ${meeting.scheduled_time}</p>
                            <p><strong>Type:</strong> ${getMeetingTypeTranslation(meeting.type)}</p>
                            <p><strong>Status:</strong> ${meeting.status}</p>
                            <p><strong>Name:</strong> ${meeting.name}</p>
                            <p><strong>Email:</strong> ${meeting.email}</p>
                            ${meeting.notes ? `<p><strong>Notes:</strong> ${meeting.notes}</p>` : ''}
                        </div>
                        <div class="modal-action">
                            <button class="btn" onclick="this.closest('.modal').remove()">Close</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Error viewing meeting details:', error);
            alert('Failed to load meeting details');
        }
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('<?php echo $locale; ?>', options);
    }

    function getMeetingTypeTranslation(type) {
        const translations = <?php echo json_encode([
            'video_call_zoom' => __safe('cars.schedule_viewing.meeting_types.video_call_zoom'),
            'video_call_skype' => __safe('cars.schedule_viewing.meeting_types.video_call_skype'),
            'video_call_gmeet' => __safe('cars.schedule_viewing.meeting_types.video_call_gmeet'),
            'dealership_visit' => __safe('cars.schedule_viewing.meeting_types.dealership_visit'),
        ]); ?>;
        return translations[type] || type;
    }
    </script>
</body>
</html>