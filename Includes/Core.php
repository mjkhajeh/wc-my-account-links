<?php
/**
 * Core plugin class.
 *
 * Bootstraps the plugin, applies the WooCommerce menu filter on the frontend,
 * and exposes shared helper methods used by the admin class.
 *
 * @package MJ_MY_ACCOUNT_LINKS
 */

namespace MJ\MyAccountLinks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MJ_MY_ACCOUNT_LINKS
 */
class Core {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Cached plugin settings.
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Private constructor – use get_instance().
	 */
	private function __construct() {
		$this->hooks();
	}

	/**
	 * Return (or create) the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	private function hooks() {
		// Admin side.
		if ( is_admin() ) {
			$admin = new Admin( $this );
			$admin->hooks();
		}

		// Frontend: modify WooCommerce My Account menu items.
		add_filter( 'woocommerce_account_menu_items', array( $this, 'filter_menu_items' ), 99 );
	}

	// -----------------------------------------------------------------------
	// Frontend filter
	// -----------------------------------------------------------------------

	/**
	 * Apply saved settings to WooCommerce My Account navigation.
	 *
	 * Runs on the `woocommerce_account_menu_items` filter (priority 99).
	 *
	 * @param  array $items Default WooCommerce menu items (slug => label).
	 * @return array Modified items.
	 */
	public function filter_menu_items( array $items ): array {
		$settings = $this->get_settings();

		// No configuration saved yet – return untouched.
		if ( empty( $settings ) ) {
			return $items;
		}

		$configured = $settings['items'] ?? array();

		if ( empty( $configured ) ) {
			return $items;
		}

		$new_items = array();

		// 1. Walk through the admin-configured order and apply overrides.
		foreach ( $configured as $slug => $config ) {
			// Skip explicitly disabled items.
			if ( isset( $config['enabled'] ) && ! $config['enabled'] ) {
				continue;
			}

			// Only include items that either exist in WooCommerce's current
			// list OR were previously known (they may have been added by
			// another plugin that is still active).
			if ( ! isset( $items[ $slug ] ) ) {
				// Item no longer present (plugin deactivated, etc.) – skip.
				continue;
			}

			// Use custom label if set; fall back to WooCommerce label.
			$label = ( ! empty( $config['label'] ) )
				? wp_kses_post( $config['label'] )
				: $items[ $slug ];

			$new_items[ $slug ] = $label;
		}

		// 2. Preserve any unknown endpoints added by third-party plugins
		//    that weren't present when the admin last saved settings.
		foreach ( $items as $slug => $label ) {
			if ( ! array_key_exists( $slug, $configured ) ) {
				$new_items[ $slug ] = $label;
			}
		}

		return $new_items;
	}

	// -----------------------------------------------------------------------
	// Settings helpers
	// -----------------------------------------------------------------------

	/**
	 * Retrieve the saved plugin settings (with in-memory cache).
	 *
	 * @return array
	 */
	public function get_settings(): array {
		if ( null === $this->settings ) {
			$raw            = get_option( 'my_account_links_settings', array() );
			$this->settings = is_array( $raw ) ? $raw : array();
		}
		return $this->settings;
	}

	/**
	 * Persist settings and clear the in-memory cache.
	 *
	 * @param  array $settings Sanitized settings array.
	 * @return bool  True on success.
	 */
	public function save_settings( array $settings ): bool {
		$this->settings = null; // Clear cache.
		return update_option( 'my_account_links_settings', $settings );
	}

	/**
	 * Delete all saved settings (used for "Reset all").
	 *
	 * @return bool
	 */
	public function delete_settings(): bool {
		$this->settings = null;
		return delete_option( 'my_account_links_settings' );
	}

	/**
	 * Return the raw WooCommerce My Account menu items, unfiltered by this plugin.
	 *
	 * We temporarily remove our own filter, apply the WooCommerce filter stack,
	 * then re-add it.
	 *
	 * @return array slug => label
	 */
	public function get_default_wc_menu_items(): array {
		// Remove our filter temporarily so we get unmodified items.
		remove_filter( 'woocommerce_account_menu_items', array( $this, 'filter_menu_items' ), 99 );

		// WooCommerce builds the default list via this filter.
		$items = wc_get_account_menu_items();

		// Re-add our filter.
		add_filter( 'woocommerce_account_menu_items', array( $this, 'filter_menu_items' ), 99 );

		return is_array( $items ) ? $items : array();
	}

	/**
	 * Sanitize a single item config array coming from $_POST.
	 *
	 * @param  mixed $raw Raw value.
	 * @return array
	 */
	public function sanitize_item_config( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array(
				'enabled' => false,
				'label'   => '',
			);
		}
		return array(
			'enabled' => ! empty( $raw['enabled'] ),
			'label'   => sanitize_text_field( $raw['label'] ?? '' ),
		);
	}
}
