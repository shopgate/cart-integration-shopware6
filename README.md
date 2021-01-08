# Shopgate Shopware62 Integration

### Usage

Go to Shopware 6 root folder and add the composer package to the require list, e.g.:
```
composer require composer/alias
```

Install and activate the module:
```
php bin/console plugin:refresh
php bin/console plugin:install ShopgateModule
php bin/console plugin:activate ShopgateModule
php bin/console cache:clear
```
