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
<!-- start process monitor modal -->
<div class="modal fade" id="shoppingfeedProcessModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">{l s='Processing cron: ' mod='shoppingfeed'} <span class="cron-name"></span></h4>
      </div>
      <div class="modal-body">
        <div class="loading-step text-center">
          <p>{l s='Loading...' mod='shoppingfeed'}</p>
          <i class="icon-refresh icon-spin icon-fw process-loading"></i>
        </div>
        <div class="complete-step hidden text-center">
          {l s='The task is completed. Please see logs tab for more informations.' mod='shoppingfeed'}
        </div>
        <div class="error-step hidden text-left">
          <span class="generic-error">
            {l s='The task return an error. Please see logs tab for more informations.' mod='shoppingfeed'}
          </span>
          <span class="custom-error">
            <ul>
            </ul>
          </span>
        </div>
        <div class="progress">
          <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 0;">
            0%
          </div>
        </div>
      </div>
      <div class="modal-footer hidden">
        <button type="button" class="btn btn-default close-btn" data-dismiss="modal">Close</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- end process monitor modal -->