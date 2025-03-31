# 3.5.2

- fixed 6.6.x issue with order info not loading on SG orders
- fixed 6.5.x twig order date template

# 3.5.1

- changed indexer priority to run after categories, set to -10
- fixed ACL permissions for API credential lists
- fixed uninstall routine to also remove the `...category_product_mapping` table

# 3.5.0

- added API endpoint for specific category indexing
- added deletion type config for index map to always remove (default), full index only or never
- added a multi-select config to allow exporting properties of other domains (e.g. Manufacturer)
- added a configuration to export manufacturer data as properties (name, description, url)

# 3.4.0

- added category/product map logging
- added log API reader (see postman collection for example)
- fixed some performance issues with generating category/product maps
- fixed language sort order for category export
- fixed select custom field value export
- fixed multi-select custom field value export

# 3.3.4

- added fallback to tier price edge case where no "all valid" or "default customer group" tier price exists
- updated SDK to 2.10.3

# 3.3.3

- fixed `register_customer` call when address salutation (gender) is empty
- removed unnecessary sort export config

# 3.3.2

- adjusted symfony/expression-language to be compatible with 6.6.4.x+

# 3.3.1

- added catchers of category/product update events
- added category/product mapping pruning better

# 3.3.0

- added category -> product indexer to help speed up catalog export
- added API endpoint for main controller action. Use `website.de/api/shopgate/plugin` instead.
- fixed order date in order twig template
- removed flag that disabled sort export

# 3.2.1

- fixed admin panel JS for 6.6 compatibility
- removed SG logo from Settings > Extensions

# 3.2.0

- added support to SW 6.6

# 3.1.2

- updated symfony expression dependency to support ver 6.4
- fixed error with command `bin/console debug:event-dispatcher`

# 3.1.1

- added filter for product ID to avoid duplicates in product export

# 3.1.0

- added Shopgate icon to settings > extensions tab 
- removed order manual shipping re-calculation for 6.5.5.0+

# 3.0.1

- fixed marketplace installation does not have the needed dependencies

# 3.0.0

- added support for SW 6.5.2.0
- added new custom field exported for customer (`account_type` -> business or private)
- updated `get_settings` export for countries, tax-free countries are for `private` customers, not `business` customers
- updated filesystem writer classes
- updated context language logic
- removed support for SW 6.4
- removed support for PHP < 8.1

# 2.10.1

- changed order payment/shipping labels to inherit parent if not a shopgate order

# 2.10.0

- changed plugin response types to SW native ones
- updated SDK
- removed exit codes

# 2.9.0

- added salutation mapping
- fixed duplicate customer address creation despite being identical (register, check_cart, add_order)
- removed config warning that API connection entries were moved elsewhere

# 2.8.0

- added configuration flag to disable product sort order in categories

# 2.7.0

- added product export property event

# 2.6.0

- fixed `check_cart` & `add_order` context reset to no longer assign an inactive payment method
- fixed 100% shipping discount to not have more than 0 in shipping gross field (for NET customer group)
- fixed wrong export format of `external_coupons` when calling `get_orders`
- fixed `check_cart` & `add_order` customer session language change
- updated min SDK to 2.9.90

# 2.5.1

- fixed scenario for shipping & cart discounts with one coupon code

# 2.5.0

- changed shipping method order to honor SW6.4.11 position & selected to be last
- added support for currently "selected" shipping method for SW6 rules to work on shipping

# 2.4.0

- removed language uniqueness constraint for API connections

# 2.3.1

- fixed incoming order shipping amount to match the Shopware customer group tax state
- changed rounding calculations for whole `check_cart` to 3 decimals

# 2.3.0

- removed being able to de-activate channel via set_setting's `shop_is_active` flag

# 2.2.0

- added item, coupon, shipping, product export rounding up to 3 decimal points
- updated Shopgate logo

# 2.1.2

- removed item, coupon & shipping rounding in check_cart

# 2.1.1

- added caching of cart after addItem is added to support 3rd party plugin cart re-calculations

# 2.1.0

- added configuration that allows for reviews to be exported from all sales channels (default: on)
- added support to SW 6.4.11

# 2.0.0

- added ability to add settings via `set_settings`
- added `live shopping` for cart & orders
- added multi-store/currency support
- added order export for customers to view in the App
- added product tax calculations to `cart`
- added review export via `get_reviews`
- added Shopgate `custom_fields` support to customer & customer addresses (see [README](README.md))
- added support for categories with `dynamic product groups`
- added support for product `CrossSell` sliders (max 4)
- added support to PHP 8.0
- added Net/Gross export option to configuration
- added cheapest & previous prices to product property export
- changed location of API credentials to accommodate multi-language calls
- fixed cron logic to accurately cancel orders & mark orders shipped in Shopgate Panel
- fixed product sort to use parent ID instead of child ID for sort order positions in a category
- fixed product export not honoring clearance flag
- fixed category sort cache is now more responsive to cache clears
- fixed issue with order import when an SG coupon is present

# 1.8.5

- added support to customer registration configuration - `Data protection checkbox`
- fixed customer account page > payment method loading

# 1.8.4

- fixed export of product tags

# 1.8.3

- added decoration possibility to product mapping classes
- added decoration possibility to category mapping class
- changed translation service to use abstract class

# 1.8.2

- added translations to configurations
- changed uncaught errors to be converted into json

# 1.8.1

- fixed rules not honoring customer/guest address mapping

# 1.8.0

- changed generated file location to use `var/cache` and `var/log` folders instead of our SDK in vendor

# 1.7.2

- fixed guest `check_cart` call running out of memory when customer database has a lot of customers

# 1.7.1

- added `expression-language` composer dependency for installations without dev-dependencies installed

# 1.7.0

- added configuration to save export files via Flysystem

# 1.6.5

- fixed customer address type mapping in case billing and shipping address are the same
- fixed guest check_cart calls failing

# 1.6.4

- added postman tests
- fixed customer `last_name` mapping
- fixed address `company` mapping

# 1.6.3

- fixed issues with plugin renaming & installation error
- fixed catching error properly when no `shop_number` is sent in API call

# 1.6.2

- fixed issues with addOrder & checkCart not logging in customer/guests properly

# 1.6.1

- added compatibility for SW versions lower than 6.4.4.0 introduced in release 1.5.0
- fixed issues with payment/shipping labels not loading on frontend

# 1.6.0

- added an automatic deserializer for order item internal fields
- removed re-use of existing customer cart when adding an order
- removed deleting existing customer cart when adding an order
- removed re-use of existing customer cart on app check cart calls
- removed deleting existing customer cart on app check cart calls

# 1.5.0

- added order email template variables (dependent on [SW 6.4.4.0])

# 1.4.2

- added first unit test
- added json helper trait
- fixed package dimensions & pack units to not export if value is empty
- fixed `check_cart` without email or external_customer_id fatal error
- fixed new coupons no longer being added to cart
- removed strict SDK version constraint, should auto-update

# 1.4.1

- fixed issue with cart rules not removing from App cart when invalidated
- changed incoming `external_coupons` to be invalidated and used as outgoing coupons

# 1.4.0

- added package dimensions (length, height, width) & pack units to product export

# 1.3.0

- added support for **guest** billing address when checking out

# 1.2.0

- added order `custom_fields` to order view page

# 1.1.1

- added clarity to errors when mapping `check_cart` shipping methods

# 1.1.0

- changed order class locations
- added line item & shipping events
- added current payment mapping
- added mapping for selected Cash on Delivery (COD) payment in `check_cart` and `add_order`
- added product export events to support extra product injection

# 1.0.1

- fixed issues with uninitialized php7.4 properties

# 1.0.0

- added support for Shopware 6.4 & min version PHP 7.4
- removed support for Shopware below 6.4

# 0.1.3

- fixed issue when exporting properties with false (checkbox) value
- fixed issue with shopgate order getting deleted on order edit

# 0.1.2

- added product custom fields to product export
- fixed coupon error handling for ineligible rules
- fixed property values to be concatenated with a comma instead of being separate values

# 0.1.1

- added more support for FinSearch subscribers

# 0.1.0

- added shopgate order details on admin shopware order detail page

# 0.0.13

- added variant (child) manufacturer, price & property export
- fixed issue with missing payment/shipping method notifications showing for customer after a mobile order is made

# 0.0.12

- added exclusion of shopgate free shipping method from check_cart
- added customer shipping/billing addresses to check_cart for rule calculations
- fixed base_price currency formatting & adjusted overall presentation

# 0.0.11

- added shopgate free shipping method for 0 priced imports
- added detailed trace SDK debug logs for addOrder issues
- added detailed debug logs for check_cart calls
- added availability text to product export. e.g. (Available, delivery time 2 days)
- added base price string to product export, e.g. ($100 / 1 kg)
- fixed issue with creating and order for promotions without a coupon code
- fixed image sort order for product export

# 0.0.10

- added better error logging for item export
- fixed cover picture warnings when empty
- fixed empty shipping info when checking out
- fixed problematic country DB entries without ISO (we now skip these)
- changed sortTree logic to be more error resistant

# 0.0.9

- added detailed error log to product export
- fixed shopNumber matching to be more precise
- fixed DeepLink logic to output SEO URI instead of internal

# 0.0.8

- added support to sublevel root categories (2nd level+)
- fixed co-existence with FindLogic Search & Navigation plugin
- fixed products not exporting due to `array_value` null exception

# 0.0.7

- added shopgate shipping/payment showing up on admin/storefront

# 0.0.6

- fixed order address mapping
- fixed check_cart exporting shopgate shipping method

# 0.0.5

- added cart support with shipping, items (partial), promos, customer - check_cart
- added order support
- added shopgate_order table
- added generic payment method
- added generic shipping method
- added is_shopgate rule for shipping/payment to not appear on frontend
- added cron cancellations/shipping
- fixed sort order for categories
- fixed inactive/invisible categories not exporting
- fixed products referencing a root category
- fixed cart qty issues
- fixed installation routines

# 0.0.4

- Added authentication configuration
- Added category export - get_categories
- Added product export (simple/variant) - get_items
- get_settings
- get_customer
- ping

[SW 6.4.4.0]: (https://github.com/shopware/platform/releases/tag/v6.4.4.0)
