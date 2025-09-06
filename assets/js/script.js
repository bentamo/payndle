jQuery(document).ready(function($) {
    // Add Service
    $('#mvp-add-form').on('submit', function(e) {
        e.preventDefault();

        $.post(mvp_ajax.ajax_url, {
            action: 'mvp_add_service',
            nonce: mvp_ajax.nonce,
            title: $(this).find('[name="title"]').val(),
            description: $(this).find('[name="description"]').val()
        }, function(response) {
            if (response.success) {
                let service = response.data;
                $('#mvp-service-list').prepend(`
                    <div class="mvp-card" data-id="${service.id}">
                        <h4>${service.title}</h4>
                        <p>${service.desc}</p>
                        <button class="mvp-delete">Delete</button>
                    </div>
                `);
                $('#mvp-add-form')[0].reset();
            } else {
                alert(response.data);
            }
        });
    });

    // Delete Service
    $(document).on('click', '.mvp-delete', function() {
        let card = $(this).closest('.mvp-card');
        let id = card.data('id');

        $.post(mvp_ajax.ajax_url, {
            action: 'mvp_delete_service',
            nonce: mvp_ajax.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                card.fadeOut(300, function(){ $(this).remove(); });
            } else {
                alert(response.data);
            }
        });
    });
});
