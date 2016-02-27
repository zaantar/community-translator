<?php

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
