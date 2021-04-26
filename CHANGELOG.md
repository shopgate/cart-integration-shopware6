# 0.0.12
- added exclusion of shopgate free shipping method from check_cart
- fixed base_price currency formatting & adjusted overall presentation
- added customer shipping/billing addresses to check_cart for rule calculations

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
