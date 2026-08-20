/**
 * Hor-Ex admin behaviour: repeaters, media pickers, colour fields.
 *
 * Everything is bound through delegation on the document, so rows added later work
 * without rebinding. Selectors are scoped with :scope so a nested repeater's rows are
 * never mistaken for its parent's.
 */
( function () {
	'use strict';

	var strings = window.horexAdmin || {};
	var frames = new WeakMap();

	/**
	 * First match for any of the given scoped selectors.
	 *
	 * @param {HTMLElement} element Element to search within.
	 * @param {Array}       paths   Scoped selectors, most specific first.
	 * @return {HTMLElement|null} The match.
	 */
	function scoped( element, paths ) {
		for ( var i = 0; i < paths.length; i++ ) {
			var found = element.querySelector( paths[ i ] );

			if ( found ) {
				return found;
			}
		}

		return null;
	}

	/**
	 * The header parts of a row, whether it is a card or a simple line.
	 *
	 * @param {HTMLElement} row Row element.
	 * @return {Object} Named parts.
	 */
	function rowParts( row ) {
		return {
			index: scoped( row, [ ':scope > .horex-row__header > .horex-row__index', ':scope > .horex-row__index' ] ),
			title: row.querySelector( ':scope > .horex-row__header > [data-row-title]' ),
			key: row.querySelector( ':scope > .horex-row__header > [data-row-key]' ),
			swatch: row.querySelector( ':scope > .horex-row__header > [data-row-swatch]' ),
			actions: scoped( row, [ ':scope > .horex-row__header > .horex-row__actions', ':scope > .horex-row__actions' ] )
		};
	}

	/**
	 * This row's own slug input, ignoring those of any nested repeater.
	 *
	 * @param {HTMLElement} row Row element.
	 * @return {HTMLElement|null} The input.
	 */
	function ownSlugInput( row ) {
		return scoped( row, [
			':scope > .horex-row__body > .horex-advanced > .horex-grid > .horex-field > [data-horex-slug]',
			':scope > .horex-row__body > .horex-grid > .horex-field > [data-horex-slug]',
			':scope > [data-horex-slug]'
		] );
	}

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
	 * Names are rewritten by leading-prefix replacement, so inputs belonging to nested
	 * repeaters are carried along with their parent row.
	 *
	 * @param {HTMLElement} repeater The [data-repeater] element.
	 */
	function reindex( repeater ) {
		var prefix = repeater.getAttribute( 'data-prefix' );
		var rows = directRows( repeater );

		rows.forEach( function ( row, i ) {
			var previous = row.getAttribute( 'data-index' );
			var parts = rowParts( row );

			if ( parts.index ) {
				parts.index.textContent = String( i + 1 );
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

			if ( parts.actions ) {
				var up = parts.actions.querySelector( '.horex-repeater__move[data-move="up"]' );
				var down = parts.actions.querySelector( '.horex-repeater__move[data-move="down"]' );

				if ( up ) {
					up.disabled = 0 === i;
				}

				if ( down ) {
					down.disabled = i === rows.length - 1;
				}
			}
		} );

		var empty = repeater.querySelector( ':scope > .horex-repeater__empty' );

		if ( empty ) {
			empty.hidden = rows.length > 0;
		}
	}

	/**
	 * Append a blank row built from the repeater's template.
	 *
	 * Placeholders are depth-tagged, so substituting a parent's tokens leaves a nested
	 * repeater's own template intact.
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

	/* Media --------------------------------------------------------------- */

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
				title: strings.chooseImage || 'Choose image',
				button: { text: strings.useImage || 'Use this image' },
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
		var choose = wrapper.querySelector( '.button.horex-image__select' );

		if ( ! input || ! preview ) {
			return;
		}

		input.value = id ? String( id ) : '';
		preview.innerHTML = '';
		wrapper.classList.toggle( 'has-image', !! id );

		if ( id && url ) {
			var img = document.createElement( 'img' );
			img.src = url;
			img.alt = '';
			preview.appendChild( img );
		} else {
			var icon = document.createElement( 'span' );
			icon.className = 'dashicons dashicons-format-image';
			icon.setAttribute( 'aria-hidden', 'true' );
			preview.appendChild( icon );
		}

		if ( remove ) {
			remove.hidden = ! id;
		}

		if ( choose ) {
			choose.textContent = id
				? ( strings.replace || 'Replace' )
				: ( strings.chooseImage || 'Choose image' );
		}

		// Keep the row header's thumbnail in step.
		var row = wrapper.closest( '[data-repeater-row]' );
		var thumb = row && row.querySelector( ':scope > .horex-row__header > .horex-row__thumb' );

		if ( thumb ) {
			thumb.style.backgroundImage = id && url ? 'url(' + url + ')' : '';
		}
	}

	/* Events -------------------------------------------------------------- */

	document.addEventListener( 'click', function ( event ) {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		var toggle = event.target.closest( '.horex-row__toggle' );

		if ( toggle ) {
			event.preventDefault();

			var toggling = toggle.closest( '[data-repeater-row]' );
			var collapsed = toggling.classList.toggle( 'is-collapsed' );
			toggle.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );

			return;
		}

		var select = event.target.closest( '.horex-image__select' );

		if ( select ) {
			event.preventDefault();
			openMediaFrame( select.closest( '[data-horex-image]' ) );

			return;
		}

		var removeImage = event.target.closest( '.horex-image__remove' );

		if ( removeImage ) {
			event.preventDefault();
			setImage( removeImage.closest( '[data-horex-image]' ), 0, '' );

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

			var row = remove.closest( '[data-repeater-row]' );

			if ( ! row || ! window.confirm( strings.confirmRemove || 'Remove this row?' ) ) {
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

			var moving = move.closest( '[data-repeater-row]' );

			if ( ! moving ) {
				return;
			}

			var up = 'up' === move.getAttribute( 'data-move' );
			var target = up ? moving.previousElementSibling : moving.nextElementSibling;

			if ( ! target ) {
				return;
			}

			if ( up ) {
				target.before( moving );
			} else {
				target.after( moving );
			}

			reindex( moving.parentElement.closest( '[data-repeater]' ) );
		}
	} );

	document.addEventListener( 'input', function ( event ) {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		// Keep the swatch picker and the hex field showing the same colour.
		var colour = event.target.closest( '.horex-color' );

		if ( colour ) {
			var picker = colour.querySelector( '.horex-color__picker' );
			var hex = colour.querySelector( '.horex-color__hex' );

			if ( picker && hex ) {
				if ( event.target === picker ) {
					hex.value = picker.value.toUpperCase();
				} else if ( event.target === hex && /^#[0-9a-f]{6}$/i.test( hex.value ) ) {
					picker.value = hex.value;
				}

				var colourRow = colour.closest( '[data-repeater-row]' );
				var swatch = colourRow && rowParts( colourRow ).swatch;

				if ( swatch ) {
					swatch.style.backgroundColor = hex.value || 'transparent';
				}
			}
		}

		// Keep each row's header showing the name the customer will see.
		var labelField = event.target.closest( '[data-row-label-field]' );

		if ( labelField ) {
			var labelled = labelField.closest( '[data-repeater-row]' );
			var title = labelled && rowParts( labelled ).title;

			if ( title ) {
				var value = ( event.target.value || '' ).trim();
				title.textContent = value || title.getAttribute( 'data-placeholder' ) || '';
			}
		}

		// Mirror an edited key into the row header.
		if ( event.target.matches( '[data-horex-slug]' ) ) {
			var slugRow = event.target.closest( '[data-repeater-row]' );
			var keyLabel = slugRow && rowParts( slugRow ).key;

			if ( keyLabel ) {
				keyLabel.textContent = event.target.value;
			}
		}
	} );

	// Derive an empty key from the row's name, once, when the name is finished.
	document.addEventListener( 'change', function ( event ) {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		var field = event.target.closest( '[data-row-label-field]' );
		var row = field && field.closest( '[data-repeater-row]' );

		if ( ! row ) {
			return;
		}

		var slug = ownSlugInput( row );

		if ( slug && ! slug.value ) {
			slug.value = slugify( event.target.value || '' );

			var keyLabel = rowParts( row ).key;

			if ( keyLabel ) {
				keyLabel.textContent = slug.value;
			}
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
