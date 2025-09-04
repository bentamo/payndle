jQuery(document).ready(function($) {
    $(".plan-button").on("click", function() {
        let plan = $(this).closest(".plan-box").find("h2").text();
        alert("You selected: " + plan + " (payment integration coming soon)");
    });
});
