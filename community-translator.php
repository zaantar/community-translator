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

require plugin_dir_path( __FILE__ ) . 'includes/class-community-translator.php';

Community_Translator::get_instance();
