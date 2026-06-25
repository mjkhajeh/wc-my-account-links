=== My Account Links ===
Contributors:      mjkhajeh
Tags:              woocommerce, my account, navigation, menu, links
Requires at least: 6.0
Tested up to:      7.0
Requires PHP:      7.4
Stable tag:        1.0.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Remove, rename, and reorder WooCommerce My Account navigation links from a polished admin UI.

== Description ==

**My Account Links** gives store administrators full control over the navigation
items displayed in WooCommerce's My Account section.

**Features:**

* ✅ Enable / disable any My Account navigation item with a toggle switch.
* ✏️ Rename any item with a custom label (or reset it back to the WooCommerce default).
* 🔀 Drag-and-drop reordering — no numbers to type, no page reloads.
* 🔄 Per-item and global "Reset to defaults" options.
* 🔌 Safe with third-party plugins — endpoints added by other plugins are preserved unless you explicitly configure them.
* 🚫 Graceful degradation — the plugin does nothing harmful if WooCommerce is deactivated.

== Installation ==

1. Upload the `my-account-links` folder to the `/wp-content/plugins/` directory,
   OR install it via **Plugins → Add New → Upload Plugin** in the WordPress admin.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **WooCommerce → My Account Links** to configure the navigation items.

== Frequently Asked Questions ==

= Does this plugin work if WooCommerce is deactivated? =

The plugin detects whether WooCommerce is active on every page load. If WooCommerce is not
present, the plugin shows an admin notice and does nothing else — it will not cause errors.

= Will my customisations survive a WooCommerce update? =

Yes. All settings are stored in the WordPress Options table (`mj_wc_account_links_settings`)
and applied via WooCommerce's own `woocommerce_account_menu_items` filter, so they persist
across WooCommerce updates.

= I have a third-party plugin that adds extra My Account items. Will they still appear? =

Yes. The plugin only modifies items it knows about (those saved in its settings). Any endpoint
registered by another plugin after you last saved your settings will be appended in its default
position and remain visible. Visit the settings page and save again to take control of newly
added items.

= Can I change the order of the "Logout" item? =

Yes — Logout is just another WooCommerce endpoint slug (`customer-logout`) and can be reordered
or hidden like any other item.

== Screenshots ==

1. The plugin settings page with drag-and-drop reordering.
2. Individual item controls: toggle, custom label input, reset button.
3. The sticky save bar with contextual hint text.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.

== Developer Notes ==

**Hooks used:**

| Hook | Purpose |
|------|---------|
| `plugins_loaded` (priority 20) | Bootstrap; check WooCommerce is active |
| `admin_menu` | Register submenu under WooCommerce |
| `admin_enqueue_scripts` | Enqueue CSS/JS only on plugin page |
| `admin_post_mj_wc_account_links_save` | Handle settings save form |
| `admin_post_mj_wc_account_links_reset` | Handle reset-all form |
| `woocommerce_account_menu_items` (priority 99) | Apply saved settings on frontend |

**Option key:** `mj_wc_account_links_settings`

**Option schema:**
```json
{
  "items": {
    "<endpoint-slug>": {
      "enabled": true,
      "label":   "Custom Label or empty string"
    }
  }
}
```
