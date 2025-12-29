// Mobile Long Press Support
// This script enables "long press" to trigger "right click" (context menu) on mobile devices

document.addEventListener('DOMContentLoaded', function () {
    let longPressTimer;
    const longPressDuration = 600; // 600ms threshold
    let isLongPress = false;
    let touchStartX = 0;
    let touchStartY = 0;

    // Define selectors that support context menu
    const contextMenuSelectors = [
        '.renewal-item',
        '.reminder-item',
        '.site-row',
        '.customer-row',
        '[oncontextmenu]' // Catch-all for inline handlers
    ];

    function getTarget(target) {
        for (const selector of contextMenuSelectors) {
            const el = target.closest(selector);
            if (el) return el;
        }
        return null;
    }

    document.addEventListener('touchstart', function (e) {
        isLongPress = false; // Reset always to prevent blocking subsequent clicks
        const target = getTarget(e.target);
        if (!target) return;

        // Only single touch
        if (e.touches.length > 1) return;

        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;

        longPressTimer = setTimeout(function () {
            isLongPress = true;

            // Visual feedback (optional)
            target.classList.add('bg-gray-100');
            setTimeout(() => target.classList.remove('bg-gray-100'), 200);

            // Trigger Context Menu Event
            const contextEvent = new MouseEvent('contextmenu', {
                bubbles: true,
                cancelable: true,
                view: window,
                clientX: e.touches[0].clientX,
                clientY: e.touches[0].clientY,
                button: 2, // Right click
                buttons: 2
            });

            target.dispatchEvent(contextEvent);

            // Haptic feedback
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }

        }, longPressDuration);
    }, { passive: false });

    document.addEventListener('touchmove', function (e) {
        // If moved significantly, cancel long press
        if (Math.abs(e.touches[0].clientX - touchStartX) > 10 ||
            Math.abs(e.touches[0].clientY - touchStartY) > 10) {
            clearTimeout(longPressTimer);
            longPressTimer = null;
        }
    });

    document.addEventListener('touchend', function (e) {
        if (longPressTimer) {
            clearTimeout(longPressTimer);
        }

        // If it was a long press, prevent default click behavior if necessary
        if (isLongPress) {
            e.preventDefault();
            // Prevent ghost clicks
            e.stopPropagation();
        }
    });

    // Provide visual cues that items are actionable
    const style = document.createElement('style');
    style.innerHTML = `
        @media (max-width: 768px) {
            .renewal-item, .reminder-item, .site-row, .customer-row {
                user-select: none; /* Prevent text selection during long press */
                -webkit-user-select: none;
            }
        }
    `;
    document.head.appendChild(style);
});
