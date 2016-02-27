<?php

namespace Community_Translator;

require_once plugin_dir_path( __FILE__ ) . 'class-text-translation.php';
require_once plugin_dir_path( __FILE__ ) . 'class-singleton.php';

class Community_Translator extends Singleton {

	const STYLE_HANDLE = 'community-translator';

	const SCRIPT_HANDLE = self::STYLE_HANDLE;

	private $strings_used_on_page = array();

	protected function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'gettext', array( $this, 'catch_gettext' ), 10, 3 );
		add_filter( 'gettext_with_context', array( $this, 'catch_gettext_with_context' ), 10, 4 );
		add_action( 'wp_footer', array( $this, 'jumpstart' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'jumpstart' ) );
	}

	function init() {
		wp_register_style( self::STYLE_HANDLE, plugins_url( 'community-translator.css', COMMUNITY_TRANSLATOR_FILE ) );
		wp_register_script( self::SCRIPT_HANDLE, plugins_url( 'community-translator.js', COMMUNITY_TRANSLATOR_FILE ), array( 'jquery' ) );
	}


	function enqueue() {
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );
	}


	function catch_gettext( $translated_text, $original_text, $domain ) {
		$this->strings_used_on_page["$original_text#$domain"] = new Text_Translation( $original_text, $translated_text, $domain );

		return $translated_text;
	}


	function catch_gettext_with_context( $translated_text, $original_text, $context, $domain ) {
		$this->strings_used_on_page["$original_text#$domain#$context"] = new Text_Translation( $original_text, $translated_text, $domain, $context );

		return $translated_text;
	}

	function jumpstart() {

		$locale = \get_locale();

		$language = 'Čeština';

		$url = "https://translate.wordpress.org";

		$project = "wp,wp-plugins/akismet";

		$strings_used_on_page = apply_filters( 'community-translator-strings-used-on-page', $this->strings_used_on_page );

		$strings_used_on_page_js = array();

		if ( false === empty( $strings_used_on_page ) && true === is_array( $strings_used_on_page ) ) {
			foreach ( $this->strings_used_on_page as $string ) {
				$strings_used_on_page_js = array_merge( $strings_used_on_page_js, $string->get_jumpstart_format() );
			}
		}

		printf( $this->jumpstart_template(), wp_json_encode( $strings_used_on_page_js ), wp_json_encode( $locale ), wp_json_encode( $language ), wp_json_encode( esc_url_raw( $url ) ), wp_json_encode( $project ) );
	}

	function jumpstart_template() {
		$template = '
<script type="text/javascript">
	translatorJumpstart = {
			stringsUsedOnPage: %1$s,
		localeCode: %2$s,
		languageNames: %3$s,
		pluralForms: "nplurals=2; plural=(n > 1)",
		glotPress: {
		url: %4$s,
			project: %5$s
	}
	};
	communityTranslator.load();
</script>';

		return $template;
	}

}
