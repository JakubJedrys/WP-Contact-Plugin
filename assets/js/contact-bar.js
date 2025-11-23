(function () {
    function initContactBar() {
        var bar = document.querySelector('.wp-contact-bar');
        if (!bar) {
            return;
        }

        var toggle = bar.querySelector('.wp-contact-bar__toggle');
        var panel = bar.querySelector('.wp-contact-bar__panel');

        if (!toggle || !panel) {
            return;
        }

        function closePanel() {
            bar.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            var isOpen = bar.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!bar.classList.contains('is-open')) {
                return;
            }

            if (!bar.contains(event.target)) {
                closePanel();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && bar.classList.contains('is-open')) {
                closePanel();
            }
        });

    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initContactBar();
    } else {
        document.addEventListener('DOMContentLoaded', initContactBar);
    }
})();
