<?php
/**
 * Plugin Name:       My Account Links
 * Plugin URI:        https://wordpress.org/plugins/my-account-links/
 * Description:       Remove, rename, and reorder WooCommerce My Account navigation links.
 * Version:           1.0.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Tested up to:      7.0
 * Author:            MohammadJafar Khajeh
 * Author URI:        https://mjkhajeh.ir
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-account-links
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 */

namespace MJ\MyAccountLinks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'MJ_MY_ACCOUNT_LINKS_VERSION', '1.0.0.0' );
define( 'MJ_MY_ACCOUNT_LINKS_FILE', __FILE__ );
define( 'MJ_MY_ACCOUNT_LINKS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MJ_MY_ACCOUNT_LINKS_URL', plugin_dir_url( __FILE__ ) );

class Init {
	/**
	 * Check if WooCommerce is active and bootstrap the plugin.
	 */
	public static function init() {
		load_plugin_textdomain(
			'my-account-links',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		if ( ! class_exists( 'WooCommerce' ) ) {
			// WooCommerce is not active; show an admin notice.
			add_action( 'admin_notices', [__CLASS__, 'notice'] );
			return;
		}

		// Load core class files.
		require_once MJ_MY_ACCOUNT_LINKS_PATH . 'Includes/Core.php';
		require_once MJ_MY_ACCOUNT_LINKS_PATH . 'Includes/Admin.php';

		// Boot the plugin singleton.
		Core::get_instance();
	}

	/**
	 * Admin notice shown when WooCommerce is not active.
	 *
	 * Renders a rich, styled banner notice — no external stylesheet needed,
	 * everything is inline so it works even before our admin CSS is enqueued.
	 */
	public static function notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$install_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'install-plugin',
					'plugin' => 'woocommerce',
				),
				admin_url( 'update.php' )
			),
			'install-plugin_woocommerce'
		);

		?>
		<div style="
			margin-block-start: 16px;
			margin-block-end: 0;
			margin-inline-start: 2px;
			margin-inline-end: 20px;
			border-radius: 10px;
			overflow: hidden;
			box-shadow: 0 2px 12px rgba(0,0,0,0.10);
			border: none;
		">
			<!-- Coloured top bar -->
			<div style="background: linear-gradient(to right,#ef4444 0%,#f97316 100%); block-size:4px;"></div>

			<div style="
				display: flex;
				align-items: center;
				gap: 18px;
				padding-block: 18px;
				padding-inline: 22px;
				background: #fff8f8;
				flex-wrap: wrap;
			">
				<!-- Icon -->
				<span style="
					display: flex;
					align-items: center;
					justify-content: center;
					inline-size: 44px;
					block-size: 44px;
					border-radius: 50%;
					background: #fee2e2;
					flex-shrink: 0;
				">
					<span class="dashicons dashicons-warning" style="
						color: #ef4444;
						font-size: 22px;
						inline-size: 22px;
						block-size: 22px;
						margin: 0;
					"></span>
				</span>

				<!-- Text -->
				<div style="flex: 1; min-inline-size: 200px;">
					<p style="margin: 0 0 4px; font-size: 14px; font-weight: 700; color: #1d2327;">
						<?php esc_html_e( 'WooCommerce is required', 'my-account-links' ); ?>
					</p>
					<p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.5;">
						<?php
						printf(
							wp_kses(
								/* translators: %s: plugin name */
								__( '<strong>My Account Links</strong> requires WooCommerce to be installed and active. Please install or activate WooCommerce to use this plugin.', 'my-account-links' ),
								array( 'strong' => array() )
							)
						);
						?>
					</p>
				</div>

				<!-- Action buttons -->
				<div style="display: flex; gap: 10px; flex-wrap: wrap; flex-shrink: 0;">
					<a href="<?php echo esc_url( $install_url ); ?>" style="
						display: inline-flex;
						align-items: center;
						gap: 6px;
						padding-block: 9px;
						padding-inline: 18px;
						background: #ef4444;
						color: #fff;
						border-radius: 7px;
						font-size: 13px;
						font-weight: 600;
						text-decoration: none;
						border: none;
						transition: background .15s;
					" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
						<span class="dashicons dashicons-download" style="font-size:15px;inline-size:15px;block-size:15px;margin:0;"></span>
						<?php esc_html_e( 'Install WooCommerce', 'my-account-links' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" style="
						display: inline-flex;
						align-items: center;
						gap: 6px;
						padding-block: 9px;
						padding-inline: 18px;
						background: #fff;
						color: #374151;
						border-radius: 7px;
						font-size: 13px;
						font-weight: 600;
						text-decoration: none;
						border: 1.5px solid #d1d5db;
					">
						<span class="dashicons dashicons-admin-plugins" style="font-size:15px;inline-size:15px;block-size:15px;margin:0;"></span>
						<?php esc_html_e( 'Go to Plugins', 'my-account-links' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}
add_action( 'init', [Init::class, 'init'] );