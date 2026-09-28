<?php
/**
 * Bootstrap for the AI Reporting Abilities (WordPress Abilities API / MCP).
 *
 * Only ever loaded when PHP >= 7.4 and vendor/autoload.php exists.
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wcap-ability-utils.php';
require_once __DIR__ . '/class-wcap-ability-top-abandoned-products.php';
require_once __DIR__ . '/class-wcap-ability-recovered-revenue.php';
require_once __DIR__ . '/class-wcap-ability-recovery-rate-ranking.php';
require_once __DIR__ . '/class-wcap-ability-template-performance.php';

/**
 * WCAP_Abilities_Bootstrap class.
 */
class WCAP_Abilities_Bootstrap {

	/**
	 * Option name for the merchant opt-in setting. Defaults to disabled.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'wcap_enable_ai_abilities';

	/**
	 * Ability category slug all four abilities.
	 *
	 * @var string
	 */
	const CATEGORY = 'abandoned-cart-pro';

	/**
	 * Constructor: no-op unless the Abilities API and MCP Adapter are available and the merchant has opted in.
	 *
	 * @since 10.6.0
	 */
	public function __construct() {

		if ( ! function_exists( 'wp_register_ability' ) || ! class_exists( 'WP\MCP\Core\McpAdapter' ) ) {
			return;
		}

		if ( ! self::is_enabled() ) {
			return;
		}

		\WP\MCP\Core\McpAdapter::instance();

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the ability category.
	 *
	 * @return void
	 * @since 10.6.0
	 */
	public function register_category() {

		if ( ! self::is_enabled() ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Abandoned Cart Pro', 'woocommerce-ac' ),
				'description' => __( 'Read-only abandoned-cart reporting data.', 'woocommerce-ac' ),
			)
		);
	}

	/**
	 * Whether the merchant has AI Reporting Abilities enabled. Defaults OFF.
	 *
	 * @return bool
	 * @since 10.6.0
	 */
	public static function is_enabled() {
		return 'on' === get_option( self::OPTION_NAME, '' );
	}

	/**
	 * Register all four abilities on wp_abilities_api_init.
	 *
	 * Registration itself is skipped entirely when disabled — not just a
	 * permission_callback failure — so the abilities don't appear in MCP
	 * discovery at all when the merchant has turned the feature off.
	 *
	 * @return void
	 * @since 10.6.0
	 */
	public function register_abilities() {

		if ( ! self::is_enabled() ) {
			return;
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		WCAP_Ability_Top_Abandoned_Products::register();
		WCAP_Ability_Recovered_Revenue::register();
		WCAP_Ability_Recovery_Rate_Ranking::register();
		WCAP_Ability_Template_Performance::register();
	}
}

new WCAP_Abilities_Bootstrap();
