<?php
/**
 * Settings screen (Settings > Media dates).
 *
 * @package S2J_MLDC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin settings via Settings API.
 */
class S2J_MLDC_Settings {

	/**
	 * Option name.
	 */
	public const OPTION_MISMATCH_NOTICE = 's2j_mldc_mismatch_notice';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Whether mismatch notice should display (default true).
	 *
	 * @return bool
	 */
	public static function should_show_mismatch_notice(): bool {
		$value = get_option( self::OPTION_MISMATCH_NOTICE, true );
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Add options page.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_options_page(
			__( 'Media dates', 's2j-media-library-date-corrector' ),
			__( 'Media dates', 's2j-media-library-date-corrector' ),
			'manage_options',
			's2j-mldc-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register setting field.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			's2j_mldc_settings',
			self::OPTION_MISMATCH_NOTICE,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_notice_option' ),
				'default'           => true,
			)
		);

		add_settings_section(
			's2j_mldc_main',
			__( 'Media library notices', 's2j-media-library-date-corrector' ),
			'__return_false',
			's2j-mldc-settings'
		);

		add_settings_field(
			's2j_mldc_mismatch_notice_field',
			__( 'Mismatch notice', 's2j-media-library-date-corrector' ),
			array( $this, 'render_notice_field' ),
			's2j-mldc-settings',
			's2j_mldc_main'
		);
	}

	/**
	 * Sanitize checkbox to boolean.
	 *
	 * @param mixed $value Submitted value.
	 * @return bool
	 */
	public function sanitize_notice_option( $value ): bool {
		if ( is_array( $value ) ) {
			$value = end( $value );
		}
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Render checkbox field.
	 *
	 * @return void
	 */
	public function render_notice_field(): void {
		$checked = self::should_show_mismatch_notice();
		printf(
			'<input type="hidden" name="%1$s" value="0" />',
			esc_attr( self::OPTION_MISMATCH_NOTICE )
		);
		printf(
			'<label><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s</label><p class="description">%4$s</p>',
			esc_attr( self::OPTION_MISMATCH_NOTICE ),
			checked( $checked, true, false ),
			esc_html__( 'Show mismatch notice on the media library list', 's2j-media-library-date-corrector' ),
			esc_html__(
				'When the current list page has at least one mismatch, show a notice at the top. Correction actions are not affected.',
				's2j-media-library-date-corrector'
			)
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Media dates', 's2j-media-library-date-corrector' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 's2j_mldc_settings' );
				do_settings_sections( 's2j-mldc-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
