<?php
// Remove the access check
// defined('ALLOW_ACCESS') || die('Direct access not permitted');

// Get car ID from URL if present
$carId = $_GET['carId'] ?? null;

// Initialize CarListing to get cars
$carListing = new \Classes\Cars\CarListing();
$allCars = array_filter($carListing->getAll(), function($car) {
    return $car['status'] === 'active';
});

// Add debug logging
error_log("All cars: " . json_encode($carListing->getAll()));
error_log("Active cars: " . json_encode($allCars));

// Get specific car if ID provided
$selectedCar = $carId ? $carListing->getById($carId) : null;

function __safe($key) {
    try {
        return __($key);
    } catch (Exception $e) {
        return $key;
    }
}
?>

<!-- Scheduling Section -->
<div class="card bg-base-100 shadow-xl mb-8">
    <div class="card-body">
        <h2 class="card-title mb-4">
            <i class="fas fa-calendar-alt mr-2"></i>
            <?php echo __safe('cars.schedule_viewing.title'); ?>
        </h2>

        <!-- Car Selection -->
        <div class="form-control mb-4">
            <label class="label">
                <span class="label-text"><?php echo __safe('cars.schedule_viewing.select_car'); ?></span>
            </label>
            <select id="carSelect" class="select select-bordered w-full" 
                    <?php echo $selectedCar ? 'disabled' : ''; ?>>
                <option value=""><?php echo __safe('cars.schedule_viewing.select_car_placeholder'); ?></option>
                <?php foreach ($allCars as $car): ?>
                    <option value="<?php echo $car['id']; ?>" 
                            <?php echo ($selectedCar && $selectedCar['id'] === $car['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model'] . ' ' . $car['year']); ?>
                        - $<?php echo number_format($car['price']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Car Preview -->
        <div id="carPreview" class="mb-4 <?php echo $selectedCar ? '' : 'hidden'; ?>">
            <?php if ($selectedCar): ?>
                <div class="bg-base-200 rounded-lg p-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <img src="/car-project/public/uploads/car_images/<?php echo $selectedCar['images'][0] ?? 'default.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($selectedCar['title']); ?>"
                             class="w-full md:w-48 h-48 md:h-48 object-cover rounded-lg">
                        <div class="space-y-2 flex-1">
                            <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($selectedCar['title']); ?></h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="opacity-70"><?php echo __safe('cars.year'); ?>:</p>
                                    <p class="font-medium"><?php echo $selectedCar['year']; ?></p>
                                </div>
                                <div>
                                    <p class="opacity-70"><?php echo __safe('cars.mileage'); ?>:</p>
                                    <p class="font-medium"><?php echo number_format($selectedCar['mileage']); ?> km</p>
                                </div>
                                <div>
                                    <p class="opacity-70"><?php echo __safe('cars.transmission'); ?>:</p>
                                    <p class="font-medium"><?php echo $selectedCar['transmission']; ?></p>
                                </div>
                                <div>
                                    <p class="opacity-70"><?php echo __safe('cars.fuel_type'); ?>:</p>
                                    <p class="font-medium"><?php echo $selectedCar['fuel_type']; ?></p>
                                </div>
                            </div>
                            <p class="text-sm opacity-70">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                <?php echo htmlspecialchars($selectedCar['location']); ?>
                            </p>
                            <div class="flex items-center gap-2 mt-2">
                                <?php if ($selectedCar['warranty']): ?>
                                    <span class="badge badge-success gap-1">
                                        <i class="fas fa-shield-alt"></i>
                                        <?php echo __safe('cars.warranty_active'); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($selectedCar['negotiable']): ?>
                                    <span class="badge badge-info gap-1">
                                        <i class="fas fa-handshake"></i>
                                        <?php echo __safe('cars.price_negotiable'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-primary font-bold text-xl mt-2">
                                <i class="fas fa-tag mr-1"></i>
                                $<?php echo number_format($selectedCar['price']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Scheduling Form -->
        <form id="scheduleForm" action="/car-project/public/Components/Contact/schedule-handler.php" method="POST">
            <input type="hidden" name="car_id" value="<?php echo $selectedCar ? $selectedCar['id'] : ''; ?>">
            
            <!-- Meeting Type -->
            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text"><?php echo __safe('cars.schedule_viewing.meeting_type'); ?></span>
                </label>
                <select name="type" class="select select-bordered" required>
                    <?php foreach (\Classes\Meeting\MeetingConfig::MEETING_TYPES as $type => $label): ?>
                        <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date and Time -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text"><?php echo __safe('cars.schedule_viewing.date'); ?></span>
                    </label>
                    <input type="date" name="date" class="input input-bordered" required
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text"><?php echo __safe('cars.schedule_viewing.time'); ?></span>
                    </label>
                    <select name="slot_id" class="select select-bordered" required>
                        <option value=""><?php echo __safe('cars.schedule_viewing.select_time'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text"><?php echo __safe('contact.form.fields.name'); ?></span>
                    </label>
                    <input type="text" name="name" class="input input-bordered" required>
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text"><?php echo __safe('contact.form.fields.email'); ?></span>
                    </label>
                    <input type="email" name="email" class="input input-bordered" required>
                </div>
            </div>

            <!-- Notes -->
            <div class="form-control mb-6">
                <label class="label">
                    <span class="label-text"><?php echo __safe('cars.schedule_viewing.notes'); ?></span>
                </label>
                <textarea name="notes" class="textarea textarea-bordered h-24"></textarea>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-full">
                <?php echo __safe('cars.schedule_viewing.submit'); ?>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const carSelect = document.getElementById('carSelect');
    const carPreview = document.getElementById('carPreview');
    const carIdInput = document.querySelector('input[name="car_id"]');

    // Handle car selection change
    carSelect.addEventListener('change', function() {
        const carId = this.value;
        if (carId) {
            window.location.href = `?carId=${carId}`;
        }
    });

    // Update available time slots when date changes
    const dateInput = document.querySelector('input[name="date"]');
    const timeSelect = document.querySelector('select[name="slot_id"]');

    dateInput.addEventListener('change', async function() {
        const date = this.value;
        const carId = carIdInput.value;

        if (!date || !carId) {
            console.error('Date or car ID is missing');
            return;
        }

        try {
            const response = await fetch(`/car-project/public/Components/Contact/get-available-slots.php?date=${date}&carId=${carId}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const slots = await response.json();
            
            timeSelect.innerHTML = '<option value=""><?php echo __safe('cars.schedule_viewing.select_time'); ?></option>';
            if (Array.isArray(slots) && slots.length > 0) {
                slots.forEach(slot => {
                    timeSelect.innerHTML += `<option value="${slot.id}">${slot.time}</option>`;
                });
            } else {
                timeSelect.innerHTML += '<option value="" disabled>No available slots</option>';
            }
        } catch (error) {
            console.error('Error fetching time slots:', error);
            timeSelect.innerHTML = '<option value="" disabled>Error loading time slots</option>';
        }
    });
});
</script> 