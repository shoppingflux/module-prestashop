
$(document).ready(function() { 

    $('#SHOPPINGFEED_PRODUCT_FEED_TIME_FULL_UPDATE, #SHOPPINGFEED_PRODUCT_FEED_INTERVAL_CRON').change(function() {
        if ($('#SHOPPINGFEED_PRODUCT_FEED_TIME_FULL_UPDATE').val() == 0 && $('#SHOPPINGFEED_PRODUCT_FEED_INTERVAL_CRON').val() == 0) {
            $('input[name="SHOPPINGFEED_PRODUCT_SYNC_BY_DATE_UPD"]').prop("disabled", false);
        } else {
            $('input[name="SHOPPINGFEED_PRODUCT_SYNC_BY_DATE_UPD"]').prop("disabled", true);
        }
        
    });

    $('input[name="SHOPPINGFEED_PRODUCT_SYNC_BY_DATE_UPD"]').change(function() {
        if ($(this).val() == 0) {
            $('#SHOPPINGFEED_PRODUCT_FEED_TIME_FULL_UPDATE, #SHOPPINGFEED_PRODUCT_FEED_INTERVAL_CRON').prop("disabled", false);
        } else {
            $('#SHOPPINGFEED_PRODUCT_FEED_TIME_FULL_UPDATE, #SHOPPINGFEED_PRODUCT_FEED_INTERVAL_CRON').prop("disabled", true);
        }
        
    });
});
