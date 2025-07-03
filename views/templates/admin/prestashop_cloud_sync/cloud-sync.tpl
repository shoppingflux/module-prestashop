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

<div class="panel">
  <div id="prestashop-cloudsync"></div>
</div>

<script>
  var contextPsAccounts = {$contextPsAccounts|json_encode nofilter};
  var contextPsEventbus = {$contextPsEventbus|json_encode nofilter};

  if (window.cloudSyncSharingConsent === undefined) {
    const cloudsyncCdn = document.createElement('script');
    cloudsyncCdn.src = '{$urlCloudsync|escape:'htmlall':'UTF-8'}';
    document.body.appendChild(cloudsyncCdn);
  }
  if (window.psaccountsVue === undefined) {
    const accountsCdn = document.createElement('script');
    accountsCdn.src = '{$urlAccountsCdn|escape:'htmlall':'UTF-8'}';
    document.body.appendChild(accountsCdn);
  }

  setTimeout(function(){
    const initCloudsync = function() {
      if (!window.cloudSyncSharingConsent || !window.psaccountsVue) {
        setTimeout(initCloudsync, 200);
        return;
      }

      window.psaccountsVue.init();
      window.cloudSyncSharingConsent.init('#prestashop-cloudsync');
      window.cloudSyncSharingConsent.on('OnboardingCompleted', (isCompleted) => {
        console.log('OnboardingCompleted', isCompleted);
      });
      window.cloudSyncSharingConsent.isOnboardingCompleted((isCompleted) => {
        console.log('Onboarding is already Completed', isCompleted);
      });
    }

    initCloudsync();
  }, 0);
</script>
