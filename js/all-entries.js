jQuery(document).ready(function($) {
    // Handle the click event for the "View Details" link
    $('.wp-list-table').on('click', '.view-entries-details', function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var $target = $(targetId);

        $target.slideToggle();

        // Optional: Change the link text
        var currentText = $(this).text();
        var newText = (currentText === 'View Details') ? 'Hide Details' : 'View Details';
        $(this).text(newText);
    });
});