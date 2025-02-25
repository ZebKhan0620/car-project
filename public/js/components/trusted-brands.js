document.addEventListener("DOMContentLoaded", () => {
    const isDesktop = () => window.innerWidth > 767.9;

    let gap = 15;

    if (isDesktop()) gap = 0.0285 * window.innerWidth;

    const sliders = [];

    ["#horizontal-ticker-rtl", "#horizontal-ticker-ltr"].forEach(
        (query, index) => {
            sliders.push(
                new Swiper(query, {
                    loop: true,
                    slidesPerView: "auto",
                    spaceBetween: gap,
                    speed: 8000,
                    allowTouchMove: false,
                    autoplay: {
                        delay: 0,
                        reverseDirection: index,
                        disableOnInteraction: false
                    }
                })
            );
        }
    );

    window.addEventListener("resize", () => {
        isDesktop() ? (gap = 0.0285 * window.innerWidth) : (gap = 15);

        sliders.forEach((slider) => {
            slider.params.spaceBetween = gap;
            slider.update();
        });
    });
}); 