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
