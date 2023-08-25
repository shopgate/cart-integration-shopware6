# Shopgate Shopware6 Integration

## Install

### Packagist install (recommended)
This plugin is available on `packagist.org`. To install simply add the composer package to the shopware's root composer:

```shell
cd [shopware6 root folder]
composer require shopgate/cart-integration-shopware6
```

Afterward just increment the plugin version inside `root/composer.json`, and run `composer update` to get the latest
version.

#### Folder install

It can be installed manually by copying the plugin folder to `custom/plugins` directory. Like
so `custom/plugins/SgateShopgatePluginSW6`. Then you can install & enable like any other plugin. For this install method,
please make sure that there is a `vendor` directory inside the plugin folder as we have composer dependencies. You could
do it yourself by running:

```shell
cd [plugin folder]
# this is because we do not want to install shopware core files
composer remove shopware/core
```

#### Composer symlink (development)

Place the plugin in the `custom/plugins` folder. You can now link it to
composer by running this command in the root directory:

```shell
cd custom/plugins
git clone git@github.com:shopgate/cart-integration-shopware6.git
cd ../..
composer require shopgate/cart-integration-shopware6
```

## Enable & Activate

Install and activate the module:

```shell
cd [shopware6 root folder]
php bin/console plugin:refresh
php bin/console plugin:install -a SgateShopgatePluginSW6
```

You may install and activate via the Shopware administration panel instead, if you prefer.

### JS Compilation

For Shopware 6.5+ after installing & enabling the plugin you will need to re-compile JS.
If you have a GitHub installation, this should suffice:

```shell
composer run build:js
bin/console theme:compile
```

For regular installations via Symfony2 Flex, zip or shopware-installer.phar.php:
```shell
bin/build-js.sh
```

# Known errors

* `No SaleChannel domain exists corresponding to the SaleChannel default language` - indicates an issue when there is a
  default language set for a domain, but no domain URL exists that has that language. In short:
  1. go to `SalesChannels`
  2. select SaleChannel that is being queried by Shopgate API
  3. Check `General Settings` default language (e.g., English)
  4. Check `Domains` list, see that there is no domain URL with default language (e.g., English)
* `Cannot declare interface XXX, because the name is already in use` - happens after installing of our plugin via
  symlink. This is because there is a `vendor` directory inside our plugin folder. Either remove `vendor` directory from
  our plugin directory **or** do not install via symlink. These are two different ways of installing our plugin.
* `ConstraintViolationException: Caught 1 violation errors` during a `check_cart` or customer `registration`. One of the
  known errors is when the Shopgate App does not require the phone number to be set, but the Shopware does. So when the
  `check_cart` attempts to create the address a constraint violation occurs.
* Export prices are too long (e.g. `"unit_amount":2.3999999999999999`) - there is a server setting that should help 
  narrow down prices to 3 decimals, in short try php.ini setting `serialize_precision = -1`. Link to issue on 
  [stack](https://stackoverflow.com/questions/42981409/php7-1-json-encode-float-issue).

# Configuration

### Administration

##### Export

- Flysystem - uses Flysystem to write SDK file export

### Email template variable usage

For create order emails:

```html
{% set shopgateOrder = order.extensions.shopgateOrder|default(false) %}

Selected shipping type:
{% if shopgateOrder %}
  {{ shopgateOrder.getShippingMethodName() }}
{% else %}
  {{ delivery.shippingMethod.translated.name }}
{% endif %}

Payment Type:
{% if shopgateOrder %}
  {{ shopgateOrder.getPaymentMethodName() }}
{% endif %}
```

### Set settings API

- **server** - `live`, `pg` or `custom`
- **api_url** - `http://my.url.com`
- **product_types_to_export** - `simple,variant` (a comma separated list)

### Custom fields

These values can be set inside Shopgate admin panel or script to pass to Shopware & will bind to the Entities. One can
also pass non-existing or existing customFields to Shopware. If a Custom Set exists for an entity (e.g. Order), and it
has a customField defined, it will map.

#### Customer

- `title` - customer title
- `affiliate_code`
- `campaign_code`
- `account_type` - `business` or `private`
- `vat_ids` - provide a **single** VAT ID, `accountType` must be `business` for this to be set

#### Customer Address

- `department` - department of a company
- `phone_number` - phone number can be passed via `customFields` as an alternative to `address->phone`

#### Order

- `affiliate_code`
- `campaign_code`
- `customer_comment` - a comment string from the customer
