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

        if (bar.dataset && bar.dataset.floating === 'floating') {
            enableDrag(bar);
            hydrateStoredPosition(bar);
        }
    }

    function hydrateStoredPosition(bar) {
        try {
            var saved = localStorage.getItem('wpContactBarPosition');
            if (!saved) return;
            var data = JSON.parse(saved);
            if (!data || typeof data !== 'object') return;

            if (typeof data.top === 'number') {
                bar.style.top = data.top + 'px';
                bar.style.bottom = 'auto';
            }
            if (typeof data.left === 'number') {
                bar.style.left = data.left + 'px';
                bar.style.right = 'auto';
            }
        } catch (e) {
            /* ignore storage errors */
        }
    }

    function enableDrag(bar) {
        var dragging = false;
        var startX = 0;
        var startY = 0;
        var startLeft = 0;
        var startTop = 0;

        var handle = bar.querySelector('.wp-contact-bar__toggle');
        if (!handle) return;

        var updatePosition = function (left, top) {
            bar.style.left = left + 'px';
            bar.style.top = top + 'px';
            bar.style.right = 'auto';
            bar.style.bottom = 'auto';
        };

        var savePosition = function (left, top) {
            try {
                localStorage.setItem('wpContactBarPosition', JSON.stringify({ left: left, top: top }));
            } catch (e) {
                /* ignore storage errors */
            }
        };

        var onMove = function (event) {
            if (!dragging) return;
            event.preventDefault();

            var clientX = event.touches ? event.touches[0].clientX : event.clientX;
            var clientY = event.touches ? event.touches[0].clientY : event.clientY;

            var deltaX = clientX - startX;
            var deltaY = clientY - startY;

            var newLeft = Math.max(0, startLeft + deltaX);
            var newTop = Math.max(0, startTop + deltaY);

            updatePosition(newLeft, newTop);
        };

        var onEnd = function () {
            if (!dragging) return;
            dragging = false;
            savePosition(parseInt(bar.style.left || '0', 10), parseInt(bar.style.top || '0', 10));
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onEnd);
            document.removeEventListener('touchmove', onMove);
            document.removeEventListener('touchend', onEnd);
        };

        var onStart = function (event) {
            dragging = true;
            startX = event.touches ? event.touches[0].clientX : event.clientX;
            startY = event.touches ? event.touches[0].clientY : event.clientY;
            var rect = bar.getBoundingClientRect();
            startLeft = rect.left;
            startTop = rect.top;

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onEnd);
            document.addEventListener('touchmove', onMove, { passive: false });
            document.addEventListener('touchend', onEnd);
        };

        handle.addEventListener('mousedown', onStart);
        handle.addEventListener('touchstart', onStart, { passive: false });
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initContactBar();
    } else {
        document.addEventListener('DOMContentLoaded', initContactBar);
    }
})();
