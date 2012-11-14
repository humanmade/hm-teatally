<?php 

/**
 * HMOT_User class.
 */
class HM_Tea_Tally {

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	function __construct( ) {
	
		$this->grab_users();
	
	}
	
	/**
	 * grab_userdata function.
	 * 
	 * @access public
	 * @return array
	 */
	function grab_users() {
	
		$users = get_users( array(
		
			'meta_key' => 'hmtt_active',
			'meta_compare' => '>',
			'meta_value' => 0,
	
		) ); 
		
		$this->next_up = $users[0]->ID;
		
		foreach ( $users as $key => $user ){
			
			$this->users[$user->ID] = $user;
			
			$this->users[$user->ID]->name					= ( $name = get_the_author_meta( 'first_name', $user->ID ) ) ? $name : $this->users[$user->ID]->display_name; 		
			$this->users[$user->ID]->hmtt_offset 			= (int) get_user_meta( $user->ID, 'hmtt_offset', true );
			$this->users[$user->ID]->hmtt_rolling_total  	= (int) get_user_meta( $user->ID, 'hmtt_rolling_total', true );
			$this->users[$user->ID]->hmtt_total 			= $this->users[$user->ID]->hmtt_rolling_total + (int) get_user_meta( $user->ID, 'hmtt_offset', true );
			
			$this->next_up = ( $this->users[$user->ID]->hmtt_total > $this->users[$this->next_up]->hmtt_total ) ? $user->ID : $this->next_up;
		}
		
		if ( isset( $this->users ) )	
			return $this->users;
		
		return false;		
	}			

	/**
	 * set_wage function.
	 * 
	 * @access public
	 * @param int $date (default: 0)
	 * @return void
	 */
	function set_offset( $offset = 0 ) {

		if ( ! $offset )
			return;
	
		update_user_meta( $this->ID, 'hmtt_offset', $offset );
	}
	
	/**
	 * add_tea function.
	 * 
	 * @access public
	 * @return int
	 */
	function add_tea( $user_id ) {
		
		update_user_meta( $user_id, 'hmtt_rolling_total', (int) get_user_meta( $user_id, 'hmtt_rolling_total', true ) + 1 );
		
		$this->users[$user_id]->hmtt_rolling_total++;
		$this->users[$user_id]->hmtt_total++;
		
		return;
	}	
	
	/**
	 * remove_tea function.
	 * 
	 * @access public
	 * @return int
	 */
	function remove_tea( $user_id ) {
		
		update_user_meta( $user_id, 'hmtt_rolling_total', ( (int) get_user_meta( $user_id, 'hmtt_rolling_total', true ) - 1 ) );
		
		$this->users[$user_id]->hmtt_rolling_total--;
		$this->users[$user_id]->hmtt_total--;		
		return;
	}	

	/**
	 * do_a_round function.
	 * 
	 * @access public
	 * @param mixed $start_date
	 * @param mixed $duration
	 * @return int
	 */
	function do_a_round ( $who_made = 0, $who_received = array()  ) {
	
		if ( ! $who_made || ! $who_received )
			throw new Exception ( 'Maker or receiver has not been defined' ); 
		 
		 $data = $this->users[$who_made]->name . ' made tea for: <br />';	
		 
		 $who_received = explode( ',', str_replace( $who_made . ',', '', ( join( ',', $who_received ) ) ) );
		 
		 $people = ( count( $who_received ) > 1 ) ? count( $who_received ) . ' people: ' : count( $who_received ) . ' person: ' ;
		 
		 foreach ( $who_received as $key => $person ) {
			
			 if ( count( $who_received ) > 1 ) { 
		
			 	 $people .= ( $key == ( count( $who_received ) - 1 ) ) ? 'and ' . $this->users[$person]->name : $this->users[$person]->name . ' ';

			 } else {
				
				 $people .= $this->users[$person]->name;
			 }	 
				
		 	 $data .= $this->users[$person]->name . ' (' . $this->users[$person]->hmtt_total . ') <br />';
		 	
		 	 $this->add_tea( $person );
		 	
		 	 $this->remove_tea( $who_made );
		 }
		
		$post = array(
			'post_author' => $who_made,
		  	'post_content' => $data,
		  	'post_name' => sanitize_title( $this->users[$who_made]->name . ' made a round for ' . $people ),
		  	'post_title' => $this->users[$who_made]->name . ' made a round for '  . $people,
		  	'post_type' => 'tea-round',
		  	'post_status' => 'publish'
		);  
		
		$post_id = wp_insert_post( $post );
		
		update_post_meta( $post_id, 'hmtt_who_received', $who_received );
		update_post_meta( $post_id, 'hmtt_who_made', $who_made );
		update_post_meta( $post_id, 'hmtt_who_received_count', count( $who_received ) );
		update_post_meta( $post_id, 'hmtt_date', time() );
		
		return $post_id;	
	}

}