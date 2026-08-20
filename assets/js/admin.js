/**
 * Settings page behaviour: media pickers and colour inputs.
 *
 * Everything is bound through delegation on the document, so rows added later by the
 * repeater component work without rebinding.
 */
( function () {
	'use strict';

	var strings = window.horexAdmin || {};
	var frames = new WeakMap();

	/**
	 * Open the media library for an image field and store the chosen attachment.
	 *
	 * @param {HTMLElement} wrapper The [data-horex-image] element.
	 */
	function openMediaFrame( wrapper ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		var frame = frames.get( wrapper );

		if ( ! frame ) {
			frame = window.wp.media( {
				title: strings.chooseImage || 'Afbeelding kiezen',
				button: { text: strings.useImage || 'Deze afbeelding gebruiken' },
				library: { type: 'image' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var size = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;

				setImage( wrapper, attachment.id, size );
			} );

			frames.set( wrapper, frame );
		}

		frame.open();
	}

	/**
	 * Write an attachment into an image field, or clear it when id is 0.
	 *
	 * @param {HTMLElement} wrapper The [data-horex-image] element.
	 * @param {number}      id      Attachment ID.
	 * @param {string}      url     Thumbnail URL.
	 */
	function setImage( wrapper, id, url ) {
		var input = wrapper.querySelector( '.horex-image__value' );
		var preview = wrapper.querySelector( '.horex-image__preview' );
		var remove = wrapper.querySelector( '.horex-image__remove' );

		if ( ! input || ! preview ) {
			return;
		}

		input.value = id ? String( id ) : '';
		preview.innerHTML = '';

		if ( id && url ) {
			var img = document.createElement( 'img' );
			img.src = url;
			img.alt = '';
			preview.appendChild( img );
		}

		if ( remove ) {
			remove.hidden = ! id;
		}
	}

	document.addEventListener( 'click', function ( event ) {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		var select = event.target.closest( '.horex-image__select' );

		if ( select ) {
			event.preventDefault();
			openMediaFrame( select.closest( '[data-horex-image]' ) );
			return;
		}

		var remove = event.target.closest( '.horex-image__remove' );

		if ( remove ) {
			event.preventDefault();
			setImage( remove.closest( '[data-horex-image]' ), 0, '' );
		}
	} );

	// Keep the swatch picker and the hex field showing the same colour.
	document.addEventListener( 'input', function ( event ) {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		var field = event.target.closest( '.horex-color' );

		if ( ! field ) {
			return;
		}

		var picker = field.querySelector( '.horex-color__picker' );
		var hex = field.querySelector( '.horex-color__hex' );

		if ( ! picker || ! hex ) {
			return;
		}

		if ( event.target === picker ) {
			hex.value = picker.value.toUpperCase();
		} else if ( event.target === hex && /^#[0-9a-f]{6}$/i.test( hex.value ) ) {
			picker.value = hex.value;
		}
	} );
}() );
