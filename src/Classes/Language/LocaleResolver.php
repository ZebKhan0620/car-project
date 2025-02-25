<?php

namespace Classes\Language;

class LocaleResolver {
    private $defaultLocale = 'en';
    private $supportedLocales = ['en', 'ja'];

    public function resolveLocale(): string {
        // Check session first
        $locale = $this->getLocaleFromSession();
        if ($locale) {
            return $locale;
        }

        // Check browser preferences
        $locale = $this->getLocaleFromBrowser();
        if ($locale) {
            return $locale;
        }

        // Fallback to default
        return $this->defaultLocale;
    }

    private function getLocaleFromSession(): ?string {
        $session = \Classes\Auth\Session::getInstance();
        $locale = $session->get('locale');
        
        return $this->isValidLocale($locale) ? $locale : null;
    }

    private function getLocaleFromBrowser(): ?string {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $browserLocales = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($browserLocales as $browserLocale) {
            $locale = substr($browserLocale, 0, 2);
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        return null;
    }

    private function isValidLocale(?string $locale): bool {
        return $locale !== null && in_array($locale, $this->supportedLocales);
    }

    public function getSupportedLocales(): array {
        return $this->supportedLocales;
    }
} 