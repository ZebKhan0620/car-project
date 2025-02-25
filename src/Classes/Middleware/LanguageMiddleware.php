<?php

namespace Classes\Middleware;

use Classes\Language\TranslationManager;
use Classes\Language\LocaleResolver;

class LanguageMiddleware {
    private $translationManager;
    private $localeResolver;

    public function __construct() {
        $this->translationManager = TranslationManager::getInstance();
        $this->localeResolver = new LocaleResolver();
    }

    public function handle(): void {
        // Check if locale is set in URL
        if (isset($_GET['locale'])) {
            $locale = $_GET['locale'];
            try {
                $this->translationManager->setLocale($locale);
                
                // Remove locale from URL and redirect
                $params = $_GET;
                unset($params['locale']);
                $queryString = http_build_query($params);
                $redirectUrl = $_SERVER['PHP_SELF'] . ($queryString ? "?{$queryString}" : '');
                
                header("Location: {$redirectUrl}");
                exit;
            } catch (\InvalidArgumentException $e) {
                // Invalid locale, ignore and continue with default
            }
        }

        // If no locale is set in session, resolve it
        if (!isset($_SESSION['locale'])) {
            $locale = $this->localeResolver->resolveLocale();
            $this->translationManager->setLocale($locale);
        }
    }
} 