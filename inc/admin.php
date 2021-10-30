<?php
function reading_timer_register_setting(){
	add_settings_section(
		'reading_timer_settings_section_id', // section ID
		'', // title (if needed)
		'', // callback function (if needed)
		'reading_timer-slug' // page slug
	);

    // Timer in minute
    register_setting(
		'reading_timer_settings', // settings group name
		'reading_timer_countdown', // option name
		'sanitize_text_field' // sanitization function
	);

	add_settings_field(
		'reading_timer_countdown',
		'Timer',
		'reading_timer_countdown_html', // function which prints the field
		'reading_timer-slug', // page slug
		'reading_timer_settings_section_id', // section ID
	);
}
add_action( 'admin_init',  'reading_timer_register_setting' );


function reading_timer_countdown_html(){
	$reading_timer_countdown = get_option( 'reading_timer_countdown' );

	printf(
		'<input type="text" id="reading_timer_countdown" name="reading_timer_countdown" value="%s" />
        <p class="description">Only number. In minute.</p>',
		esc_attr( $reading_timer_countdown )
	);
}


function add_reading_timer_admin_menu() {
	//add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	add_menu_page(  
		__( "Reading Timer" ), 
		__( "Reading Timer" ), 
		'administrator', 
		'reading-timer', 
		'', 
		'dashicons-clock', 
		26 
	);

	//add_submenu_page( '$parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	add_submenu_page( 
		'reading-timer', 
		__( 'Settings' ), 
		__( 'Settings' ), 
		'administrator', 
		'reading-timer', 
		'display_reading_timer_setting'
	);

	add_submenu_page( 
		'reading-timer', 
		__( 'Data' ), 
		__( 'Data' ), 
		'administrator', 
		'reading-timer-data', 
		'display_reading_timer_data'
	);
}
add_action( 'admin_menu', 'add_reading_timer_admin_menu', 9 );

function display_reading_timer_setting() { ?>
	<div class="wrap">
	    <h1><?php _e( "Reading Timer Setting", "reading_timer" ); ?></h1>
	    <form method="post" action="options.php">
			
            <?php settings_fields( 'reading_timer_settings' ); // settings group name ?>
            <?php do_settings_sections( 'reading_timer-slug' ); // just a page slug ?>
            <?php submit_button(); ?>

        </form>
    </div>
<?php }

function display_reading_timer_data() { 
	global $wpdb;

	$items_per_page = 2;
	$page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
	$offset = ( $page * $items_per_page ) - $items_per_page;
	$table_name = $wpdb->prefix . 'reading_timer';

	$query = 'SELECT * FROM ' . $table_name;

	$total_query = "SELECT COUNT(1) FROM (${query}) AS combined_table";
	$total = $wpdb->get_var( $total_query );

	$results = $wpdb->get_results( $query.' ORDER BY create_at DESC LIMIT '. $offset.', '. $items_per_page, OBJECT );
	?>

	<div class="wrap">
	    <h1><?php _e( "Reading Timer Data", "reading_timer" ); ?></h1>

		<?php if ( $total > 0 ) : ?>
			<table class="wp-list-table widefat fixed striped table-view-list posts">
				<thead>
					<tr>
						<td><?php _e( "User ID" ); ?></td>
						<td><?php _e( "Post" ); ?></td>
						<td><?php _e( "IP Address" ); ?></td>
						<td><?php _e( "User Agent" ); ?></td>
						<td><?php _e( "Timer (in minute)" ); ?></td>
						<td><?php _e( "Code" ); ?></td>
					</tr>
				</thead>

				<tbody>
					<?php foreach( $results as $item ): ?>
						<tr>
							<td><?php echo $item->user_id; ?></td>
							<td><?php echo get_the_title( $item->post_id ); ?></td>
							<td><?php echo $item->ip_address; ?></td>
							<td><?php echo $item->user_agent; ?></td>
							<td><?php echo $item->timer; ?></td>
							<td><strong><?php echo $item->code; ?></strong></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		
		<div class="tablenav">
			<div class="tablenav-pages">
				<?php
				echo paginate_links( array(
					'base' => add_query_arg( 'cpage', '%#%' ),
					'format' => '',
					'prev_text' => __('&laquo;'),
					'next_text' => __('&raquo;'),
					'total' => ceil($total / $items_per_page),
					'current' => $page
				));
				?>
			</div>
		</div>
	</div>
<?php }


