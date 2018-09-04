$(document).ready(function () {

    if ($('#SHOPPINGFEED_REAL_TIME_SYNCHRONIZATION_off').prop('checked')==false) {
        $( '.for_real' ).closest('.form-group').hide();
        $( '#for_real' ).closest('.form-group').hide();
    }


    $('[name="SHOPPINGFEED_REAL_TIME_SYNCHRONIZATION"]').change(function()
    {
        if ($('#SHOPPINGFEED_REAL_TIME_SYNCHRONIZATION_off').is(':checked')) {
            console.log('true');
            $( '.for_real' ).closest('.form-group').show();
            $( '#for_real' ).closest('.form-group').show();
        } else {
            console.log('true');
            $( '.for_real' ).closest('.form-group').hide();
            $( '#for_real' ).closest('.form-group').hide();
        }
    });

});
