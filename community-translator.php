<?php
/*
Plugin Name: Community Translator
Plugin URI: https://github.com/zaantar/community-translator
Description:
Version: 0
Author:
Author URI:
Text Domain: community-translator
Domain Path: /languages
*/

namespace CommunityTranslator;

class CommunityTranslator {

	const STYLE_HANDLE = 'community-translator';

	const SCRIPT_HANDLE = self::STYLE_HANDLE;

	private $strings_used_on_page = array();

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'gettext', array( $this, 'catch_gettext' ) );
		add_filter( 'gettext_with_context', array( $this, 'catch_gettext_with_context' ) );
		add_action( 'wp_footer', array( $this, 'jumpstart' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'jumpstart' ) );
	}


	function init() {
		wp_register_style( self::STYLE_HANDLE, plugins_url( 'community-translator.css' ) );
		wp_register_script( self::SCRIPT_HANDLE, plugins_url( 'community-translator.js' ) );
	}


	function enqueue() {
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );
	}


	function catch_gettext( $translated_text, $original_text, $domain ) {
		$this->strings_used_on_page["$original_text#$domain"] = new TextTranslation( $original_text, $translated_text, $domain );
	}


	function catch_gettext_with_context( $translated_text, $original_text, $context, $domain ) {
		$this->strings_used_on_page["$original_text#$domain#$context"] = new TextTranslation( $original_text, $translated_text, $domain, $context );
	}


	function jumpstart() {

	}

}


class TextTranslation {

	private $original;
	private $translation;
	private $domain;
	private $context;

	public function __construct( $original, $translation, $domain, $context = null ) {
		$this->original = $original;
		$this->translation = $translation;
		$this->domain = $domain;
		$this->context = $context;
	}


}
