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
/*
    globals shoppingfeedProcessMonitorController
 */
var ShoppingfeedProcessMonitor = {
    $cronButton: false,
    initVariables: function () {
        this.$cronButton = $('.btn-cron');
    },
    init: function () {
        this.initVariables();
        this.registerEvents();
    },
    launchCron: function (btn) {
        var self = this;
        var $processModal = $("#shoppingfeedProcessModal");
        var cronName = 'shoppingfeed:importOrder';
        $.ajax({
            url: shoppingfeedProcessMonitorController,
            type: 'POST',
            data: {
                ajax: true,
                action: 'GetLastCronDuration',
                cronName: cronName
            },
            success: function (data) {
                var durationObj = JSON.parse(data);
                self.showModal(cronName, durationObj.duration);
                $.ajax({
                    url: shoppingfeedProcessOrderImportController,
                    ajax: true,
                    type: 'POST',
                    dataType: "json",
                    data: {
                        ajax: true,
                        action: 'ImportShoppingfluxexport',
                    },
                    success: function (data) {
                        $processModal.find('.loading-step').addClass('hidden');
                        $processModal.find('.error-step').removeClass('hidden');
                        $processModal.find('.error-step').find('.generic-error').addClass('hidden');
                        $processModal.find('.complete-step').addClass('hidden');
                        var customError =  $processModal.find('.error-step').find('.custom-error');
                        customError.removeClass('hidden').empty();
                        data.errors.forEach(error => customError.append('<li>' + error + '</li>'));
                    },
                    error: function (error) {
                        $processModal.find('.loading-step').addClass('hidden');
                        $processModal.find('.generic-error').removeClass('hidden');
                        $processModal.find('.custom-error').addClass('hidden');
                        $processModal.find('.complete-step').addClass('hidden');
                    },
                    complete: function (data) {
                        $(window).trigger('processCompleted');
                        $processModal.find('.modal-footer').removeClass('hidden');
                    }
                });
            },
            error: function (error) {
            }
        });

    },
    showModal: function (cronName, cronDuration) {
        var $processModal = $("#shoppingfeedProcessModal");
        $processModal.find('.cron-name').text(cronName);
        $processModal.modal({
            keyboard: false,
            backdrop: 'static'
        });
        this.startProgressBar(cronDuration);
    },
    startProgressBar: function (cronDuration) {
        var $processModal = $("#shoppingfeedProcessModal");
        var counter = 0;
        var progressInterval = setInterval(function () {
            counter++;
            if (counter <= 100) {
                $processModal.find('.progress-bar').css('width', counter + '%');
                $processModal.find('.progress-bar').text(counter + '%');
            } else {
                clearInterval(progressInterval);
            }
        }, cronDuration * 10);
        $(window).on('processCompleted', function () {
            $processModal.find('.progress-bar').css('width', '100%');
            $processModal.find('.progress-bar').text('100%');
            clearInterval(progressInterval);
        });
    },
    registerEvents: function () {
        var self = this;
        var $processModal = $("#shoppingfeedProcessModal");

        self.$cronButton.on('click', function (event) {
            self.launchCron(event.currentTarget);
        });

        $processModal.on('hidden.bs.modal', function () {
            $processModal.find('.loading-step').removeClass('hidden');
            $processModal.find('.complete-step').addClass('hidden');
            $processModal.find('.error-step').find('.custom-error').addClass('hidden');
            $processModal.find('.error-step').find('.generic-error').addClass('hidden');
            $processModal.find('.modal-footer').addClass('hidden');
        })
    }
};

$(function () {
    ShoppingfeedProcessMonitor.init();
});
