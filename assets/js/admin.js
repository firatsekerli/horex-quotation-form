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

	/* Repeater ------------------------------------------------------------ */

	/**
	 * The rows belonging to this repeater, excluding rows of nested repeaters.
	 *
	 * @param {HTMLElement} repeater The [data-repeater] element.
	 * @return {Array} Row elements.
	 */
	function directRows( repeater ) {
		var container = repeater.querySelector( ':scope > .horex-repeater__rows' );

		if ( ! container ) {
			return [];
		}

		return Array.prototype.filter.call( container.children, function ( el ) {
			return el.matches( '[data-repeater-row]' );
		} );
	}

	/**
	 * Renumber rows and rewrite the indices in their input names.
	 *
	 * Input names are rewritten by leading-prefix replacement, so inputs belonging to
	 * nested repeaters are carried along with their parent row.
	 *
	 * @param {HTMLElement} repeater The [data-repeater] element.
	 */
	function reindex( repeater ) {
		var prefix = repeater.getAttribute( 'data-prefix' );
		var rows = directRows( repeater );

		rows.forEach( function ( row, i ) {
			var previous = row.getAttribute( 'data-index' );
			var number = row.querySelector( ':scope > .horex-repeater__header > .horex-repeater__number' );

			if ( number ) {
				number.textContent = String( i + 1 );
			}

			if ( previous !== String( i ) ) {
				var from = prefix + '[' + previous + ']';
				var to = prefix + '[' + i + ']';

				row.querySelectorAll( '[name]' ).forEach( function ( el ) {
					if ( el.name.indexOf( from ) === 0 ) {
						el.name = to + el.name.slice( from.length );
					}
				} );

				row.querySelectorAll( '[data-repeater]' ).forEach( function ( nested ) {
					var nestedPrefix = nested.getAttribute( 'data-prefix' );

					if ( nestedPrefix && nestedPrefix.indexOf( from ) === 0 ) {
						nested.setAttribute( 'data-prefix', to + nestedPrefix.slice( from.length ) );
					}
				} );

				row.setAttribute( 'data-index', String( i ) );
			}

			toggleMoveButtons( row, i === 0, i === rows.length - 1 );
		} );

		var empty = repeater.querySelector( ':scope > .horex-repeater__empty' );

		if ( empty ) {
			empty.hidden = rows.length > 0;
		}
	}

	/**
	 * Disable the move buttons that would run off the ends of the list.
	 *
	 * @param {HTMLElement} row     Row element.
	 * @param {boolean}     isFirst Row is first in its repeater.
	 * @param {boolean}     isLast  Row is last in its repeater.
	 */
	function toggleMoveButtons( row, isFirst, isLast ) {
		var header = row.querySelector( ':scope > .horex-repeater__header' );

		if ( ! header ) {
			return;
		}

		var up = header.querySelector( '.horex-repeater__move[data-move="up"]' );
		var down = header.querySelector( '.horex-repeater__move[data-move="down"]' );

		if ( up ) {
			up.disabled = isFirst;
		}

		if ( down ) {
			down.disabled = isLast;
		}
	}

	/**
	 * Append a blank row built from the repeater's template.
	 *
	 * Placeholders are depth-tagged, so substituting a parent's tokens leaves a nested
	 * repeater's own template untouched.
	 *
	 * @param {HTMLElement} repeater The [data-repeater] element.
	 */
	function addRow( repeater ) {
		var template = repeater.querySelector( ':scope > .horex-repeater__template' );
		var container = repeater.querySelector( ':scope > .horex-repeater__rows' );

		if ( ! template || ! container ) {
			return;
		}

		var depth = repeater.getAttribute( 'data-depth' ) || '0';
		var prefix = repeater.getAttribute( 'data-prefix' );
		var index = directRows( repeater ).length;

		// split/join rather than replace: prefixes contain [ and $, which replace() reads.
		var markup = template.innerHTML
			.split( '__PREFIX' + depth + '__' ).join( prefix )
			.split( '__INDEX' + depth + '__' ).join( String( index ) );

		var holder = document.createElement( 'div' );
		holder.innerHTML = markup;

		var row = holder.querySelector( '[data-repeater-row]' );

		if ( ! row ) {
			return;
		}

		container.appendChild( row );
		reindex( repeater );

		var first = row.querySelector( 'input[type="text"], textarea, select' );

		if ( first ) {
			first.focus();
		}
	}

	/**
	 * Turn a label into a slug, matching sanitize_title closely enough to preview it.
	 *
	 * @param {string} value Source text.
	 * @return {string} Slug.
	 */
	function slugify( value ) {
		return value
			.toLowerCase()
			.normalize( 'NFD' )
			.replace( /[\u0300-\u036f]/g, '' )
			.replace( /[^a-z0-9]+/g, '-' )
			.replace( /^-+|-+$/g, '' );
	}

	/**
	 * The row a field belongs to, ignoring rows of any nested repeater above it.
	 *
	 * @param {HTMLElement} element Element inside a row.
	 * @return {HTMLElement|null} Row element.
	 */
	function ownRow( element ) {
		return element.closest( '[data-repeater-row]' );
	}

	document.addEventListener( 'click', function ( event ) {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		var add = event.target.closest( '.horex-repeater__add' );

		if ( add ) {
			event.preventDefault();
			addRow( add.closest( '[data-repeater]' ) );
			return;
		}

		var remove = event.target.closest( '.horex-repeater__remove' );

		if ( remove ) {
			event.preventDefault();

			var row = ownRow( remove );

			if ( ! row ) {
				return;
			}

			if ( ! window.confirm( strings.confirmRemove || 'Deze rij verwijderen?' ) ) {
				return;
			}

			var owner = row.parentElement.closest( '[data-repeater]' );
			row.remove();

			if ( owner ) {
				reindex( owner );
			}

			return;
		}

		var move = event.target.closest( '.horex-repeater__move' );

		if ( move ) {
			event.preventDefault();

			var moving = ownRow( move );

			if ( ! moving ) {
				return;
			}

			var target = 'up' === move.getAttribute( 'data-move' )
				? moving.previousElementSibling
				: moving.nextElementSibling;

			if ( ! target ) {
				return;
			}

			if ( 'up' === move.getAttribute( 'data-move' ) ) {
				target.before( moving );
			} else {
				target.after( moving );
			}

			reindex( moving.parentElement.closest( '[data-repeater]' ) );
		}
	} );

	// Keep each row's header showing the name the customer will see.
	document.addEventListener( 'input', function ( event ) {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		var field = event.target.closest( '[data-row-label-field]' );

		if ( ! field ) {
			return;
		}

		var row = ownRow( field );
		var title = row && row.querySelector( ':scope > .horex-repeater__header > [data-row-title]' );

		if ( ! title ) {
			return;
		}

		var value = ( event.target.value || '' ).trim();
		title.textContent = value || title.getAttribute( 'data-placeholder' ) || '';
	} );

	// Derive an empty slug from the row's name, once, when the name is finished.
	document.addEventListener( 'change', function ( event ) {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		var field = event.target.closest( '[data-row-label-field]' );
		var row = field && ownRow( field );

		if ( ! row ) {
			return;
		}

		var slug = row.querySelector( ':scope > .horex-repeater__body > .horex-field [data-horex-slug]' );

		if ( slug && ! slug.value ) {
			slug.value = slugify( event.target.value || '' );
		}
	} );

	/**
	 * Number the rows rendered by PHP and set the move buttons' initial state.
	 */
	function init() {
		document.querySelectorAll( '[data-repeater]' ).forEach( reindex );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
