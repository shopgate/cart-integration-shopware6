# Shopgate Shopware6 Integration

## Install

### Packagist (public) install
If this plugin is available on `packagist.org`, simply add the composer package to the shopware's root composer:
```shell
cd [shopware6 root folder]
composer require shopgate/cart-integration-shopware6
```

### Folder install (Recommended)

In case Shopgate module is not publicly available via `packagist.org`, it can be installed manually by copying the
plugin folder to `custom/static-plugins` folder.

#### Composer symlink

After placing it in the `static-plugins` folder you can now link it to composer by running this command in the root
directory:

```shell
cd [shopware6 root folder]
composer require shopgate/cart-integration-shopware6:1.6.2
```

With this method, make sure to use the exact version (e.g. `1.6.2`) that is provided in the composer.json of this
plugin.

#### Composer folder (Untested)

Alternatively you could place the `SgateShopgatePluginSW6` folder in the `custom/plguins`. Afterwards make sure the run
the `composer update` inside this plugin's folder so that the Shopgate SDK is installed.

```shell
cd custom/plugins/SgateShopgatePluginSW6
composer update
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
  1. select SaleChannel that is being queried by Shopgate API
  1. Check `General Settings` default language (e.g., English)
  1. Check `Domains` list, see that there is no domain URL with default language (e.g., English)

# Configuration

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
