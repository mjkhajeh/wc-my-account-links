# My Account Links

> Remove, rename, and reorder WooCommerce My Account navigation links from a polished admin UI.

![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b?logo=wordpress&logoColor=white)
![WooCommerce](https://img.shields.io/badge/WooCommerce-required-96588a?logo=woocommerce&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-GPL%20v2%2B-green)

---

## Features

- **Toggle visibility** — show or hide any My Account navigation item with a single switch
- **Rename items** — set a custom label per item, or reset it back to the WooCommerce default with one click
- **Drag-and-drop reordering** — rearrange items without typing numbers or reloading the page
- **Global reset** — restore all WooCommerce defaults in one action
- **Third-party plugin safe** — endpoints added by other plugins are preserved unless you explicitly configure them
- **Graceful degradation** — does nothing harmful if WooCommerce is deactivated; shows a clear admin notice instead

---

## Screenshots

<img width="1920" height="1080" alt="screenshot-1" src="https://github.com/user-attachments/assets/6ab2938e-2fc5-4da3-91b5-11f488e3c5b9" />


---

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 6.0 or later |
| WooCommerce | Any recent version (must be active) |
| PHP | 7.4 or later |

---

## Installation

**Via WordPress admin (recommended)**

1. Go to **Plugins → Add New → Upload Plugin**
2. Upload the `my-account-links.zip` file
3. Click **Install Now**, then **Activate**

**Manual install**

1. Clone or download this repository
2. Copy the `my-account-links` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

Once activated, navigate to **WooCommerce → My Account Links** to configure the navigation.

---

## Usage

1. Open **WooCommerce → My Account Links** in the WordPress admin
2. **Toggle** the switch in the *Visible* column to show or hide an item
3. **Type** in the *Custom Label* field to rename an item (leave blank to keep the WooCommerce default)
4. **Drag** the handle on the left to reorder items
5. Click **Save Settings** in the sticky bar at the bottom

To undo all changes, click **Reset All Defaults** in the top-right corner of the page.

---

## How It Works

Settings are stored in the WordPress Options table under the key `mj_wc_account_links_settings` and applied at runtime via the `woocommerce_account_menu_items` filter (priority 99). This means:

- Customisations survive WooCommerce updates
- No database schema changes are required
- The plugin has zero effect when WooCommerce is inactive

**Option schema**

```json
{
  "items": {
    "<endpoint-slug>": {
      "enabled": true,
      "label": "Custom Label or empty string"
    }
  }
}
```

---

## Hooks Reference

| Hook | Priority | Purpose |
|---|---|---|
| `plugins_loaded` | 20 | Bootstrap; check WooCommerce is active |
| `admin_menu` | default | Register submenu under WooCommerce |
| `admin_enqueue_scripts` | default | Enqueue CSS/JS only on the plugin page |
| `admin_post_mj_wc_account_links_save` | — | Handle settings save form |
| `admin_post_mj_wc_account_links_reset` | — | Handle reset-all form |
| `woocommerce_account_menu_items` | 99 | Apply saved settings on the frontend |

---

## FAQ

**Will my customisations survive a WooCommerce update?**  
Yes. Settings live in the WordPress Options table and are applied via a WooCommerce filter, so they persist across updates.

**I have a third-party plugin that adds extra My Account items. Will they appear?**  
Yes. The plugin only modifies items it knows about. Any endpoint registered after your last save is appended in its default position. Visit the settings page and save again to take control of newly added items.

**Can I reorder or hide the Logout link?**  
Yes — `customer-logout` is treated the same as any other endpoint.

**What happens if I deactivate WooCommerce?**  
The plugin detects that WooCommerce is missing, shows an admin notice with options to install or activate it, and does nothing else.

---

## Contributing

Pull requests are welcome. For significant changes, please open an issue first to discuss what you'd like to change.

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-change`
3. Commit your changes: `git commit -m 'Add my change'`
4. Push to the branch: `git push origin feature/my-change`
5. Open a pull request

---

## License

Licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

## Author

**MohammadJafar Khajeh** — [mjkhajeh.ir](https://mjkhajeh.ir)  
Plugin page: [wordpress.org/plugins/my-account-links](https://wordpress.org/plugins/my-account-links/)
