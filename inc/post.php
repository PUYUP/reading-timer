<?php
// Function to get the client IP address
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}


/***
 * Show timer on last post content
 */
function the_content_extend( $content ) {
    global $post;
    
    $post_id = get_the_ID();
    $post_type = get_post_type( $post_id );
    $current_reading_code = $post->reading_code;

    if ( $post_type == 'post' && is_single() ) {
      if ($_SESSION['views'] % 3 == 0) {
        $timer_element = '<div id="timer-' . $post_id . '" class="countdown-timer">
            ' . ( !$current_reading_code ? '<div id="countdown"></div>' : '' ) . '
            <div id="action"><button type="button" data-post-id="' . $post_id . '">Start Timer</button></div>
            <div id="code">' . ( $current_reading_code ? __( "Your code is " ) . $current_reading_code : '' ) . '</div>
        </div>';

        $content = $content . $timer_element;
      }

      if(isset($_SESSION['views'])){
        $_SESSION['views'] = $_SESSION['views']+ 1;
      }else{
        $_SESSION['views'] = 1;
      }
    }

    return $content;
}
add_filter( 'the_content', 'the_content_extend' );


/* Fire our meta box setup function on the post editor screen. */
add_action( 'load-post.php', 'timer_post_meta_boxes_setup' );
add_action( 'load-post-new.php', 'timer_post_meta_boxes_setup' );


/* Create one or more meta boxes to be displayed on the post editor screen. */
function timer_add_post_meta_boxes() {

  add_meta_box(
    'reading-timer',      // Unique ID
    esc_html__( 'Reading Timer', 'example' ),    // Title
    'reading_timer_meta_box',   // Callback function
    'post',         // Admin page (or post type)
    'side',         // Context
    'default'         // Priority
  );
}

/* Display the post meta box. */
function reading_timer_meta_box( $post ) { ?>

  <?php wp_nonce_field( basename( __FILE__ ), 'reading_timer_nonce' ); ?>

  <p>
    <input class="widefat" type="text" name="reading-timer" id="reading-timer" value="<?php echo esc_attr( get_post_meta( $post->ID, 'reading_timer', true ) ); ?>" size="30" placeholder="<?php _e( "Set reading timer in minute", 'example' ); ?>" />
  </p>
<?php }

/* Save post meta on the 'save_post' hook. */
/* Meta box setup function. */
function timer_post_meta_boxes_setup() {

  /* Add meta boxes on the 'add_meta_boxes' hook. */
  add_action( 'add_meta_boxes', 'timer_add_post_meta_boxes' );

  /* Save post meta on the 'save_post' hook. */
  add_action( 'save_post', 'timer_save_post_class_meta', 10, 2 );
}

/* Save the meta box’s post metadata. */
function timer_save_post_class_meta( $post_id, $post ) {

  /* Verify the nonce before proceeding. */
  if ( !isset( $_POST['reading_timer_nonce'] ) || !wp_verify_nonce( $_POST['reading_timer_nonce'], basename( __FILE__ ) ) )
    return $post_id;

  /* Get the post type object. */
  $post_type = get_post_type_object( $post->post_type );

  /* Check if the current user has permission to edit the post. */
  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
    return $post_id;

  /* Get the posted data and sanitize it for use as an HTML class. */
  $new_meta_value = ( isset( $_POST['reading-timer'] ) ? $_POST['reading-timer'] : ’ );

  /* Get the meta key. */
  $meta_key = 'reading_timer';

  /* Get the meta value of the custom field key. */
  $meta_value = get_post_meta( $post_id, $meta_key, true );

  /* If a new meta value was added and there was no previous value, add it. */
  if ( $new_meta_value && ’ == $meta_value )
    add_post_meta( $post_id, $meta_key, $new_meta_value, true );

  /* If the new meta value does not match the old value, update it. */
  elseif ( $new_meta_value && $new_meta_value != $meta_value )
    update_post_meta( $post_id, $meta_key, $new_meta_value );

  /* If there is no new meta value but an old value exists, delete it. */
  elseif ( ’ == $new_meta_value && $meta_value )
    delete_post_meta( $post_id, $meta_key, $meta_value );
}


/***
 * Save to db
 */
function reading_timer_save() {
	global $wpdb; // this is how you get access to the database

    $user_id = get_current_user_id();
    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    $code = isset( $_POST['code'] ) ? $_POST['code'] : '';
    $timer = isset( $_POST['timer'] ) ? $_POST['timer'] : '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $ip_address = get_client_ip();
    
    $data = array(
        'post_id' => $post_id,
        'user_id' => $user_id,
        'code' => $code,
        'user_agent' => $user_agent,
        'ip_address' => $ip_address,
        'timer' => $timer
    );

    $table = $wpdb->prefix . 'reading_timer';
    $timer = $wpdb->insert(
        $table,
        $data,
        array(
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s'
        )
    );

    $data['id'] = $timer->insert_id;

	wp_send_json( $data );
	wp_die(); // this is required to terminate immediately and return a proper response
}
add_action( 'wp_ajax_reading_timer_save', 'reading_timer_save' );
add_action( 'wp_ajax_nopriv_reading_timer_save', 'reading_timer_save' );


/***
 * Join table
 */
function posts_join_extend( $join, $query ) {
	global $wpdb;

    $timer_table = $wpdb->prefix . 'reading_timer';
    $user_id = get_current_user_id();
    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    $code = isset( $_POST['code'] ) ? $_POST['code'] : '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $ip_address = get_client_ip();

    $join .= " LEFT JOIN (
        SELECT code, user_id, post_id, ip_address
        FROM {$timer_table}
        WHERE ip_address = '{$ip_address}' AND user_id = {$user_id}
    ) AS prt ON $wpdb->posts.ID = prt.post_id ";

	return $join;
}
add_filter( 'posts_join', 'posts_join_extend', 10, 2 );


/***
 * Extend select field
 */
function posts_fields_extend( $field, $query ) {
	$field .= ", prt.code AS reading_code ";

	return $field;
}
add_filter( 'posts_fields', 'posts_fields_extend', 10, 2 );


/***
 * Extend group by
 */
function posts_groupby_extend( $groupby ) {
	global $wpdb;
	
	$groupby = " $wpdb->posts.ID ";
	return $groupby;
}
add_filter( 'posts_groupby', 'posts_groupby_extend' );