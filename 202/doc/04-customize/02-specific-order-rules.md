---
category: 'Improve this addon'
name: '2. Specific import order rules'
---


You can mange classes of rules to add a behaviour during the import of an order.


You need to create a rules class extends RuleAbstract and implements RuleInterface and declare on the `actionShoppingfeedOrderImportRegisterSpecificRules` hook.


Basic usage :

```php
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class MyOwnRules extends RuleAbstract implements RuleInterface
{

    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        // apiOrderData give you all order data coming from API
        // put here conditions to set this rules according to apiOrderData
        // for instance according to the marketplace, the carrier, ...

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('My description of condition 'MyOwnRules');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('My description of what to do 'MyOwnRules');
    }

}
```

After defining your own class you can add one or several event schedule to be called back during the import process :

* onPreProcess
* onCarrierRetrieval
* onVerifyOrder
* onCustomerCreation
* onCustomerRetrieval
* beforeBillingAddressCreation
* beforeBillingAddressSave
* beforeShippingAddressCreation
* beforeShippingAddressSave
* checkProductStock
* onCartCreation
* afterCartCreation
* afterOrderCreation
* onPostProcess


You can also add configuration form on your backoffice "Specific rules" by adding a method `getConfigurationSubform`.

```php
    public function getConfigurationSubform()
    {
        return array(
            array(
                'type' => 'switch',
                'label' => $this->l('My label', 'MyOwnRules'),
                'name' => 'mybeautyfullname',
                'is_bool' => true,
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    )
                ),
            )
        );
    }

    public function getDefaultConfiguration()
    {
        return array('mybeautyfullname' => true);
    }

    // sample usage
    public function onPreProcess($params)
    {
        if (empty($this->configuration['mybeautyfullname'])) {
            return false;
        }
        // rest of the process
    }
```
