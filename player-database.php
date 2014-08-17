<?
/*
Plugin Name: Player Database
Description: Custom plugin that creates Player post type for scouting reports and creates sortable player rankings lists in admin area
Version: 1.0
Author: Will Spencer  follow me on Twitter @wspencer428
License: GPL2
*/

/* Add the stylesheet for the plugin */

function my_admin_head() {
        echo '<link rel="stylesheet" type="text/css" href="' .plugins_url('player_db_style.css', __FILE__). '">';
}

add_action('admin_head', 'my_admin_head');

function get_position_groups() {
	$positions = array('QB','RB','FB','WR','TE','OT','OG','C','DL','EDGE','LB','CB','S','K','P','LS');
	return $positions;
};

/* Create the Player Custom Post Type */
	
	
function wsdev_player_posttype() {
    register_post_type( 'player',
        array(
            'labels' => array(
                'name' => __( 'Players' ),
                'singular_name' => __( 'Player' ),
                'add_new' => __( 'Add New Player' ),
                'add_new_item' => __( 'Add New Player' ),
                'edit_item' => __( 'Edit Player' ),
                'new_item' => __( 'Add New Player' ),
                'view_item' => __( 'View Player' ),
                'search_items' => __( 'Search Players' ),
                'not_found' => __( 'No players found' ),
                'not_found_in_trash' => __( 'No players found in trash' )
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array( 'title', 'editor', 'thumbnail','author','comments' ),
            'capability_type' => 'post',
            'rewrite' => array("slug" => "players"), 
            'menu_position' => 6
        )
    );
}
 
add_action( 'init', 'wsdev_player_posttype' );

/* Add custom columns to the view players admin page */

add_filter( 'manage_edit-player_columns', 'set_custom_edit_player_columns' );
add_action( 'manage_player_posts_custom_column' , 'custom_player_column', 10, 2 );

function set_custom_edit_player_columns($columns) {
    unset($columns['author']);
	unset($columns['date']);
    return $columns 
         + array('school' => __('School'), 
                 'position' => __('Position'),
                 'number_videos' => __('# Videos'),
				 'draft_class' => __('Draft Class'));
}

function custom_player_column( $column, $post_id ) {
    switch ( $column ) {
      	case 'school':
	        echo get_post_meta( $post_id , '_school' , true ); 
	        break;

      case 'position':
        echo get_post_meta( $post_id , '_position' , true ); 
        break;

		case 'draft_class':
			echo get_post_meta( $post_id, '_draft_class', true);
			break;

		case 'number_videos':
			echo get_post_meta( $post_id, '_number_videos', true);
			break;
    }
}

/* Make custom columns sortable on view players admin page */

add_filter("manage_edit-player_sortable_columns", 'player_sort');
function player_sort($columns) {
	$custom = array(
		'school' 	=> 'school',
		'position' 		=> 'position',
		'number_videos' => 'number_videos',
		'draft_class'	=> 'draft_class'
	);
	return wp_parse_args($custom, $columns);
	
}

add_filter( 'request', 'school_column_orderby' );
function school_column_orderby( $vars ) {
	if ( isset( $vars['orderby'] ) && 'school' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => '_school',
			//'orderby' => 'meta_value_num', // does not work
			'orderby' => 'meta_value'
			//'order' => 'asc' // don't use this; blocks toggle UI
		) );
	}
	return $vars;
}

add_filter( 'request', 'position_column_orderby' );
function position_column_orderby( $vars ) {
	if ( isset( $vars['orderby'] ) && 'position' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => '_position',
			//'orderby' => 'meta_value_num', // does not work
			'orderby' => 'meta_value'
			//'order' => 'asc' // don't use this; blocks toggle UI
		) );
	}
	return $vars;
}

add_filter( 'request', 'number_videos_column_orderby' );
function number_videos_column_orderby( $vars ) {
	if ( isset( $vars['orderby'] ) && 'number_videos' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => '_number_videos',
			//'orderby' => 'meta_value_num', // does not work
			'orderby' => 'meta_value_num'
			//'order' => 'asc' // don't use this; blocks toggle UI
		) );
	}
	return $vars;
}

add_filter( 'request', 'draft_class_column_orderby' );
function draft_class_column_orderby( $vars ) {
	if ( isset( $vars['orderby'] ) && 'draft_class' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => '_draft_class',
			//'orderby' => 'meta_value_num', // does not work
			'orderby' => 'meta_value'
			//'order' => 'asc' // don't use this; blocks toggle UI
		) );
	}
	return $vars;
}

/* Remove the Facebook and Twitter Publisher boxes from player admin pages */

add_action( 'do_meta_boxes' , 'remove_player_extra_meta' );

function remove_player_extra_meta() {
 	remove_meta_box( 'sfc-publish-div' , 'player' , 'side' ); 
	remove_meta_box( 'wptotwitter_div' , 'player' , 'advanced' );
	// remove_meta_box( 'wp-content-wrap', 'player' , 'normal' );
	
}


/* Move Author Box to the side for Player Edit Screens */

add_action('do_meta_boxes', 'player_author_box');

function player_author_box() {

	remove_meta_box( 'authordiv', 'player', 'normal' );

	add_meta_box('authordiv', __('Report Author'), 'post_author_meta_box', 'player', 'side', 'low');

}

add_action('do_meta_boxes', 'player_image_box');

function player_image_box() {

	remove_meta_box( 'postimagediv', 'player', 'side' );

	add_meta_box('postimagediv', __('Player Image'), 'post_thumbnail_meta_box', 'player', 'side', 'default');

}

/* Hide the Comment Status box by default on Player post types */

add_filter('default_hidden_meta_boxes', 'hide_meta_lock', 10, 2);

function hide_meta_lock($hidden, $screen) {
	if ( 'player' == $screen->base )
		$hidden = array('postexcerpt','slugdiv','postcustom','trackbacksdiv', 'authordiv', 'revisionsdiv');
		// removed ''commentstatusdiv', 'commentsdiv'
	return $hidden;
}

add_action('do_meta_boxes', 'wsdev_remove_comments_meta');

/* Remove the comments box on Player post types */

function wsdev_remove_comments_meta() {
	
	//remove_meta_box('commentstatusdiv', 'player', 'normal');
	remove_meta_box('commentsdiv', 'player', 'normal');
}

/* Move the post editor box to the bottom and add report specific info */

add_action( 'add_meta_boxes', 'add_scouting_report_box', 0 );

function add_scouting_report_box() {
	global $_wp_post_type_features;
	if (isset($_wp_post_type_features['player']['editor']) && $_wp_post_type_features['player']['editor']) {
		unset($_wp_post_type_features['player']['editor']);
		add_meta_box(
			'scouting_report',
			__('Official Scouting Report'),
			'scouting_report_box',
			'player', 'normal', 'low'
		);
	}
	
}
function scouting_report_box( $post ) {
	the_editor($post->post_content);
}

/***************************************
 	Player Information Metabox
***************************************/

add_action( 'add_meta_boxes', 'add_player_info_metabox' );
 
function add_player_info_metabox() {
	$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;

	 add_meta_box('player_info_box', 'Player Information', 'player_info_box', 'player', 'normal', 'high');
}



// Callback function to show fields in meta box

function player_info_box() {
    global $post;
 
    // Define the noncename
    wp_nonce_field( plugin_basename(__FILE__), 'playermeta_noncename' );
 
    // Get the data if its already been entered
    $school = get_post_meta($post->ID, '_school', true);
	$position = get_post_meta($post->ID, '_position', true);
	$jersey = get_post_meta($post->ID, '_jersey', true);
	$draft_class = get_post_meta($post->ID, '_draft_class', true);
	$school_class = get_post_meta($post->ID, '_school_class', true);
	$games_started = get_post_meta($post->ID, '_games_started', true);
	$captain = get_post_meta($post->ID, '_captain', true);
	$allstar = get_post_meta($post->ID, '_allstar', true);
	$complete = get_post_meta($post->ID, '_complete', true);
	
	$position_rank = get_post_meta($post->ID, '_position_rank', true);
	$overall_rank = get_post_meta($post->ID, '_overall_rank', true);

	$number_videos = get_post_meta($post->ID, '_number_videos', true);

	

	$mockdraftable = get_post_meta($post->ID, '_mockdraftable', true);
		
    // HTML for meta box form
	?>
	<table border="1" cellspacing="5" width="100%">
		<tr style="font-size:18px;">
			<th style="padding:5px;background-color:#cccccc;">School</th>
			<th style="padding:5px;background-color:#cccccc;">Position</th>
			<th style="padding:5px;background-color:#cccccc;">Jersey #</th>
			<th style="padding:5px;background-color:#cccccc;">Draft Class</th>
		</tr>
		<tr>
			<td align="center"><input type="text" name="_school" value="<?php echo $school; ?>" size="15" /></td>
			<td align="center"><select name="_position" id="_position">
								<?php $position_groups = get_position_groups(); 
									foreach($position_groups as $group) {
										echo '<option value="'.$group.'" ';
										if ( $position == $group ) {
											echo 'selected';
										}
										echo '>'.$group.'</option>';
									}
								?>
							</select></td>
			<td align="center"><input type="text" name="_jersey" value="<?php echo $jersey; ?>" size="5" /></td>
			<td align="center"><select name="_draft_class" id="_draft_class">
								<option value=""<?php if ($draft_class == '') echo "selected='selected'"; ?>></option>
								<option value="2012"<?php if ($draft_class == '2012') echo "selected='selected'"; ?>>2012</option>
								<option value="2013"<?php if ($draft_class == '2013') echo "selected='selected'"; ?>>2013</option>
								<option value="2014"<?php if ($draft_class == '2014') echo "selected='selected'"; ?>>2014</option>
								<option value="2015"<?php if ($draft_class == '2015') echo "selected='selected'"; ?>>2015</option>
								<option value="2016"<?php if ($draft_class == '2016') echo "selected='selected'"; ?>>2016</option>
								<option value="2017"<?php if ($draft_class == '2017') echo "selected='selected'"; ?>>2017</option>
							</select></td>
		</tr>
	</table>
	<br />
	<table border="1" cellspacing="5" width="100%">
		<tr style="font-size:18px;">
			<th style="padding:5px;background-color:#cccccc;">School Class</th>
			<th style="padding:5px;background-color:#cccccc;">Games Started</th>
			<th style="padding:5px;background-color:#cccccc;">Captain</th>
			<th style="padding:5px;background-color:#cccccc;">All-Star</th>
		</tr>
		<tr>
			<td align="center"><select name="_school_class" id="_school_class">
								<option value=""<?php if ($school_class == '') echo "selected='selected'"; ?>></option>
								<option value="RS Senior"<?php if ($school_class == 'RS Senior') echo "selected='selected'"; ?>>RS Senior</option>
								<option value="Senior"<?php if ($school_class == 'Senior') echo "selected='selected'"; ?>>Senior</option>
								<option value="RS Junior"<?php if ($school_class == 'RS Junior') echo "selected='selected'"; ?>>RS Junior</option>
								<option value="Junior"<?php if ($school_class == 'Junior') echo "selected='selected'"; ?>>Junior</option>
								<option value="RS Sophomore"<?php if ($school_class == 'RS Sophomore') echo "selected='selected'"; ?>>RS Sophomore</option>
								<option value="Sophomore"<?php if ($school_class == 'Sophomore') echo "selected='selected'"; ?>>Sophomore</option>
								<option value="Freshman"<?php if ($school_class == 'Freshman') echo "selected='selected'"; ?>>Freshman</option>
							</select></td>
			<td align="center"><input type="text" name="_games_started" value="<?php echo $games_started; ?>" size="5" /></td>
			<td align="center"><input type="checkbox" name="_captain" id="_captain" value="checked" <?php if ( $captain == 'checked' ) echo "checked='checked'"; ?> /> - (Check if Yes)</td>
			<td align="center"><select name="_allstar" id="_allstar">
								<option value=""<?php if ($allstar == '') echo "selected='selected'"; ?>></option>
								<option value="Senior Bowl"<?php if ($allstar == 'Senior Bowl') echo "selected='selected'"; ?>>Senior Bowl</option>
								<option value="Shrine Game"<?php if ($allstar == 'Shrine Game') echo "selected='selected'"; ?>>Shrine Game</option>
								<!-- <option value="Texas vs Nation"<?php if ($allstar == 'Texas vs Nation') echo "selected='selected'"; ?>>Texas vs Nation</option> -->
								<option value="NFLPA Game"<?php if ($allstar == 'NFLPA Game') echo "selected='selected'"; ?>>NFLPA Game</option>
							</select></td>
		</tr>
	</table>
	<br />
	<br />
	<span style="font-size:18px;color:#ff0000;font-weight:600;">Report Completed? <input type="checkbox" name="_complete" id="_complete" value="checked" <?php if ( $complete == 'checked' ) echo "checked='checked'"; ?> /></span>
	<input type="hidden" name="_position_rank" id="_position_rank" value="<?php if ($position_rank == '') { echo "1000"; } else { echo $position_rank; } ?>" />
	<input type="hidden" name="_overall_rank" id="_overall_rank" value="<?php if ($overall_rank == '') { echo "1000"; } else { echo $overall_rank; } ?>" />
	<!-- <span style="font-size:18px;color:#ff0000;font-weight:600;">Number of Videos - <?php echo $number_videos; ?></span> --><br/><br/>
	
	<br/><br/>
	<label><strong>MockDraftable Player ID</strong></label>
	<input type="text" class="widefat" name="_mockdraftable" id="_mockdraftable" value="<?php echo $mockdraftable; ?>" placeholder="Player ID for embed link" />
	<?php
 
}

function save_player_meta($post_ID, $post) {
 
    // verify the nonce
    if ( !wp_verify_nonce( $_POST['playermeta_noncename'], plugin_basename(__FILE__) )) {
    return $post->ID;
    }
 
    // check user status
    if ( !empty($_POST) && check_admin_referer( plugin_basename(__FILE__), 'playermeta_noncename') )
 
    $player_meta['_school'] = esc_textarea( $_POST['_school'] );
	$player_meta['_position'] = esc_textarea( $_POST['_position'] );
	$player_meta['_jersey'] = esc_textarea( $_POST['_jersey'] );
	$player_meta['_draft_class'] = esc_textarea( $_POST['_draft_class'] );
	$player_meta['_school_class'] = esc_textarea( $_POST['_school_class'] );
	$player_meta['_games_started'] = esc_textarea( $_POST['_games_started'] );
	$player_meta['_captain'] = esc_textarea( $_POST['_captain'] );
	$player_meta['_allstar'] = esc_textarea( $_POST['_allstar'] );
	$player_meta['_complete'] = esc_textarea( $_POST['_complete'] );
	$player_meta['_position_rank'] = esc_textarea( $_POST['_position_rank'] );
	$player_meta['_overall_rank'] = esc_textarea( $_POST['_overall_rank'] );
	
	$player_meta['_mockdraftable'] = esc_textarea( $_POST['_mockdraftable'] );
	//$player_meta['_number_videos'] = esc_textarea( $_POST['_number_videos']);
 
    // Add or update values of $combine_meta to the db
 
    foreach ($player_meta as $key => $value) { 
        if( $post->post_type == 'revision' ) return; 
      
        if(get_post_meta($post->ID, $key, FALSE)) { 
            update_post_meta($post->ID, $key, $value);
        } else { 
            add_post_meta($post->ID, $key, $value);
        }
        if(!$value) delete_post_meta($post->ID, $key); 
    }

    if ( get_post_meta($post->ID, '_number_videos', true ) < 1 ) {
    	update_post_meta($post->ID, '_number_videos', 0 );
    }
 
}
 
add_action('save_post', 'save_player_meta', 1, 2);


/***********************************
	Measurements Metabox
***********************************/

add_action( 'add_meta_boxes', 'add_combine_metabox' );
 
function add_combine_metabox() {
	$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;

	 add_meta_box('combine_numbers_box', 'Measurements', 'combine_numbers_box', 'player', 'normal', 'default');
}

 
function combine_numbers_box() {
    global $post;
 
    // Define the noncename
    wp_nonce_field( plugin_basename(__FILE__), 'combinemeta_noncename' );
 
    // Get the data if its already been entered
    $dash = get_post_meta($post->ID, '_dash', true);
	$height = get_post_meta($post->ID, '_height', true);
	$weight = get_post_meta($post->ID, '_weight', true);
	$arm = get_post_meta($post->ID, '_arm', true);
	$hand = get_post_meta($post->ID, '_hand', true);
	$vertical = get_post_meta($post->ID, '_vertical', true);
	$bench = get_post_meta($post->ID, '_bench', true);
	$shuttle = get_post_meta($post->ID, '_shuttle', true);
	$cone = get_post_meta($post->ID, '_cone', true);
	$broad = get_post_meta($post->ID, '_broad', true);
	
	$position_rank = get_post_meta($post->ID, '_position_rank', true);
	$overall_rank = get_post_meta($post->ID, '_overall_rank', true);
		
    // HTML for meta box form
	?>
	<table border="1" cellspacing="5">
		<tr style="font-size:14px">
			
			<th style="padding:5px;background-color:#cccccc;">Height</th>
			<th style="padding:5px;background-color:#cccccc;">Weight</th>
			<th style="padding:5px;background-color:#cccccc;">Arm</th>
			<th style="padding:5px;background-color:#cccccc;">Hand</th>
			<th style="padding:5px;background-color:#cccccc;">40</th>
			<th style="padding:5px;background-color:#cccccc;">Bench</th>
			<th style="padding:5px;background-color:#cccccc;">Vertical</th>
			<th style="padding:5px;background-color:#cccccc;">Broad</th>
			<th style="padding:5px;background-color:#cccccc;">3-Cone</th>		
			<th style="padding:5px;background-color:#cccccc;">Shuttle</th>

			
		</tr>
		<tr style="font-size:14px">
			
			<td align="center"><input type="text" name="_height" value="<?php echo $height; ?>" size="5" /></td>
			<td align="center"><input type="text" name="_weight" value="<?php echo $weight; ?>" size="5" /></td>
			<td align="center"><input type="text" name="_arm" value="<?php echo $arm; ?>" size="5" /></td>
			<td align="center"><input type="text" name="_hand" value="<?php echo $hand; ?>" size="5" /></td>
			<td align="center"><input type="text" name="_dash" value="<?php echo $dash; ?>" size="5" /></td>
			<td align="center"><input type="text" name="_bench" value="<?php echo $bench; ?>" size="5" /></td>
			<td align="center"><input type="text" name="_vertical" value="<?php echo $vertical; ?>" size="5" /></td>
			<td align="center"><input type="text" name="_broad" value="<?php echo $broad; ?>" size="5" /></td>
			<td align="center"><input type="text" name="_cone" value="<?php echo $cone; ?>" size="5" /></td>
			<td align="center"><input type="text" name="_shuttle" value="<?php echo $shuttle; ?>" size="5" /></td>
			
			
		</tr>
	</table>
	<p><em>Be sure to use Scouting format only for height! Ex: 6013 for 6 feet 1 inch and 3 8ths and DO NOT include any " for inches!</em></p>
	<?php
 
}

// Save the Metabox Data
 
function save_combine_meta($post_ID, $post) {
 
    // verify the nonce
    if ( !wp_verify_nonce( $_POST['combinemeta_noncename'], plugin_basename(__FILE__) )) {
    return $post->ID;
    }
 
    // check user status
    if ( !empty($_POST) && check_admin_referer( plugin_basename(__FILE__), 'combinemeta_noncename') )
 
    $combine_meta['_dash'] = esc_textarea( $_POST['_dash'] );
	$combine_meta['_arm'] = esc_textarea( $_POST['_arm'] );
	$combine_meta['_hand'] = esc_textarea( $_POST['_hand'] );
	$combine_meta['_height'] = esc_textarea( $_POST['_height'] );
	$combine_meta['_weight'] = esc_textarea( $_POST['_weight'] );
	$combine_meta['_vertical'] = esc_textarea( $_POST['_vertical'] );
	$combine_meta['_bench'] = esc_textarea( $_POST['_bench'] );
	$combine_meta['_shuttle'] = esc_textarea( $_POST['_shuttle'] );
	$combine_meta['_cone'] = esc_textarea( $_POST['_cone'] );
	$combine_meta['_broad'] = esc_textarea( $_POST['_broad'] );
	
	$combine_meta['_overall_rank'] = esc_textarea( $_POST['_overall_rank'] );
	$combine_meta['_position_rank'] = esc_textarea( $_POST['_position_rank'] );
 
    // Add or update values of $combine_meta to the db
 
    foreach ($combine_meta as $key => $value) { 
        if( $post->post_type == 'revision' ) return; 
      
        if(get_post_meta($post->ID, $key, FALSE)) { 
            update_post_meta($post->ID, $key, $value);
        } else { 
            add_post_meta($post->ID, $key, $value);
        }
        if(!$value) delete_post_meta($post->ID, $key); 
    }
 
}
 
add_action('save_post', 'save_combine_meta', 1, 2);

/***********************************
	Player Grade Metabox
***********************************/

// add_action( 'add_meta_boxes', 'add_grade_metabox' );
 
function add_grade_metabox() {
	$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;

	 add_meta_box('grade_box', 'Player Grade', 'grade_box', 'player', 'normal', 'default');
}

 
function grade_box() {
    global $post;
 
    // Define the noncename
    wp_nonce_field( plugin_basename(__FILE__), 'grademeta_noncename' );
 
    // Get the data if its already been entered
    $grade = get_post_meta($post->ID, '_grade', true);
	$projection = get_post_meta($post->ID, '_projection', true);
	$grade_a = get_post_meta($post->ID, '_grade_a', true);
	$grade_b = get_post_meta($post->ID, '_grade_b', true);
	$grade_c = get_post_meta($post->ID, '_grade_c', true);
	$grade_d = get_post_meta($post->ID, '_grade_d', true);
	$grade_e = get_post_meta($post->ID, '_grade_e', true);
	$grade_f = get_post_meta($post->ID, '_grade_f', true);
	$grade_m = get_post_meta($post->ID, '_grade_m', true);
	$grade_o = get_post_meta($post->ID, '_grade_o', true);
	$grade_p = get_post_meta($post->ID, '_grade_p', true);
	$grade_s = get_post_meta($post->ID, '_grade_s', true);
	$grade_t = get_post_meta($post->ID, '_grade_t', true);
	$grade_u = get_post_meta($post->ID, '_grade_u', true);
	$grade_x = get_post_meta($post->ID, '_grade_x', true);
	$grade_z = get_post_meta($post->ID, '_grade_z', true);
	$position_rank = get_post_meta($post->ID, '_position_rank', true);
	$overall_rank = get_post_meta($post->ID, '_overall_rank', true);
	
    // HTML for meta box form
	?>
	<table border="1" cellspacing="5">
		<tr style="font-size:18px">
			<th style="padding:5px;background-color:#cccccc;">Overall Grade</th>
			<th style="padding:5px;background-color:#cccccc;">Projection</th>
			<th style="padding:5px;background-color:#cccccc;">Overall Rank</th>
			<th style="padding:5px;background-color:#cccccc;">Position Rank</th>
		</tr>
		<tr style="font-size:18px;">
			<td align="center"><input type="text" name="_grade" value="<?php echo $grade; ?>" size="5" /></td>
			<td align="center"><select name="_projection" id="_projection">
								<option value="" <?php if ($projection == '') echo "selected='selected'"; ?>>&nbsp;</option>
								<option value="Top 5 Pick"<?php if ($projection == 'Top 5 Pick') echo "selected='selected'"; ?>>Top 5 Pick</option>
								<option value="Top 10 Pick"<?php if ($projection == 'Top 10 Pick') echo "selected='selected'"; ?>>Top 10 Pick</option>
								<option value="Top 25 Pick"<?php if ($projection == 'Top 25 Pick') echo "selected='selected'"; ?>>Top 25 Pick</option>
								<option value="First Round"<?php if ($projection == 'First Round') echo "selected='selected'"; ?>>First Round</option>
								<option value="Late 1st - Early 2nd"<?php if ($projection == 'Late 1st - Early 2nd') echo "selected='selected'"; ?>>Late 1st - Early 2nd</option>
								<option value="Second Round"<?php if ($projection == 'Second Round') echo "selected='selected'"; ?>>Second Round</option>
								<option value="Late 2nd - Early 3rd"<?php if ($projection == 'Late 2nd - Early 3rd') echo "selected='selected'"; ?>>Late 2nd - Early 3rd</option>
								<option value="Third Round"<?php if ($projection == 'Third Round') echo "selected='selected'"; ?>>Third Round</option>
								<option value="Mid-Round Pick"<?php if ($projection == 'Mid-Round Pick') echo "selected='selected'"; ?>>Mid-Round Pick</option>
								<option value="Late Round Pick"<?php if ($projection == 'Late Round Pick') echo "selected='selected'"; ?>>Late Round Pick</option>
								<option value="UDFA"<?php if ($projection == 'UDFA') echo "selected='selected'"; ?>>UDFA</option>
								</select></td>
			<td align="center"><?php echo $overall_rank; ?></td>
			<td align="center"><?php echo $position_rank; ?></td>
		</tr>
	</table>
	<br />
	<a href="#">Grading Scale Information</a>
	<br />
	<table>
		<tr>
			<td><p><input type="checkbox" name="_grade_a" id="_grade_a" value="checked" <?php if ( $grade_a == 'checked' ) echo "checked='checked'"; ?> /> A - Overaged, 25+ on draft day</p></td>
			<td><p><input type="checkbox" name="_grade_b" id="_grade_b" value="checked" <?php if ( $grade_b == 'checked' ) echo "checked='checked'"; ?> /> B - Currently lacks ideal bulk for position but CAN get bigger</p></td>
		</tr>
		<tr>
			<td><p><input type="checkbox" name="_grade_c" id="_grade_c" value="checked" <?php if ( $grade_c == 'checked' ) echo "checked='checked'"; ?> /> C - Character concerns</p></td>
			<td><p><input type="checkbox" name="_grade_d" id="_grade_d" value="checked" <?php if ( $grade_d == 'checked' ) echo "checked='checked'"; ?> /> D - Developmental player</p></td>
		</tr>
		<tr>
			<td><p><input type="checkbox" name="_grade_e" id="_grade_e" value="checked" <?php if ( $grade_e == 'checked' ) echo "checked='checked'"; ?> /> E - Exception player, deficient in either size or speed</p></td>
			<td><p><input type="checkbox" name="_grade_f" id="_grade_f" value="checked" <?php if ( $grade_f == 'checked' ) echo "checked='checked'"; ?> /> F - Presently lacks bulk for the position AND the frame to add bulk</p></td>
		</tr>
		<tr>
			<td><p><input type="checkbox" name="_grade_m" id="_grade_m" value="checked" <?php if ( $grade_m == 'checked' ) echo "checked='checked'"; ?> /> M - Has difficulties learning</p></td>
			<td><p><input type="checkbox" name="_grade_o" id="_grade_o" value="checked" <?php if ( $grade_o == 'checked' ) echo "checked='checked'"; ?> /> O - Overachiever. Lacks ideal physical tools but high in production, character, etc.</p></td>
		</tr>
		<tr>
			<td><p><input type="checkbox" name="_grade_s" id="_grade_s" value="checked" <?php if ( $grade_s == 'checked' ) echo "checked='checked'"; ?> /> S - Speed deficient for the position</p></td>
			<td><p><input type="checkbox" name="_grade_t" id="_grade_t" value="checked" <?php if ( $grade_t == 'checked' ) echo "checked='checked'"; ?> /> T - Tightness or stiffness that affects play</p></td>
		</tr>
		<tr>
			<td><p><input type="checkbox" name="_grade_u" id="_grade_u" value="checked" <?php if ( $grade_u == 'checked' ) echo "checked='checked'"; ?> /> U - Underachiever due to something other than physical tools</p></td>
			<td><p><input type="checkbox" name="_grade_x" id="_grade_x" value="checked" <?php if ( $grade_x == 'checked' ) echo "checked='checked'"; ?> /> X - Injury problems or history that affected play</p></td>
		</tr>
		<tr>
			<td><p><input type="checkbox" name="_grade_z" id="_grade_z" value="checked" <?php if ( $grade_z == 'checked' ) echo "checked='checked'"; ?> /> Z - Height deficient for the position</p></td>
			<td><p>&nbsp;</td>
		</tr>
	</table>
	<?php
 
}

// Save the Metabox Data
 
function save_grade_meta($post_ID, $post) {
 
    // verify the nonce
    if ( !wp_verify_nonce( $_POST['grademeta_noncename'], plugin_basename(__FILE__) )) {
    return $post->ID;
    }
 
    // check user status
    if ( !empty($_POST) && check_admin_referer( plugin_basename(__FILE__), 'grademeta_noncename') )
 
    $grade_meta['_grade'] = esc_textarea( $_POST['_grade'] );
	$grade_meta['_projection'] = esc_textarea( $_POST['_projection'] );
	$grade_meta['_grade_a'] = esc_textarea( $_POST['_grade_a'] );
	$grade_meta['_grade_b'] = esc_textarea( $_POST['_grade_b'] );
	$grade_meta['_grade_c'] = esc_textarea( $_POST['_grade_c'] );
	$grade_meta['_grade_d'] = esc_textarea( $_POST['_grade_d'] );
	$grade_meta['_grade_e'] = esc_textarea( $_POST['_grade_e'] );
	$grade_meta['_grade_f'] = esc_textarea( $_POST['_grade_f'] );
	$grade_meta['_grade_m'] = esc_textarea( $_POST['_grade_m'] );
	$grade_meta['_grade_o'] = esc_textarea( $_POST['_grade_o'] );
	$grade_meta['_grade_p'] = esc_textarea( $_POST['_grade_p'] );
	$grade_meta['_grade_s'] = esc_textarea( $_POST['_grade_s'] );
	$grade_meta['_grade_t'] = esc_textarea( $_POST['_grade_t'] );
	$grade_meta['_grade_u'] = esc_textarea( $_POST['_grade_u'] );
	$grade_meta['_grade_x'] = esc_textarea( $_POST['_grade_x'] );
	$grade_meta['_grade_z'] = esc_textarea( $_POST['_grade_z'] );
	
 
    // Add or update values of $combine_meta to the db
 
    foreach ($grade_meta as $key => $value) { 
        if( $post->post_type == 'revision' ) return; 
      
        if(get_post_meta($post->ID, $key, FALSE)) { 
            update_post_meta($post->ID, $key, $value);
        } else { 
            add_post_meta($post->ID, $key, $value);
        }
        if(!$value) delete_post_meta($post->ID, $key); 
    }
 
}
 
add_action('save_post', 'save_grade_meta', 1, 2);

/***********************************
	Prospect Videos Metabox
***********************************/

//add_action( 'add_meta_boxes', 'add_videos_metabox' );
 
function add_videos_metabox() {
	$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;

	 add_meta_box('player_videos_box', 'Prospect Videos', 'player_videos_box', 'player', 'side', 'low');
}

 
function player_videos_box() {
    if ( get_the_title() != '' ) {
		global $post;
		$original_post = $post;
	
		$player_name = get_the_title();
		$args = array(
			'category_name' => 'videos',
			'post_type' => 'post',
			'posts_per_page' => -1
			);
	
			$videos = new WP_Query( $args );

			while ( $videos->have_posts() ) : $videos->the_post();
			
			
				$video_title = get_the_title();
			
				$match = substr_count( $video_title, $player_name );
			
				if ( $match > 0 ) {
				
				?><div class="video_category_wrapper">

						<a href="<?php the_permalink(); ?>"><img class="video_page_thumb" src="<?php video_thumbnail(); ?>" />

							<?php the_title(); ?></a>

					</div><?php
				}
			
				else {
					continue;
				}
			
			
			
				endwhile;
	
			wp_reset_query();
			wp_reset_postdata();
	
			$post = $original_post;
 	}

	
}

/***********************************
	Player Notes Metabox
***********************************/

add_action( 'add_meta_boxes', 'add_notes_metabox' );
 
function add_notes_metabox() {
	$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;

	 add_meta_box('notes_box', 'Scouting Notes', 'notes_box', 'player', 'normal', 'default');
}

 
function notes_box() {
    global $post;
 
    // Define the noncename
    wp_nonce_field( plugin_basename(__FILE__), 'notesmeta_noncename' );
 
    // Get the data if its already been entered
    $notes = get_post_meta($post->ID, '_notes', true );
	
    // HTML for meta box form
	?>
	
	<textarea name="_notes" rows="20" cols="100"/><?php echo $notes; ?></textarea>
	<?php
		
}

// Save the Metabox Data
 
function save_notes_meta($post_ID, $post) {
 
    // verify the nonce
    if ( !wp_verify_nonce( $_POST['notesmeta_noncename'], plugin_basename(__FILE__) )) {
    return $post->ID;
    }
 
    // check user status
    if ( !empty($_POST) && check_admin_referer( plugin_basename(__FILE__), 'notesmeta_noncename') )
 
	$notes_meta['_notes'] = $_POST['_notes'];
 
    // Add or update values of $notes_meta to the db
 
   foreach ($notes_meta as $key => $value) { 
        if( $post->post_type == 'revision' ) return; 
      
        if(get_post_meta($post->ID, $key, FALSE)) { 
            update_post_meta($post->ID, $key, $value);
        } else { 
            add_post_meta($post->ID, $key, $value);
        }
        if(!$value) delete_post_meta($post->ID, $key); 
    } 
 
}
 
add_action('save_post', 'save_notes_meta', 1, 2);


/***********************************
	Create Template Tags
***********************************/

function player_school() {
	
	echo get_post_meta( get_the_ID(), '_school', true);
}

function player_height() {
	
	echo get_post_meta( get_the_ID(), '_height', true);
	
}

function display_player_height() {
	
	if ( get_post_meta( get_the_ID(), '_height', true) != '' ) {
		
		$height = get_post_meta( get_the_ID(), '_height', true);  // get the scouting format height
	
		$feet = substr( $height, 0, 1 ); // get the number of feet
	
		$inches = substr( $height, 1, 2 ); // get the number of inches
	
		$eighths = substr( $height, 3, 1 ); // get the number of eighths
	
		if ( substr( $inches, 0, 1 ) == '0' ) {
			$inches = substr( $inches, 1, 1 );
		}
	
		switch ( $eighths ) {
			
			case 2:
			$top = 1;
			$bottom = 4;
			break;
			
			case 4:
			$top = 1;
			$bottom = 2;
			break;
			
			case 6:
			$top = 3;
			$bottom = 4;
			break;
			
			default:
			$top = $eighths;
			$bottom = 8;
			
		}
	
		if ( $eighths != 0 ) {
			echo $feet. "' ".$inches." <sup>".$top."</sup>/<sub>".$bottom."</sub>\"";
		}
	
		else {
			echo $feet. "' ".$inches."\"";
		}
	}
	
	else {
		echo '--';
	}
}

function player_weight() {
	
	echo get_post_meta( get_the_ID(), '_weight', true);
}

function player_draft_class() {
	
	echo get_post_meta( get_the_ID(), '_draft_class', true);
}

function player_overall_rank() {
	
	echo get_post_meta( get_the_ID(), '_overall_rank', true);
}

function display_overall_rank() {
	
	if ( get_post_meta( get_the_ID(), '_overall_rank', true) == '1000' ) {
		
		echo 'N/A';
	}
	
	else {
	
		echo get_post_meta( get_the_ID(), '_overall_rank', true);
	}
	
}

function player_position_rank() {
	
	echo get_post_meta( get_the_ID(), '_position_rank', true);
	
}

function display_position_rank() {
	
	if ( get_post_meta( get_the_ID(), '_position_rank', true) == '1000' ) {
		
		echo 'N/A';
	}
	
	else {
	
		echo get_post_meta( get_the_ID(), '_position_rank', true);
	}
}

function player_position() {
	
	echo get_post_meta( get_the_ID(), '_position', true);
}

function player_position2() {
	
	echo get_post_meta( get_the_ID(), '_position2', true);
}

function player_position3() {
	
	echo get_post_meta( get_the_ID(), '_position3', true);
}

function player_school_class() {
	
	echo get_post_meta( get_the_ID(), '_school_class', true);
}

function player_captain() {
	
	$captain = get_post_meta( get_the_ID(), '_captain', true);
	
	if ($captain == 'checked') {
		echo "Yes";
	}
	
	else {
		echo "No";
	}	
	
}

function player_allstar() {
	
	if ( get_post_meta( get_the_ID(), '_allstar', true ) == '' ) {
		
		echo "None";
	}
	
	else {

		echo get_post_meta( get_the_ID(), '_allstar', true);
	}
}

function player_dash() {
	
	if ( get_post_meta( get_the_ID(), '_dash', true) == '' ) {
		
		echo "unk";
	}
	
	else {
	
		echo get_post_meta( get_the_ID(), '_dash', true);
	}
}

function player_report_author() {
	
	echo get_post_meta( get_the_ID(), '_report_author', true);
}

function player_projection() {
	
	echo get_post_meta( get_the_ID(), '_projection', true);
}

function player_photo() {
	
	if ( has_post_thumbnail() ) {
		the_post_thumbnail( array( 105,145 ) );
	}
	
	else {
		
		?> <img src="<?php bloginfo( 'stylesheet_directory' ); ?>/images/unknown_player.jpg" width="105" height="145"><?php
		
	}
}