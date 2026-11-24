{*
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
*}



{* Carrier selection rules (#34133), rendered by views/js/order_import/carrier_rules.js *}
<div id="shoppingfeed_carrier-rules" class="form-group"{if !$input.advanced_rules_enabled} style="display: none;"{/if}>
    <div class="col-lg-12">
        <input type="hidden" name="shoppingfeed_carrier_rules" id="shoppingfeed_carrier-rules-input" value="" />
        <div id="shoppingfeed_carrier-rules-app"></div>
    </div>
</div>
