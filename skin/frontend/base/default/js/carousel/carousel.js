/* Mageaustralia_Carousel - self-contained carousel driver (vanilla JS, no jQuery).
 * Drives the bundled base/default widget template: prev/next, dots, autoplay
 * (with pause-on-hover) via a translateX track. One instance per .mhc element. */
(function () {
    'use strict';

    function initCarousel(el) {
        var track = el.querySelector('.mhc__track');
        var slides = el.querySelectorAll('.mhc__slide');
        if (!track || slides.length <= 1) {
            return;
        }
        var dots = el.querySelectorAll('.mhc__dot');
        var prev = el.querySelector('.mhc__nav--prev');
        var next = el.querySelector('.mhc__nav--next');
        var autoplay = parseInt(el.getAttribute('data-mhc-autoplay'), 10) || 0;
        var index = 0;
        var timer = null;

        function go(i) {
            index = (i % slides.length + slides.length) % slides.length;
            track.style.transform = 'translateX(' + (-index * 100) + '%)';
            for (var d = 0; d < dots.length; d++) {
                dots[d].classList.toggle('is-active', d === index);
            }
        }

        function stop() {
            if (timer) { clearInterval(timer); timer = null; }
        }

        function start() {
            stop();
            if (autoplay > 0) {
                timer = setInterval(function () { go(index + 1); }, autoplay);
            }
        }

        if (prev) { prev.addEventListener('click', function () { go(index - 1); start(); }); }
        if (next) { next.addEventListener('click', function () { go(index + 1); start(); }); }
        for (var k = 0; k < dots.length; k++) {
            (function (dot) {
                dot.addEventListener('click', function () {
                    go(parseInt(dot.getAttribute('data-index'), 10) || 0);
                    start();
                });
            })(dots[k]);
        }

        el.addEventListener('mouseenter', stop);
        el.addEventListener('mouseleave', start);

        go(0);
        start();
    }

    function initAll() {
        var carousels = document.querySelectorAll('.mhc[data-mhc]');
        for (var i = 0; i < carousels.length; i++) {
            initCarousel(carousels[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
