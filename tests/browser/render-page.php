<?php
/**
 * Render the shortcode into a standalone page so a browser can drive it.
 *
 * Wrapped in a stand-in header and footer, because that is how it ships: inside a
 * page builder's layout rather than as a page of its own.
 *
 * Usage:  php tests/browser/render-page.php > page.html
 *
 * @package Horex
 */

$root = dirname( __DIR__, 2 );

require_once $root . '/tests/bootstrap.php';

horex_maybe_seed();

$body = horex_render_shortcode( array() );
$css  = file_get_contents( $root . '/assets/css/offerte.css' );
$js   = file_get_contents( $root . '/assets/js/offerte.js' );

echo '<!doctype html><html lang="nl"><head><meta charset="utf-8">'
	. '<meta name="viewport" content="width=device-width,initial-scale=1">'
	. '<title>Hor-Ex</title><style>body{margin:0;font-family:sans-serif}' . "\n" . $css . '</style></head><body>'
	. '<header style="height:60px;background:#eee;padding:8px">Breakdance header</header>'
	. $body
	. '<footer style="height:120px;background:#eee;padding:8px">Breakdance footer</footer>'
	. '<script>' . "\n" . $js . "\n" . '</script></body></html>';
