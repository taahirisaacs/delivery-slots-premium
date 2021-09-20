<?php
/**
 * The template for the reservation table.
 *
 * @package Iconic_WDS
 */

?>
<div class="jckwds-reserve-wrap">
	<?php if ( $reservation_table_data ) { ?>
	<table class="jckwds-reserve">
		<thead>
		<tr>
			<th class="alwaysVis">
				<a href="#" class="jckwds-prevday"><i class="jckwds-icn-left"></i></a>
				<a href="#" class="jckwds-nextday"><i class="jckwds-icn-right"></i></a>
			</th>
			<?php
			if ( ! empty( $reservation_table_data['headers'] ) ) {
				$i = 0;
				foreach ( $reservation_table_data['headers'] as $header_data ) {
					?>

					<th class='<?php echo esc_attr( $header_data['classes'] ); ?>'><?php echo esc_html( $header_data['cell'] ); ?></th>

					<?php
					$i ++;
				}
			}
			?>
		</tr>
		</thead>
		<tbody>
		<?php
		if ( ! empty( $reservation_table_data['body'] ) ) {
			$i = 0;
			foreach ( $reservation_table_data['body'] as $rows ) {
				?>
				<tr>
				<?php
				foreach ( $rows as $row ) {
					?>
					<<?php echo esc_attr( $row['cell_type'] ); ?>
					class='<?php echo esc_attr( $row['classes'] ); ?>'
					<?php
					if ( ! empty( $row['attributes'] ) && is_array( $row['attributes'] ) ) {
						foreach ( $row['attributes'] as $attribute_key => $attribute_value ) {
							echo sprintf( ' %s="%s" ', esc_attr( $attribute_key ), esc_attr( $attribute_value ) );
						}
					}
					?>
					>
					<?php echo wp_kses_post( $row['cell'] ); ?>
					</<?php echo esc_attr( $row['cell_type'] ); ?>>
					<?php
				}
				?>
				</tr>
				<?php
				$i ++;
			}
		}
		?>
		</tbody>
	</table>
	<?php } else { ?>
		<p><?php echo wp_kses_post( __( 'Sorry, there are currently no slots available. Please try again later.', 'iconic-wds' ) ); ?></p>
	<?php } ?>
</div>
