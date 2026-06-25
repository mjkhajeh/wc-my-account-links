<?php
/**
 * Admin class.
 *
 * Handles the WordPress admin settings page:
 *  - Registers the menu page under WooCommerce.
 *  - Enqueues assets only on its own page.
 *  - Renders the sortable UI.
 *  - Processes save / reset form submissions.
 *
 * @package MJ_MY_ACCOUNT_LINKS
 */

namespace MJ\MyAccountLinks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	/**
	 * Reference to the core plugin instance.
	 *
	 * @var MJ_MY_ACCOUNT_LINKS
	 */
	private $plugin;

	/**
	 * The admin page hook suffix returned by add_submenu_page().
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Nonce action / name constants.
	 */
	const NONCE_ACTION_SAVE  = 'mj_MY_Account_Links_save';
	const NONCE_ACTION_RESET = 'mj_MY_Account_Links_reset';
	const NONCE_NAME         = 'mj_MY_Account_Links_nonce';

	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register WordPress admin hooks.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_mj_MY_Account_Links_save', array( $this, 'handle_save' ) );
		add_action( 'admin_post_mj_MY_Account_Links_reset', array( $this, 'handle_reset' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	// -----------------------------------------------------------------------
	// Menu registration
	// -----------------------------------------------------------------------

	/**
	 * Add a sub-menu page under WooCommerce.
	 */
	public function register_menu() {
		$this->page_hook = add_submenu_page(
			'woocommerce',
			__( 'My Account Links', 'my-account-links' ),
			__( 'My Account Links', 'my-account-links' ),
			'manage_woocommerce',
			'my-account-links',
			array( $this, 'render_page' )
		);
	}

	// -----------------------------------------------------------------------
	// Asset enqueuing
	// -----------------------------------------------------------------------

	/**
	 * Enqueue CSS and JS only on our settings page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook_suffix ) {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		// jQuery UI Sortable is bundled with WordPress core.
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Plugin admin CSS.
		wp_enqueue_style(
			'mj-my-account-links-admin',
			MJ_MY_ACCOUNT_LINKS_URL . 'assets/css/admin.css',
			array(),
			MJ_MY_ACCOUNT_LINKS_VERSION
		);

		// Plugin admin JS.
		wp_enqueue_script(
			'mj-my-account-links-admin',
			MJ_MY_ACCOUNT_LINKS_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			MJ_MY_ACCOUNT_LINKS_VERSION,
			true
		);

		// Pass translated strings and other data to JS.
		wp_localize_script(
			'mj-my-account-links-admin',
			'mjWcAccountLinks',
			array(
				'confirmReset' => __( 'Are you sure you want to reset all settings to WooCommerce defaults? This cannot be undone.', 'my-account-links' ),
				'dragHandle'   => __( 'Drag to reorder', 'my-account-links' ),
			)
		);
	}

	// -----------------------------------------------------------------------
	// Save / Reset handlers
	// -----------------------------------------------------------------------

	/**
	 * Handle the "Save Settings" form submission.
	 *
	 * Validates nonce, capability, sanitizes input, saves to the database,
	 * then redirects back with a success/error query arg.
	 */
	public function handle_save() {
		// Capability check.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'my-account-links' ) );
		}

		// Nonce verification.
		if (
			! isset( $_POST[ self::NONCE_NAME ] ) ||
			! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION_SAVE )
		) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'my-account-links' ) );
		}

		// Sanitize submitted items.
		$submitted_items  = isset( $_POST['mj_items'] ) && is_array( $_POST['mj_items'] )
			? $_POST['mj_items']  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below
			: array();

		$submitted_order = isset( $_POST['mj_order'] ) && is_array( $_POST['mj_order'] )
			? array_map( 'sanitize_key', $_POST['mj_order'] )
			: array();

		// Build ordered, sanitized settings.
		$settings_items = array();
		foreach ( $submitted_order as $slug ) {
			$slug = sanitize_key( $slug );
			if ( empty( $slug ) ) {
				continue;
			}
			$raw_config              = $submitted_items[ $slug ] ?? array();
			$settings_items[ $slug ] = $this->plugin->sanitize_item_config( $raw_config );
		}

		// Include any slugs that were in $_POST but not in the order array
		// (edge case: JS may not have captured a new item).
		foreach ( $submitted_items as $slug => $raw_config ) {
			$slug = sanitize_key( $slug );
			if ( ! array_key_exists( $slug, $settings_items ) ) {
				$settings_items[ $slug ] = $this->plugin->sanitize_item_config( $raw_config );
			}
		}

		$result = $this->plugin->save_settings( array( 'items' => $settings_items ) );

		$redirect = add_query_arg(
			array(
				'page'              => 'my-account-links',
				'mj_wc_al_updated' => $result ? '1' : '0',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle the "Reset All" form submission.
	 *
	 * Deletes saved settings so WooCommerce defaults are used.
	 */
	public function handle_reset() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'my-account-links' ) );
		}

		if (
			! isset( $_POST[ self::NONCE_NAME ] ) ||
			! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION_RESET )
		) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'my-account-links' ) );
		}

		$this->plugin->delete_settings();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => 'my-account-links',
					'mj_wc_al_reset'  => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -----------------------------------------------------------------------
	// Admin notices
	// -----------------------------------------------------------------------

	/**
	 * Display rich, styled admin notices after save / reset redirects.
	 *
	 * Three possible states:
	 *  - Save success  (mj_wc_al_updated=1)  -> green
	 *  - Save error    (mj_wc_al_updated=0)  -> red
	 *  - Reset success (mj_wc_al_reset=1)    -> purple / info
	 */
	public function display_admin_notices() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== $this->page_hook ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['mj_wc_al_updated'] ) ) {
			if ( '1' === $_GET['mj_wc_al_updated'] ) {
				$this->render_notice(
					'success',
					__( 'Settings saved', 'my-account-links' ),
					__( 'Your My Account navigation has been updated and is now live on the frontend.', 'my-account-links' ),
					'dashicons-yes-alt'
				);
			} else {
				$this->render_notice(
					'error',
					__( 'Save failed', 'my-account-links' ),
					__( 'There was a problem saving your settings. Please try again. If the issue persists, check your server error log.', 'my-account-links' ),
					'dashicons-dismiss'
				);
			}
		}

		if ( isset( $_GET['mj_wc_al_reset'] ) && '1' === $_GET['mj_wc_al_reset'] ) {
			$this->render_notice(
				'info',
				__( 'Settings reset', 'my-account-links' ),
				__( 'All customisations have been cleared. WooCommerce default My Account navigation is now restored.', 'my-account-links' ),
				'dashicons-image-rotate'
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Render a single styled admin notice banner.
	 *
	 * Uses inline styles so no admin CSS is needed (works before our stylesheet
	 * is enqueued, and avoids style conflicts on other admin pages).
	 *
	 * @param string $type    One of: success | error | info | warning.
	 * @param string $title   Bold notice title.
	 * @param string $message Body text.
	 * @param string $icon    Dashicons class name (e.g. dashicons-yes-alt).
	 */
	private function render_notice( string $type, string $title, string $message, string $icon = 'dashicons-info-outline' ) {
		$colours = array(
			'success' => array(
				'bar'    => 'linear-gradient(to right,#16a34a 0%,#22c55e 100%)',
				'bg'     => '#f0fdf4',
				'circle' => '#dcfce7',
				'icon'   => '#16a34a',
			),
			'error'   => array(
				'bar'    => 'linear-gradient(to right,#dc2626 0%,#ef4444 100%)',
				'bg'     => '#fff8f8',
				'circle' => '#fee2e2',
				'icon'   => '#dc2626',
			),
			'info'    => array(
				'bar'    => 'linear-gradient(to right,#7c3aed 0%,#a855f7 100%)',
				'bg'     => '#faf5ff',
				'circle' => '#ede9fe',
				'icon'   => '#7c3aed',
			),
			'warning' => array(
				'bar'    => 'linear-gradient(to right,#d97706 0%,#f59e0b 100%)',
				'bg'     => '#fffbeb',
				'circle' => '#fef3c7',
				'icon'   => '#d97706',
			),
		);

		$c = $colours[ $type ] ?? $colours['info'];
		?>
		<div style="margin-block-start:16px;margin-block-end:0;margin-inline-start:2px;margin-inline-end:20px;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.08);border:none;" role="alert">
			<div style="background:<?php echo esc_attr( $c['bar'] ); ?>;block-size:4px;"></div>
			<div style="display:flex;align-items:center;gap:16px;padding-block:16px;padding-inline:20px;background:<?php echo esc_attr( $c['bg'] ); ?>;flex-wrap:wrap;">
				<span style="display:flex;align-items:center;justify-content:center;inline-size:42px;block-size:42px;border-radius:50%;background:<?php echo esc_attr( $c['circle'] ); ?>;flex-shrink:0;">
					<span class="dashicons <?php echo esc_attr( $icon ); ?>" style="color:<?php echo esc_attr( $c['icon'] ); ?>;font-size:22px;inline-size:22px;block-size:22px;margin:0;"></span>
				</span>
				<div style="flex:1;min-inline-size:160px;">
					<p style="margin:0 0 3px;font-size:14px;font-weight:700;color:#1d2327;"><?php echo esc_html( $title ); ?></p>
					<p style="margin:0;font-size:13px;color:#6b7280;line-height:1.5;"><?php echo esc_html( $message ); ?></p>
				</div>
				<button
					type="button"
					onclick="this.closest('[role=alert]').style.display='none'"
					aria-label="<?php esc_attr_e( 'Dismiss this notice', 'my-account-links' ); ?>"
					style="display:flex;align-items:center;justify-content:center;inline-size:30px;block-size:30px;background:transparent;border:none;border-radius:6px;cursor:pointer;color:#9ca3af;flex-shrink:0;padding:0;"
					onmouseover="this.style.background='rgba(0,0,0,0.06)';this.style.color='#374151'"
					onmouseout="this.style.background='transparent';this.style.color='#9ca3af'"
				>
					<span class="dashicons dashicons-no-alt" style="font-size:18px;inline-size:18px;block-size:18px;margin:0;"></span>
				</button>
			</div>
		</div>
		<?php
	}

	// -----------------------------------------------------------------------
	// Page rendering
	// -----------------------------------------------------------------------

	/**
	 * Render the full admin settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'my-account-links' ) );
		}

		// Fetch the canonical WooCommerce items and the saved config.
		$default_items = $this->plugin->get_default_wc_menu_items();
		$settings      = $this->plugin->get_settings();
		$saved_items   = $settings['items'] ?? array();

		// Build the display list:
		// – Start with saved items in their saved order.
		// – Append any WooCommerce items not yet in saved config.
		$display_items = array();

		foreach ( $saved_items as $slug => $config ) {
			// Only show items that still exist in WooCommerce.
			if ( isset( $default_items[ $slug ] ) ) {
				$display_items[ $slug ] = array(
					'default_label' => $default_items[ $slug ],
					'label'         => $config['label'] ?? '',
					'enabled'       => isset( $config['enabled'] ) ? (bool) $config['enabled'] : true,
				);
			}
		}

		// Append items newly added by WooCommerce or other plugins.
		foreach ( $default_items as $slug => $label ) {
			if ( ! array_key_exists( $slug, $display_items ) ) {
				$display_items[ $slug ] = array(
					'default_label' => $label,
					'label'         => '',
					'enabled'       => true,
				);
			}
		}
		?>
		<div class="wrap mj-wc-al-wrap">

			<!-- Page header -->
			<div class="mj-wc-al-header">
				<div class="mj-wc-al-header__inner">
					<div class="mj-wc-al-header__title-group">
						<h1 class="mj-wc-al-header__title">
							<?php esc_html_e( 'My Account Links', 'my-account-links' ); ?>
						</h1>
						<p class="mj-wc-al-header__subtitle">
							<?php esc_html_e( 'Control the navigation links shown in WooCommerce My Account pages.', 'my-account-links' ); ?>
						</p>
					</div>

					<!-- Reset all form -->
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mj-reset-form">
						<input type="hidden" name="action" value="mj_MY_Account_Links_reset">
						<?php wp_nonce_field( self::NONCE_ACTION_RESET, self::NONCE_NAME ); ?>
						<button type="submit" class="mj-wc-al-btn mj-wc-al-btn--ghost" id="mj-btn-reset-all">
							<span class="dashicons dashicons-image-rotate"></span>
							<?php esc_html_e( 'Reset All Defaults', 'my-account-links' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- Main settings form -->
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mj-settings-form">
				<input type="hidden" name="action" value="mj_MY_Account_Links_save">
				<?php wp_nonce_field( self::NONCE_ACTION_SAVE, self::NONCE_NAME ); ?>

				<div class="mj-wc-al-card">

					<!-- Card header / column labels -->
					<div class="mj-wc-al-table-header">
						<span class="mj-wc-al-col mj-wc-al-col--drag"></span>
						<span class="mj-wc-al-col mj-wc-al-col--enabled">
							<?php esc_html_e( 'Visible', 'my-account-links' ); ?>
						</span>
						<span class="mj-wc-al-col mj-wc-al-col--slug">
							<?php esc_html_e( 'Endpoint', 'my-account-links' ); ?>
						</span>
						<span class="mj-wc-al-col mj-wc-al-col--label">
							<?php esc_html_e( 'Custom Label', 'my-account-links' ); ?>
						</span>
						<span class="mj-wc-al-col mj-wc-al-col--default">
							<?php esc_html_e( 'WooCommerce Default', 'my-account-links' ); ?>
						</span>
						<span class="mj-wc-al-col mj-wc-al-col--actions">
							<?php esc_html_e( 'Actions', 'my-account-links' ); ?>
						</span>
					</div>

					<!-- Sortable rows -->
					<ul class="mj-wc-al-sortable" id="mj-sortable-list">
						<?php foreach ( $display_items as $slug => $item ) : ?>
							<?php $this->render_item_row( $slug, $item ); ?>
						<?php endforeach; ?>

						<?php if ( empty( $display_items ) ) : ?>
							<li class="mj-wc-al-empty">
								<span class="dashicons dashicons-info-outline"></span>
								<?php esc_html_e( 'No WooCommerce My Account menu items found. Make sure WooCommerce is fully configured.', 'my-account-links' ); ?>
							</li>
						<?php endif; ?>
					</ul>

					<!-- Hidden order input – populated by JS on submit -->
					<input type="hidden" name="mj_order[]" id="mj-order-input" value="">

				</div><!-- .mj-wc-al-card -->

				<!-- Sticky save bar -->
				<div class="mj-wc-al-save-bar">
					<div class="mj-wc-al-save-bar__inner">
						<span class="mj-wc-al-save-bar__hint dashicons dashicons-info-outline"></span>
						<span class="mj-wc-al-save-bar__text">
							<?php esc_html_e( 'Drag rows to reorder. Toggle the switch to show/hide items. Enter a custom label to rename.', 'my-account-links' ); ?>
						</span>
						<button type="submit" class="mj-wc-al-btn mj-wc-al-btn--primary">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'Save Settings', 'my-account-links' ); ?>
						</button>
					</div>
				</div>

			</form><!-- #mj-settings-form -->

		</div><!-- .mj-wc-al-wrap -->
		<?php
	}

	/**
	 * Render a single sortable item row.
	 *
	 * @param string $slug Endpoint slug.
	 * @param array  $item {
	 *     @type string $default_label WooCommerce default label.
	 *     @type string $label         Current custom label (may be empty).
	 *     @type bool   $enabled       Whether the item is currently enabled.
	 * }
	 */
	private function render_item_row( string $slug, array $item ) {
		$enabled       = (bool) $item['enabled'];
		$row_class     = $enabled ? 'mj-wc-al-item' : 'mj-wc-al-item mj-wc-al-item--disabled';
		?>
		<li class="<?php echo esc_attr( $row_class ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>">

			<!-- Drag handle -->
			<span class="mj-wc-al-col mj-wc-al-col--drag mj-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'my-account-links' ); ?>">
				<span class="dashicons dashicons-move"></span>
			</span>

			<!-- Enable / disable toggle -->
			<span class="mj-wc-al-col mj-wc-al-col--enabled">
				<label class="mj-toggle" aria-label="<?php esc_attr_e( 'Enable item', 'my-account-links' ); ?>">
					<input
						type="checkbox"
						name="mj_items[<?php echo esc_attr( $slug ); ?>][enabled]"
						value="1"
						class="mj-toggle__input"
						<?php checked( $enabled, true ); ?>
					>
					<span class="mj-toggle__track">
						<span class="mj-toggle__thumb"></span>
					</span>
				</label>
			</span>

			<!-- Endpoint slug (read-only) -->
			<span class="mj-wc-al-col mj-wc-al-col--slug" title="<?php echo esc_attr( urldecode( $slug ) ) ?>">
				<code class="mj-slug-badge"><?php echo esc_html( urldecode( $slug ) ); ?></code>
			</span>

			<!-- Custom label input -->
			<span class="mj-wc-al-col mj-wc-al-col--label">
				<input
					type="text"
					name="mj_items[<?php echo esc_attr( $slug ); ?>][label]"
					value="<?php echo esc_attr( $item['label'] ?? '' ); ?>"
					class="mj-label-input"
					placeholder="<?php echo esc_html( $item['default_label'] ?? $slug ); ?>"
					aria-label="<?php esc_attr_e( 'Custom label', 'my-account-links' ); ?>"
				>
			</span>

			<!-- WooCommerce default label (read-only) -->
			<span class="mj-wc-al-col mj-wc-al-col--default">
				<span class="mj-default-label"><?php echo esc_html( $item['default_label'] ?? $slug ); ?></span>
			</span>

			<!-- Reset individual item label -->
			<span class="mj-wc-al-col mj-wc-al-col--actions">
				<button
					type="button"
					class="mj-btn-reset-item button button-secondary"
					data-default="<?php echo esc_html( $item['default_label'] ?? $slug ); ?>"
					title="<?php esc_attr_e( 'Reset to WooCommerce default label', 'my-account-links' ); ?>"
				>
					<span class="dashicons dashicons-image-rotate"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Reset label', 'my-account-links' ); ?></span>
				</button>
			</span>

		</li>
		<?php
	}
}
