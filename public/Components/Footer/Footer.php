<?php

class Footer {
    private $baseUrl;
    private $translationManager;
    private $currentLocale;
    private $availableLocales;

    public function __construct() {
        $this->baseUrl = '/car-project/public';
        $this->translationManager = \Classes\Language\TranslationManager::getInstance();
        $this->currentLocale = $this->translationManager->getLocale();
        $this->availableLocales = $this->translationManager->getAvailableLocales();
    }

    private function getLanguageName(string $locale): string {
        $names = [
            'en' => 'English',
            'ja' => '日本語'
        ];
        return $names[$locale] ?? $locale;
    }

    private function renderLanguageSwitcher(): string {
        $html = '<div class="flex items-center gap-2">';
        
        foreach ($this->availableLocales as $locale) {
            $isActive = $locale === $this->currentLocale;
            $buttonClass = $isActive 
                ? 'btn btn-sm btn-primary' 
                : 'btn btn-sm btn-ghost hover:btn-primary';
            
            $html .= sprintf(
                '<a href="?lang=%s" class="%s" aria-label="%s" %s>%s</a>',
                $locale,
                $buttonClass,
                sprintf('Switch language to %s', $this->getLanguageName($locale)),
                $isActive ? 'aria-current="true"' : '',
                $this->getLanguageName($locale)
            );
        }
        
        $html .= '</div>';
        return $html;
    }

    private function getSocialIcons() {
        return [
            'twitter' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"></path></svg>',
            'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"></path></svg>',
            'whatsapp' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>'
        ];
    }

    public function render(): string {
        $socialIcons = $this->getSocialIcons();
        $translations = [
            'about_services' => $this->translationManager->trans('footer.sections.about_services'),
            'about_us' => $this->translationManager->trans('footer.links.about_us'),
            'car_services' => $this->translationManager->trans('footer.sections.car_services'),
            'featured_cars' => $this->translationManager->trans('footer.links.featured_cars'),
            'search_cars' => $this->translationManager->trans('footer.links.search_cars'),
            'add_listing' => $this->translationManager->trans('footer.links.add_listing'),
            'my_favorites' => $this->translationManager->trans('footer.links.my_favorites'),
            'stay_updated' => $this->translationManager->trans('footer.sections.stay_updated'),
            'email_placeholder' => $this->translationManager->trans('footer.newsletter.email_placeholder'),
            'subscribe_button' => $this->translationManager->trans('footer.newsletter.subscribe_button'),
            'back_to_top' => $this->translationManager->trans('footer.actions.back_to_top'),
            'copyright' => $this->translationManager->trans('footer.copyright', ['year' => date('Y')]),
            'social_facebook' => $this->translationManager->trans('footer.social.facebook_aria'),
            'social_twitter' => $this->translationManager->trans('footer.social.twitter_aria'),
            'social_whatsapp' => $this->translationManager->trans('footer.social.whatsapp_aria'),
            'brand_toyota' => $this->translationManager->trans('footer.brands.toyota_alt'),
            'brand_honda' => $this->translationManager->trans('footer.brands.honda_alt'),
            'brand_mercedes' => $this->translationManager->trans('footer.brands.mercedes_alt')
        ];

        // Get current URL parameters for language switcher
        $currentParams = $_GET;
        unset($currentParams['lang']); // Remove lang parameter if exists
        $currentQueryString = !empty($currentParams) ? '&' . http_build_query($currentParams) : '';
        
        return '
        <footer>
            <div class="container mx-auto px-4 py-8">
                <!-- Upper section with logo and actions -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                    <div class="footer-logo mb-4 md:mb-0">
                        <a href="/car-project/public/index.php" class="flex items-center gap-2 hover:opacity-90 transition-all duration-200">
                                <span class="text-2xl font-black bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent">
                                    CarVerse
                                </span>
                                <div class="w-px h-6 bg-base-300/50"></div>
                                <span class="text-2xl font-black bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent">DriveLink</span>
                            </a>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            ' . implode('', array_map(function($locale) use ($currentQueryString) {
                                $isActive = $locale === $this->currentLocale;
                                $buttonClass = $isActive 
                                    ? 'btn btn-sm btn-primary' 
                                    : 'btn btn-sm btn-ghost hover:btn-primary';
                                return sprintf(
                                    '<a href="?lang=%s%s" class="%s" aria-label="%s" %s>%s</a>',
                                    $locale,
                                    $currentQueryString,
                                    $buttonClass,
                                    sprintf('Switch language to %s', $this->getLanguageName($locale)),
                                    $isActive ? 'aria-current="true"' : '',
                                    $this->getLanguageName($locale)
                                );
                            }, $this->availableLocales)) . '
                        </div>
                        <button onclick="scrollToTop()" class="btn btn-primary hover:scale-105 transition-transform duration-200">' . $translations['back_to_top'] . '</button>
                    </div>
                </div>
                
                <!-- Main footer content -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 py-8">
                    <!-- About & Services -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">' . $translations['about_services'] . '</h3>
                        <ul class="space-y-2">
                            <li><a href="' . $this->baseUrl . '/about.php" class="hover:text-primary transition-colors">' . $translations['about_us'] . '</a></li>
                        </ul>
                    </div>

                    <!-- Car Services -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">' . $translations['car_services'] . '</h3>
                        <ul class="space-y-2">
                            <li><a href="' . $this->baseUrl . '/Components/Cars/index.php" class="hover:text-primary transition-colors">' . $translations['featured_cars'] . '</a></li>
                            <li><a href="' . $this->baseUrl . '/Components/Cars/search.php" class="hover:text-primary transition-colors">' . $translations['search_cars'] . '</a></li>
                            <li><a href="' . $this->baseUrl . '/Components/Cars/add-listing.php" class="hover:text-primary transition-colors">' . $translations['add_listing'] . '</a></li>
                            <li><a href="' . $this->baseUrl . '/Components/Cars/favorites.php" class="hover:text-primary transition-colors">' . $translations['my_favorites'] . '</a></li>
                        </ul>
                    </div>

                    <!-- Newsletter -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">' . $translations['stay_updated'] . '</h3>
                        <form class="space-y-4" id="newsletter-form">
                            <div>
                                <input type="email" 
                                       placeholder="' . $translations['email_placeholder'] . '" 
                                       class="input input-bordered w-full newsletter-input" 
                                       required>
                            </div>
                            <button type="submit" class="btn btn-primary w-full">
                                ' . $translations['subscribe_button'] . '
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Trusted Brands -->
                <div class="py-8 border-t border-base-300">
                    <div class="flex flex-wrap justify-center gap-8">
                        <img src="' . $this->baseUrl . '/assets/images/car-brands/toyota-1-red.svg" alt="' . $translations['brand_toyota'] . '" class="brand-logo">
                        <img src="' . $this->baseUrl . '/assets/images/car-brands/honda-1-gray.svg" alt="' . $translations['brand_honda'] . '" class="brand-logo">
                        <img src="' . $this->baseUrl . '/assets/images/car-brands/mercedes-benz-gray-9.svg" alt="' . $translations['brand_mercedes'] . '" class="brand-logo">
                    </div>
                </div>

                <!-- Bottom Section -->
                <div class="border-t border-base-300 py-8">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="text-sm text-base-content/80">
                            ' . $translations['copyright'] . '
                        </div>
                        <div class="flex gap-4 mt-4 md:mt-0">
                            <a href="#" class="social-icon" aria-label="' . $translations['social_facebook'] . '">' . $socialIcons['facebook'] . '</a>
                            <a href="#" class="social-icon" aria-label="' . $translations['social_twitter'] . '">' . $socialIcons['twitter'] . '</a>
                            <a href="#" class="social-icon" aria-label="' . $translations['social_whatsapp'] . '">' . $socialIcons['whatsapp'] . '</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Include CSS and JS -->
        <link rel="stylesheet" href="' . $this->baseUrl . '/css/components/footer.css">
        <script src="' . $this->baseUrl . '/js/components/newsletter.js"></script>
        <script>
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        }
        </script>';
    }
}