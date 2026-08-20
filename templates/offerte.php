<?php
/**
 * Shortcode markup: the shell the step engine renders into.
 *
 * The site header and footer come from the theme or page builder; this is only the
 * configurator itself, so it drops into a page builder column without fighting it.
 *
 * @package Horex
 *
 * @var bool $sticky Whether the bar sticks to the top while scrolling.
 * @var int  $offset Pixels to leave clear for a sticky site header.
 */

defined( 'ABSPATH' ) || exit;

$horex_logo = horex_attachment_url( (int) horex_get_setting( 'logo_licht' ), 'medium' );
?>
<div
	class="horex<?php echo $sticky ? ' horex--sticky' : ''; ?>"
	data-horex
	<?php echo $offset ? 'style="--horex-offset:' . esc_attr( (string) $offset ) . 'px"' : ''; ?>
>
	<div class="horex-bar">
		<div class="horex-bar__inner">
			<?php if ( $horex_logo ) : ?>
				<img class="horex-bar__logo" src="<?php echo esc_url( $horex_logo ); ?>" alt="Hor-Ex" />
			<?php endif; ?>
			<span class="horex-bar__step" data-horex-step aria-live="polite"></span>
		</div>
		<div class="horex-bar__track">
			<i class="horex-bar__fill" data-horex-progress></i>
		</div>
	</div>

	<div class="horex-stage" data-horex-stage>
		<noscript>
			<div class="horex-screen">
				<h2 class="horex-title">Offerte aanvragen</h2>
				<p class="horex-sub">
					Voor deze configurator is JavaScript nodig. Zet het aan in uw browser, of
					neem gerust telefonisch contact met ons op — dan nemen we uw maten samen door.
				</p>
			</div>
		</noscript>
	</div>

	<div class="horex-modal" data-horex-modal hidden></div>
</div>
