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
	 * A whole number from a field, or 0.
	 *
	 * @param {*} value Raw value.
	 * @return {number} Millimetres.
	 */
	function mm( value ) {
		return parseInt( value, 10 ) || 0;
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
		var modal = root.querySelector( '[data-horex-modal]' );

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
		var sending = false;
		var reference = '';
		var trap = '';
		var customer = { naam: '', email: '', telefoon: '', adres: '', postcode: '', plaats: '', opmerkingen: '' };

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
		 * The colour list a product draws from.
		 *
		 * @param {Object} product Product.
		 * @return {Array} Colours.
		 */
		function colours( product ) {
			return ( config.kleuren && config.kleuren[ product.kleurType ] ) || [];
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

		/**
		 * The step after this one.
		 *
		 * @param {string} step Current step.
		 * @return {string} Next step.
		 */
		function next( step ) {
			var order = steps();
			var index = order.indexOf( step );

			return index > -1 && order[ index + 1 ] ? order[ index + 1 ] : 'maat';
		}

		/**
		 * The number shown in the eyebrow for a step.
		 *
		 * @param {string} step Step key.
		 * @return {string} Label.
		 */
		function stepLabelFor( step ) {
			var order = steps();

			return 'Stap ' + ( order.indexOf( step ) + 1 );
		}

		/**
		 * How a product's fill is drawn in the preview.
		 *
		 * @param {Object}  product Product.
		 * @param {string}  hex     Chosen colour.
		 * @param {boolean} fine    Whether the mesh is a fine weave.
		 * @return {string} Inline style.
		 */
		function fillStyle( product, hex, fine ) {
			var density = fine ? 2 : 3;

			if ( 'gaas' === product.vulling ) {
				return 'background-color:rgba(255,255,255,.4);background-image:'
					+ 'repeating-linear-gradient(0deg,rgba(0,0,0,.14) 0 .5px,transparent .5px ' + density + 'px),'
					+ 'repeating-linear-gradient(90deg,rgba(0,0,0,.14) 0 .5px,transparent .5px ' + density + 'px);';
			}

			if ( 'plisse' === product.vulling ) {
				return 'background-color:' + hex + ';background-image:repeating-linear-gradient(0deg,rgba(0,0,0,.13) 0 1px,transparent 1px 8px);';
			}

			if ( 'wave' === product.vulling ) {
				return 'background-color:' + hex + ';background-image:repeating-linear-gradient(90deg,rgba(0,0,0,.16) 0 2px,rgba(255,255,255,.22) 8px,transparent 18px 26px);';
			}

			return 'background-color:' + hex + ';background-image:repeating-linear-gradient(0deg,rgba(0,0,0,.07) 0 2px,transparent 2px 5px);';
		}

		/**
		 * The swatch face for a colour.
		 *
		 * @param {Object} colour Colour entry.
		 * @return {string} Inline style.
		 */
		function swatchStyle( colour ) {
			if ( colour.swatch ) {
				return 'background-image:url(' + esc( colour.swatch ) + ');background-size:cover;background-position:center;';
			}

			var style = 'background-color:' + esc( colour.hex ) + ';';

			if ( colour.textuur ) {
				style += 'background-image:repeating-linear-gradient(45deg,rgba(255,255,255,.10) 0 2px,transparent 2px 4px);';
			}

			return style;
		}

		/**
		 * The mesh pattern shown beside a mesh option.
		 *
		 * @param {Object} gaas Mesh entry.
		 * @return {string} Inline style.
		 */
		function meshStyle( gaas ) {
			var density = gaas.fijnmazig ? 2 : 3;

			return 'background-color:rgba(255,255,255,.4);background-image:'
				+ 'repeating-linear-gradient(0deg,rgba(0,0,0,.16) 0 .5px,transparent .5px ' + density + 'px),'
				+ 'repeating-linear-gradient(90deg,rgba(0,0,0,.16) 0 .5px,transparent .5px ' + density + 'px);';
		}

		/* Screens ---------------------------------------------------------- */

		/**
		 * The back button.
		 *
		 * @param {string} target Screen to return to.
		 * @return {string} Markup.
		 */
		function back( target ) {
			return '<button type="button" class="horex-back" data-horex-go="' + esc( target ) + '">&larr; Terug</button>';
		}

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
		 * The variant cards.
		 *
		 * @return {string} Markup.
		 */
		function viewUitvoering() {
			var product = current();

			var cards = product.uitvoeringen.map( function ( variant ) {
				return '<button type="button" class="horex-card' + ( draft.uitvoering === variant.slug ? ' is-on' : '' ) + '" data-horex-uitvoering="' + esc( variant.slug ) + '">'
					+ '<span class="horex-card__body">'
					+ '<span class="horex-card__t">' + esc( variant.naam ) + '</span>'
					+ ( variant.omschrijving ? '<span class="horex-card__d">' + esc( variant.omschrijving ) + '</span>' : '' )
					+ '</span></button>';
			} ).join( '' );

			return '<div class="horex-screen">'
				+ back( previous( 'uitvoering' ) )
				+ '<p class="horex-eyebrow">' + stepLabelFor( 'uitvoering' ) + '</p>'
				+ '<h2 class="horex-title">Welke uitvoering?</h2>'
				+ '<p class="horex-sub">' + esc( product.naam ) + ' zijn er in meerdere varianten. Kies wat bij uw raam of deur past.</p>'
				+ '<div class="horex-cards">' + cards + '</div>'
				+ '</div>';
		}

		/**
		 * The colour swatches.
		 *
		 * @return {string} Markup.
		 */
		function viewKleur() {
			var product = current();

			var swatches = colours( product ).map( function ( colour ) {
				return '<button type="button" class="horex-swatch' + ( draft.kleur === colour.slug ? ' is-on' : '' ) + '" data-horex-kleur="' + esc( colour.slug ) + '">'
					+ '<span class="horex-swatch__face" style="' + swatchStyle( colour ) + '"></span>'
					+ '<span class="horex-swatch__t">' + esc( colour.naam ) + '</span>'
					+ '</button>';
			} ).join( '' );

			return '<div class="horex-screen">'
				+ back( previous( 'kleur' ) )
				+ '<p class="horex-eyebrow">' + stepLabelFor( 'kleur' ) + '</p>'
				+ '<h2 class="horex-title">' + esc( product.kleurVraag ) + '</h2>'
				+ '<p class="horex-sub">Kleuren wijken op een scherm altijd iets af. Bij de inmeting nemen we stalen mee zodat u ze in uw eigen licht kunt zien.</p>'
				+ '<div class="horex-swatches">' + swatches + '</div>'
				+ '</div>';
		}

		/**
		 * The mesh cards.
		 *
		 * @return {string} Markup.
		 */
		function viewGaas() {
			var cards = ( config.gaas || [] ).map( function ( gaas ) {
				return '<button type="button" class="horex-card' + ( draft.gaas === gaas.slug ? ' is-on' : '' ) + '" data-horex-gaas="' + esc( gaas.slug ) + '">'
					+ '<span class="horex-card__key" style="' + meshStyle( gaas ) + '"></span>'
					+ '<span class="horex-card__body">'
					+ '<span class="horex-card__t">' + esc( gaas.naam ) + '</span>'
					+ ( gaas.omschrijving ? '<span class="horex-card__d">' + esc( gaas.omschrijving ) + '</span>' : '' )
					+ '</span></button>';
			} ).join( '' );

			return '<div class="horex-screen">'
				+ back( previous( 'gaas' ) )
				+ '<p class="horex-eyebrow">' + stepLabelFor( 'gaas' ) + '</p>'
				+ '<h2 class="horex-title">Welk gaas?</h2>'
				+ '<div class="horex-cards">' + cards + '</div>'
				+ '</div>';
		}

		/**
		 * The measurement screen.
		 *
		 * @return {string} Markup.
		 */
		function viewMaat() {
			var product = current();
			var framed = 'gaas' === product.vulling;
			var help = ( config.meethulp || {} )[ product.meethulp ] || {};
			var thumb = help.diagram
				? '<img src="' + esc( help.diagram ) + '" alt="" />'
				: ( help.tekening || '' );

			return '<div class="horex-screen">'
				+ back( previous( 'maat' ) )
				+ '<p class="horex-eyebrow">' + stepLabelFor( 'maat' ) + '</p>'
				+ '<h2 class="horex-title">Wat zijn de maten?</h2>'
				+ '<p class="horex-sub">' + ( framed
					? 'Meet de binnenmaat van het kozijn, op drie plekken. Twijfelt u? Neem de kleinste maat.'
					: 'Meet de breedte van het raam of de rail, en de hoogte tot waar het doek moet vallen.' ) + '</p>'
				+ '<div class="horex-measure">'
				+ '<div>'
				+ '<div class="horex-field">'
				+ '<label class="horex-label" for="horex-ruimte">Waar komt dit?</label>'
				+ '<input class="horex-input" id="horex-ruimte" type="text" data-horex-field="ruimte" value="' + esc( draft.ruimte ) + '" placeholder="Bijv. slaapkamer voorzijde" autocomplete="off" />'
				+ '<p class="horex-hint">Geef elk product een eigen naam. Zo weet u bij levering precies wat waar hoort.</p>'
				+ '</div>'
				+ '<button type="button" class="horex-meethulp" data-horex-meethulp>'
				+ '<span class="horex-meethulp__mini">' + thumb + '</span>'
				+ '<span class="horex-meethulp__text"><b>' + esc( help.titel || 'Hoe meet ik dit op?' ) + '</b><span>Voorbeeld met de juiste meetpunten</span></span>'
				+ '<span class="horex-meethulp__arrow">&rarr;</span>'
				+ '</button>'
				+ '<div class="horex-row">'
				+ '<div class="horex-field"><label class="horex-label" for="horex-breedte">Breedte</label>'
				+ '<span class="horex-unit"><input class="horex-input horex-input--mm" id="horex-breedte" type="text" inputmode="numeric" data-horex-field="breedte" value="' + esc( draft.breedte ) + '" placeholder="1200" autocomplete="off" /><b>mm</b></span></div>'
				+ '<div class="horex-field"><label class="horex-label" for="horex-hoogte">Hoogte</label>'
				+ '<span class="horex-unit"><input class="horex-input horex-input--mm" id="horex-hoogte" type="text" inputmode="numeric" data-horex-field="hoogte" value="' + esc( draft.hoogte ) + '" placeholder="2100" autocomplete="off" /><b>mm</b></span></div>'
				+ '</div>'
				+ '<div data-horex-warning></div>'
				+ '<div class="horex-actions"><button type="button" class="horex-btn horex-btn--primary" data-horex-add disabled>Toevoegen aan aanvraag</button></div>'
				+ '</div>'
				+ '<div class="horex-preview">'
				+ '<h4 class="horex-preview__title">Voorbeeld op schaal</h4>'
				+ '<div class="horex-preview__stage">'
				+ '<div class="horex-frame" data-horex-frame style="border-width:' + ( framed ? 7 : 0 ) + 'px">'
				+ '<span class="horex-fill" data-horex-fill></span>'
				+ '<span class="horex-dim horex-dim--w" data-horex-dim-w></span>'
				+ '<span class="horex-dim horex-dim--h" data-horex-dim-h></span>'
				+ '</div></div></div>'
				+ '</div></div>';
		}

		/**
		 * Describe a stored item in one line: variant, colour and mesh.
		 *
		 * @param {Object} item Stored item.
		 * @return {string} Summary.
		 */
		function summarise( item ) {
			var product = bySlug( config.products, item.product );

			if ( ! product ) {
				return '';
			}

			var variant = bySlug( product.uitvoeringen, item.uitvoering );
			var colour = bySlug( colours( product ), item.kleur );
			var gaas = 'horren' === product.type ? bySlug( config.gaas, item.gaas ) : null;

			return [
				variant ? variant.naam : product.naam,
				colour ? colour.naam : '',
				gaas ? gaas.naam : ''
			].filter( Boolean ).join( ' · ' );
		}

		/**
		 * The summary of everything added so far.
		 *
		 * @return {string} Markup.
		 */
		function viewOverzicht() {
			var rows = items.map( function ( item, index ) {
				var product = bySlug( config.products, item.product );
				var drawing = ( config.tekeningen || {} )[ product ? product.illustratie : '' ] || '';
				var photo = product && product.foto
					? '<img src="' + esc( product.foto ) + '" alt="" onerror="this.remove()" />'
					: '';

				return '<li class="horex-item">'
					+ '<span class="horex-item__thumb">' + drawing + photo + '</span>'
					+ '<span class="horex-item__body">'
					+ '<span class="horex-item__t">' + esc( item.ruimte ) + '</span>'
					+ '<span class="horex-item__d">' + esc( summarise( item ) ) + '</span>'
					+ '</span>'
					+ '<span class="horex-item__m">' + item.breedte + ' &#215; ' + item.hoogte + ' mm</span>'
					+ '<button type="button" class="horex-item__del" data-horex-remove="' + index + '">verwijder</button>'
					+ '</li>';
			} ).join( '' );

			return '<div class="horex-screen">'
				+ '<p class="horex-eyebrow">Uw aanvraag</p>'
				+ '<h2 class="horex-title">' + items.length + ( 1 === items.length ? ' product' : ' producten' ) + ' in uw aanvraag</h2>'
				+ '<ul class="horex-items">' + rows + '</ul>'
				+ '<div class="horex-actions">'
				+ '<button type="button" class="horex-btn horex-btn--ghost" data-horex-again>+ Nog iets toevoegen</button>'
				+ '<button type="button" class="horex-btn horex-btn--primary" data-horex-go="gegevens">Verder</button>'
				+ '</div>'
				+ '<div class="horex-note">Uw vorige keuzes staan alvast klaar bij het volgende product van hetzelfde type. Aanpassen kan altijd.</div>'
				+ '</div>';
		}

		/**
		 * One contact field.
		 *
		 * @param {string} name        Field key.
		 * @param {string} label       Visible label.
		 * @param {string} type        Input type.
		 * @param {string} hint        Optional hint.
		 * @param {string} placeholder Optional placeholder.
		 * @return {string} Markup.
		 */
		function contactField( name, label, type, hint, placeholder ) {
			return '<div class="horex-field">'
				+ '<label class="horex-label" for="horex-' + name + '">' + esc( label ) + '</label>'
				+ '<input class="horex-input" id="horex-' + name + '" type="' + type + '" data-horex-klant="' + name + '"'
				+ ' value="' + esc( customer[ name ] ) + '"'
				+ ( placeholder ? ' placeholder="' + esc( placeholder ) + '"' : '' )
				+ ' autocomplete="' + ( 'email' === name ? 'email' : ( 'telefoon' === name ? 'tel' : 'on' ) ) + '" />'
				+ ( hint ? '<p class="horex-hint">' + esc( hint ) + '</p>' : '' )
				+ '</div>';
		}

		/**
		 * Where to send the quote.
		 *
		 * @return {string} Markup.
		 */
		function viewGegevens() {
			return '<div class="horex-screen">'
				+ back( 'overzicht' )
				+ '<p class="horex-eyebrow">Bijna klaar</p>'
				+ '<h2 class="horex-title">Waar mogen we de prijsopgave naartoe sturen?</h2>'
				+ '<div class="horex-form">'
				+ '<div class="horex-row">'
				+ contactField( 'naam', 'Naam', 'text', '', '' )
				+ contactField( 'telefoon', 'Telefoon', 'tel', '', '' )
				+ '</div>'
				+ contactField( 'email', 'E-mailadres', 'email', '', '' )
				+ contactField( 'adres', 'Adres', 'text', '', 'Straat en huisnummer' )
				+ '<div class="horex-row">'
				+ contactField( 'postcode', 'Postcode', 'text', '', '' )
				+ contactField( 'plaats', 'Plaats', 'text', '', '' )
				+ '</div>'
				+ '<p class="horex-hint">Uw adres hebben we nodig om te bepalen of we bij u kunnen inmeten.</p>'
				+ '<div class="horex-field" style="margin-top:19px">'
				+ '<label class="horex-label" for="horex-opmerkingen">Iets wat we moeten weten?</label>'
				+ '<textarea class="horex-input" id="horex-opmerkingen" rows="3" data-horex-klant="opmerkingen" placeholder="Optioneel">' + esc( customer.opmerkingen ) + '</textarea>'
				+ '</div>'
				// Bots fill everything in; a customer never sees this.
				+ '<div class="horex-trap" aria-hidden="true">'
				+ '<label for="horex-website">Website</label>'
				+ '<input id="horex-website" type="text" data-horex-trap tabindex="-1" autocomplete="off" />'
				+ '</div>'
				+ '</div>'
				+ '<div data-horex-error></div>'
				+ '<div class="horex-actions">'
				+ '<button type="button" class="horex-btn horex-btn--primary" data-horex-send>Aanvraag versturen</button>'
				+ '</div>'
				+ '</div>';
		}

		/**
		 * The confirmation.
		 *
		 * @return {string} Markup.
		 */
		function viewKlaar() {
			var count = items.length;

			return '<div class="horex-screen horex-done">'
				+ '<div class="horex-done__mark">&#10003;</div>'
				+ '<h2 class="horex-title">Aanvraag verstuurd</h2>'
				+ '<p class="horex-sub">We hebben uw ' + count + ( 1 === count ? ' maat' : ' maten' )
				+ ' ontvangen en nemen binnen één werkdag contact op om een inmeetafspraak te plannen. '
				+ 'Een kopie staat in uw mailbox.</p>'
				+ ( reference ? '<p class="horex-reference">Uw referentie: <strong>' + esc( reference ) + '</strong></p>' : '' )
				+ '</div>';
		}

		/**
		 * Send the request.
		 */
		function send() {
			if ( sending ) {
				return;
			}

			var button = root.querySelector( '[data-horex-send]' );
			var errorBox = root.querySelector( '[data-horex-error]' );

			var problem = '';

			if ( ! customer.naam.trim() ) {
				problem = 'Vul uw naam in.';
			} else if ( ! /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test( customer.email.trim() ) ) {
				problem = 'Vul een geldig e-mailadres in, dan kunnen we de prijsopgave versturen.';
			}

			if ( problem ) {
				errorBox.innerHTML = '<div class="horex-warn">' + esc( problem ) + '</div>';

				return;
			}

			sending = true;
			button.disabled = true;
			button.textContent = 'Versturen…';
			errorBox.innerHTML = '';

			var body = new window.FormData();

			body.append( 'action', config.action );
			body.append( 'nonce', config.nonce );
			body.append( 'website', trap );
			body.append( 'aanvraag', JSON.stringify( { klant: customer, items: items } ) );

			window.fetch( config.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( result ) {
					if ( ! result || ! result.success ) {
						throw new Error( result && result.data && result.data.message
							? result.data.message
							: 'Er ging iets mis bij het versturen.' );
					}

					reference = result.data && result.data.referentie ? result.data.referentie : '';
					sending = false;
					go( 'klaar' );
				} )
				.catch( function ( error ) {
					sending = false;
					button.disabled = false;
					button.textContent = 'Aanvraag versturen';
					errorBox.innerHTML = '<div class="horex-warn">' + esc( error.message ) + '</div>';
				} );
		}

		/**
		 * Redraw the scale preview and the add button, without touching the inputs.
		 *
		 * Re-rendering the screen on every keystroke sends the cursor back to the start
		 * and 2100 gets typed as 0012.
		 */
		function updatePreview() {
			var frame = root.querySelector( '[data-horex-frame]' );

			if ( ! frame ) {
				return;
			}

			var product = current();
			var colour = bySlug( colours( product ), draft.kleur ) || { hex: '#383E42' };
			var gaas = bySlug( config.gaas, draft.gaas );
			var width = mm( draft.breedte );
			var height = mm( draft.hoogte );
			var scale = ( width && height ) ? Math.min( 232 / width, 172 / height ) : 0;

			frame.style.width = ( width && height ? Math.max( 42, width * scale ) : 152 ) + 'px';
			frame.style.height = ( width && height ? Math.max( 42, height * scale ) : 112 ) + 'px';
			frame.style.borderColor = 'gaas' === product.vulling ? colour.hex : 'transparent';

			root.querySelector( '[data-horex-fill]' ).style.cssText = fillStyle( product, colour.hex, gaas && gaas.fijnmazig );
			root.querySelector( '[data-horex-dim-w]' ).textContent = width ? width + ' mm' : '';
			root.querySelector( '[data-horex-dim-h]' ).textContent = height ? height + ' mm' : '';

			var rules = config.maten || {};
			var outside = ( width && ( width < rules.min || width > rules.max ) )
				|| ( height && ( height < rules.min || height > rules.max ) );

			root.querySelector( '[data-horex-warning]' ).innerHTML = outside
				? '<div class="horex-warn">' + esc( rules.waarschuwing ) + '</div>'
				: '';

			// Warn on an unusual size, never block it: a 6.2 metre veranda is a real
			// customer. Only a missing answer stops the customer moving on.
			var button = root.querySelector( '[data-horex-add]' );

			if ( button ) {
				button.disabled = ! ( draft.ruimte.trim() && width > 0 && height > 0 );
			}
		}

		/* Measuring help --------------------------------------------------- */

		/**
		 * Open the measuring help for the current product.
		 */
		function openHelp() {
			var product = current();
			var help = ( config.meethulp || {} )[ product.meethulp ] || {};

			var figure = help.video
				? '<video src="' + esc( help.video ) + '" controls playsinline preload="metadata"></video>'
				: '<div class="horex-drawing">' + ( help.diagram ? '<img src="' + esc( help.diagram ) + '" alt="" />' : ( help.tekening || '' ) ) + '</div>';

			var lines = ( help.stappen || [] ).map( function ( step, index ) {
				return '<li class="horex-step"><b>' + ( index + 1 ) + '</b><span>' + esc( step ) + '</span></li>';
			} ).join( '' );

			modal.innerHTML = '<div class="horex-modal__panel" role="dialog" aria-modal="true" aria-label="' + esc( help.titel || 'Hoe meet ik dit op?' ) + '">'
				+ '<button type="button" class="horex-modal__close" data-horex-close aria-label="Sluiten">&#10005;</button>'
				+ '<h3 class="horex-modal__title">' + esc( help.titel || 'Hoe meet ik dit op?' ) + '</h3>'
				+ figure
				+ '<ol class="horex-steps">' + lines + '</ol>'
				+ '<p class="horex-modal__foot">Komt u er niet uit? Vul een schatting in — wij meten alles zelf na op locatie voordat er iets geproduceerd wordt.</p>'
				+ '</div>';

			modal.hidden = false;
			document.body.style.overflow = 'hidden';
			modal.querySelector( '[data-horex-close]' ).focus();
		}

		/**
		 * Close the measuring help.
		 */
		function closeHelp() {
			modal.hidden = true;
			modal.innerHTML = '';
			document.body.style.overflow = '';
		}

		/* Rendering -------------------------------------------------------- */

		var views = {
			intro: viewIntro,
			product: viewProduct,
			uitvoering: viewUitvoering,
			kleur: viewKleur,
			gaas: viewGaas,
			maat: viewMaat,
			overzicht: viewOverzicht,
			gegevens: viewGegevens,
			klaar: viewKlaar
		};

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
					: ( { intro: '0%', overzicht: '88%', gegevens: '96%', klaar: '100%' }[ screen ] || '88%' );
			}

			stage.innerHTML = views[ screen ] ? views[ screen ]() : viewIntro();

			if ( 'maat' === screen ) {
				updatePreview();
			}
		}

		/**
		 * Move to a screen and bring it into view.
		 *
		 * @param {string} target Screen key.
		 */
		function go( target ) {
			screen = target;
			render();

			var top = root.getBoundingClientRect().top + window.pageYOffset;

			window.scrollTo( { top: top, behavior: 'smooth' } );
		}

		/**
		 * Record a choice, let it highlight, then move on by itself.
		 *
		 * @param {string} field Draft field.
		 * @param {string} value Chosen key.
		 * @param {string} target Screen to advance to.
		 */
		function choose( field, value, target ) {
			draft[ field ] = value;
			render();

			window.setTimeout( function () {
				go( target );
			}, 230 );
		}

		/* Events ----------------------------------------------------------- */

		root.addEventListener( 'click', function ( event ) {
			if ( ! ( event.target instanceof Element ) ) {
				return;
			}

			if ( event.target.closest( '[data-horex-start]' ) ) {
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

				render();

				var order = steps();

				window.setTimeout( function () {
					go( order[ 1 ] || 'maat' );
				}, 230 );

				return;
			}

			var variant = event.target.closest( '[data-horex-uitvoering]' );

			if ( variant ) {
				choose( 'uitvoering', variant.getAttribute( 'data-horex-uitvoering' ), next( 'uitvoering' ) );

				return;
			}

			var colour = event.target.closest( '[data-horex-kleur]' );

			if ( colour ) {
				choose( 'kleur', colour.getAttribute( 'data-horex-kleur' ), next( 'kleur' ) );

				return;
			}

			var gaas = event.target.closest( '[data-horex-gaas]' );

			if ( gaas ) {
				choose( 'gaas', gaas.getAttribute( 'data-horex-gaas' ), next( 'gaas' ) );

				return;
			}

			if ( event.target.closest( '[data-horex-meethulp]' ) ) {
				openHelp();

				return;
			}

			if ( event.target.closest( '[data-horex-close]' ) || event.target === modal ) {
				closeHelp();

				return;
			}

			var remove = event.target.closest( '[data-horex-remove]' );

			if ( remove ) {
				items.splice( parseInt( remove.getAttribute( 'data-horex-remove' ), 10 ), 1 );

				if ( items.length ) {
					render();
				} else {
					draft = blank();
					go( 'intro' );
				}

				return;
			}

			if ( event.target.closest( '[data-horex-again]' ) ) {
				// A fresh draft, but the carried colour and mesh come with it.
				draft = blank();
				go( 'product' );

				return;
			}

			if ( event.target.closest( '[data-horex-send]' ) ) {
				send();

				return;
			}

			if ( event.target.closest( '[data-horex-add]' ) ) {
				var chosen = current();

				items.push( {
					product: draft.product,
					uitvoering: draft.uitvoering,
					kleur: draft.kleur,
					// The mesh carries over between insect screens, but must not
					// follow onto a curtain or an awning, which have no mesh at all.
					gaas: 'horren' === chosen.type ? draft.gaas : null,
					ruimte: draft.ruimte.trim(),
					breedte: mm( draft.breedte ),
					hoogte: mm( draft.hoogte )
				} );

				// Almost everyone picks the same finish throughout the house.
				carried = {
					kleur: draft.kleur,
					gaas: 'horren' === chosen.type ? draft.gaas : carried.gaas,
					kleurType: chosen.kleurType
				};

				go( 'overzicht' );
			}
		} );

		// Measurement fields update the preview only — never the whole screen.
		root.addEventListener( 'input', function ( event ) {
			if ( ! ( event.target instanceof Element ) ) {
				return;
			}

			var contact = event.target.closest( '[data-horex-klant]' );

			if ( contact ) {
				customer[ contact.getAttribute( 'data-horex-klant' ) ] = contact.value;

				return;
			}

			if ( event.target.closest( '[data-horex-trap]' ) ) {
				trap = event.target.value;

				return;
			}

			var field = event.target.closest( '[data-horex-field]' );

			if ( ! field ) {
				return;
			}

			var name = field.getAttribute( 'data-horex-field' );

			if ( 'ruimte' !== name ) {
				var digits = field.value.replace( /\D/g, '' ).slice( 0, 5 );

				if ( digits !== field.value ) {
					var caret = field.selectionStart - ( field.value.length - digits.length );

					field.value = digits;
					field.setSelectionRange( caret, caret );
				}
			}

			draft[ name ] = field.value;
			updatePreview();
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && modal && ! modal.hidden ) {
				closeHelp();
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
