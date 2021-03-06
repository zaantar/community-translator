/**
 * Community Translation core module
 */
'use strict';

/**
 * External dependencies
 */
var debug = require( 'debug' )( 'community-translator' );

/**
 * Internal dependencies
 */
var TranslationPair = require( './translation-pair' ),
	Walker = require( './walker' ),
	Locale = require( './locale' ),
	Popover = require( './popover' ),
	GlotPress = require ( './glotpress' ),
	WebUIPopover = require('./jquery.webui-popover.js' );

/**
 * Local variables
 */
var debounceTimeout,
	currentlyWalkingTheDom = false,
	loadCSS, loadData, registerContentChangedCallback, registerDomChangedCallback,
	registerPopoverHandlers, findNewTranslatableTexts,
	glotPress, currentUserId, walker,
	baseUrl = 'http://vvv.dev/community-translator/',
	translationData = {
		currentUserId: false,
		localeCode: 'en',
		languageName: 'English',
		pluralForms: 'nplurals=2; plural=(n != 1)',
		contentChangedCallback: function() {},
		glotPress: {
			url: 'http://glotpress.dev',
			project: 'test'
		}
	},
	translationUpdateCallbacks = [];

module.exports = {

	load: function() {
		if ( 'undefined' === typeof window.translatorJumpstart ) {
			return false;
		}
		loadCSS();
		loadData( window.translatorJumpstart );

		registerPopoverHandlers();
		registerContentChangedCallback();
		findNewTranslatableTexts();
	},

	unload: function() {
		if ( debounceTimeout ) {
			clearTimeout( debounceTimeout );
		}
		if ( 'object' === typeof window.translatorJumpstart ) {
			window.translatorJumpstart.contentChangedCallback = function() {};
		}
		unRegisterPopoverHandlers();
		removeCssClasses();
	},

	registerTranslatedCallback: function( callback ) {
		translationUpdateCallbacks.push( callback );
	}

};

function notifyTranslated( newTranslationPair ) {
	debug( 'Notifying string translated', newTranslationPair.serialize() );
	translationUpdateCallbacks.forEach( function( hook ) {
		hook( newTranslationPair.serialize() );
	} );
}

loadCSS = function() {
	var s = document.createElement( 'link' );
	s.setAttribute( 'rel', 'stylesheet' );
	s.setAttribute( 'type', 'text/css' );
	s.setAttribute( 'href', baseUrl + 'community-translator.css' );
	document.getElementsByTagName( 'head' )[ 0 ].appendChild( s );

	var t = document.createElement( 'link' );
	t.setAttribute( 'rel', 'stylesheet' );
	t.setAttribute( 'type', 'text/css' );
	t.setAttribute( 'href', 'https://s1.wp.com/i/noticons/noticons.css' );
	document.getElementsByTagName( 'head' )[ 0 ].appendChild( t );

	jQuery( 'iframe' ).addClass( 'translator-untranslatable' );
};

loadData = function( translationDataFromJumpstart ) {
	if (
			typeof translationDataFromJumpstart === 'object' &&
			typeof translationDataFromJumpstart.localeCode === 'string'
	) {
		translationData = translationDataFromJumpstart;
	}

	translationData.locale = new Locale( translationData.localeCode, translationData.languageName, translationData.pluralForms );
	currentUserId = translationData.currentUserId;

	glotPress = new GlotPress( translationData.locale );
	if ( 'undefined' !== typeof translationData.glotPress ) {
		glotPress.loadSettings( translationData.glotPress );
	} else {
		debug( 'Missing GlotPress settings' );
	}

	TranslationPair.setTranslationData( translationData );
	walker = new Walker( TranslationPair, jQuery, document );
};

registerContentChangedCallback = function() {
	if ( 'object' === typeof window.translatorJumpstart ) {
		debug( 'Registering translator contentChangedCallback' );
		window.translatorJumpstart.contentChangedCallback = function() {
			if ( debounceTimeout ) {
				clearTimeout( debounceTimeout );
			}
			debounceTimeout = setTimeout( findNewTranslatableTexts, 250 );
		};

		if ( typeof window.translatorJumpstart.stringsUsedOnPage === 'object' ) {
			registerDomChangedCallback();
		}

	}
};

// This is a not very elegant but quite efficient way to check if the DOM has changed
// after the initial walking of the DOM
registerDomChangedCallback = function() {
	var checksRemaining = 10,
		lastBodySize = document.body.innerHTML.length,
		checkBodySize = function() {
			var bodySize;

			if ( --checksRemaining <= 0 ) {
				return;
			}

			bodySize = document.body.innerHTML.length;
			if ( lastBodySize !== bodySize ) {
				lastBodySize = bodySize;

				if ( debounceTimeout ) {
					clearTimeout( debounceTimeout );
				}
				debounceTimeout = setTimeout( findNewTranslatableTexts, 1700 );
			}
			setTimeout( checkBodySize, 1500 );
		};

	setTimeout( checkBodySize, 1500 );
};

registerPopoverHandlers = function() {

	jQuery( document ).on( 'keyup', 'textarea.translation', function() {
		var textareasWithInput,
			$form = jQuery( this ).parents( 'form.community-translator' ),
			$allTextareas = $form.find( 'textarea' ),
			$button = $form.find( 'button' );

		textareasWithInput = $allTextareas.filter( function() {
			return this.value.length;
		} );

		// disable if no textarea has an input
		$button.prop( 'disabled', 0 === textareasWithInput.length );
	} );

	jQuery( document ).on( 'submit', 'form.community-translator', function() {
		var $form = jQuery( this ),
			$node = jQuery( '.' + $form.data( 'nodes' ) ),
			val = $form.find( 'textarea' ).val(),
			translationPair = $form.data( 'translationPair' ),
			newTranslationStringsFromForm = $form.find( 'textarea' ).map( function() {
				return jQuery( this ).val();
			} ).get();

		function notEmpty( string ) {
			return string.trim().length > 0;
		}

		if ( ! newTranslationStringsFromForm.every( notEmpty ) ) {
			return false;
		}

		// We're optimistic
		// TODO: reset on failure.
		// TODO: use Jed to insert with properly replaced variables
		$node.addClass( 'translator-user-translated' ).removeClass( 'translator-untranslated' );

		$form.closest( '.webui-popover' ).hide();

		// Reporting to GlotPress
		jQuery
			.when( translationPair.getOriginal().getId() )
			.done( function( originalId ) {
				var submittedTranslations = jQuery.makeArray( newTranslationStringsFromForm ),
					translation = {};

				translation[ originalId ] = submittedTranslations;
				glotPress.submitTranslation( translation ).done( function( data ) {


					if ( typeof data[ originalId ] === 'undefined' ) {
						return;
					}

					translationPair.updateAllTranslations( data[ originalId ], currentUserId );
					makeTranslatable( translationPair, $node );
					notifyTranslated( translationPair );

				} ).fail( function() {
					debug( 'Submitting new translation failed', translation );
				} );
			} ).fail( function() {
			debug( 'Original cannot be found in GlotPress' );
		} );

		return false;
	} );

	jQuery( document ).on( 'submit', 'form.ct-existing-translation', function() {
		var enclosingNode = jQuery( this ), popover,
			translationPair = enclosingNode.data( 'translationPair' );

		if ( 'object' !== typeof translationPair ) {
			debug( 'could not find translation for node', enclosingNode );
			return false;
		}

		popover = new Popover( translationPair, translationData.locale, glotPress );
		enclosingNode.parent().empty().append( popover.getTranslationHtml() );

		return false;
	} );
};

function removeCssClasses() {
	var classesToDrop = [
		'translator-checked',
		'translator-untranslated',
		'translator-translated',
		'translator-user-translated',
		'translator-untranslatable',
		'translator-dont-translate' ];

	jQuery( '.' + classesToDrop.join( ', .' ) ).removeClass( classesToDrop.join( ' ' ) );
}

function unRegisterPopoverHandlers() {
	jQuery( document ).off( 'submit', 'form.community-translator' );
	jQuery( '.translator-translatable' ).webuiPopover( 'destroy' );
}

function makeUntranslatable( $node ) {
	debug( 'makeUntranslatable:', $node );
	$node.removeClass( 'translator-untranslated translator-translated translator-translatable translator-checking' );
	$node.addClass( 'translator-dont-translate' );
}

function makeTranslatable( translationPair, node ) {
	translationPair.createPopover( node, glotPress );
	node.removeClass( 'translator-checking' ).addClass( 'translator-translatable' );
	if ( translationPair.isFullyTranslated() ) {
		if ( translationPair.isTranslationWaiting() ) {
			node.removeClass( 'translator-translated' ).addClass( 'translator-user-translated' );
		} else {
			node.removeClass( 'translator-user-translated' ).addClass( 'translator-translated' );
		}
	} else {
		node.addClass( 'translator-untranslated' );
	}
}

findNewTranslatableTexts = function() {
	if ( currentlyWalkingTheDom ) {
		if ( debounceTimeout ) {
			clearTimeout( debounceTimeout );
		}
		debounceTimeout = setTimeout( findNewTranslatableTexts, 1500 );
		return;
	}

	currentlyWalkingTheDom = true;

	debug( 'Searching for translatable texts' );
	walker.walkTextNodes( document.body, function( translationPair, enclosingNode ) {
		enclosingNode.addClass( 'translator-checking' );

		translationPair.fetchOriginalAndTranslations( glotPress, currentUserId )
			.fail(
				// Failure indicates that the string is not in GlotPress yet
				makeUntranslatable.bind( null, enclosingNode )
			)
			.done(
				makeTranslatable.bind( null, translationPair, enclosingNode )
		);

	}, function() {
		currentlyWalkingTheDom = false;
	} );
};
