<?php
/**
 * User defined CSS for reservation table.
 *
 * @package Iconic_WDS
 */

?>
<style>
	<?php global $jckwds; ?>

	<?php // Default Cells. ?>
	body table.jckwds-reserve { background: <?php echo esc_attr( $jckwds->settings['reservations_styling_reservebgcol'] ); ?>; }
	body table.jckwds-reserve td { border-color:  <?php echo esc_attr( $jckwds->settings['reservations_styling_reservebordercol'] ); ?>; background: <?php echo esc_attr( $jckwds->settings['reservations_styling_reservebgcol'] ); ?>; }
	body table.jckwds-reserve tbody td a { color:  <?php echo esc_attr( $jckwds->settings['reservations_styling_reserveiconcol'] ); ?>; }
	body table.jckwds-reserve tbody td a:hover { color:  <?php echo esc_attr( $jckwds->settings['reservations_styling_reserveiconhovcol'] ); ?>; }

	<?php // Header Cells. ?>
	body table.jckwds-reserve tr th { background: <?php echo esc_attr( $jckwds->settings['reservations_styling_thbgcol'] ); ?>; border-color: <?php echo esc_attr( $jckwds->settings['reservations_styling_thbordercol'] ); ?>; color: <?php echo esc_attr( $jckwds->settings['reservations_styling_thfontcol'] ); ?>; }
	body table.jckwds-reserve tr th { background: <?php echo esc_attr( $jckwds->settings['reservations_styling_thbgcol'] ); ?>; border-color: <?php echo esc_attr( $jckwds->settings['reservations_styling_thbordercol'] ); ?>; color: <?php echo esc_attr( $jckwds->settings['reservations_styling_thfontcol'] ); ?>; }
	body table.jckwds-reserve thead tr th .jckwds-prevday, body table.jckwds-reserve thead tr th .jckwds-nextday { color: <?php echo esc_attr( $jckwds->settings['reservations_styling_tharrcol'] ); ?>; }
	body table.jckwds-reserve thead tr th .jckwds-prevday:hover, body table.jckwds-reserve thead tr th .jckwds-nextday:hover { color: <?php echo esc_attr( $jckwds->settings['reservations_styling_tharrhovcol'] ); ?>; }

	<?php // Unavailable Cells. ?>
	body table.jckwds-reserve tbody td.jckwds_full { background: <?php echo esc_attr( $jckwds->settings['reservations_styling_unavailcell'] ); ?>; }

	<?php // Reserved Cells. ?>
	body table.jckwds-reserve tbody td.jckwds-reserved {  background: <?php echo esc_attr( $jckwds->settings['reservations_styling_reservedbgcol'] ); ?>; color: <?php echo esc_attr( $jckwds->settings['reservations_styling_reservediconcol'] ); ?>; border-color: <?php echo esc_attr( $jckwds->settings['reservations_styling_reservebordercol'] ); ?> }
	body table.jckwds-reserve tbody td.jckwds-reserved a { color: <?php echo esc_attr( $jckwds->settings['reservations_styling_reservediconcol'] ); ?>; }
	body table.jckwds-reserve tbody td.jckwds-reserved a:visited { color: <?php echo esc_attr( $jckwds->settings['reservations_styling_reservediconcol'] ); ?>; }

	<?php // Loading Icon. ?>
	body div.jckwds-reserve-wrap .jckwds_loading { color: <?php echo esc_attr( $jckwds->settings['reservations_styling_loadingiconcol'] ); ?>; }

	<?php // Lock Icon. ?>
	body div.jckwds-reserve-wrap .jckwds-icn-lock { color: <?php echo esc_attr( $jckwds->settings['reservations_styling_lockiconcol'] ); ?>; }
</style>
