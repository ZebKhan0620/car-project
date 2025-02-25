<?php

namespace Classes\Language;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Classes\Auth\Session;

class TranslationManager {
    private static $instance = null;
    private $translator;
    private $session;
    private $defaultLocale = 'en';
    private $availableLocales = ['en', 'ja'];

    private function __construct() {
        $this->session = Session::getInstance();
        $this->initializeTranslator();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeTranslator(): void {
        $locale = $this->session->get('locale') ?? $this->defaultLocale;
        $this->translator = new Translator($locale);
        $this->translator->addLoader('json', new JsonFileLoader());

        // Load translation files for all available locales
        foreach ($this->availableLocales as $locale) {
            $this->translator->addResource(
                'json',
                dirname(__DIR__, 3) . "/resources/translations/messages.{$locale}.json",
                $locale
            );
        }
    }

    public function setLocale(string $locale): void {
        if (!in_array($locale, $this->availableLocales)) {
            throw new \InvalidArgumentException("Unsupported locale: {$locale}");
        }

        $this->session->set('locale', $locale);
        $this->translator->setLocale($locale);
    }

    public function getLocale(): string {
        return $this->translator->getLocale();
    }

    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function getAvailableLocales(): array {
        return $this->availableLocales;
    }
} 