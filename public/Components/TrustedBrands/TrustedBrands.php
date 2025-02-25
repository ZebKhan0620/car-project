<?php

namespace Components\TrustedBrands;

use Classes\Language\TranslationManager;

class TrustedBrands {
    private $translationManager;

    public function __construct() {
        $this->translationManager = TranslationManager::getInstance();
    }

    public function render() {
        ob_start();
        ?>
        <section class="base-template">
            <div class="wrapper base-template__wrapper">
                <h1 class="base-template__title">
                    <?php echo __('trusted_brands.section.title'); ?>
                </h1>
                <div class="base-template__text">
                    <?php echo __('trusted_brands.section.subtitle'); ?>
                </div>
                <div class="base-template__content">
                    <div class="horizontal-ticker">
                        <!-- Horizontal Ticker: Slider RTL -->
                        <div id="horizontal-ticker-rtl" class="swiper horizontal-ticker__slider">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-1-0.svg" alt="Toshiba">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-1-1.svg" alt="Toshiba">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-2-0.svg" alt="Fujitsu">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-2-1.svg" alt="Fujitsu">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-3-0.svg" alt="Philips">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-3-1.svg" alt="Philips">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-4-0.svg" alt="Casio">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-4-1.svg" alt="Casio">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-5-0.svg" alt="Nichicon">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-5-1.svg" alt="Nichicon">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-6-0.svg" alt="Sony">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-6-1.svg" alt="Sony">
                                </div>

                                <!-- slides copies -->
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-1-0.svg" alt="Toshiba">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-1-1.svg" alt="Toshiba">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-2-0.svg" alt="Fujitsu">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-2-1.svg" alt="Fujitsu">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-3-0.svg" alt="Philips">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-3-1.svg" alt="Philips">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-4-0.svg" alt="Casio">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-4-1.svg" alt="Casio">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-5-0.svg" alt="Nichicon">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-5-1.svg" alt="Nichicon">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-6-0.svg" alt="Sony">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-6-1.svg" alt="Sony">
                                </div>
                            </div>
                        </div>

                        <!-- Horizontal Ticker: Slider LTR -->
                        <div id="horizontal-ticker-ltr" class="swiper horizontal-ticker__slider">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-7-0.svg" alt="Daikin">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-7-1.svg" alt="Daikin">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-8-0.svg" alt="Kyocera">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-8-1.svg" alt="Kyocera">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-9-0.svg" alt="Omron">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-9-1.svg" alt="Omron">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-10-0.svg" alt="Hoya">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-10-1.svg" alt="Hoya">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-11-0.svg" alt="Olympus">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-11-1.svg" alt="Olympus">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-12-0.svg" alt="Fujikura">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-12-1.svg" alt="Fujikura">
                                </div>

                                <!-- slides copies -->
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-7-0.svg" alt="Daikin">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-7-1.svg" alt="Daikin">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-8-0.svg" alt="Kyocera">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-8-1.svg" alt="Kyocera">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-9-0.svg" alt="Omron">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-9-1.svg" alt="Omron">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-10-0.svg" alt="Hoya">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-10-1.svg" alt="Hoya">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-11-0.svg" alt="Olympus">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-11-1.svg" alt="Olympus">
                                </div>
                                <div class="swiper-slide horizontal-ticker__slide">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-12-0.svg" alt="Fujikura">
                                    <img src="https://bato-web-agency.github.io/bato-shared/img/ticker-1/image-12-1.svg" alt="Fujikura">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
?> 