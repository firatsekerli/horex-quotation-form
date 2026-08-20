/**
 * Drives the configurator in a real browser.
 *
 * The catalogue only reaches the screens through the browser, so a payload that fails
 * to arrive leaves a blank step that no PHP test can see. This walks the flow.
 *
 * Usage:
 *   php tests/browser/render-page.php > /tmp/horex-page.html
 *   node tests/browser/drive.js /tmp/horex-page.html
 */
const { chromium } = require( 'playwright' );

const PAGE = process.argv[ 2 ] || '/tmp/horex-page.html';
const CHROME = process.env.HOREX_CHROME || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';

let failures = 0;
function check( label, got, want ) {
	const ok = JSON.stringify( got ) === JSON.stringify( want );
	if ( ! ok ) failures++;
	console.log( `${ ok ? 'PASS' : 'FAIL' }  ${ label }` );
	if ( ! ok ) console.log( `      got:  ${ JSON.stringify( got ) }\n      want: ${ JSON.stringify( want ) }` );
}

/**
 * Walk the back button until the product step is reached.
 *
 * There is no jump-to-start link by design: the back chain is the way out, so this
 * exercises it.
 *
 * @param {Object} page Playwright page.
 */
async function backToProduct( page ) {
	for ( let i = 0; i < 6; i++ ) {
		if ( await page.locator( '.horex-pcard' ).count() ) return;
		await page.click( '.horex-back' );
		await page.waitForTimeout( 260 );
	}

	throw new Error( 'could not walk back to the product step' );
}

( async () => {
	const browser = await chromium.launch( { executablePath: CHROME } );
	const page = await browser.newPage( { viewport: { width: 900, height: 900 } } );

	const errors = [];
	page.on( 'pageerror', e => errors.push( String( e ) ) );
	page.on( 'console', m => { if ( m.type() === 'error' ) errors.push( m.text() ); } );

	await page.goto( PAGE.startsWith( '/' ) ? 'file://' + PAGE : PAGE );
	await page.waitForTimeout( 200 );

	// Intro
	check( 'intro renders its heading', await page.locator( '.horex-title' ).first().innerText(), 'Stel uw raamoplossing samen\nen ontvang een prijsopgave' );
	check( 'step counter is empty before starting', await page.locator( '[data-horex-step]' ).innerText(), '' );

	await page.click( '[data-horex-start]' );
	await page.waitForTimeout( 150 );

	// Product step — the screen that was blank.
	check( 'five product cards render', await page.locator( '.horex-pcard' ).count(), 5 );
	check( 'cards carry their drawings', await page.locator( '.horex-pcard .horex-shot svg' ).count(), 5 );
	check( 'first card names the product', await page.locator( '.horex-pcard .horex-pmeta__t' ).first().innerText(), 'Plissé horren' );
	check( 'first card carries its subtitle', await page.locator( '.horex-pcard .horex-pmeta__d' ).first().innerText(), 'Schuift open als een harmonica' );
	check( 'step counter shows step one only', await page.locator( '[data-horex-step]' ).innerText(), 'Stap 1' );

	const box = await page.locator( '.horex-pcard' ).first().boundingBox();
	check( 'cards have real size on screen', box.width > 100 && box.height > 100, true );

	// Insect screen: the full five steps, each screen real.
	await page.click( '[data-horex-product="plisse-horren"]' );
	await page.waitForTimeout( 500 );
	check( 'insect screen advances to step 2 of 5', await page.locator( '[data-horex-step]' ).innerText(), 'Stap 2 van 5' );
	check( 'the variant heading is shown', await page.locator( '.horex-title' ).first().innerText(), 'Welke uitvoering?' );
	check( 'variants come from the catalogue', await page.locator( '.horex-card__t' ).allInnerTexts(), [ 'Plissé hordeur', 'Plissé horraam', 'DUO — hor + gordijn' ] );
	check( 'variants carry their subtitles', await page.locator( '.horex-card__d' ).first().innerText(), 'Achterdeur, tuindeur of schuifpui' );

	// Tapping advances on its own — no next button.
	await page.click( '[data-horex-uitvoering="plisse-hordeur"]' );
	await page.waitForTimeout( 500 );
	check( 'auto-advance reaches the colour step', await page.locator( '[data-horex-step]' ).innerText(), 'Stap 3 van 5' );
	check( 'the frame question is asked', await page.locator( '.horex-title' ).first().innerText(), 'Welke kleur frame?' );
	check( 'six frame colours render', await page.locator( '.horex-swatch' ).count(), 6 );

	await page.click( '[data-horex-kleur="antraciet"]' );
	await page.waitForTimeout( 500 );
	check( 'auto-advance reaches the mesh step', await page.locator( '[data-horex-step]' ).innerText(), 'Stap 4 van 5' );
	check( 'both mesh types render', await page.locator( '.horex-card' ).count(), 2 );

	await page.click( '[data-horex-gaas="anti-pollen-gaas"]' );
	await page.waitForTimeout( 500 );
	check( 'auto-advance reaches the measurements', await page.locator( '[data-horex-step]' ).innerText(), 'Stap 5 van 5' );
	check( 'measurement fields are in millimetres', await page.locator( '.horex-unit b' ).first().innerText(), 'mm' );
	check( 'the add button starts disabled', await page.locator( '[data-horex-add]' ).isDisabled(), true );

	// The cursor must not jump: 2100 typed left to right must stay 2100.
	await page.fill( '#horex-ruimte', 'Woonkamer schuifpui' );
	await page.click( '#horex-breedte' );
	await page.type( '#horex-breedte', '2100', { delay: 30 } );
	check( 'typing a width does not reorder the digits', await page.inputValue( '#horex-breedte' ), '2100' );

	await page.click( '#horex-hoogte' );
	await page.type( '#horex-hoogte', '2400', { delay: 30 } );
	check( 'typing a height does not reorder the digits', await page.inputValue( '#horex-hoogte' ), '2400' );
	check( 'letters are rejected in a measurement', await ( async () => {
		await page.fill( '#horex-breedte', '' );
		await page.type( '#horex-breedte', '12a0b0' );
		const v = await page.inputValue( '#horex-breedte' );
		await page.fill( '#horex-breedte', '2100' );
		return v;
	} )(), '1200' );

	check( 'the add button is now enabled', await page.locator( '[data-horex-add]' ).isDisabled(), false );
	check( 'the room name survived typing', await page.inputValue( '#horex-ruimte' ), 'Woonkamer schuifpui' );

	// The preview scales with the numbers. It transitions over .2s, so let it settle
	// before measuring or the assertion races the animation.
	await page.waitForTimeout( 400 );
	const frame = await page.locator( '[data-horex-frame]' ).boundingBox();
	check( 'the preview is taller than it is wide, as entered', frame.height > frame.width, true );
	check( 'the width is labelled', await page.locator( '[data-horex-dim-w]' ).innerText(), '2100 mm' );
	check( 'the height is labelled', await page.locator( '[data-horex-dim-h]' ).innerText(), '2400 mm' );

	// Out of range warns but never blocks.
	await page.fill( '#horex-breedte', '6200' );
	await page.waitForTimeout( 100 );
	check( 'an oversize measurement warns', await page.locator( '.horex-warn' ).count(), 1 );
	check( 'an oversize measurement is still accepted', await page.locator( '[data-horex-add]' ).isDisabled(), false );
	await page.fill( '#horex-breedte', '120' );
	await page.waitForTimeout( 100 );
	check( 'an undersize measurement warns', await page.locator( '.horex-warn' ).count(), 1 );
	check( 'an undersize measurement is still accepted', await page.locator( '[data-horex-add]' ).isDisabled(), false );
	await page.fill( '#horex-breedte', '2100' );
	await page.waitForTimeout( 100 );
	check( 'an in-range measurement does not warn', await page.locator( '.horex-warn' ).count(), 0 );

	// Measuring help.
	await page.click( '[data-horex-meethulp]' );
	await page.waitForTimeout( 300 );
	check( 'the measuring help opens', await page.locator( '.horex-modal__panel' ).isVisible(), true );
	check( 'it shows the numbered steps', await page.locator( '.horex-step' ).count(), 5 );
	check( 'it shows the shipped diagram', await page.locator( '.horex-drawing svg' ).count(), 1 );
	await page.keyboard.press( 'Escape' );
	await page.waitForTimeout( 200 );
	check( 'escape closes it', await page.locator( '[data-horex-modal]' ).isHidden(), true );

	// Back preserves what was already answered.
	await page.click( '[data-horex-go="gaas"]' );
	await page.waitForTimeout( 300 );
	check( 'the previous mesh choice is still marked', await page.locator( '.horex-card.is-on' ).count(), 1 );
	await page.click( '[data-horex-gaas="anti-pollen-gaas"]' );
	await page.waitForTimeout( 500 );
	check( 'the measurements survived the detour', await page.inputValue( '#horex-breedte' ), '2100' );
	check( 'the room name survived the detour', await page.inputValue( '#horex-ruimte' ), 'Woonkamer schuifpui' );

	// Back to the start, then a curtain: three steps.
	await backToProduct( page );
	check( 'the back chain reaches the product step', await page.locator( '.horex-pcard' ).count(), 5 );
	await page.click( '[data-horex-product="wave-gordijnen"]' );
	await page.waitForTimeout( 500 );
	check( 'curtain advances to step 2 of 3', await page.locator( '[data-horex-step]' ).innerText(), 'Stap 2 van 3' );
	check( 'curtain skips variant and lands on colour', await page.locator( '.horex-title' ).first().innerText(), 'Welke stofkleur?' );

	await page.click( '.horex-swatch >> nth=0' );
	await page.waitForTimeout( 500 );
	check( 'curtain skips mesh and lands on measurements', await page.locator( '[data-horex-step]' ).innerText(), 'Stap 3 van 3' );
	check( 'a curtain preview has no frame border', await page.locator( '[data-horex-frame]' ).evaluate( el => el.style.borderWidth ), '0px' );

	// Sun shading asks its own question.
	await backToProduct( page );
	await page.click( '[data-horex-product="veranda-zonwering"]' );
	await page.waitForTimeout( 500 );
	check( 'the canvas question is asked', await page.locator( '.horex-title' ).first().innerText(), 'Welke doekkleur?' );

	// Progress bar actually moves.
	const width = await page.locator( '[data-horex-progress]' ).evaluate( el => el.style.width );
	check( 'progress bar has advanced', parseFloat( width ) > 12, true );

	/* The loop: add one, add another, carry the finish over. */
	await backToProduct( page );
	await page.click( '[data-horex-product="plisse-horren"]' ); await page.waitForTimeout( 420 );
	await page.click( '[data-horex-uitvoering="plisse-hordeur"]' ); await page.waitForTimeout( 420 );
	await page.click( '[data-horex-kleur="antraciet"]' ); await page.waitForTimeout( 420 );
	await page.click( '[data-horex-gaas="anti-pollen-gaas"]' ); await page.waitForTimeout( 420 );
	await page.fill( '#horex-ruimte', 'Woonkamer schuifpui' );
	await page.fill( '#horex-breedte', '2100' );
	await page.fill( '#horex-hoogte', '2400' );
	await page.waitForTimeout( 150 );
	await page.click( '[data-horex-add]' );
	await page.waitForTimeout( 400 );

	check( 'the summary lists the product', await page.locator( '.horex-item' ).count(), 1 );
	check( 'the summary names the room', await page.locator( '.horex-item__t' ).first().innerText(), 'Woonkamer schuifpui' );
	check( 'the summary spells out the choices', await page.locator( '.horex-item__d' ).first().innerText(), 'Plissé hordeur · Antraciet · Anti-pollen gaas' );
	check( 'the summary shows the measurements', await page.locator( '.horex-item__m' ).first().innerText(), '2100 × 2400 mm' );
	check( 'the bar counts what is added', await page.locator( '[data-horex-step]' ).innerText(), '1 product toegevoegd' );

	// Adding another of the same type pre-selects the finish.
	await page.click( '[data-horex-again]' );
	await page.waitForTimeout( 400 );
	await page.click( '[data-horex-product="inzet-horren"]' ); await page.waitForTimeout( 420 );
	await page.click( '[data-horex-uitvoering="inzet-horraam"]' ); await page.waitForTimeout( 420 );
	check( 'the colour carried over', await page.locator( '.horex-swatch.is-on .horex-swatch__t' ).innerText(), 'Antraciet' );
	await page.click( '[data-horex-kleur="antraciet"]' ); await page.waitForTimeout( 420 );
	check( 'the mesh carried over too', await page.locator( '.horex-card.is-on .horex-card__t' ).innerText(), 'Anti-pollen gaas' );
	await page.click( '[data-horex-gaas="anti-pollen-gaas"]' ); await page.waitForTimeout( 420 );
	await page.fill( '#horex-ruimte', 'Slaapkamer voorzijde' );
	await page.fill( '#horex-breedte', '900' );
	await page.fill( '#horex-hoogte', '1200' );
	await page.waitForTimeout( 150 );
	await page.click( '[data-horex-add]' );
	await page.waitForTimeout( 400 );

	check( 'both products are listed', await page.locator( '.horex-item' ).count(), 2 );
	check( 'the bar pluralises', await page.locator( '[data-horex-step]' ).innerText(), '2 producten toegevoegd' );

	// A curtain does not inherit a frame colour.
	await page.click( '[data-horex-again]' );
	await page.waitForTimeout( 400 );
	await page.click( '[data-horex-product="wave-gordijnen"]' ); await page.waitForTimeout( 420 );
	check( 'a different palette does not carry over', await page.locator( '.horex-swatch.is-on' ).count(), 0 );

	// Removing one leaves the other.
	await backToProduct( page );
	await page.click( '.horex-back' );
	await page.waitForTimeout( 300 );
	check( 'back from the product step returns to the summary', await page.locator( '.horex-item' ).count(), 2 );
	await page.click( '[data-horex-remove="0"]' );
	await page.waitForTimeout( 300 );
	check( 'removing one leaves the other', await page.locator( '.horex-item' ).count(), 1 );
	check( 'the right one was removed', await page.locator( '.horex-item__t' ).first().innerText(), 'Slaapkamer voorzijde' );

	check( 'no JavaScript errors', errors, [] );

	if ( process.env.HOREX_SHOT ) {
		await page.screenshot( { path: process.env.HOREX_SHOT, fullPage: true } );
	}

	await browser.close();
	console.log( failures ? `\n${ failures } check(s) failed` : '\nAll checks passed' );
	process.exit( failures ? 1 : 0 );
} )();
