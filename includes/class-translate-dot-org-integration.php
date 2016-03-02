<?php

namespace Community_Translator;

class Translate_Dot_Org_Integration extends Singleton {

	private $cookies;

	private function __construct() {
		add_action( 'wp_ajax_community_translator_send_to_dot_org', array( $this, 'send_to_dot_org' ) );
	}

	public function send_to_dot_org() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'send-to-dot-org' ) ) {
			wp_send_json_error( esc_html__( 'Cheating?', 'community-translator' ) );
		}

		if ( false === isset( $_POST['translation_id'] ) ) {
			wp_send_json_error( esc_html__( 'Mising translation ID', 'community-translator' ) );
		}

		$translation_id = intval( $_POST['translation_id'] );

		//@todo: non-hardcoded project url
		$project_url = 'https://translate.wordpress.org/projects/wp-plugins/jetpack/dev/cs/default';

		$args = array(
			'headers' => array(
				'', //cookies
			),
			'body' => '', //urlencoded form data
		);

		$response = wp_remote_post( esc_url_row( $project_url ), $args );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response ); //sends the WP_Error object
		} else {
			wp_send_json_success( $response ); //translate.wordpress.org succeess
		}
	}

	public function authenticate( $username ) {

		$args = array(
			'headers' => array(),
			'body' => '', //urlencoded form data
		);

		$response = wp_remote_post( esc_url_raw( $authentication_url ), $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( 200 !== intval( wp_remote_retrieve_response_code( $response ) ) ) {
			return false;
		}

		$this->cookies = $response['cookies'];

		return true;

	}

}