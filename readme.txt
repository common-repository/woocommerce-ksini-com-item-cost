=== Plugin Name ===
Contributors: havban
Donate link: http://febiansyah.name
Tags: woocommerce, cost, profit, statistic
Requires at least: 3.0.1
Tested up to: 3.9
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adding item cost field to general tab in product editing, and add stats for profit calculation

== Description ==

Adding item cost field to general tab in product editing, and add stats for profit calculation.

To make the profit statistic works, you need to:

* Update item cost value in each product editing page, the profit calculation will be calculated automatically for
previous orders that includes currently edited product
* All update to item cost post initial update, will not recalculate previous orders profit
* If you need to update all orders related to the product disregard its purchase date, you need to set the item cost field
empty, save it, then enter again the new item cost price 


== Installation ==

1. Upload folder `woocommerce-ksini-com-item-cost` to the `/wp-content/plugins/` directory. Some other option is
   by installing the plugin through Add plugin page in dashboard, search for "woocommerce ksini item cost"
   without double quote.
2. Activate the plugin through the 'Plugins' menu in WordPresss
3. Update each product's item cost through product edit page
4. You can vew statistic at "Ksini Profit Report"

== Frequently Asked Questions ==

To update all orders related with the product, disregard its purchase date, empty the item cost field, save it, then
fill new item cost price and save it again.

= A question that someone might have =

Question you may ask.

= Is it free? =

Yes.

= Will it calculate previous orders? =

Yes.

== Screenshots ==

1. You can enter the item cost value in the field and save it. All previous orders will be calculated also in profit statitic.
2. You can view the statistic here, through menu "Ksini Profit Report".s

== Changelog ==
= 1.3 =
* bug fix on icon url
* updated readme on installation description

= 1.2 =
* Bug fix on added order, item cost meta data was from order, now fixed.
* jquery-ui style moved from external source to local plugin folder

= 1.1 =
* Bug Fix
* Put currency value for item cost

= 1.0 =
* Initial Release
