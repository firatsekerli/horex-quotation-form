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

	// Insect screen: five steps.
	await page.click( '[data-horex-product="plisse-horren"]' );
	await page.waitForTimeout( 500 );
	check( 'insect screen advances to step 2 of 5', await page.locator( '[data-horex-step]' ).innerText(), 'Stap 2 van 5' );
	check( 'the variant heading is shown', await page.locator( '.horex-title' ).first().innerText(), 'Welke uitvoering?' );
	check( 'the computed order is listed', await page.locator( '.horex-todo__steps li' ).allInnerTexts(), [ 'Product', 'Uitvoering', 'Kleur', 'Gaas', 'Maten' ] );

	// Back, then a curtain: three steps.
	await page.click( '[data-horex-go="product"]' );
	await page.waitForTimeout( 200 );
	await page.click( '[data-horex-product="wave-gordijnen"]' );
	await page.waitForTimeout( 500 );
	check( 'curtain advances to step 2 of 3', await page.locator( '[data-horex-step]' ).innerText(), 'Stap 2 van 3' );
	check( 'curtain skips variant and mesh', await page.locator( '.horex-todo__steps li' ).allInnerTexts(), [ 'Product', 'Kleur', 'Maten' ] );
	check( 'the fabric question is asked', await page.locator( '.horex-title' ).first().innerText(), 'Welke stofkleur?' );

	// Sun shading asks its own question.
	await page.click( '[data-horex-go="product"]' );
	await page.waitForTimeout( 200 );
	await page.click( '[data-horex-product="veranda-zonwering"]' );
	await page.waitForTimeout( 500 );
	check( 'the canvas question is asked', await page.locator( '.horex-title' ).first().innerText(), 'Welke doekkleur?' );

	// Progress bar actually moves.
	const width = await page.locator( '[data-horex-progress]' ).evaluate( el => el.style.width );
	check( 'progress bar has advanced', parseFloat( width ) > 12, true );

	check( 'no JavaScript errors', errors, [] );

	await page.click( '[data-horex-go="product"]' );
	await page.waitForTimeout( 300 );
	if ( process.env.HOREX_SHOT ) {
		await page.screenshot( { path: process.env.HOREX_SHOT, fullPage: true } );
	}

	await browser.close();
	console.log( failures ? `\n${ failures } check(s) failed` : '\nAll checks passed' );
	process.exit( failures ? 1 : 0 );
} )();
