jQuery(document).ready(function($) {
    // Open modal with placeholder details
    $(".view-details").on("click", function() {
        let bookingId = $(this).data("id");
        $("#booking-modal").fadeIn();
        $(".booking-modal-content p").text(
            "Details for booking #" + bookingId + " will load here (placeholder for DB connection)."
        );
    });

    // Close modal
    $(".close-modal").on("click", function() {
        $("#booking-modal").fadeOut();
    });

    // Close modal when clicking outside
    $(window).on("click", function(e) {
        if ($(e.target).is("#booking-modal")) {
            $("#booking-modal").fadeOut();
        }
    });
});
