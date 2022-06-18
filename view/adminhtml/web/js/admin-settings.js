require([
    "jquery",
    "jquery/ui"
], function($){

    $('.customers-canvas__input-hide').click(function() {
        var input = $($( this ).parent().children('input')[0]);
        if (input.attr('type') === 'password') {
            $( this ).children('span.customers-canvas__button-text').html('Hide');
            input.attr('type', 'text');
        } else {
            $( this ).children('span.customers-canvas__button-text').html('Show');
            input.attr('type', 'password');
        }
    });
    
    $('.customers-canvas__input-copy').click(function() {
        var input = $($( this ).parent().children('input')[0]);
        navigator.clipboard.writeText(input.val());
        $( this ).children('span.customers-canvas__button-text').html('Copied');
        setTimeout(() => {
            $( this ).children('span.customers-canvas__button-text').html('Copy');
        }, 800);
    });

});