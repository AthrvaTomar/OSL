const custom = ($) => {
    $(document).ready(function () {
        $('.tn-slider').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: true,
            dots: false
            // autoplay: true,
            // infinite: true,
        });
       
    })
}
custom(jQuery);