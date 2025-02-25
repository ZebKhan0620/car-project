<?php

namespace Components\Contact;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../src/Classes/Auth/Session.php';
require_once __DIR__ . '/../../../src/Classes/Contact/ContactStorage.php';
require_once __DIR__ . '/../../../src/Classes/Storage/JsonStorage.php';

use Classes\Contact\ContactStorage;
use Classes\Security\CSRF;
use Classes\Language\TranslationManager;
use Classes\Auth\Session;

class Contact {
    private $errors = [];
    private $success = false;
    private $contactStorage;
    private $translationManager;
    private $csrf;
    private $maxAttemptsPerHour = 5;
    private $rateLimitKey = 'contact_form_attempts';
    private $session;

    public function __construct() {
        // Initialize session first
        $this->session = Session::getInstance();
        $this->session->start();

        // Initialize other dependencies
        try {
            $this->contactStorage = new ContactStorage();
            $this->translationManager = TranslationManager::getInstance();
            $this->csrf = new CSRF();
        } catch (\Exception $e) {
            error_log("Contact initialization error: " . $e->getMessage());
            throw $e;
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
            $this->handleSubmission();
        }
    }

    private function handleSubmission() {
        // Check CSRF token first
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $this->errors['system'] = 'Invalid security token. Please refresh the page and try again.';
            return;
        }

        // Check honeypot
        if (!empty($_POST['honeypot'])) {
            // Silently fail for potential spam bots
            $this->success = true;
            return;
        }

        // Check rate limit
        if ($this->isRateLimited()) {
            $this->errors['system'] = 'Too many attempts. Please try again later.';
            return;
        }

        // Validate form data
        $this->validateForm();

        if (empty($this->errors)) {
            // Sanitize and prepare submission data
            $submissionData = [
                'name' => $this->sanitizeInput($_POST['name']),
                'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
                'phone' => $this->sanitizeInput($_POST['phone'] ?? ''),
                'subject' => $this->sanitizeInput($_POST['subject']),
                'message' => $this->sanitizeInput($_POST['message']),
                'contact_purpose' => $this->sanitizeInput($_POST['contact_purpose']),
                'ip_address' => hash('sha256', $_SERVER['REMOTE_ADDR']), // Hash IP for privacy
                'user_agent' => $this->sanitizeInput($_SERVER['HTTP_USER_AGENT'])
            ];

            // Save to JSON storage
            if ($this->contactStorage->saveSubmission($submissionData)) {
                $this->success = true;
                $this->incrementAttempts(); // Track successful submission
                // Log successful submission
                error_log("[Contact Form] New submission from: " . $submissionData['email']);
            } else {
                $this->errors['system'] = 'Failed to save your message. Please try again.';
                error_log("[Contact Form] Failed to save submission");
            }
        }
    }

    private function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return trim($input);
    }

    private function isRateLimited(): bool {
        $attempts = $_SESSION[$this->rateLimitKey] ?? [];
        $oneHourAgo = time() - 3600;
        
        // Clean up old attempts
        $attempts = array_filter($attempts, function($timestamp) use ($oneHourAgo) {
            return $timestamp > $oneHourAgo;
        });
        
        $_SESSION[$this->rateLimitKey] = $attempts;
        
        return count($attempts) >= $this->maxAttemptsPerHour;
    }

    private function incrementAttempts(): void {
        $attempts = $_SESSION[$this->rateLimitKey] ?? [];
        $attempts[] = time();
        $_SESSION[$this->rateLimitKey] = $attempts;
    }

    private function validateForm() {
        // Validate name
        if (empty($_POST['name'])) {
            $this->errors['name'] = $this->translationManager->trans('contact.form.validation.name.required');
        } elseif (strlen($_POST['name']) > 100) {
            $this->errors['name'] = $this->translationManager->trans('contact.form.validation.name.max_length');
        }

        // Validate email
        if (empty($_POST['email'])) {
            $this->errors['email'] = $this->translationManager->trans('contact.form.validation.email.required');
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = $this->translationManager->trans('contact.form.validation.email.invalid');
        }

        // Validate phone (optional)
        if (!empty($_POST['phone']) && !preg_match('/^[0-9\-\(\)\/\+\s]*$/', $_POST['phone'])) {
            $this->errors['phone'] = $this->translationManager->trans('contact.form.validation.phone.invalid');
        }

        // Validate subject
        if (empty($_POST['subject'])) {
            $this->errors['subject'] = $this->translationManager->trans('contact.form.validation.subject.required');
        } elseif (strlen($_POST['subject']) > 200) {
            $this->errors['subject'] = $this->translationManager->trans('contact.form.validation.subject.max_length');
        }

        // Validate message
        if (empty($_POST['message'])) {
            $this->errors['message'] = $this->translationManager->trans('contact.form.validation.message.required');
        } elseif (strlen($_POST['message']) > 3000) {
            $this->errors['message'] = $this->translationManager->trans('contact.form.validation.message.max_length');
        }

        // Validate contact purpose
        if (empty($_POST['contact_purpose']) || !in_array($_POST['contact_purpose'], ['general', 'sales', 'support', 'partnership'])) {
            $this->errors['contact_purpose'] = $this->translationManager->trans('contact.form.validation.contact_purpose.invalid');
        }
    }

    public function render() {
        // Get CSRF token for the form
        $csrf_token = $this->csrf->getToken();
        
        // Get current locale for formatting
        $locale = $this->translationManager->getLocale();

        // Get form errors and success state
        $errors = $this->getErrors() ?? [];
        $success = $this->isSuccess() ?? false;

        // Prepare all translations
        $translations = [
            'title' => $this->translationManager->trans('contact.title'),
            'subtitle' => $this->translationManager->trans('contact.subtitle'),
            'form' => [
                'fields' => [
                    'name' => $this->translationManager->trans('contact.form.fields.name'),
                    'email' => $this->translationManager->trans('contact.form.fields.email'),
                    'phone' => $this->translationManager->trans('contact.form.fields.phone'),
                    'subject' => $this->translationManager->trans('contact.form.fields.subject'),
                    'message' => $this->translationManager->trans('contact.form.fields.message')
                ],
                'buttons' => [
                    'send' => $this->translationManager->trans('contact.form.buttons.send')
                ]
            ],
            'purposes' => [
                'general' => [
                    'title' => $this->translationManager->trans('contact.purposes.general.title'),
                    'description' => $this->translationManager->trans('contact.purposes.general.description')
                ],
                'sales' => [
                    'title' => $this->translationManager->trans('contact.purposes.sales.title'),
                    'description' => $this->translationManager->trans('contact.purposes.sales.description')
                ],
                'support' => [
                    'title' => $this->translationManager->trans('contact.purposes.support.title'),
                    'description' => $this->translationManager->trans('contact.purposes.support.description')
                ],
                'partnership' => [
                    'title' => $this->translationManager->trans('contact.purposes.partnership.title'),
                    'description' => $this->translationManager->trans('contact.purposes.partnership.description')
                ]
            ],
            'business_hours' => [
                'title' => $this->translationManager->trans('contact.business_hours.title'),
                'status' => [
                    'open' => $this->translationManager->trans('contact.business_hours.status.open'),
                    'closed' => $this->translationManager->trans('contact.business_hours.status.closed')
                ],
                'days' => [
                    'monday' => $this->translationManager->trans('contact.business_hours.days.monday'),
                    'tuesday' => $this->translationManager->trans('contact.business_hours.days.tuesday'),
                    'wednesday' => $this->translationManager->trans('contact.business_hours.days.wednesday'),
                    'thursday' => $this->translationManager->trans('contact.business_hours.days.thursday'),
                    'friday' => $this->translationManager->trans('contact.business_hours.days.friday'),
                    'saturday' => $this->translationManager->trans('contact.business_hours.days.saturday'),
                    'sunday' => $this->translationManager->trans('contact.business_hours.days.sunday')
                ]
            ],
            'quick_contact' => [
                'title' => $this->translationManager->trans('contact.quick_contact.title'),
                'call_now' => $this->translationManager->trans('contact.quick_contact.call_now'),
                'email_us' => $this->translationManager->trans('contact.quick_contact.email_us')
            ],
            'location' => [
                'title' => $this->translationManager->trans('contact.location.title'),
                'get_directions' => $this->translationManager->trans('contact.location.get_directions'),
                'copy_address' => $this->translationManager->trans('contact.location.copy_address'),
                'address_copied' => $this->translationManager->trans('contact.location.address_copied')
            ],
            'social' => [
                'title' => $this->translationManager->trans('contact.social.title')
            ],
            'messages' => [
                'success' => $this->translationManager->trans('contact.messages.success'),
                'error' => $this->translationManager->trans('contact.messages.error'),
                'rate_limit' => $this->translationManager->trans('contact.messages.rate_limit'),
                'system_error' => $this->translationManager->trans('contact.messages.system_error')
            ]
        ];

        // Contact purposes with translations and icons
        $contactPurposes = [
            'general' => [
                'title' => $translations['purposes']['general']['title'],
                'description' => $translations['purposes']['general']['description'],
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />'
            ],
            'sales' => [
                'title' => $translations['purposes']['sales']['title'],
                'description' => $translations['purposes']['sales']['description'],
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
            ],
            'support' => [
                'title' => $translations['purposes']['support']['title'],
                'description' => $translations['purposes']['support']['description'],
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />'
            ],
            'partnership' => [
                'title' => $translations['purposes']['partnership']['title'],
                'description' => $translations['purposes']['partnership']['description'],
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />'
            ]
        ];

        // Prepare JavaScript translations
        $jsTranslations = [
            'businessHours' => [
                'open' => $this->translationManager->trans('contact.business_hours.status.open'),
                'closed' => $this->translationManager->trans('contact.business_hours.status.closed')
            ],
            'messages' => [
                'success' => $this->translationManager->trans('contact.messages.success'),
                'error' => $this->translationManager->trans('contact.messages.error'),
                'addressCopied' => $this->translationManager->trans('contact.location.address_copied'),
                'rateLimited' => $this->translationManager->trans('contact.messages.rate_limit'),
                'systemError' => $this->translationManager->trans('contact.messages.system_error')
            ],
            'validation' => [
                'name' => [
                    'required' => $this->translationManager->trans('contact.form.validation.name.required'),
                    'maxLength' => $this->translationManager->trans('contact.form.validation.name.max_length')
                ],
                'email' => [
                    'required' => $this->translationManager->trans('contact.form.validation.email.required'),
                    'invalid' => $this->translationManager->trans('contact.form.validation.email.invalid')
                ],
                'phone' => [
                    'invalid' => $this->translationManager->trans('contact.form.validation.phone.invalid')
                ],
                'subject' => [
                    'required' => $this->translationManager->trans('contact.form.validation.subject.required'),
                    'maxLength' => $this->translationManager->trans('contact.form.validation.subject.max_length')
                ],
                'message' => [
                    'required' => $this->translationManager->trans('contact.form.validation.message.required'),
                    'maxLength' => $this->translationManager->trans('contact.form.validation.message.max_length')
                ],
                'contactPurpose' => [
                    'invalid' => $this->translationManager->trans('contact.form.validation.contact_purpose.invalid')
                ]
            ],
            'form' => [
                'buttons' => [
                    'send' => $this->translationManager->trans('contact.form.buttons.send'),
                    'sending' => $this->translationManager->trans('contact.form.buttons.sending')
                ]
            ]
        ];

        // Business hours with locale-aware formatting
        $businessHours = [
            $translations['business_hours']['days']['monday'] => ['09:00-12:00', '13:00-17:00'],
            $translations['business_hours']['days']['tuesday'] => ['09:00-12:00', '13:00-17:00'],
            $translations['business_hours']['days']['wednesday'] => ['09:00-12:00', '13:00-17:00'],
            $translations['business_hours']['days']['thursday'] => ['09:00-12:00', '13:00-17:00'],
            $translations['business_hours']['days']['friday'] => ['09:00-12:00', '13:00-17:00'],
            $translations['business_hours']['days']['saturday'] => ['10:00-12:00', '13:00-15:00'],
            $translations['business_hours']['days']['sunday'] => ['Closed']
        ];

        // Location data with locale-specific formatting
        $location = [
            'address' => $locale === 'ja' ? 
                '〒123-4567 東京都千代田区カー通り1-2-3' : 
                '123 Car Street, Auto City, AC 12345',
            'phone' => $locale === 'ja' ? 
                '03-1234-5678' : 
                '+1 (555) 123-4567',
            'email' => 'contact@carmarketplace.com',
            'coordinates' => [
                'lat' => '35.6762',
                'lng' => '139.6503'
            ]
        ];

        // Social media links (no translations needed for icons)
        $socialLinks = [
            'facebook' => [
                'url' => '#',
                'title' => 'Facebook'
            ],
            'twitter' => [
                'url' => '#',
                'title' => 'Twitter'
            ],
            'instagram' => [
                'url' => '#',
                'title' => 'Instagram'
            ],
            'linkedin' => [
                'url' => '#',
                'title' => 'LinkedIn'
            ]
        ];

        // Extract variables to be used in the view
        extract([
            'errors' => $errors,
            'success' => $success,
            'csrf_token' => $csrf_token,
            'translationManager' => $this->translationManager,
            'translations' => $translations,
            'contactPurposes' => $contactPurposes,
            'businessHours' => $businessHours,
            'location' => $location,
            'socialLinks' => $socialLinks,
            'jsTranslations' => $jsTranslations
        ]);

        // Define constant to allow view access
        define('ALLOW_ACCESS', true);

        // Include the view file with all variables in scope
        ob_start();
        include __DIR__ . '/contact-view.php';
        return ob_get_clean();
    }

    public function getErrors() {
        return $this->errors;
    }

    public function isSuccess() {
        return $this->success;
    }
} 