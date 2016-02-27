<?php

namespace Community_Translator;

require_once COMMUNITY_TRANSLATOR_INC . 'class-singleton.php';

class Community_Translator_Admin extends Singleton {

	private $page;

	private $page_slug = 'community-translator-settings';

	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_init', array( $this, 'register_page_settings' ) );
	}

	public function register_admin_page() {
		$this->page = add_options_page(
			__( 'Community Translator', 'community-translator' ),
			__( 'Community Translator', 'community-translator' ),
			'manage_options', //for admins only for now
			$this->page_slug,
			array( $this, 'settings_page' )
		);
	}

	public function register_page_settings() {

		add_settings_section(
			'commuity-translator-glotpress-section',
			__( 'GlotPress settings', 'community-translator' ),
			null, //No description
			$this->page
		);

		$this->add_settings_field( 'url', __( 'GlotPress URL', 'community-translator' ), 'text_field' , 'esc_url_raw');
		$this->add_settings_field( 'locale', __( 'GlotPress Locale', 'community-translator' ) );
		$this->add_settings_field( 'language', __( 'GlotPress Language', 'community-translator' ) );
	}

	private function add_settings_field( $option, $label, $callback = 'text_field', $sanitize = 'sanitize_text_field' ) {
		add_settings_field(
			'community_translator_glotpress_' . $option,
			sprintf( '<label for="%s">%s</label>',
				'community_translator_glotpress_' . $option,
				esc_html( $label )
			),
			array( $this, $callback ),
			$this->page,
			'commuity-translator-glotpress-section',
			array(
				'option' => 'community_translator_glotpress_' . $option,
			)
		);
		register_setting( $this->page, 'community_translator_glotpress_' . $option, $sanitize );
	}

	public function text_field( $args ) {
		$option = $args['option'];
		echo sprintf( '<input type="text" id="%s" name="%s" value="%s"/>',
			esc_attr( $option ),
			esc_attr( $option ),
			esc_attr( get_option( $option ) )
		);
	}

	public function settings_page() {
		printf( '<div class="wrap"><h2>%s</h2><form method="post" action="options.php">',
			esc_html__( 'Community Translator', 'community-translator' )
		);
		settings_fields( $this->page );
		do_settings_sections( $this->page );
		submit_button();
		echo '</form></div>';
	}
}