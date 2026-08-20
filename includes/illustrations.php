<?php
/**
 * The drawings shipped with the plugin.
 *
 * Ported from the prototype in reference/. They stand in for photographs Hor-Ex has
 * not uploaded yet, and stay behind a photo that fails to load, so a product card is
 * never an empty grey box.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Colours the drawings are built from.
 *
 * @return array
 */
function horex_drawing_palette() {
	return array(
		'ink'  => '#1E1E1E',
		'geel' => '#FEC129',
		'bg'   => '#F3EEE2',
		'lijn' => '#B9AE96',
		'maat' => '#C99310',
	);
}

/**
 * Wrap drawing contents in the shared 200x150 canvas.
 *
 * @param string $inner SVG fragment.
 * @return string
 */
function horex_drawing_wrap( $inner ) {
	$c = horex_drawing_palette();

	return '<svg viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
		. '<rect width="200" height="150" fill="' . $c['bg'] . '"/>' . $inner . '</svg>';
}

/**
 * Repeat a fragment, substituting %d-style placeholders with the iteration values.
 *
 * @param int      $count   Number of repetitions.
 * @param callable $builder Receives the index, returns a fragment.
 * @return string
 */
function horex_drawing_repeat( $count, callable $builder ) {
	$out = '';

	for ( $i = 0; $i < $count; $i++ ) {
		$out .= $builder( $i );
	}

	return $out;
}

/**
 * One product drawing by key, or an empty string when the key is unknown.
 *
 * @param string $key Illustration key.
 * @return string
 */
function horex_drawing( $key ) {
	$drawings = horex_drawings();

	return isset( $drawings[ $key ] ) ? $drawings[ $key ] : '';
}

/**
 * Every product drawing, keyed as the settings select expects.
 *
 * @return array
 */
function horex_drawings() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$c = horex_drawing_palette();

	$cache = array(
		'plisse-hor'        => horex_drawing_wrap(
			'<rect x="34" y="22" width="132" height="108" fill="#fff" stroke="' . $c['ink'] . '" stroke-width="3"/>'
			. '<rect x="34" y="22" width="66" height="108" fill="' . $c['geel'] . '" opacity=".22"/>'
			. horex_drawing_repeat(
				11,
				function ( $i ) use ( $c ) {
					$x = 38 + $i * 5.6;

					return sprintf(
						'<path d="M%1$s 24 L%2$s 76 L%1$s 128" fill="none" stroke="%3$s" stroke-width="1.6"/>',
						round( $x, 2 ),
						round( $x + 3, 2 ),
						$c['ink']
					);
				}
			)
			. '<line x1="100" y1="22" x2="100" y2="130" stroke="' . $c['ink'] . '" stroke-width="4"/>'
			. '<line x1="34" y1="130" x2="166" y2="130" stroke="' . $c['ink'] . '" stroke-width="4"/>'
		),

		'inzet-hor'         => horex_drawing_wrap(
			'<rect x="30" y="18" width="140" height="114" fill="#fff" stroke="' . $c['ink'] . '" stroke-width="3"/>'
			. '<rect x="44" y="32" width="112" height="86" fill="none" stroke="' . $c['ink'] . '" stroke-width="3"/>'
			. '<rect x="47" y="35" width="106" height="80" fill="' . $c['geel'] . '" opacity=".18"/>'
			. horex_drawing_repeat(
				14,
				function ( $i ) use ( $c ) {
					$x = round( 47 + $i * 7.6, 2 );

					return sprintf( '<line x1="%1$s" y1="35" x2="%1$s" y2="115" stroke="%2$s" stroke-width="1"/>', $x, $c['lijn'] );
				}
			)
			. horex_drawing_repeat(
				10,
				function ( $i ) use ( $c ) {
					$y = round( 35 + $i * 8.5, 2 );

					return sprintf( '<line x1="47" y1="%1$s" x2="153" y2="%1$s" stroke="%2$s" stroke-width="1"/>', $y, $c['lijn'] );
				}
			)
			. '<circle cx="150" cy="75" r="4.5" fill="' . $c['geel'] . '" stroke="' . $c['ink'] . '" stroke-width="2"/>'
		),

		'plisse-gordijn'    => horex_drawing_wrap(
			'<rect x="36" y="20" width="128" height="112" fill="#fff" stroke="' . $c['ink'] . '" stroke-width="3"/>'
			. '<rect x="39" y="23" width="122" height="52" fill="' . $c['geel'] . '" opacity=".3"/>'
			. horex_drawing_repeat(
				8,
				function ( $i ) use ( $c ) {
					$y = round( 26 + $i * 6.5, 2 );

					return sprintf( '<line x1="39" y1="%1$s" x2="161" y2="%1$s" stroke="%2$s" stroke-width="1.7"/>', $y, $c['ink'] );
				}
			)
			. '<line x1="39" y1="75" x2="161" y2="75" stroke="' . $c['ink'] . '" stroke-width="4"/>'
			. '<line x1="100" y1="75" x2="100" y2="84" stroke="' . $c['ink'] . '" stroke-width="2.5"/>'
		),

		'wave-gordijn'      => horex_drawing_wrap(
			'<rect x="30" y="16" width="140" height="118" fill="#fff" stroke="' . $c['ink'] . '" stroke-width="3"/>'
			. '<line x1="24" y1="20" x2="176" y2="20" stroke="' . $c['ink'] . '" stroke-width="4"/>'
			. '<path d="M32 22 C42 46 22 74 34 100 C44 122 30 128 34 132 L92 132 C88 122 100 108 92 84 C84 58 100 44 90 22 Z" fill="' . $c['geel'] . '" opacity=".28" stroke="' . $c['ink'] . '" stroke-width="2.4"/>'
			. '<path d="M50 22 C60 48 42 76 54 104 C60 118 52 126 54 132" fill="none" stroke="' . $c['ink'] . '" stroke-width="1.8"/>'
			. '<path d="M70 22 C80 48 62 76 74 104 C80 118 72 126 74 132" fill="none" stroke="' . $c['ink'] . '" stroke-width="1.8"/>'
		),

		'veranda-zonwering' => horex_drawing_wrap(
			'<path d="M18 44 L100 14 L182 44" fill="none" stroke="' . $c['ink'] . '" stroke-width="4" stroke-linejoin="round"/>'
			. '<line x1="18" y1="44" x2="182" y2="44" stroke="' . $c['ink'] . '" stroke-width="4"/>'
			. '<rect x="46" y="48" width="108" height="52" fill="' . $c['geel'] . '" opacity=".34" stroke="' . $c['ink'] . '" stroke-width="2.4"/>'
			. horex_drawing_repeat(
				7,
				function ( $i ) use ( $c ) {
					$y = round( 54 + $i * 7, 2 );

					return sprintf( '<line x1="46" y1="%1$s" x2="154" y2="%1$s" stroke="%2$s" stroke-width="1" opacity=".5"/>', $y, $c['ink'] );
				}
			)
			. '<line x1="46" y1="100" x2="154" y2="100" stroke="' . $c['ink'] . '" stroke-width="4"/>'
			. '<line x1="46" y1="44" x2="46" y2="134" stroke="' . $c['ink'] . '" stroke-width="3"/>'
			. '<line x1="154" y1="44" x2="154" y2="134" stroke="' . $c['ink'] . '" stroke-width="3"/>'
		),
	);

	return $cache;
}

/**
 * Arrow markers shared by the measuring diagrams.
 *
 * @return string
 */
function horex_diagram_markers() {
	$c = horex_drawing_palette();

	return '<defs>'
		. '<marker id="horex-arrow-end" markerWidth="7" markerHeight="7" refX="6" refY="3.5" orient="auto">'
		. '<path d="M7 3.5 L0 0 L0 7 Z" fill="' . $c['ink'] . '"/></marker>'
		. '<marker id="horex-arrow-start" markerWidth="7" markerHeight="7" refX="1" refY="3.5" orient="auto">'
		. '<path d="M0 3.5 L7 0 L7 7 Z" fill="' . $c['ink'] . '"/></marker>'
		. '</defs>';
}

/**
 * A dimension line with an arrowhead at each end.
 *
 * @param float  $x1     Start x.
 * @param float  $y1     Start y.
 * @param float  $x2     End x.
 * @param float  $y2     End y.
 * @param string $colour Stroke colour.
 * @return string
 */
function horex_diagram_rule( $x1, $y1, $x2, $y2, $colour ) {
	return sprintf(
		'<line x1="%1$s" y1="%2$s" x2="%3$s" y2="%4$s" stroke="%5$s" stroke-width="2" marker-start="url(#horex-arrow-start)" marker-end="url(#horex-arrow-end)"/>',
		$x1,
		$y1,
		$x2,
		$y2,
		$colour
	);
}

/**
 * The measuring diagram for a measuring-help group.
 *
 * @param string $key Either horren or gordijn.
 * @return string
 */
function horex_diagram( $key ) {
	$c    = horex_drawing_palette();
	$font = 'font-family="DM Sans, sans-serif"';

	if ( 'horren' === $key ) {
		return '<svg viewBox="0 0 340 250" xmlns="http://www.w3.org/2000/svg" role="img">' . horex_diagram_markers()
			. '<rect width="340" height="250" fill="#FCF8EE"/>'
			. '<rect x="26" y="22" width="288" height="206" fill="#E9E2D2"/>'
			. '<rect x="54" y="46" width="232" height="158" fill="' . $c['ink'] . '"/>'
			. '<rect x="66" y="58" width="208" height="134" fill="#fff"/>'
			. horex_diagram_rule( 66, 76, 274, 76, $c['ink'] )
			. horex_diagram_rule( 66, 125, 274, 125, $c['maat'] )
			. horex_diagram_rule( 66, 174, 274, 174, $c['ink'] )
			. horex_diagram_rule( 96, 58, 96, 192, $c['ink'] )
			. horex_diagram_rule( 170, 58, 170, 192, $c['maat'] )
			. horex_diagram_rule( 244, 58, 244, 192, $c['ink'] )
			. '<text x="170" y="20" text-anchor="middle" ' . $font . ' font-size="13" font-weight="600" fill="' . $c['ink'] . '">Binnenmaat kozijn</text>'
			. '<text x="290" y="129" ' . $font . ' font-size="11" fill="' . $c['ink'] . '">3&#215;</text>'
			. '<text x="170" y="222" text-anchor="middle" ' . $font . ' font-size="11" fill="' . $c['ink'] . '">meet breedte &#233;n hoogte op drie plekken</text>'
			. '</svg>';
	}

	return '<svg viewBox="0 0 340 250" xmlns="http://www.w3.org/2000/svg" role="img">' . horex_diagram_markers()
		. '<rect width="340" height="250" fill="#FCF8EE"/>'
		. '<rect x="30" y="40" width="280" height="10" fill="' . $c['ink'] . '"/>'
		. '<rect x="86" y="66" width="168" height="112" fill="#fff" stroke="' . $c['ink'] . '" stroke-width="3"/>'
		. '<line x1="170" y1="66" x2="170" y2="178" stroke="' . $c['ink'] . '" stroke-width="2"/>'
		. '<line x1="20" y1="212" x2="320" y2="212" stroke="' . $c['ink'] . '" stroke-width="3"/>'
		. horex_diagram_rule( 30, 30, 310, 30, $c['ink'] )
		. horex_diagram_rule( 300, 52, 300, 210, $c['maat'] )
		. '<text x="170" y="22" text-anchor="middle" ' . $font . ' font-size="13" font-weight="600" fill="' . $c['ink'] . '">Breedte van de rail</text>'
		. '<text x="286" y="134" text-anchor="end" ' . $font . ' font-size="12" font-weight="600" fill="' . $c['ink'] . '">Hoogte</text>'
		. '<text x="170" y="234" text-anchor="middle" ' . $font . ' font-size="11" fill="' . $c['ink'] . '">hoogte loopt tot de vloer of tot de vensterbank</text>'
		. '</svg>';
}
