/*
 *
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *
 */
$(document).ready(function()
{
    $('#purge-cache').click(function(){
        $.ajax({
            url: $(this).data('link-purge-cache'),
            type: 'POST',
            dataType: 'JSON',
            async: true,
            data: {
                ajax: true,
                action: 'purgeCache',
            },
        });
    });

    if ($('#SHOPPINGFEED_REAL_TIME_SYNCHRONIZATION_off').prop('checked') == false) {
        $('.for_real').closest('.form-group').hide();
        $('#for_real').closest('.form-group').hide();
    }

    $('[name="SHOPPINGFEED_REAL_TIME_SYNCHRONIZATION"]').change(function()
    {
        if ($('#SHOPPINGFEED_REAL_TIME_SYNCHRONIZATION_off').is(':checked')) {
            $('.for_real').closest('.form-group').show();
            $('#for_real').closest('.form-group').show();
        } else {
            $('.for_real').closest('.form-group').hide();
            $('#for_real').closest('.form-group').hide();
        }
    });

    $('[name="SHOPPINGFEED_ORDER_IMPORT_ENABLED"]').change(function() {
        if ($("#SHOPPINGFEED_ORDER_IMPORT_ENABLED_on").prop('checked')) {
            $("#shoppingfeed_carriers-matching").show();
            $('[name="SHOPPINGFEED_ORDER_IMPORT_TEST"]').removeAttr('disabled');
            $('[name="SHOPPINGFEED_ORDER_IMPORT_SHIPPED"]').removeAttr('disabled');
        } else {
            $("#shoppingfeed_carriers-matching").hide();
            $('[name="SHOPPINGFEED_ORDER_IMPORT_TEST"]').attr('disabled', true);
            $('[name="SHOPPINGFEED_ORDER_IMPORT_SHIPPED"]').attr('disabled', true);
        }
    }).change();

    function shoppingfeed_doubleListUpdate(doubleList) {
        var unselectedList = doubleList.find('.shoppingfeed_double-list-unselected');
        var selectedList = doubleList.find('.shoppingfeed_double-list-selected');
        var doubleListValues = doubleList.find('.shoppingfeed_double-list-values');

        selectedList.find('option').each(function() {
            doubleListValues.find("[value='"+this.value+"']").attr('checked', true);
        });
        unselectedList.find('option').each(function() {
            doubleListValues.find("[value='"+this.value+"']").attr('checked', false);
        });
    }

    $(".shoppingfeed_double-list-group").each(function() {

        var doubleList = $(this);
        var unselectedList = doubleList.find('.shoppingfeed_double-list-unselected');
        var selectedList = doubleList.find('.shoppingfeed_double-list-selected');

        doubleList.find('.shoppingfeed_double-list-btn-select').click(function() {
            unselectedList.find('option:selected').appendTo(selectedList);
            shoppingfeed_doubleListUpdate(doubleList);
        });

        doubleList.find('.shoppingfeed_double-list-btn-unselect').click(function() {
            selectedList.find('option:selected').appendTo(unselectedList);
            shoppingfeed_doubleListUpdate(doubleList);
        });
    });

    $("#shoppingfeed_marketplace-filter").change(function() {
        var marketplace = $(this).val();
        $("#shoppingfeed_carriers-matching-table tbody tr").each(function() {
            if (marketplace == '' || marketplace == $(this).data('shoppingfeed-marketplace')) {
                $(this).show();
            } else {
                $(this).hide();
            }
        })
    });

});
