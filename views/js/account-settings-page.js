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

var AccountSettingsPage = function() {
    this.registerEventListeners();
    this.checkLoginForm();
};

AccountSettingsPage.prototype.registerEventListeners = function() {
    document.querySelectorAll('[btn-get-storeid]').forEach(function(button) {
        button.addEventListener('click', this.updateStoreId.bind(this));
    }.bind(this));
    document.querySelectorAll('[name="username"]').forEach(function(username) {
        username.addEventListener('change', this.checkLoginForm.bind(this));
    }.bind(this));
    document.querySelectorAll('[name="password"]').forEach(function(password) {
        password.addEventListener('change', this.checkLoginForm.bind(this));
    }.bind(this));
};

AccountSettingsPage.prototype.updateStoreId = function(event) {
    if (false === event.target instanceof HTMLElement) {
        return;
    }

    var form = event.target.closest('form');
    var formData = new FormData();
    var url = new URL(location.href);

    if (!form) {
        return;
    }

    var token = form.querySelector('[name="SHOPPINGFEED_AUTH_TOKEN"]');
    var username = form.querySelector('[name="username"]');
    var password = form.querySelector('[name="password"]');
    var storeSelect = form.querySelector('[name="store_id"]');

    if (token instanceof HTMLInputElement) {
        formData.append('SHOPPINGFEED_AUTH_TOKEN', token.value);
    }
    if (username instanceof HTMLInputElement) {
        formData.append('username', username.value);
    }
    if (password instanceof HTMLInputElement) {
        formData.append('password', password.value);
    }

    url.searchParams.append('ajax', '1');
    url.searchParams.append('action', 'GetStoreId')

    fetch(url, {
        method: 'POST',
        body: formData,
    })
        .then(function(response) {
            return response.json();
        })
        .then(function(response) {
            if (response.success && response.storeID) {
                if (storeSelect instanceof HTMLSelectElement) {
                    storeSelect.innerHTML = this.buildOptions(response.storeID);
                }
            }
        }.bind(this));
}

AccountSettingsPage.prototype.buildOptions = function(storeID) {
    var options = '';

    if (!storeID) {
        return options;
    }

    for (var i in storeID) {
        options += `<option value="${storeID[i]}">${storeID[i]}</option>`;
    }

    return options;
}

AccountSettingsPage.prototype.checkLoginForm = function() {
    var username = document.querySelector('[name="username"]');
    var password = document.querySelector('[name="password"]');

    if (username && password) {
        console.log([username.value, password.value]);
        var form = username.closest('form');
        if (username.value && password.value) {
            if (form) {
                form.querySelector('button[type="submit"]').disabled = false;
            }
        } else {
            if (form) {
                form.querySelector('button[type="submit"]').disabled = true;
            }
        }
    }
}

window.addEventListener('load', function() {
    new AccountSettingsPage();
})