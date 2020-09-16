---
category: 'Features : Products'
name: 5. Products feed synchronization
---

# Overview

The XML feed is not computed when Shoppingfeed call it.
In fact each time a product is updated (on `actionObjectProductUpdateBefore`
or `actionObjectCombinationUpdateBefore`), a serialized version of the product
is put in cache (on the database).


# Add or modify content on the feed

You can used 3 hooks called just before put in cache the serialized product:
* shoppingfeedSerialize
* shoppingfeedSerializePrice
* shoppingfeedSerializeStock

The array `content` of the product are set by reference, so you can modify it
as you want.


```php
// myCustomShoppingfeedModule.php

/**
 * Change boolean attribute "isWashable" by a friendly text
 */
public function hookShoppingfeedSerialize(&$params)
{
    $isWashable = $params['content']['attributes']['isWashable'];

    if ($isWashable === true) {
        $params['content']['attributes']['washableText'] = 'This item is washable.';
    } else {
        $params['content']['attributes']['washableText'] = 'This item is NOT washable.';
    }

}
```


# Processing changes

Updated products are _always_ saved in the database before being sent. The
`ProcessLogger` component from Classlib is used to track every update process.

If real-time synchronization is selected, the `ActionsHandler` will execute the
update chain in the `actionObjectProductUpdateAfter` and
`actionObjectCombinationUpdateAfter` hooks. If not, a CRON task managed with
Classlib's `ProcessMonitor` component will process the saved updates using the
same chain of actions.
