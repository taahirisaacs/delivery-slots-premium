<?php
/**
 * Output a listing of all reservations, ordered by date.
 *
 * @package Iconic_WDS
 */

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_tab = ( isset( $_GET['tab'] ) ) ? filter_input( INPUT_GET, 'tab' ) : 'upcoming-deliveries';
?>

<div class="wrap">

	<h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=jckwds-deliveries' ) ); ?>" class="nav-tab <?php echo 'upcoming-deliveries' === $current_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Upcoming Orders', 'jckwds' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=jckwds-deliveries&tab=currently-reserved' ) ); ?>" class="nav-tab <?php echo 'currently-reserved' === $current_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Currently Reserved', 'jckwds' ); ?></a>
	</h2>

	<?php
	if ( 'upcoming-deliveries' === $current_tab ) {
		$upcoming_deliveries = Iconic_WDS_Reservations::get_reservations( 1 );
		$this->reservations_layout( $upcoming_deliveries );
	}
	?>

	<?php
	if ( 'currently-reserved' === $current_tab ) {
		$upcoming_reservations = Iconic_WDS_Reservations::get_reservations( 0 );
		$this->reservations_layout( $upcoming_reservations );
	}
	?>

</div>
