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
  <div id="cdc-container"></div>
</div>

{literal}
<script>
  if (window.mboCdcDependencyResolver === undefined) {
    const mboCdn = document.createElement('script');
    mboCdn.src = 'https://assets.prestashop3.com/dst/mbo/v1/mbo-cdc-dependencies-resolver.umd.js';
    document.body.appendChild(mboCdn);
  }

  setTimeout(function(){
    const init = function() {
      if (!window.mboCdcDependencyResolver) {
        setTimeout(init, 200);
        return;
      }

      const renderMboCdcDependencyResolver = window.mboCdcDependencyResolver.render
      const context = {
        ...{/literal}{$dependencies|json_encode}{literal},
        onDependenciesResolved: () => location.reload(),
        onDependencyFailed: (dependencyData) => console.log('Failed to install dependency', dependencyData),
      }
      renderMboCdcDependencyResolver(context, '#cdc-container');
    }

    init();
  }, 0);

</script>
{/literal}
