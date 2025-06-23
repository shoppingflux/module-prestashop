{**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *}
<div class="w-100 mb-3">
  <div class="row">
    <div class="col-sm-12">
      <div class="row justify-content-center">
        <div class="col-xl-12 pr-5 pl-5">
          <div class="card">
            <div class="card-header">
              <div>
                <span class="material-icons">file_download</span>
                  {l s='Export' mod='shoppingfeed'}
              </div>
            </div>
            <div class="form-wrapper justify-content-center col-xl-12 my-3">
              {l s='You can export all informations using the button `Export`' mod='shoppingfeed'}
            </div>
            <div class="card-footer">
              <div class="d-flex justify-content-end">
                <a href="{$exportStubLink|escape:'html':'UTF-8'}" class="btn btn-default">{l s='Export' mod='shoppingfeed'}</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>