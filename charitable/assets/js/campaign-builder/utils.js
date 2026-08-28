'use strict';

// eslint-disable-next-line no-unused-vars
const CharitableUtils = window.CharitableUtils || ( function( document, window, $ ) {

	/**
	 * Characters removed from user-entered headlines, labels and titles.
	 *
	 * This is a denylist on purpose. The previous allowlist only permitted ASCII
	 * word characters plus the Latin Extended blocks, so every non-Latin script
	 * (Japanese, Chinese, Korean, Cyrillic, Greek, Arabic, Hebrew, Thai,
	 * Devanagari) was silently deleted as the user typed. An allowlist widened to
	 * \p{L}\p{N}\p{M}\p{Zs} still drops the punctuation those languages actually
	 * use, so bare kanji would survive while "今すぐ寄付！" quietly lost its
	 * fullwidth "！" and "寄付する。" lost its kuten.
	 *
	 * Escaping is handled server side -- generate_text() strips quotes and runs
	 * esc_html() before echoing into a value attribute, and every output path
	 * uses esc_html() -- so this is belt and braces. It only needs to remove the
	 * characters that could break an HTML context, plus control characters.
	 *
	 * Deliberately NOT stripped, despite looking like candidates:
	 *
	 * - Bidi marks and isolates (U+200E/U+200F, U+202A-U+202E, U+2066-U+2069) and
	 *   zero-width joiners (U+200B-U+200D, U+FEFF). Removing these would break the
	 *   very languages this fix exists to support: RLM/LRM are load-bearing in
	 *   Arabic and Hebrew, and ZWNJ/ZWJ are required in Persian, Arabic and Indic
	 *   scripts and in emoji sequences. They carry a theoretical display-spoofing
	 *   risk, but the author already needs campaign-edit capability, so spoofing
	 *   their own button label grants them nothing.
	 * - HTML entity text such as "&lt;script&gt;". innerHTML decodes entities into
	 *   text nodes rather than re-parsing them as markup, so they cannot create an
	 *   element. Verified.
	 *
	 * @since 1.8.12.3
	 *
	 * @type {RegExp}
	 */
	const UNSAFE_TEXT_CHARS = /[<>`]|[\u0000-\u001F\u007F-\u009F]/g; // eslint-disable-line no-control-regex

	/**
	 * Strip the unsafe characters from a value, tolerating non-string input.
	 *
	 * Non-strings return '' rather than being coerced. The previous implementation
	 * called .replace() directly and so threw a TypeError on null, undefined and
	 * numbers alike; coercing with String() instead would put the literal text
	 * "null" or "[object Object]" into a campaign field, which is worse than
	 * either. Every real caller passes a jQuery .val(), which is always a string.
	 *
	 * @since 1.8.12.3
	 *
	 * @param {string} stringValue The text, usually coming from user input.
	 *
	 * @returns {string} The cleaned string.
	 */
	function stripUnsafeText( stringValue ) {

		return 'string' === typeof stringValue ? stringValue.replace( UNSAFE_TEXT_CHARS, '' ) : '';

	}

	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.0
	 *
	 * @type {object}
	 */
	const app = {

		/**
		 * function that prevents certain HTML/JS/etc characters from being inputted into headlines, campaign titles, etc.
		 *
		 * @since 1.8.0
		 * @since 1.8.12.3 Switched to a denylist so non-Latin scripts survive.
		 *
		 * @param {string} stringValue The text, usually coming from user input.
		 *
		 * @returns {Event} Event object.
		 */
		santitizeTitle: function ( stringValue ) {

			return stripUnsafeText( stringValue );

		},


		/**
		 * function that prevents certain HTML/JS/etc characters from being inputted into generic text input areas/boxes.
		 *
		 * @since 1.8.0
		 * @since 1.8.12.3 Switched to a denylist so non-Latin scripts survive.
		 *
		 * @param {string} stringValue The text, usually coming from user input.
		 *
		 * @returns {Event} Event object.
		 */
		santitizeTextInput: function ( stringValue ) {

			return stripUnsafeText( stringValue );

		},

		/**
		 * function that prevents certain HTML/JS/etc characters from being inputted into CSS class input areas/boxes.
		 *
		 * @since 1.8.0
		 *
		 * @param {string} stringValue The text, usually coming from user input.
		 *
		 * @returns {Event} Event object.
		 */
		santitizeCSSInput: function ( stringValue ) {

			return stringValue.replace(/[^a-zA-Z0-9_,\/$:\-\.!;]/g, ''); //eslint-disable-line

		},

		/**
		 * Wrapper to trigger a native or custom event and return the event object.
		 *
		 * @since 1.8.0
		 *
		 * @param {jQuery} $element  Element to trigger event on.
		 * @param {string} eventName Event name to trigger (custom or native).
		 * @param {Array}  args      Trigger arguments.
		 *
		 * @returns {Event} Event object.
		 */
		triggerEvent: function( $element, eventName, args = [] ) {

			let eventObject = new $.Event( eventName );

			$element.trigger( eventObject, args );

			return eventObject;
		},

		/**
		 * Debounce.
		 *
		 * This function comes directly from underscore.js:
		 *
		 * Returns a function, that, as long as it continues to be invoked, will not
		 * be triggered. The function will be called after it stops being called for
		 * N milliseconds. If `immediate` is passed, trigger the function on the
		 * leading edge, instead of the trailing.
		 *
		 * Debouncing is removing unwanted input noise from buttons, switches or other user input.
		 * Debouncing prevents extra activations or slow functions from triggering too often.
		 *
		 * @param {Function} func      The function to be debounced.
		 * @param {int}      wait      The amount of time to delay calling func.
		 * @param {bool}     immediate Whether or not to trigger the function on the leading edge.
		 *
		 * @returns {Function} Returns a function that, as long as it continues to be invoked, will not be triggered.
		 */
		debounce: function( func, wait, immediate ) {

			var timeout;

			return function() {

				var context = this,
					args = arguments;
				var later = function() {

					timeout = null;

					if ( ! immediate ) {
						func.apply( context, args );
					}
				};

				var callNow = immediate && ! timeout;

				clearTimeout( timeout );

				timeout = setTimeout( later, wait );

				if ( callNow ) {
					func.apply( context, args );
				}
			};
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) ); // eslint-disable-line
