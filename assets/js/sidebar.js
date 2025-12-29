// Sidebar Toggle Functionality
function initSidebar() {
    $('#toggleSidebar').click(function () {
        const sb = $('#sidebar');
        const isExpanded = sb.hasClass('w-64');
        const texts = $('.sidebar-text');
        const icon = $(this).find('i');

        if (isExpanded) {
            // Collapse sidebar
            sb.removeClass('w-64').addClass('w-20');
            texts.addClass('hidden opacity-0');
            icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
            localStorage.setItem('sidebarExpanded', 'false');
        } else {
            // Expand sidebar
            sb.removeClass('w-20').addClass('w-64');
            setTimeout(() => texts.removeClass('hidden opacity-0'), 150);
            icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
            localStorage.setItem('sidebarExpanded', 'true');
        }
    });

    // Initial Sidebar State - Default to collapsed
    if (localStorage.getItem('sidebarExpanded') === 'true') {
        // Expand sidebar
        $('#sidebar').removeClass('w-20').addClass('w-64');
        $('.sidebar-text').removeClass('hidden opacity-0');
        $('#toggleSidebar i').removeClass('fa-chevron-right').addClass('fa-chevron-left');
    } else {
        // Keep collapsed (default)
        $('#sidebar').removeClass('w-64').addClass('w-20');
        $('.sidebar-text').addClass('hidden opacity-0');
        $('#toggleSidebar i').removeClass('fa-chevron-left').addClass('fa-chevron-right');
    }
}

// Initialize sidebar on DOM ready
$(document).ready(function () {
    initSidebar();
});
