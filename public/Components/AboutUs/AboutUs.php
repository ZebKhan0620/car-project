<?php

namespace Components\AboutUs;

use Classes\Language\TranslationManager;

class AboutUs {
    private $stats;
    private $translationManager;

    public function __construct() {
        $this->translationManager = TranslationManager::getInstance();
        
        // Initialize statistics (in a real app, these would come from your database)
        $this->stats = [
            [
                'number' => __('about_us.stats.cars_listed.number'),
                'label' => __('about_us.stats.cars_listed.label'),
                'icon' => 'M19 15v4H5v-4h14m1-2H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-6c0-.55-.45-1-1-1M7 18.5c-.28 0-.5-.22-.5-.5s.22-.5.5-.5.5.22.5.5-.22.5-.5.5M19 5v4H5V5h14m1-2H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h16c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1M7 8.5c-.28 0-.5-.22-.5-.5s.22-.5.5-.5.5.22.5.5-.22.5-.5.5'
            ],
            [
                'number' => __('about_us.stats.active_users.number'),
                'label' => __('about_us.stats.active_users.label'),
                'icon' => 'M12 4a4 4 0 0 1 4 4 4 4 0 0 1-4 4 4 4 0 0 1-4-4 4 4 0 0 1 4-4m0 10c4.42 0 8 1.79 8 4v2H4v-2c0-2.21 3.58-4 8-4'
            ],
            [
                'number' => __('about_us.stats.success_deals.number'),
                'label' => __('about_us.stats.success_deals.label'),
                'icon' => 'M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z'
            ],
            [
                'number' => __('about_us.stats.trusted_partners.number'),
                'label' => __('about_us.stats.trusted_partners.label'),
                'icon' => 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z'
            ]
        ];
    }

    public function render(): string {
        $html = '
        <!-- About Us Section -->
        <section class="py-16 bg-base-100">
            <!-- Hero Section -->
            <div class="container mx-auto px-4">
                <div class="flex flex-col lg:flex-row items-center gap-8 mb-16" data-aos="fade-up">
                    <div class="lg:w-1/2">
                        <h2 class="text-4xl font-bold mb-6 text-primary" data-aos="fade-right" data-aos-delay="200">
                            ' . __('about_us.hero.title') . '
                        </h2>
                        <p class="text-lg mb-6 text-base-content/80" data-aos="fade-right" data-aos-delay="400">
                            ' . __('about_us.hero.description') . '
                        </p>
                        <div class="flex gap-4" data-aos="fade-right" data-aos-delay="600">
                            <button class="btn btn-primary hover:scale-105 transform transition-transform duration-200 hover:shadow-lg">
                                ' . __('about_us.hero.buttons.learn_more') . '
                            </button>
                            <button class="btn btn-outline hover:scale-105 transform transition-transform duration-200">
                                ' . __('about_us.hero.buttons.contact_us') . '
                            </button>
                        </div>
                    </div>
                    <div class="lg:w-1/2 relative perspective-1000">
                        <div class="relative z-10 transform-gpu hover:rotate-y-12 transition-transform duration-500" data-aos="fade-left" data-aos-delay="200">
                            <img src="https://images.unsplash.com/photo-1550355291-bbee04a92027?auto=format&fit=crop&q=80&w=500" 
                                 alt="' . __('about_us.hero.title') . '" 
                                 class="rounded-lg shadow-xl"
                            />
                        </div>
                        <div class="absolute top-[9rem] right-[-4.5rem] z-0 transform-gpu hover:rotate-y-12 transition-transform duration-500" data-aos="fade-left" data-aos-delay="400">
                            <img src="https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&q=80&w=500" 
                                 alt="' . __('about_us.hero.title') . '" 
                                 class="rounded-lg shadow-xl opacity-50"
                            />
                        </div>
                    </div>
                </div>

                <!-- Statistics Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
                ';
                
                foreach ($this->stats as $index => $stat) {
                    $delay = ($index + 1) * 200;
                    $html .= '
                    <div class="card bg-base-200 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2" 
                         data-aos="zoom-in" 
                         data-aos-delay="' . $delay . '">
                        <div class="card-body text-center relative overflow-hidden group">
                            <div class="absolute inset-0 bg-primary opacity-0 group-hover:opacity-5 transition-opacity duration-300"></div>
                            <div class="mx-auto w-16 h-16 mb-4 text-primary transform group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="animate-pulse">
                                    <path d="' . $stat['icon'] . '"/>
                                </svg>
                            </div>
                            <h3 class="text-3xl font-bold mb-2 text-primary counter relative">
                                <span class="counter-value">' . $stat['number'] . '</span>
                                <div class="absolute -inset-1 bg-primary opacity-0 group-hover:opacity-10 rounded-lg transition-opacity duration-300"></div>
                            </h3>
                            <p class="text-base-content/70">' . $stat['label'] . '</p>
                        </div>
                    </div>';
                }

                $html .= '
                </div>

                <!-- Services Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
                    <div class="card bg-base-200 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:rotate-1" 
                         data-aos="fade-right"
                         data-tilt
                         data-tilt-max="5">
                        <div class="card-body group">
                            <h3 class="card-title text-primary group-hover:scale-105 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                ' . __('about_us.services.import.title') . '
                            </h3>
                            <p class="group-hover:text-primary transition-colors duration-300">' . __('about_us.services.import.description') . '</p>
                        </div>
                    </div>
                    <div class="card bg-base-200 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:rotate-1" 
                         data-aos="fade-left"
                         data-tilt
                         data-tilt-max="5">
                        <div class="card-body group">
                            <h3 class="card-title text-primary group-hover:scale-105 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                ' . __('about_us.services.partnership.title') . '
                            </h3>
                            <p class="group-hover:text-primary transition-colors duration-300">' . __('about_us.services.partnership.description') . '</p>
                        </div>
                    </div>
                    <div class="card bg-base-200 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:rotate-1" 
                         data-aos="fade-right"
                         data-aos-delay="200"
                         data-tilt
                         data-tilt-max="5">
                        <div class="card-body group">
                            <h3 class="card-title text-primary group-hover:scale-105 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                ' . __('about_us.services.scheduling.title') . '
                            </h3>
                            <p class="group-hover:text-primary transition-colors duration-300">' . __('about_us.services.scheduling.description') . '</p>
                        </div>
                    </div>
                    <div class="card bg-base-200 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:rotate-1" 
                         data-aos="fade-left"
                         data-aos-delay="200"
                         data-tilt
                         data-tilt-max="5">
                        <div class="card-body group">
                            <h3 class="card-title text-primary group-hover:scale-105 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                ' . __('about_us.services.valuation.title') . '
                            </h3>
                            <p class="group-hover:text-primary transition-colors duration-300">' . __('about_us.services.valuation.description') . '</p>
                        </div>
                    </div>
                </div>

                <!-- Why Choose Us -->
                <div class="bg-base-200 rounded-xl p-8 shadow-xl transform hover:scale-[1.02] transition-transform duration-300" data-aos="fade-up">
                    <h2 class="text-3xl font-bold text-center mb-12 text-primary" data-aos="fade-up" data-aos-delay="200">
                        ' . __('about_us.why_choose_us.title') . '
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="text-center transform hover:scale-105 transition-transform duration-300" data-aos="fade-up" data-aos-delay="400">
                            <div class="w-16 h-16 mx-auto mb-4 text-primary animate-bounce">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-2">' . __('about_us.why_choose_us.features.trusted_platform.title') . '</h3>
                            <p class="text-base-content/70">' . __('about_us.why_choose_us.features.trusted_platform.description') . '</p>
                        </div>
                        <div class="text-center transform hover:scale-105 transition-transform duration-300" data-aos="fade-up" data-aos-delay="600">
                            <div class="w-16 h-16 mx-auto mb-4 text-primary animate-bounce">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-2">' . __('about_us.why_choose_us.features.expert_knowledge.title') . '</h3>
                            <p class="text-base-content/70">' . __('about_us.why_choose_us.features.expert_knowledge.description') . '</p>
                        </div>
                        <div class="text-center transform hover:scale-105 transition-transform duration-300" data-aos="fade-up" data-aos-delay="800">
                            <div class="w-16 h-16 mx-auto mb-4 text-primary animate-bounce">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-2">' . __('about_us.why_choose_us.features.support.title') . '</h3>
                            <p class="text-base-content/70">' . __('about_us.why_choose_us.features.support.description') . '</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>';

        return $html;
    }
} 