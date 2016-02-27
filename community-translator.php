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

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'COMMUNITY_TRANSLATOR_FILE', __FILE__  );
define( 'COMMUNITY_TRANSLATOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'COMMUNITY_TRANSLATOR_URL', plugin_dir_url( __FILE__ ) );

require plugin_dir_path( __FILE__ ) . 'includes/class-community-translator.php';

Community_Translator::get_instance();
