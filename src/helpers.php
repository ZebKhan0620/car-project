<?php

use Classes\Language\TranslationManager;

if (!function_exists('__')) {
    /**
     * Translate a message
     *
     * @param string $id The message id
     * @param array $parameters Parameters to be replaced in the message
     * @param string|null $domain The domain for the message or null to use the default
     * @param string|null $locale The locale or null to use the default
     * @return string The translated message
     */
    function __(string $id, array $parameters = [], string $domain = null, string $locale = null): string {
        return TranslationManager::getInstance()->trans($id, $parameters, $domain, $locale);
    }
}

if (!function_exists('get_current_locale')) {
    /**
     * Get the current locale
     *
     * @return string The current locale
     */
    function get_current_locale(): string {
        return TranslationManager::getInstance()->getLocale();
    }
}

if (!function_exists('get_available_locales')) {
    /**
     * Get available locales
     *
     * @return array List of available locales
     */
    function get_available_locales(): array {
        return TranslationManager::getInstance()->getAvailableLocales();
    }
} 