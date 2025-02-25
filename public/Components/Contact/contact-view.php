<?php
// Prevent direct access
defined('ALLOW_ACCESS') or die('Direct access is not allowed');

// Ensure required variables are set
if (!isset($translationManager) || !isset($translations) || !isset($contactPurposes)) {
    die('Required variables are not set');
}

// Initialize variables with defaults
$errors = $errors ?? [];
$success = $success ?? false;
$locale = $translationManager->getLocale();
?>

<!-- Hero Section -->
<section class="relative bg-base-200 overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"30\" height=\"30\" viewBox=\"0 0 30 30\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cpath d=\"M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z\" fill=\"rgba(0,0,0,0.07)\"%3E%3C/path%3E%3C/svg%3E')"></div>
    </div>
    
    <div class="container mx-auto px-4 py-16 relative">
        <div class="text-center max-w-3xl mx-auto" data-aos="fade-up">
            <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo $translations['title']; ?></h1>
            <p class="text-base-content/70 text-lg mb-8"><?php echo $translations['subtitle']; ?></p>
            
            <!-- Contact Purpose Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <?php foreach ($contactPurposes as $key => $purpose): ?>
                <div class="card bg-base-100 shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer group"
                     onclick="selectPurpose('<?php echo $key; ?>', '<?php echo htmlspecialchars($purpose['title']); ?>')"
                     data-purpose="<?php echo $key; ?>"
                     aria-label="<?php echo htmlspecialchars($purpose['title']); ?>"
                     role="button"
                     tabindex="0">
                    <div class="card-body p-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-2 text-primary group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-full h-full" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <?php echo $purpose['icon']; ?>
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium"><?php echo htmlspecialchars($purpose['title']); ?></h3>
                        <p class="text-xs text-base-content/70 mt-1"><?php echo htmlspecialchars($purpose['description']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Main Content Section -->
<section class="py-12 bg-base-100">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 max-w-7xl mx-auto overflow-hidden">
            <!-- Contact Form -->
            <div class="lg:col-span-2 w-full">
                <div class="card bg-base-200 shadow-xl overflow-hidden" data-aos="fade-right">
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success mb-6" role="alert">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span><?php echo $translations['messages']['success']; ?></span>
                            </div>
                        <?php endif; ?>

                        <form id="contactForm" class="space-y-4" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="contact_purpose" id="contact_purpose" value="general">
                            
                            <!-- Honeypot field -->
                            <div class="hidden display-none" aria-hidden="true">
                                <input type="text" name="honeypot" tabindex="-1" autocomplete="off">
                            </div>
                            
                            <!-- Name Field -->
                            <div class="form-control">
                                <label class="label" for="name">
                                    <span class="label-text"><?php echo $translations['form']['fields']['name']; ?></span>
                                    <span class="label-text-alt text-error">*</span>
                                </label>
                                <input type="text" 
                                       id="name"
                                       name="name" 
                                       class="input input-bordered w-full<?php echo isset($errors['name']) ? ' input-error' : ''; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       required
                                       maxlength="100"
                                       aria-required="true"
                                       aria-invalid="<?php echo isset($errors['name']) ? 'true' : 'false'; ?>"
                                       aria-describedby="<?php echo isset($errors['name']) ? 'name-error' : ''; ?>">
                                <?php if (isset($errors['name'])): ?>
                                    <div class="label" id="name-error">
                                        <span class="label-text-alt text-error" role="alert"><?php echo htmlspecialchars($errors['name']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Email Field -->
                            <div class="form-control">
                                <label class="label" for="email">
                                    <span class="label-text"><?php echo $translations['form']['fields']['email']; ?></span>
                                    <span class="label-text-alt text-error">*</span>
                                </label>
                                <input type="email" 
                                       id="email"
                                       name="email" 
                                       class="input input-bordered w-full<?php echo isset($errors['email']) ? ' input-error' : ''; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       required
                                       maxlength="254"
                                       aria-required="true"
                                       aria-invalid="<?php echo isset($errors['email']) ? 'true' : 'false'; ?>"
                                       aria-describedby="<?php echo isset($errors['email']) ? 'email-error' : ''; ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="label" id="email-error">
                                        <span class="label-text-alt text-error" role="alert"><?php echo htmlspecialchars($errors['email']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Phone Field -->
                            <div class="form-control">
                                <label class="label" for="phone">
                                    <span class="label-text"><?php echo $translations['form']['fields']['phone']; ?></span>
                                </label>
                                <input type="tel" 
                                       id="phone"
                                       name="phone" 
                                       class="input input-bordered w-full<?php echo isset($errors['phone']) ? ' input-error' : ''; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       maxlength="20"
                                       aria-invalid="<?php echo isset($errors['phone']) ? 'true' : 'false'; ?>"
                                       aria-describedby="<?php echo isset($errors['phone']) ? 'phone-error' : ''; ?>"
                                       placeholder="<?php echo $locale === 'ja' ? '03-1234-5678' : '+1 (555) 123-4567'; ?>">
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="label" id="phone-error">
                                        <span class="label-text-alt text-error" role="alert"><?php echo htmlspecialchars($errors['phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Subject Field -->
                            <div class="form-control">
                                <label class="label" for="subject">
                                    <span class="label-text"><?php echo $translations['form']['fields']['subject']; ?></span>
                                    <span class="label-text-alt text-error">*</span>
                                </label>
                                <input type="text" 
                                       id="subject"
                                       name="subject" 
                                       class="input input-bordered w-full<?php echo isset($errors['subject']) ? ' input-error' : ''; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                                       required
                                       maxlength="200"
                                       aria-required="true"
                                       aria-invalid="<?php echo isset($errors['subject']) ? 'true' : 'false'; ?>"
                                       aria-describedby="<?php echo isset($errors['subject']) ? 'subject-error' : ''; ?>">
                                <?php if (isset($errors['subject'])): ?>
                                    <div class="label" id="subject-error">
                                        <span class="label-text-alt text-error" role="alert"><?php echo htmlspecialchars($errors['subject']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Message Field -->
                            <div class="form-control">
                                <label class="label" for="message">
                                    <span class="label-text"><?php echo $translations['form']['fields']['message']; ?></span>
                                    <span class="label-text-alt text-error">*</span>
                                </label>
                                <textarea id="message"
                                          name="message" 
                                          class="textarea textarea-bordered h-32<?php echo isset($errors['message']) ? ' textarea-error' : ''; ?>"
                                          required
                                          maxlength="3000"
                                          aria-required="true"
                                          aria-invalid="<?php echo isset($errors['message']) ? 'true' : 'false'; ?>"
                                          aria-describedby="message-length <?php echo isset($errors['message']) ? 'message-error' : ''; ?>"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                <div class="flex justify-end mt-1">
                                    <span class="text-sm text-base-content/70" id="message-length">0/3000</span>
                                </div>
                                <?php if (isset($errors['message'])): ?>
                                    <div class="label" id="message-error">
                                        <span class="label-text-alt text-error" role="alert"><?php echo htmlspecialchars($errors['message']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" 
                                    name="contact_submit" 
                                    class="btn btn-primary w-full"
                                    aria-label="<?php echo $translations['form']['buttons']['send']; ?>">
                                <?php echo $translations['form']['buttons']['send']; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Side Information -->
            <div class="lg:col-span-3 space-y-6 w-full" data-aos="fade-left">
                <!-- Top Row: Business Hours and Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Business Hours Card -->
                    <div class="card bg-base-200 shadow-xl h-full overflow-hidden">
                        <div class="card-body">
                            <h3 class="text-xl font-semibold mb-4 flex items-center gap-2 flex-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?php echo $translations['business_hours']['title']; ?>
                                <span class="badge business-status-badge ml-2" role="status"></span>
                            </h3>
                            <!-- Weekdays -->
                            <div class="space-y-2 overflow-x-auto">
                                <?php foreach ($businessHours as $day => $hours): ?>
                                    <div class="flex justify-between items-center flex-wrap gap-2">
                                        <span class="font-medium"><?php echo $day; ?></span>
                                        <div class="text-sm flex flex-wrap gap-2">
                                            <?php if ($hours[0] === 'Closed'): ?>
                                                <span class="badge badge-error whitespace-nowrap"><?php echo $translations['business_hours']['status']['closed']; ?></span>
                                            <?php else: ?>
                                                <?php foreach ($hours as $index => $range): ?>
                                                    <span class="badge badge-primary whitespace-nowrap"><?php echo $range; ?></span>
                                                    <?php if ($index === 0 && count($hours) > 1): ?>
                                                        <span class="mx-1 text-gray-400">|</span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Contact Card -->
                    <div class="card bg-base-200 shadow-xl overflow-hidden">
                        <div class="card-body">
                            <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <?php echo $translations['quick_contact']['title']; ?>
                            </h3>
                            <div class="space-y-4">
                                <a href="tel:<?php echo htmlspecialchars($location['phone']); ?>" 
                                   class="btn btn-primary w-full gap-2"
                                   aria-label="<?php echo $translations['quick_contact']['call_now']; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <?php echo $translations['quick_contact']['call_now']; ?>
                                </a>
                                <a href="mailto:<?php echo htmlspecialchars($location['email']); ?>" 
                                   class="btn btn-primary w-full gap-2"
                                   aria-label="<?php echo $translations['quick_contact']['email_us']; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <?php echo $translations['quick_contact']['email_us']; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location Card -->
                <div class="card bg-base-200 shadow-xl overflow-hidden">
                    <div class="card-body">
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <?php echo $translations['location']['title']; ?>
                        </h3>
                        <address class="not-italic mb-4">
                            <?php echo htmlspecialchars($location['address']); ?>
                        </address>
                        <div class="flex gap-2 flex-wrap">
                            <a href="https://maps.google.com/?q=<?php echo urlencode($location['address']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="btn btn-outline gap-2 flex-1 min-w-[200px]"
                               aria-label="<?php echo $translations['location']['get_directions']; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                <?php echo $translations['location']['get_directions']; ?>
                            </a>
                            <button onclick="copyAddress()" 
                                    class="btn btn-outline gap-2 flex-1 min-w-[200px]"
                                    aria-label="<?php echo $translations['location']['copy_address']; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                <?php echo $translations['location']['copy_address']; ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Social Media Card -->
                <div class="card bg-base-200 shadow-xl overflow-hidden">
                    <div class="card-body">
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                            <?php echo $translations['social']['title']; ?>
                        </h3>
                        <div class="flex gap-4 justify-center flex-wrap">
                            <?php foreach ($socialLinks as $platform => $data): ?>
                            <a href="<?php echo htmlspecialchars($data['url']); ?>" 
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-circle btn-lg btn-ghost hover:bg-base-300 transition-colors duration-300"
                               aria-label="<?php echo htmlspecialchars($data['title']); ?>">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <?php
                                    switch($platform) {
                                        case 'facebook':
                                            echo '<path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z" />';
                                            break;
                                        case 'twitter':
                                            echo '<path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />';
                                            break;
                                        case 'instagram':
                                            echo '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />';
                                            break;
                                        case 'linkedin':
                                            echo '<path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" />';
                                            break;
                                    }
                                    ?>
                                </svg>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pass translations to JavaScript -->
<script>
const translations = <?php echo json_encode($jsTranslations); ?>;
</script>
<?php
// End of file 