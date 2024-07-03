<script src="{{ asset('assets/home_page/js/script.js') }}"></script>


<!-- bootstrap  -->
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> --}}
<!-- fontawesome icons   -->
<script src="{{ asset('assets/home_page/js/1d2a297b20.js') }}"></script>


{{-- Sliders --}}
<script src="{{ asset('assets/home_page/js/owl.carousel.min.js') }}"></script>


<!-- custom script  -->
{{-- <script src="script.js"> --}}

</script>

<!-- bootstrap  -->
{{-- FAQs --}}
<script src="{{ asset('assets/home_page/js/bootstrap.bundle.min.js') }}"> </script>



<!-- swiper  -->
{{-- <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-element-bundle.min.js"></script> --}}

<!-- swiper  -->
{{-- <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script> --}}
<script src="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.js') }}"></script>

<script>
    $(document).ready(function() {
        // Initialize each carousel separately
        $(".swiperSect .slider-content.owl-carousel").each(function() {
            var owl = $(this).owlCarousel({
                loop: true,
                autoplay: true,
                autoplayTimeout: 1500,
                autoplaySpeed: 2000,
                margin: 30,
                nav: false,
                responsive: {
                    0: {
                        items: 1,
                    },
                    600: {
                        items: 3,
                    },
                    1000: {
                        items: 5,
                    },
                },
            });

            // Custom navigation buttons for this specific carousel
            $(this)
                .closest(".commonSlider")
                .find(".prev")
                .click(function() {
                    owl.trigger("prev.owl.carousel");
                });

            $(this)
                .closest(".commonSlider")
                .find(".next")
                .click(function() {
                    owl.trigger("next.owl.carousel");
                });
        });
    });

    // for pricingSection
    $(document).ready(function() {
        // Initialize each carousel separately
        $(".pricing .slider-content.owl-carousel").each(function() {
            var owl = $(this).owlCarousel({
                loop: false,
                autoplay: false,
                autoplayTimeout: 1000,
                autoplaySpeed: 2000,
                margin: 30,
                nav: false,
                dots: true,
                responsive: {
                    0: {
                        items: 1,
                    },
                    600: {
                        items: 2,
                    },
                    1000: {
                        items: 3,
                    },
                },
            });

            // Custom navigation buttons for this specific carousel
            $(this)
                .closest(".commonSlider")
                .find(".prev")
                .click(function() {
                    owl.trigger("prev.owl.carousel");
                });

            $(this)
                .closest(".commonSlider")
                .find(".next")
                .click(function() {
                    owl.trigger("next.owl.carousel");
                });
        });
    });




    // for counter

    document.addEventListener("DOMContentLoaded", function() {
        const counters = document.querySelectorAll('.numb');

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = +entry.target.getAttribute('data-target');
                    entry.target.innerText = 0;
                    const updateCounter = () => {
                        const value = +entry.target.innerText;
                        const increment = target /
                        150; // Adjust the speed of the counter by changing the denominator

                        if (value < target) {
                            entry.target.innerText = Math.ceil(value + increment);
                            setTimeout(updateCounter,
                            10); // Adjust the interval for smoother animation
                        } else {
                            entry.target.innerText = target;
                        }
                    };

                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });

        counters.forEach(counter => {
            observer.observe(counter);
        });
    });
</script>
