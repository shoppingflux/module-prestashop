/*
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from SARL 202 ecommence
* Use, copy, modification or distribution of this source file without written
* license agreement from the SARL 202 ecommence is strictly forbidden.
* In order to obtain a license, please contact us: tech@202-ecommerce.com
* ...........................................................................
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe 202 ecommence
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommence est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
* ...........................................................................
*
* @author    202-ecommerce <tech@202-ecommerce.com>
* @copyright Copyright (c) 202-ecommerce
* @license   Commercial license
*/
$(document).ready(function()
{
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

    $('#status_shipped_order_add_btn').click(function()
    {
        var i = 0;
        var ids_status = [];

        $("#status_shipped_order_add option:selected").each(function() {
            ids_status[i] = $(this).val();
            i++;
        });

        i = 0;
        var texts_to_transfert = [];

        $("#status_shipped_order_add option:selected").each(function() {
            texts_to_transfert[i] = $(this).html();
            i++;
        });

        if (!ids_status.length || !texts_to_transfert.length) {
            return null;
        }

        $("#status_shipped_order_add option:selected").each(function() {
            $(this).remove();
        });

        $.each(ids_status, function(i, val) {
            $("#status_shipped_order_remove").append("<option value='" + val + "'>" + texts_to_transfert[i] + "</option>");
        });
    });

    $('#status_shipped_order_remove_btn').click(function()
    {
        var i = 0;
        var ids_status = [];

        $("#status_shipped_order_remove option:selected").each(function() {
            ids_status[i] = $(this).val();
            i++;
        });

        i = 0;
        var texts_to_transfert = [];

        $("#status_shipped_order_remove option:selected").each(function() {
            texts_to_transfert[i] = $(this).html();
            i++;
        });

        if (!ids_status.length || !texts_to_transfert.length) {
            return null;
        }

        $("#status_shipped_order_remove option:selected").each(function() {
            $(this).remove();
        });

        $.each(ids_status, function(i, val) {
            $("#status_shipped_order_add").append("<option value='" + val + "'>" + texts_to_transfert[i] + "</option>");
        });
    });

    $('#status_cancelled_order_add_btn').click(function()
    {
        var i = 0;
        var ids_status = [];

        $("#status_cancelled_order_add option:selected").each(function() {
            ids_status[i] = $(this).val();
            i++;
        });

        i = 0;
        var texts_to_transfert = [];

        $("#status_cancelled_order_add option:selected").each(function() {
            texts_to_transfert[i] = $(this).html();
            i++;
        });

        if (!ids_status.length || !texts_to_transfert.length) {
            return null;
        }

        $("#status_cancelled_order_add option:selected").each(function() {
            $(this).remove();
        });

        $.each(ids_status, function(i, val) {
            $("#status_cancelled_order_remove").append("<option value='" + val + "'>" + texts_to_transfert[i] + "</option>");
        });
    });

    $('#status_cancelled_order_remove_btn').click(function()
    {
        var i = 0;
        var ids_status = [];

        $("#status_cancelled_order_remove option:selected").each(function() {
            ids_status[i] = $(this).val();
            i++;
        });

        i = 0;
        var texts_to_transfert = [];

        $("#status_cancelled_order_remove option:selected").each(function() {
            texts_to_transfert[i] = $(this).html();
            i++;
        });

        if (!ids_status.length || !texts_to_transfert.length) {
            return null;
        }

        $("#status_cancelled_order_remove option:selected").each(function() {
            $(this).remove();
        });

        $.each(ids_status, function(i, val) {
            $("#status_cancelled_order_add").append("<option value='" + val + "'>" + texts_to_transfert[i] + "</option>");
        });
    });

    $('#status_refunded_order_add_btn').click(function()
    {
        var i = 0;
        var ids_status = [];

        $("#status_refunded_order_add option:selected").each(function() {
            ids_status[i] = $(this).val();
            i++;
        });

        i = 0;
        var texts_to_transfert = [];

        $("#status_refunded_order_add option:selected").each(function() {
            texts_to_transfert[i] = $(this).html();
            i++;
        });

        if (!ids_status.length || !texts_to_transfert.length) {
            return null;
        }

        $("#status_refunded_order_add option:selected").each(function() {
            $(this).remove();
        });

        $.each(ids_status, function(i, val) {
            $("#status_refunded_order_remove").append("<option value='" + val + "'>" + texts_to_transfert[i] + "</option>");
        });
    });

    $('#status_refunded_order_remove_btn').click(function()
    {
        var i = 0;
        var ids_status = [];

        $("#status_refunded_order_remove option:selected").each(function() {
            ids_status[i] = $(this).val();
            i++;
        });

        i = 0;
        var texts_to_transfert = [];

        $("#status_refunded_order_remove option:selected").each(function() {
            texts_to_transfert[i] = $(this).html();
            i++;
        });

        if (!ids_status.length || !texts_to_transfert.length) {
            return null;
        }

        $("#status_refunded_order_remove option:selected").each(function() {
            $(this).remove();
        });

        $.each(ids_status, function(i, val) {
            $("#status_refunded_order_add").append("<option value='" + val + "'>" + texts_to_transfert[i] + "</option>");
        });
    });

    $('#configuration_form_2').submit(function()
    {
        $('#status_shipped_order_remove option').attr('selected', true);
        $('#status_cancelled_order_remove option').attr('selected', true);
        $('#status_refunded_order_remove option').attr('selected', true);
    });
});
