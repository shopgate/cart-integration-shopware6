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

- added support to sub-level root categories (2nd level+)
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
