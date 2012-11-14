<?php
/*
Plugin Name: HM Tea Tally
Description: A simple tea tally - fork of HM Holidays
Version: 0.1
Author: Human Made Limited
Author URI: http://hmn.md/
*/

define( 'HMTT_PATH', dirname( __FILE__ ) . '/' );
define( 'HMTT_URL', str_replace( ABSPATH, site_url( '/' ), HMTT_PATH ) );

/**
 * hmtt_prepare_plugin function.
 * 
 * @access public
 * @return void
 */
function hmtt_prepare_plugin() {
	
	register_post_type( 'tea-round',
		array(
			'labels' => array(
				'name' => __( 'Tea Round' ),
				'singular_name' => __( 'Tea Round' ),
				'add_new' => __( 'Add New' ),
				'add_new_item' => __( 'Add New Tea Round' ),
				'edit' => __( 'Overtime' ),
				'edit_item' => __( 'Edit Tea Round' ),
				'new_item' => __( 'New Tea Round' ),
				'view' => __( 'View Tea Round' ),
				'view_item' => __( 'View Tea Round' ),
				'search_items' => __( 'Search Tea Round' ),
				'not_found' => __( 'No Tea Round Found' ),
				'not_found_in_trash' => __( 'No Tea Round found in Trash' ),
				'parent' => __( 'Tea Round' ),
			),
			'show_ui' => true,
			'has_archive' => false
		)
	);
	
	require_once( HMTT_PATH . 'hm-tea-tally-class.php' );
	
	add_action( 'admin_menu', function() { 
			
		add_menu_page( 'Tea Tally', 'Tea Tally', 'read', 'tea-tally', 'hmtt_tally_page' );
	
	} );
	
	add_action( 'load-toplevel_page_tea-tally', 'hmtt_enqueue_styles' );

	hm_add_rewrite_rule( array( 
		'rewrite' => '^api/gecko/percentages/?$',
		'request_callback' => function( WP $wp ) {
				
			$tally = new HM_Tea_Tally();

			$response = array( 'type' => 'reverse', 'percentage' => 'hide', 'item' => array() );

			foreach ( $tally->grab_users() as $user )
				$response['item'][] = array( 'value' => $user->hmtt_total, 'label' => $user->display_name );

			echo json_encode( $response );
			exit;
		}
	) );
}
add_action( 'init', 'hmtt_prepare_plugin' );

/**
 * hmtt_enqueue_styles function.
 * 
 * @access public
 * @return void
 */
function hmtt_enqueue_styles() {

	wp_enqueue_style( 'hmtt-styles', HMTT_URL . 'hmtt-styles.css' );
}

/**
 * hmtt_all_overtime_page function.
 * 
 * @access public
 * @return void
 */
function hmtt_tally_page() {
	
	$tally = new HM_Tea_Tally();
	
	?>
	<div class="wrap">

		<div id="icon-users" class="icon32"><br></div><h2>HM Tea Tally</h2>
		<div class="clearfix"></div>
			
		<?php if ( isset( $_GET['round-done'] ) ): ?>
			
			<div class="updated message"><p>Your tea round has been successfully logged!</p></div>
		
		<?php endif; ?> 			
					
		<div class="widefat hmtt">
			<?php hmtt_display_tally( $tally ); ?>	
		</div>
		
		<div class="widefat hmtt">
			<?php hmtt_display_logging_form( $tally ); ?>	
		</div>
		
		<div class="widefat hmtt">
			<?php hmtt_history(); ?>
		</div>
	
	</div>
	<?php

}

function hmtt_display_tally( $tally ) {
?>
	<table class="hmtt-tally">
			
		<tbody>
	    	
	    	<tr>
	    		<td clospan="3"><h2 class="block">The Tally</h2></td>
	    		<td></td>
	    	</tr>
	    	
	    	<?php foreach ( $tally->users as $user ): ?>
	  			
	  			<?php $class = null; ?>
	  				
	  			<?php $count = ( $user->hmtt_total < 0 ) ? ( $user->hmtt_total * -1 ) : $user->hmtt_total;
	    		
	    	    if ( $user->hmtt_total < 0 )
	    			$class = 'hmtt-in-credit';

	    		elseif ( $tally->next_up == $user->ID )
	    			$class = "hmtt-next-up"; ?>

	    		<tr>
	    			<td class="hmtt-name"><h3 class="block"><?php echo $user->name; ?></h3></td>
	    			<td>
	    				<ul>
	    					<?php for ( $i = 0; $i < $count; $i++ ): ?>
	    					 <li class="<?php echo $class; ?>"></li>
	    					<?php endfor; ?>
	    				</ul>
	    			</td>
	    		</tr>
	
	    	<?php endforeach; ?>	
	    	
		</tbody>
	</table>
	
<?php	
}

function hmtt_display_logging_form( $tally ) {
	
	?>  
	 <form method="post">
	     
	     <table class="form-table">
	     	
	     	<tr>
	     		<td colspan="3"><h2 class="block">Make a round of tea</h2></td>
	     	</tr>
	     	
	     	<tr>
	     		<th>
	     			<label for="hmtt_offset">Who made it?
	     		</label></th>
	     		<td>
	     			
	     			<select name="hmtt_who_made_it">
	     				
	     				<?php foreach ( $tally->users as $user ): ?>
	     					
	     					<option value="<?php echo $user->ID; ?>" <?php selected( get_current_user_id(), $user->ID ); ?>><?php echo $user->name; ?></option>
	     				
	     				<?php endforeach; ?>
	     			
	     			</select><br />
	     			
	     			<span class="description"></span>
	     		
	     		</td>
	     		<td></td>
	     	</tr>
	     	
	     	<tr>
	     		<th>
	     			<label>Tea receivers</label>
	     		</th>
	     		<td class="hmtt_receivers">
	     			
	     			<?php foreach ( $tally->users as $user ): ?>
	     				
	     				<input name="hmtt_tea_receivers[]" id="tea_receiver_<?php echo $user->ID; ?>" type="checkbox" value="<?php echo $user->ID; ?>" /> 
	     				<label for="tea_receiver_<?php echo $user->ID; ?>"><?php echo $user->name; ?></label><br />
	     			
	     			<?php endforeach; ?>
	 			
	     		</td>
	     		<td class="hmtt-button">
	    			<input class="button-primary hmtt" type="submit" value="Do it!" />
	    		</td>
	     	</tr>
	     						
	     </table>		
	 </form>	     
	<?php 
}

/**
 * hmtt_history function.
 * 
 * @access public
 * @param mixed $user_id
 * @return void
 */
function hmtt_history() {
	
	$posts = get_posts( array(
		
		'post_type' 	  => 'tea-round',
		'posts_per_page'  => 20,
	) ); ?>
	
		<table class="hmtt_history">
			
			<tbody>		
				
				<tr>
					<td clospan="3"><h2 class="block">Tea Round History</h2></td>
					<td></td>
				</tr>
				
				<?php if ( ! $posts ): ?>
					
					<tr>
						<td colspan="2">No History</td>
					</tr>		
				
				<?php endif; ?>
				
				<?php foreach ( (array) $posts as $post ):
		
					$date = date( 'l \t\h\e j\t\h \o\f F Y', (int) get_post_meta( $post->ID, 'hmtt_date', true ) );
					?>
			
					<tr>
						<td class="hmtt-date"><?php echo $date; ?></td>
						
						<td> 
							<span><?php echo $post->post_title; ?></span><br />
						</td>
					</tr>
			
				<?php endforeach; ?>
				
			</tbody>
			
		</table>
	<?php
}

/**
 * hmtt_add_overtime function.
 * 
 * @access public
 * @return void
 */
function hmtt_add_round_from_post_submission() {
		
	if ( ! isset( $_POST ) || ! isset( $_POST['hmtt_who_made_it'] ) || ! isset( $_POST['hmtt_tea_receivers'] ) || ! $_POST['hmtt_who_made_it'] || ! $_POST['hmtt_tea_receivers'] )
		return;

	try{  
	
		$user = new HM_Tea_Tally();
		
		$user->do_a_round( (int) $_POST['hmtt_who_made_it'], (array) $_POST['hmtt_tea_receivers'] );	
		
	}catch ( Exception $e ) {
		
		add_action( 'toplevel_page_tea-tally', function () use ( $e ) {
			?>
			
			<div class="updated message"><p>Error: <?php var_export( $e ); ?></p></div>
			
			<?php
		} );
		
		return;		
	}
	
	wp_redirect( add_query_arg( 'round-done', 'true', wp_get_referer( ) ) );
		
	exit;

}
add_action( 'admin_init', 'hmtt_add_round_from_post_submission' );

/**
 * hmtt_add_admin_user_edit_fields function.
 * 
 * @access public
 * @param mixed $user
 * @return void
 */
function hmtt_add_admin_user_edit_fields( $user ) {
	
	if ( ! current_user_can( 'administrator' ) )
		return false;
	
	?>
	<h3>HM Tea Tally Settings</h3>
	<table class="form-table">
		<tr>
			<th>
				<label for="hmtt_start_date">Activate HMTT for this user
			</label></th>
			<td>
				<input type="checkbox" name="hmtt_active" id="hmtt_active" value="1" class="regular-text" <?php checked( (bool) get_the_author_meta( 'hmtt_active', $user->ID ) ); ?> /><br />
				<span class="description">Add this user to the tally and allow them to see and log tea rounds</span>
			</td>
		</tr>
		
		<tr>
			<th>
				<label for="hmtt_offset">Tea Tally Offset
			</label></th>
			<td>
				<input type="text" placeholder="5" name="hmtt_offset" id="hmtt_offset" value="<?php echo get_the_author_meta( 'hmtt_offset', $user->ID ); ?>" class="regular-text" /><br />
				<span class="description">Give this user an offset (cups of tea made for them before tracking)</span>
			</td>
		</tr>
		
	</table>
	<?php
}
add_action( 'edit_user_profile', 'hmtt_add_admin_user_edit_fields' );
add_action( 'show_user_profile', 'hmtt_add_admin_user_edit_fields' );

/**
 * hmtt_save_admin_user_edit_fields function.
 * 
 * @access public
 * @param mixed $user_id
 * @return void
 */
function hmtt_save_admin_user_edit_fields( $user_id ) {
	
	if ( ! current_user_can( 'administrator' ) )
		return false;
		
	if ( isset( $_POST['hmtt_active'] ) )	
		update_user_meta( $user_id, 'hmtt_active', (int) $_POST['hmtt_active'] );
		
	if ( isset( $_POST['hmtt_offset'] ) )	
		update_user_meta( $user_id, 'hmtt_offset', (int) $_POST['hmtt_offset'] );			
}
add_action( 'edit_user_profile_update', 'hmtt_save_admin_user_edit_fields' );
add_action( 'personal_options_update', 'hmtt_save_admin_user_edit_fields' );
