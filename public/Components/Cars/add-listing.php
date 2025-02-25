<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Auth/Session.php';
require_once __DIR__ . '/../../../src/Services/SessionService.php';
require_once __DIR__ . '/../../../src/Classes/Cars/CarListing.php';

use Classes\Auth\Session;
use Classes\Auth\CSRF;
use Classes\Cars\CarListing;
use Services\SessionService;
use Classes\Language\TranslationManager;
use Components\Header\Header;

$session = Session::getInstance()->start();
$sessionService = new SessionService();
$translationManager = TranslationManager::getInstance();

// Require authentication
$sessionService->requireAuth();
$userId = $session->get('user_id');

// Get old input and flash messages
$oldInput = $session->get('old_input', []);
$error = $session->getFlash('error');
$success = $session->getFlash('success');

// Clear old input
$session->remove('old_input');

// After session initialization
$csrf = new CSRF();  // Now using the correctly namespaced class
?>

<!DOCTYPE html>
<html lang="<?php echo $translationManager->getLocale(); ?>" data-theme="carmarket">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('cars.add_listing'); ?> - <?php echo __('common.welcome'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Dropzone for image uploads -->
    <link href="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone-min.js"></script>
    <meta name="csrf-token" content="<?php echo $csrf->getToken(); ?>">
</head>

<body class="min-h-screen bg-base-200">
    <?php
    $header = new Header();
    echo $header->render();
    ?>
    <!-- Breadcrumb -->
    <div class="bg-base-100 border-b">
        <div class="container mx-auto px-4 py-3">
            <div class="text-sm breadcrumbs">
                <ul>
                    <li><a href="index.php"><?php echo __('common.home'); ?></a></li>
                    <li><a href="index.php"><?php echo __('cars.search'); ?></a></li>
                    <li><?php echo __('cars.add_listing'); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-8"><?php echo __('cars.add_listing'); ?></h1>

            <?php if ($error): ?>
            <div class="alert alert-error mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <form action="process-listing.php" method="POST" class="space-y-8">
                <!-- Basic Information -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.basic_information'); ?></h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Brand -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.brand'); ?>*</span>
                                </label>
                                <select name="brand" class="select select-bordered" required>
                                    <option value=""><?php echo __('cars.featured_cars.dropdowns.select_brand'); ?></option>
                                    <?php foreach (CarListing::BRANDS as $brand => $models): ?>
                                        <option value="<?php echo htmlspecialchars($brand); ?>"<?php 
                                        echo ($oldInput['brand'] ?? '') === $brand ? ' selected' : ''; 
                                        ?>><?php echo htmlspecialchars(ucfirst($brand)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Model -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.model'); ?>*</span>
                                </label>
                                <input type="text" 
                                       name="model" 
                                       class="input input-bordered" 
                                       list="model-list"
                                       placeholder="<?php echo __('cars.featured_cars.dropdowns.model_placeholder'); ?>"
                                       value="<?php echo htmlspecialchars($oldInput['model'] ?? ''); ?>" 
                                       required 
                                       <?php echo empty($oldInput['brand'] ?? '') ? 'disabled' : ''; ?>>
                                <datalist id="model-list">
                                    <?php if (!empty($oldInput['brand'])): ?>
                                        <?php foreach (CarListing::BRANDS[$oldInput['brand']] as $model): ?>
                                            <option value="<?php echo htmlspecialchars($model); ?>">
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </datalist>
                            </div>

                            <!-- Year -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.year'); ?>*</span>
                                </label>
                                <select name="year" class="select select-bordered" required>
                                    <option value=""><?php echo __('cars.select_year'); ?></option>
                                    <?php for ($i = 2024; $i >= 1990; $i--): ?>
                                        <option value="<?php echo $i; ?>"<?php echo ($oldInput['year'] ?? '') === $i ? ' selected' : ''; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Mileage -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.mileage'); ?> (km)*</span>
                                </label>
                                <input type="number" 
                                       name="mileage" 
                                       class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($oldInput['mileage'] ?? ''); ?>" 
                                       required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Details -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.vehicle_details'); ?></h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Body Type -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.body_type'); ?>*</span>
                                </label>
                                <select name="body_type" class="select select-bordered" required>
                                    <option value=""><?php echo __('cars.select_body_type'); ?></option>
                                    <?php foreach (CarListing::BODY_TYPES as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>"<?php echo ($oldInput['body_type'] ?? '') === $type ? ' selected' : ''; ?>><?php echo __("cars.body_types.$type"); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Transmission -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.transmission'); ?>*</span>
                                </label>
                                <select name="transmission" class="select select-bordered" required>
                                    <option value=""><?php echo __('cars.select_transmission'); ?></option>
                                    <?php foreach (CarListing::TRANSMISSIONS as $transmission): ?>
                                        <option value="<?php echo htmlspecialchars($transmission); ?>"<?php echo ($oldInput['transmission'] ?? '') === $transmission ? ' selected' : ''; ?>><?php echo __("cars.transmissions.$transmission"); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Fuel Type -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.fuel_type'); ?>*</span>
                                </label>
                                <select name="fuel_type" class="select select-bordered" required>
                                    <option value=""><?php echo __('cars.select_fuel_type'); ?></option>
                                    <?php foreach (CarListing::FUEL_TYPES as $fuel): ?>
                                        <option value="<?php echo htmlspecialchars($fuel); ?>"<?php echo ($oldInput['fuel_type'] ?? '') === $fuel ? ' selected' : ''; ?>><?php echo __("cars.fuel_types.$fuel"); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Color -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.color'); ?>*</span>
                                </label>
                                <select name="color" class="select select-bordered" required>
                                    <option value=""><?php echo __('cars.select_color'); ?></option>
                                    <?php foreach (CarListing::COLORS as $value => $label): ?>
                                        <option value="<?php echo $value; ?>"<?php echo ($oldInput['color'] ?? '') === $value ? ' selected' : ''; ?>><?php echo __("cars.colors.$value"); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location & Price -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.location_and_price.title'); ?></h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Location -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.location'); ?>*</span>
                                </label>
                                <select name="location" class="select select-bordered" required>
                                    <option value=""><?php echo __('cars.location_and_price.select_location'); ?></option>
                                    <?php foreach (CarListing::LOCATIONS as $value): ?>
                                        <option value="<?php echo htmlspecialchars($value); ?>"<?php echo ($oldInput['location'] ?? '') === $value ? ' selected' : ''; ?>><?php echo __("cars.locations.$value"); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Price -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.location_and_price.price_usd'); ?>*</span>
                                </label>
                                <input type="number" name="price" class="input input-bordered" value="<?php echo htmlspecialchars($oldInput['price'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Images -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.vehicle_images.title'); ?></h2>
                        <input type="hidden" name="uploaded_images" id="uploaded_images" value="">
                        <div id="imageUpload" class="dropzone rounded-lg border-2 border-dashed border-base-300">
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.description_section.title'); ?></h2>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text"><?php echo __('cars.description_section.required'); ?></span>
                            </label>
                            <textarea name="description" 
                                    class="textarea textarea-bordered h-32" 
                                    placeholder="<?php echo __('cars.description_section.placeholder'); ?>"
                                    required><?php echo htmlspecialchars($oldInput['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Specifications -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.specifications_section.title'); ?></h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach (CarListing::SPECIFICATIONS as $key => $spec): ?>
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text"><?php echo __("cars.specifications_section.fields.$key.label"); ?><?php echo $spec['required'] ? '*' : ''; ?></span>
                                    </label>
                                    <?php if ($spec['type'] === 'select'): ?>
                                        <select name="specifications[<?php echo $key; ?>]" 
                                                class="select select-bordered" 
                                                <?php echo $spec['required'] ? 'required' : ''; ?>>
                                            <option value=""><?php echo __('cars.specifications_section.select_placeholder', ['field' => __("cars.specifications_section.fields.$key.label")]); ?></option>
                                            <?php foreach ($spec['options'] as $option): ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>"<?php echo ($oldInput['specifications'][$key] ?? '') === $option ? ' selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="<?php echo $spec['type']; ?>" 
                                               name="specifications[<?php echo $key; ?>]" 
                                               class="input input-bordered"
                                               placeholder="<?php echo __("cars.specifications_section.fields.$key.placeholder"); ?>"
                                               value="<?php echo htmlspecialchars($oldInput['specifications'][$key] ?? ''); ?>"
                                               <?php echo $spec['required'] ? 'required' : ''; ?>
                                               <?php echo isset($spec['min']) ? "min=\"{$spec['min']}\"" : ''; ?>
                                               <?php echo isset($spec['max']) ? "max=\"{$spec['max']}\"" : ''; ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.features_section.title'); ?></h2>
                        <?php foreach (CarListing::FEATURES as $category => $features): ?>
                            <div class="mb-4">
                                <h3 class="font-semibold capitalize mb-2"><?php echo __("cars.features_section.categories.$category"); ?></h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                    <?php foreach ($features as $feature): ?>
                                        <label class="label cursor-pointer justify-start gap-2">
                                            <input type="checkbox" 
                                                   name="features[]" 
                                                   value="<?php echo htmlspecialchars($feature); ?>" 
                                                   class="checkbox"
                                                   <?php echo in_array($feature, $oldInput['features'] ?? []) ? 'checked' : ''; ?>>
                                            <span class="label-text"><?php echo __("cars.features.$feature"); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Condition -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.condition_section.title'); ?></h2>
                        <div class="form-control">
                            <select name="condition" class="select select-bordered" required>
                                <option value=""><?php echo __('cars.condition_section.select_condition'); ?></option>
                                <?php foreach (CarListing::CONDITIONS as $condition): ?>
                                    <option value="<?php echo htmlspecialchars($condition); ?>"
                                            <?php echo ($oldInput['condition'] ?? '') === $condition ? ' selected' : ''; ?>>
                                        <?php echo __("cars.condition_section.conditions.$condition"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.contact_section.title'); ?></h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.contact_section.fields.name.required'); ?></span>
                                </label>
                                <input type="text" name="contact_name" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($oldInput['contact_name'] ?? ''); ?>" required>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.contact_section.fields.phone.required'); ?></span>
                                </label>
                                <input type="tel" name="contact_phone" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($oldInput['contact_phone'] ?? ''); ?>">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.contact_section.fields.email.required'); ?></span>
                                </label>
                                <input type="email" name="contact_email" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($oldInput['contact_email'] ?? ''); ?>" required>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.contact_section.fields.preferred_method.label'); ?></span>
                                </label>
                                <select name="contact_method" class="select select-bordered">
                                    <?php foreach (CarListing::CONTACT_METHODS as $method): ?>
                                        <option value="<?php echo htmlspecialchars($method); ?>"<?php echo ($oldInput['contact_method'] ?? '') === $method ? ' selected' : ''; ?>><?php echo __("cars.contact_section.fields.preferred_method.options.$method"); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Options -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo __('cars.additional_options.title'); ?></h2>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="warranty" class="checkbox" 
                                           <?php echo !empty($oldInput['warranty']) ? 'checked' : ''; ?>>
                                    <span class="label-text"><?php echo __('cars.additional_options.warranty'); ?></span>
                                </label>
                            </div>

                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="negotiable" class="checkbox" 
                                           <?php echo !empty($oldInput['negotiable']) ? 'checked' : ''; ?>>
                                    <span class="label-text"><?php echo __('cars.additional_options.negotiable'); ?></span>
                                </label>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"><?php echo __('cars.additional_options.seller_notes.label'); ?></span>
                                </label>
                                <textarea name="seller_notes" 
                                         class="textarea textarea-bordered h-24"
                                         placeholder="<?php echo __('cars.additional_options.seller_notes.placeholder'); ?>"><?php 
                                    echo htmlspecialchars($oldInput['seller_notes'] ?? ''); 
                                ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-4">
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Listing</button>
                </div>
            </form>
        </div>
    </div>

    <!-- In the head section -->
    <script>
        // Make translations available to JavaScript
        window.translations = {
            cars: {
                vehicle_images: {
                    dropzone: {
                        message: <?php echo json_encode(__('cars.vehicle_images.dropzone.message')); ?>,
                        max_files: <?php echo json_encode(__('cars.vehicle_images.dropzone.max_files')); ?>,
                        file_too_big: <?php echo json_encode(__('cars.vehicle_images.dropzone.file_too_big')); ?>,
                        invalid_type: <?php echo json_encode(__('cars.vehicle_images.dropzone.invalid_type')); ?>
                    }
                }
            }
        };
        const carBrands = <?php echo json_encode(CarListing::BRANDS); ?>;
    </script>
    <script src="/car-project/public/assets/js/car-image-upload.js"></script>
    <script src="/car-project/public/assets/js/car-model-selector.js"></script>
</body>

</html>