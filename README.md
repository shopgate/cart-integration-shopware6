# Shopgate Shopware6 Integration

## Install

### Packagist (public) install
If the package is available on packagist.org, simply add the composer package to the shopware's root composer:
```shell
cd [shopware6 root folder]
composer require shopgate/cart-integration-shopware6
```

### Folder install

In case Shopgate module is not publicly available via packagist, it can be installed manually by copying the
`ShopgateModule` folder to `custom/static-plugins` folder.

#### Composer symlink
You can now add the requirement of the Shopgate plugin by running the composer command in the root directory:
```shell
cd [shopware6 root folder]
composer require shopgate/cart-integration-shopware6:0.0.8
```
With this method, make sure to use the exact version (e.g. `0.0.8`) that is provided in the composer.json of
the ShopgateModule.

#### Composer folder (untested)
Supposedly you can place the `ShopgateModule` folder in the `custom/plguins`. 
Make sure the run the `composer update` inside the ShopgateModule folder so that the Shopgate SDK is installed.

## Enable & Activate
Install and activate the module:
```shell
cd [shopware6 root folder]
php bin/console plugin:refresh
php bin/console plugin:install ShopgateModule
php bin/console plugin:activate ShopgateModule
php bin/console cache:clear
```
You may install and activate via the Shopware administration panel instead, if you prefer.

## Compile frontend
This shopware 6 command will compile the JavaScript of frontend and backend:
```shell
cd [shopware6 root folder]
./bin/build-js.sh
```
