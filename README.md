# Shopgate Shopware6 Integration

## Install

### Packagist install (recommended)
This plugin is available on `packagist.org`. To install simply add the composer package to the shopware's root composer:
```shell
cd [shopware6 root folder]
composer require shopgate/cart-integration-shopware6
```
Afterwards just increment the plugin version inside `root/composer.json`, and run `composer update` to get the latest
version.

### Folder install

It can be installed manually by copying the plugin folder to `custom/plugins` directory. However, please make sure you
have a composer compiled version of the plugin. Meaning, you have a `vendor` directory in the plugin folder.

#### Composer symlink (development)

After placing it in the `plugins` folder you can now link it to composer by running this command in the root
directory:

```shell
cd [shopware6 root folder]

# this step is required only in case you do not already have this in the root composer.json specified
composer config repositories.sym '{"type": "path", "url": "custom/plugins/*", "options": {"symlink": true}}'

# make sure to use the exact version (e.g. `1.6.4`) that is provided in the composer.json of this plugin.
composer require shopgate/cart-integration-shopware6:1.6.4
```

## Enable & Activate

Install and activate the module:

```shell
cd [shopware6 root folder]
php bin/console plugin:refresh
php bin/console plugin:install SgateShopgatePluginSW6
php bin/console plugin:activate SgateShopgatePluginSW6
php bin/console cache:clear
```

You may install and activate via the Shopware administration panel instead, if you prefer.

## Compile frontend

This shopware 6 command will compile the JavaScript of frontend and backend:

```shell
cd [shopware6 root folder]
./bin/build-js.sh
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

# Configuration

### Administration

##### Export

- Flysystem - uses Flysystem to write SDK file export

### Email template variable usage (supported as of Shopware 6.4.4.0)

For create order emails:

```html
{% set shopgateOrder = order.extensions.shopgateOrder %}

Selected shipping type:
{% if shopgateOrder %}
{{ shopgateOrder.receivedData.shipping_infos.display_name }}
{% else %}
{{ delivery.shippingMethod.translated.name }}
{% endif %}

Payment Type:
{% if shopgateOrder %}
{{ shopgateOrder.receivedData.payment_infos.shopgate_payment_name }}
{% endif %}
```

### Set settings API

- **shop_is_active** - `0` or `1` (NB! once you disable, you will not be able to call this shop anymore)
- **server** - `live`, `pg` or `custom`
- **api_url** - `http://my.url.com`
- **product_types_to_export** - `simple,variant` (a comma separated list)

### Custom fields

These values can be set inside Shopgate admin panel or script to pass to Shopware & will bind to the Entities. One can
also pass non-existing or existing customFields to Shopware. If a Custom Set exists for an entity (e.g. Order), and it
has a customField defined, it will map.

#### Customer

- `title` - customer title
- `affiliateCode`
- `campaignCode`
- `accountType` - `business` or `private`
- `vatIds` - provide a **single** VAT ID, `accountType` must be `business` for this to be set

#### Customer Address

- `department` - department of a company

#### Order

- `affiliateCode`
- `campaignCode`
- `customerComment` - a comment string from the customer
