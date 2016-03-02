<?php

namespace Community_Translator;

class Translate_Dot_Org_Scraper extends Singleton {

	public $meta_key = 'community_translator_original_translation_id';


	//@todo: extend for plurals
	public function scrape() {
		$doc = new \DOMDocument();
		@$doc->loadHTML( file_get_contents( COMMUNITY_TRANSLATOR_PATH . 'test/glotpress-export.html' ) );

		$xpath        = new \DOMXpath( $doc );
		$translations = $xpath->query( '//tr[contains(@class, "editor")]' );

		$po = array();
		if ( $translations ) {
			foreach ( $translations as $translation ) {

				$row = $translation->getAttribute( 'row' );
				list( $original_translation_id, $current_translation_id ) = explode( '-', $row );

				$original = $xpath->query( './/p[contains(@class, "original")]', $translation );
				if ( $original ) {
					$original_string = $original->item( 0 )->nodeValue;
				}

				$current = $xpath->query( './/textarea[contains(@class, "foreign-text")]', $translation );
				if ( $current ) {
					$current_string = $current->item( 0 )->nodeValue;
				}

				$original_references = array();
				$refs                = $xpath->query( './/ul[contains(@class, "refs")]/li/a', $translation );
				if ( $refs ) {
					foreach ( $refs as $ref ) {
						$original_references[] = $ref->nodeValue;
					}
				}
				$original_references = join( ' ', $original_references );


				$po[] = array(
					'original_translation_id' => $original_translation_id,
					'original_references' => $original_references,
					'original_string' => $original_string,
					'current_string' => $current_string,
				);

			}
		}

		return $po;
	}

	public function convert_html_to_po( $po_array = false ) {
		if ( ! $po_array ) {
			$po_array = $this->scrape();
		}
		$po = array();

		foreach ( $po_array as $p ) {

			$po[] = sprintf( "\n# glotpress translation id: %s\n#: %s\nmsgid \"%s\"\nmsgstr \"%s\"\n",
				$p['original_translation_id'],
				$p['original_references'],
				$p['original_string'],
				$p['current_string']
			);

		}

		return join( '', $po );

	}

	public function save_original_ids( $po_array = false ) {
		if ( ! $po_array ) {
			$po_array = $this->scrape();
		}

		//@todo: figure out project_id
		$project_id = 1;

		foreach( $po_array as $po ) {
			$this->set_original_translation_id_meta( $po, $project_id );
		}
	}

	public function set_original_translation_id_meta( $po, $project_id ) {

		$local_original_id = $this->get_local_original_id_by_po( $po, $project_id );

		if ( $local_original_id && 0 !== intval( $po['original_translation_id'] ) ) {
			return \gp_update_meta( $local_original_id, $this->meta_key, intval( $po['original_translation_id'] ), 'thing' );
		}

		return false;
	}

	public function get_local_original_id_by_po( $po, $project_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->gp_originals} WHERE `project_id` = %d AND `references` = %s LIMIT 1;",
			intval( $project_id ),
			sanitize_text_field( $po['original_references'] )
		);
		$row = $wpdb->get_row( $query );

		if ( ! $row ) {
			return false;
		}

		//@todo: extend for plural
		if ( $row->singular !== $po['original_string'] ) {
			return false;
		}

		return intval( $row->id );
	}

}
