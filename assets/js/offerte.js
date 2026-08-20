/**
 * Hor-Ex configurator — step engine and screens.
 *
 * The step order is never hard-coded: it is computed from the selected product, so a
 * product added in the admin behaves correctly without a code change.
 */
( function () {
	'use strict';

	/**
	 * Read the catalogue belonging to one configurator.
	 *
	 * The payload travels inside the element's own markup, so it cannot be separated
	 * from it by a plugin that defers or combines scripts.
	 *
	 * @param {HTMLElement} root The [data-horex] element.
	 * @return {Object|null} Catalogue, or null when it cannot be read.
	 */
	function readConfig( root ) {
		var node = root.querySelector( '[data-horex-config]' );

		if ( node ) {
			try {
				return JSON.parse( node.textContent );
			} catch ( error ) {
				window.console && window.console.error( 'Hor-Ex: de configuratie kon niet gelezen worden.', error );

				return null;
			}
		}

		// Older markup passed the catalogue through wp_localize_script.
		return window.horexConfig || null;
	}

	/**
	 * Escape a value for insertion into markup.
	 *
	 * @param {*} value Raw value.
	 * @return {string} Escaped text.
	 */
	function esc( value ) {
		return String( value == null ? '' : value ).replace( /[&<>"']/g, function ( ch ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ ch ];
		} );
	}

	/**
	 * Find a catalogue entry by its key.
	 *
	 * @param {Array}  list List to search.
	 * @param {string} slug Key to find.
	 * @return {Object|null} The entry.
	 */
	function bySlug( list, slug ) {
		var found = ( list || [] ).filter( function ( entry ) {
			return entry.slug === slug;
		} );

		return found.length ? found[ 0 ] : null;
	}

	/**
	 * Build one configurator on a root element.
	 *
	 * @param {HTMLElement} root The [data-horex] element.
	 */
	function createConfigurator( root ) {
		var stage = root.querySelector( '[data-horex-stage]' );
		var stepLabel = root.querySelector( '[data-horex-step]' );
		var progress = root.querySelector( '[data-horex-progress]' );

		if ( ! stage ) {
			return;
		}

		var config = readConfig( root );

		if ( ! config ) {
			stage.innerHTML = '<div class="horex-screen"><div class="horex-alert">'
				+ '<strong>De configurator kon niet geladen worden.</strong>'
				+ '<span>Ververs de pagina, of bel ons gerust — dan nemen we uw maten telefonisch door.</span>'
				+ '</div></div>';

			return;
		}

		var items = [];
		var draft = null;
		var carried = {};
		var screen = 'intro';

		/**
		 * A blank product, pre-filled with whatever carried over.
		 *
		 * @return {Object} Draft item.
		 */
		function blank() {
			return {
				product: null,
				uitvoering: null,
				kleur: null,
				gaas: carried.gaas || null,
				ruimte: '',
				breedte: '',
				hoogte: ''
			};
		}

		/**
		 * The product currently being configured.
		 *
		 * @return {Object|null} Product.
		 */
		function current() {
			return draft && draft.product ? bySlug( config.products, draft.product ) : null;
		}

		/**
		 * The steps this product needs, in order.
		 *
		 * Insect screens run the full five; curtains and sun shading skip variant and
		 * mesh. A step whose catalogue list is empty is dropped rather than shown as a
		 * dead end.
		 *
		 * @return {Array} Step keys.
		 */
		function steps() {
			var product = current();

			if ( ! product ) {
				return [ 'product' ];
			}

			var order = [ 'product' ];

			if ( product.uitvoeringen && product.uitvoeringen.length ) {
				order.push( 'uitvoering' );
			}

			if ( colours( product ).length ) {
				order.push( 'kleur' );
			}

			if ( 'horren' === product.type && ( config.gaas || [] ).length ) {
				order.push( 'gaas' );
			}

			order.push( 'maat' );

			return order;
		}

		/**
		 * The colour list this product draws from.
		 *
		 * @param {Object} product Product.
		 * @return {Array} Colours.
		 */
		function colours( product ) {
			return ( config.kleuren && config.kleuren[ product.kleurType ] ) || [];
		}

		/**
		 * Where the back button on a step leads.
		 *
		 * @param {string} step Current step.
		 * @return {string} Previous step.
		 */
		function previous( step ) {
			var order = steps();
			var index = order.indexOf( step );

			return index > 0 ? order[ index - 1 ] : 'product';
		}

		/* Screens ---------------------------------------------------------- */

		/**
		 * The opening screen.
		 *
		 * @return {string} Markup.
		 */
		function viewIntro() {
			return '<div class="horex-screen">'
				+ '<p class="horex-eyebrow">Offerte aanvragen</p>'
				+ '<h2 class="horex-title">Stel uw raamoplossing samen<br />en ontvang een prijsopgave</h2>'
				+ '<p class="horex-sub">U kiest per raam of deur het product, de kleur en de afwerking, en vult de maten in. Meerdere producten voegt u achter elkaar toe. Duurt ongeveer twee minuten.</p>'
				+ '<div class="horex-note">Twijfelt u over de maten? Vul een schatting in. Wij meten altijd zelf na op locatie voordat er iets geproduceerd wordt.</div>'
				+ '<div class="horex-actions"><button type="button" class="horex-btn horex-btn--primary" data-horex-start>Beginnen</button></div>'
				+ '</div>';
		}

		/**
		 * The product cards.
		 *
		 * @return {string} Markup.
		 */
		function viewProduct() {
			var products = config.products || [];

			if ( ! products.length ) {
				window.console && window.console.warn( 'Hor-Ex: er zijn geen producten ingesteld.' );

				return '<div class="horex-screen">'
					+ back( 'intro' )
					+ '<h2 class="horex-title">Wat wilt u laten maken?</h2>'
					+ '<div class="horex-alert">'
					+ '<strong>Er staan nog geen producten klaar.</strong>'
					+ '<span>Neem gerust contact met ons op — dan nemen we uw wensen persoonlijk door.</span>'
					+ '</div></div>';
			}

			var cards = products.map( function ( product ) {
				var drawing = ( config.tekeningen || {} )[ product.illustratie ] || '';
				var photo = product.foto
					? '<img src="' + esc( product.foto ) + '" alt="' + esc( product.naam ) + '" loading="lazy" onerror="this.remove()" />'
					: '';

				return '<button type="button" class="horex-pcard' + ( draft.product === product.slug ? ' is-on' : '' ) + '" data-horex-product="' + esc( product.slug ) + '">'
					+ '<span class="horex-shot">' + drawing + photo + '<span class="horex-tick">&#10003;</span></span>'
					+ '<span class="horex-pmeta">'
					+ '<span class="horex-pmeta__t">' + esc( product.naam ) + '</span>'
					+ ( product.kort ? '<span class="horex-pmeta__d">' + esc( product.kort ) + '</span>' : '' )
					+ '</span></button>';
			} ).join( '' );

			return '<div class="horex-screen">'
				+ back( items.length ? 'overzicht' : 'intro' )
				+ '<p class="horex-eyebrow">Stap 1</p>'
				+ '<h2 class="horex-title">Wat wilt u laten maken?</h2>'
				+ '<p class="horex-sub">Nog niet zeker? Kies wat het dichtst bij uw situatie komt — we nemen het bij de inmeting samen door.</p>'
				+ '<div class="horex-products">' + cards + '</div>'
				+ '</div>';
		}

		/**
		 * A stand-in for a step that is not built yet.
		 *
		 * It still shows the computed position, so the step order can be checked by
		 * walking the flow.
		 *
		 * @param {string} step Step key.
		 * @return {string} Markup.
		 */
		function viewPending( step ) {
			var order = steps();
			var labels = {
				product: 'Product',
				uitvoering: 'Uitvoering',
				kleur: 'Kleur',
				gaas: 'Gaas',
				maat: 'Maten'
			};
			var product = current();
			var headings = {
				uitvoering: 'Welke uitvoering?',
				kleur: product ? product.kleurVraag : 'Welke kleur?',
				gaas: 'Welk gaas?',
				maat: 'Wat zijn de maten?'
			};

			var chips = order.map( function ( key ) {
				return '<li' + ( key === step ? ' class="is-now"' : '' ) + '>' + esc( labels[ key ] || key ) + '</li>';
			} ).join( '' );

			return '<div class="horex-screen">'
				+ back( previous( step ) )
				+ '<p class="horex-eyebrow">Stap ' + ( order.indexOf( step ) + 1 ) + ' van ' + order.length + '</p>'
				+ '<h2 class="horex-title">' + esc( headings[ step ] || '' ) + '</h2>'
				+ '<div class="horex-todo">'
				+ '<span class="horex-todo__label">Nog in aanbouw</span>'
				+ 'Dit scherm wordt in de volgende fase gebouwd. De stappen hieronder zijn wel al '
				+ 'bepaald door het gekozen product.'
				+ '<ul class="horex-todo__steps">' + chips + '</ul>'
				+ '</div>'
				+ '<div class="horex-actions">'
				+ '<button type="button" class="horex-btn horex-btn--ghost" data-horex-go="product">Ander product kiezen</button>'
				+ '</div>'
				+ '</div>';
		}

		/**
		 * The back button.
		 *
		 * @param {string} target Screen to return to.
		 * @return {string} Markup.
		 */
		function back( target ) {
			return '<button type="button" class="horex-back" data-horex-go="' + esc( target ) + '">&larr; Terug</button>';
		}

		/* Rendering -------------------------------------------------------- */

		/**
		 * Draw the current screen and update the bar.
		 */
		function render() {
			var order = steps();
			var index = order.indexOf( screen );
			var inFlow = index > -1;

			if ( stepLabel ) {
				// Before a product is picked the total is unknown, and watching
				// "of 5" flip to "of 3" reads like a fault.
				stepLabel.textContent = inFlow
					? ( order.length > 1 ? 'Stap ' + ( index + 1 ) + ' van ' + order.length : 'Stap 1' )
					: ( items.length ? items.length + ( 1 === items.length ? ' product' : ' producten' ) + ' toegevoegd' : '' );
			}

			if ( progress ) {
				progress.style.width = inFlow
					? ( index / Math.max( order.length, 4 ) * 100 + 12 ) + '%'
					: ( 'intro' === screen ? '0%' : '88%' );
			}

			var views = {
				intro: viewIntro,
				product: viewProduct
			};

			stage.innerHTML = views[ screen ] ? views[ screen ]() : viewPending( screen );
		}

		/**
		 * Move to a screen and scroll it into view.
		 *
		 * @param {string} target Screen key.
		 */
		function go( target ) {
			screen = target;
			render();

			var top = root.getBoundingClientRect().top + window.pageYOffset;
			window.scrollTo( { top: top, behavior: 'smooth' } );
		}

		/* Events ----------------------------------------------------------- */

		root.addEventListener( 'click', function ( event ) {
			if ( ! ( event.target instanceof Element ) ) {
				return;
			}

			var start = event.target.closest( '[data-horex-start]' );

			if ( start ) {
				draft = blank();
				go( 'product' );

				return;
			}

			var goTo = event.target.closest( '[data-horex-go]' );

			if ( goTo ) {
				if ( ! draft ) {
					draft = blank();
				}

				go( goTo.getAttribute( 'data-horex-go' ) );

				return;
			}

			var pick = event.target.closest( '[data-horex-product]' );

			if ( pick ) {
				var product = bySlug( config.products, pick.getAttribute( 'data-horex-product' ) );

				if ( ! product ) {
					return;
				}

				draft.product = product.slug;
				draft.uitvoering = null;

				// Carry the previous colour over when the palette is the same one.
				draft.kleur = carried.kleurType === product.kleurType ? carried.kleur : null;

				// Highlight the tapped card, then move on by itself.
				render();

				var order = steps();
				window.setTimeout( function () {
					go( order[ 1 ] || 'maat' );
				}, 230 );
			}
		} );

		draft = blank();
		render();
	}

	/**
	 * Start every configurator on the page.
	 */
	function init() {
		document.querySelectorAll( '[data-horex]' ).forEach( createConfigurator );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
