jQuery(function($){
    $('.toggle-dob').on('click', function(){
        var span = $(this).closest('td').find('.wsm-dob');
        span.toggle();
    });

    $('#wsm-add-client').on('submit', function(e){
        e.preventDefault();
        var form = $(this);
        $.post(ajaxurl, form.serialize() + '&action=wsm_add_client', function(resp){
            alert(resp.data);
            location.reload();
        });
    });
});
