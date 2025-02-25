<?php

namespace Components\Header;

use Classes\Language\TranslationManager;

class LanguageSwitcher {
    private $translationManager;
    private $currentLocale;
    private $availableLocales;

    public function __construct() {
        $this->translationManager = TranslationManager::getInstance();
        $this->currentLocale = $this->translationManager->getLocale();
        $this->availableLocales = $this->translationManager->getAvailableLocales();
    }

    public function render(): string {
        $html = '<div class="relative inline-block text-left">';
        $html .= '<div>';
        $html .= '<button type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-indigo-500" id="language-menu-button" aria-expanded="true" aria-haspopup="true">';
        $html .= $this->getLanguageName($this->currentLocale);
        $html .= '<svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
        $html .= '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';
        $html .= '</svg>';
        $html .= '</button>';
        $html .= '</div>';

        // Dropdown menu
        $html .= '<div class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="language-menu-button" tabindex="-1" id="language-menu-items">';
        $html .= '<div class="py-1" role="none">';
        
        foreach ($this->availableLocales as $locale) {
            $isActive = $locale === $this->currentLocale;
            $html .= $this->renderLanguageOption($locale, $isActive);
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Add JavaScript for dropdown functionality
        $html .= $this->getJavaScript();

        return $html;
    }

    private function getLanguageName(string $locale): string {
        $names = [
            'en' => 'English',
            'ja' => '日本語'
        ];
        return $names[$locale] ?? $locale;
    }

    private function renderLanguageOption(string $locale, bool $isActive): string {
        $activeClass = $isActive ? 'bg-gray-100 text-gray-900' : 'text-gray-700';
        $html = '<a href="?locale=' . $locale . '" class="' . $activeClass . ' block px-4 py-2 text-sm hover:bg-gray-100" role="menuitem" tabindex="-1">';
        $html .= $this->getLanguageName($locale);
        $html .= '</a>';
        return $html;
    }

    private function getJavaScript(): string {
        return <<<JS
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const button = document.getElementById('language-menu-button');
                const menu = document.getElementById('language-menu-items');

                button.addEventListener('click', function() {
                    const expanded = button.getAttribute('aria-expanded') === 'true';
                    button.setAttribute('aria-expanded', !expanded);
                    menu.classList.toggle('hidden');
                });

                // Close the dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!button.contains(event.target) && !menu.contains(event.target)) {
                        button.setAttribute('aria-expanded', 'false');
                        menu.classList.add('hidden');
                    }
                });
            });
        </script>
        JS;
    }
} 