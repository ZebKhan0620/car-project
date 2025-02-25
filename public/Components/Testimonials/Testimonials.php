<?php

namespace Components\Testimonials;

use Classes\Language\TranslationManager;

class Testimonials {
    private $testimonials;
    private $translationManager;

    public function __construct() {
        $this->translationManager = TranslationManager::getInstance();
        
        // Initialize testimonials from translations
        $this->testimonials = [
            [
                'content' => __('testimonials.items.0.content'),
                'author' => __('testimonials.items.0.author'),
                'role' => __('testimonials.items.0.role'),
                'image' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=300&h=300&q=80',
                'image_alt' => __('testimonials.items.0.image_alt')
            ],
            [
                'content' => __('testimonials.items.1.content'),
                'author' => __('testimonials.items.1.author'),
                'role' => __('testimonials.items.1.role'),
                'image' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=300&h=300&q=80',
                'image_alt' => __('testimonials.items.1.image_alt')
            ],
            [
                'content' => __('testimonials.items.2.content'),
                'author' => __('testimonials.items.2.author'),
                'role' => __('testimonials.items.2.role'),
                'image' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=300&h=300&q=80',
                'image_alt' => __('testimonials.items.2.image_alt')
            ]
        ];
    }

    public function render(): string {
        $html = '
        <!-- Testimonials Section -->
        <section id="testimonials" class="py-20 bg-base-100 overflow-hidden">
            <div class="max-w-sm md:max-w-4xl mx-auto px-4 md:px-8 lg:px-12">
                <!-- Section Header -->
                <div class="section-header text-center mb-16">
                    <h2 class="text-3xl font-bold text-base-content mb-4">' . __('testimonials.section.title') . '</h2>
                    <p class="text-base-content/70">' . __('testimonials.section.subtitle') . '</p>
                </div>

                <div class="relative grid grid-cols-1 md:grid-cols-2 gap-20">
                    <!-- Image Column -->
                    <div>
                        <div class="relative h-[400px] w-full">
                            <div class="testimonials-images absolute inset-0 preserve-3d">';
                            
                            foreach ($this->testimonials as $index => $testimonial) {
                                // Create data attribute with testimonial data
                                $testimonialData = htmlspecialchars(json_encode([
                                    'author' => $testimonial['author'],
                                    'role' => $testimonial['role'],
                                    'content' => $testimonial['content']
                                ]), ENT_QUOTES, 'UTF-8');

                                $html .= '
                                <div class="testimonial-image absolute inset-0 preserve-3d" data-testimonial="' . $testimonialData . '">
                                    <div class="relative h-full w-full preserve-3d">
                                        <div class="absolute inset-0 backface-hidden preserve-3d">
                                            <img 
                                                src="' . $testimonial['image'] . '"
                                                alt="' . $testimonial['image_alt'] . '"
                                                class="h-full w-full rounded-3xl object-cover object-center shadow-2xl"
                                                draggable="false"
                                            />
                                            <div class="absolute inset-0 rounded-3xl ring-1 ring-black/10 bg-gradient-to-b from-black/5 to-black/20"></div>
                                        </div>
                                    </div>
                                </div>';
                            }

                            $html .= '
                            </div>
                        </div>
                    </div>

                    <!-- Content Column -->
                    <div class="flex justify-between flex-col py-4">
                        <div class="testimonials-content">
                            <div class="testimonial-text">
                                <!-- Initial content from first testimonial -->
                                <h3 class="text-2xl font-bold text-base-content">' . htmlspecialchars($this->testimonials[0]['author']) . '</h3>
                                <p class="text-sm text-base-content/50">' . htmlspecialchars($this->testimonials[0]['role']) . '</p>
                                <div class="text-lg text-base-content/80 mt-8">' . htmlspecialchars($this->testimonials[0]['content']) . '</div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="flex gap-4 pt-12 md:pt-0">
                            <button 
                                class="testimonial-prev h-7 w-7 rounded-full bg-base-200 hover:bg-base-300 flex items-center justify-center group transition-colors duration-200"
                                aria-label="' . __('testimonials.navigation.previous') . '"
                            >
                                <svg class="h-5 w-5 text-base-content group-hover:rotate-12 transition-transform duration-300" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button 
                                class="testimonial-next h-7 w-7 rounded-full bg-base-200 hover:bg-base-300 flex items-center justify-center group transition-colors duration-200"
                                aria-label="' . __('testimonials.navigation.next') . '"
                            >
                                <svg class="h-5 w-5 text-base-content group-hover:-rotate-12 transition-transform duration-300" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <style>
            .preserve-3d {
                transform-style: preserve-3d;
                perspective: 1000px;
            }
            .backface-hidden {
                backface-visibility: hidden;
            }
            </style>
        </section>';

        return $html;
    }
} 