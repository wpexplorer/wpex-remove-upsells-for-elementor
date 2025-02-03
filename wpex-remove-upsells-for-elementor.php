<?php
/**
 * Plugin Name:       WPEX Remove Upsells for Elementor
 * Plugin URI:        https://github.com/wpexplorer/wpex-remove-upsells-for-elementor
 * Description:       Add-on plugin for Elementor to remove upsells.
 * Version:           1.0
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Author:            WPExplorer
 * Author URI:        https://www.wpexplorer.com/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wpex-remove-upsells-for-elementor
 * Update URI:        false
 */

/*
WPEX Remove Upsells for Elementor is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

WPEX Remove Upsells for Elementor is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with WPEX Remove Upsells for Elementor. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
*/

/**
 * Prevent direct access to this file.
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPEX_Remove_Upsells_For_Elementor' ) ) {

  /**
   * Plugin class.
   */
	class WPEX_Remove_Upsells_For_Elementor {

		/**
		 * Constructor.
		 */
		public function __construct() {
			if ( did_action( 'elementor/loaded' ) ) {
				$this->register_actions();
			} else {
				add_action( 'elementor/loaded', [ $this, 'register_actions' ] );
			}
		}

		/**
		 * Register our main class actions.
		 */
		public function register_actions(): void {
			if ( is_callable( 'Elementor\Utils::has_pro' ) && Elementor\Utils::has_pro() ) {
				return; // bail early if we are using Elementor Pro.
			}

			add_action( 'elementor/admin/menu/after_register', [ $this, 'remove_admin_pages' ], PHP_INT_MAX, 2 );
			add_action( 'elementor/admin_top_bar/before_enqueue_scripts', [ $this, 'admin_top_bar_css' ] );
			add_filter( 'elementor/frontend/admin_bar/settings', [ $this, 'modify_admin_bar' ], PHP_INT_MAX );
			add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'editor_css' ] );
			add_action( 'wp_dashboard_setup', [ $this, 'remove_dashboard_widget' ], PHP_INT_MAX );
		}

		/**
		 * Remove admin pages.
		 */
		public function remove_admin_pages( $menu_manager, $hooks ): void {
			$pages_to_remove = [];
			$subpages_to_remove = [];
			if ( is_callable( [ $menu_manager, 'get_all' ] ) ) {
				foreach ( (array) $menu_manager->get_all() as $item_slug => $item ) {
					if ( isset( $hooks[ $item_slug ] )
						&& is_object( $item )
						&& ( is_subclass_of( $item, 'Elementor\Modules\Promotions\AdminMenuItems\Base_Promotion_Item' )
							|| is_subclass_of( $item, 'Elementor\Modules\Promotions\AdminMenuItems\Base_Promotion_Template' )
							|| 'elementor-apps' === $item_slug
						)
					) {
						$parent_slug = is_callable( [ $item, 'get_parent_slug' ] ) ? $item->get_parent_slug() : '';
						if ( ! empty( $parent_slug ) ) {
							$subpages_to_remove[] = [ $parent_slug, $item_slug ];
						} else {
							$pages_to_remove[] = $hooks[ $item_slug ];
						}
					}
				}
			}
			foreach ( $pages_to_remove as $menu_slug ) {
				remove_menu_page( $menu_slug );
			}
			foreach ( $subpages_to_remove as $subpage ) {
				remove_submenu_page( $subpage[0], $subpage[1] );
			}
			remove_submenu_page( 'elementor', 'go_knowledge_base_site' );
			remove_submenu_page( 'elementor', 'go_elementor_pro' );
			if ( ! isset( $_GET['page'] ) || 'elementor-app' !== $_GET['page'] ) {
				remove_submenu_page( 'edit.php?post_type=elementor_library', 'elementor-app' );
			}
		}

		/**
		 * Add inline CSS to modify the Elementor admin top bar.
		 */
		public function admin_top_bar_css(): void {
			$target_icon_classes = [
				'.eicon-integration', // Add-ons
				'.eicon-upgrade-crown', // Upgrade now
			];
			wp_add_inline_style(
				'elementor-admin-top-bar',
				'.e-admin-top-bar__bar-button:has(' . implode( ',', $target_icon_classes ) . '){display:none!important;}'
			);
		}

		/**
		 * Modify the admin bar links.
		 */
		public function modify_admin_bar( $admin_bar_config ) {
			if ( isset( $admin_bar_config['elementor_edit_page']['children'] )
				&& is_array( $admin_bar_config['elementor_edit_page']['children'] )
			) {
				foreach ( $admin_bar_config['elementor_edit_page']['children'] as $k => $item ) {
					if ( isset( $item['id'] ) && 'elementor_app_site_editor' === $item['id'] ) {
						unset( $admin_bar_config['elementor_edit_page']['children'][ $k ] );
						break;
					}
				}
			}
			return $admin_bar_config;
		}

		/**
		 * Hide elements in the editor.
		 */
		public function editor_css(): void {
			wp_add_inline_style(
				'elementor-editor',
				'.e-notice-bar,.elementor-element-wrapper.elementor-element--promotion,#elementor-panel-category-pro-elements,#elementor-panel-category-theme-elements,#elementor-panel-category-theme-elements-single,#elementor-panel-category-woocommerce-elements,#elementor-panel-get-pro-elements,#elementor-panel-get-pro-elements-sticky{display:none!important;}'
			);
		}

		/**
		 * Remove dashboard widget.
		 */
		public function remove_dashboard_widget(): void {
			remove_meta_box( 'e-dashboard-overview', 'dashboard', 'normal' );
		}

	}

	new WPEX_Remove_Upsells_For_Elementor;
}
