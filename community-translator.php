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

namespace Community_Translator;

class Text_Translation {

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

	public function get_jumpstart_format() {

		$formatted = array( $this->translation => array(
			$this->original,
			array( $this->context ),
		) );

		return $formatted;
	}

}

require plugin_dir_path( __FILE__ ) . 'includes/class-community-translator.php';

Community_Translator::get_instance();
