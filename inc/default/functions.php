<?php 

if ( ! function_exists( 'get_top_level_category' )) {
	function get_top_level_category($id, $taxonomy = 'category'){
		$term = get_top_level($id, $taxonomy);
		$term_id = ($term) ? $term : $id;
		return get_term_by( 'id', $term_id, $taxonomy);
	}
}


if ( ! function_exists( 'get_top_level' )) {
	function get_top_level($id, $object){
		$terms = get_ancestors($id, $object);
		return (!empty($terms)) ? $terms[count($terms) - 1] : null;
	}
}

if ( ! function_exists( 'get_sub_category' )) {
	function get_sub_category($id){
		$sub_categories = get_categories( array('child_of' => $id, 'hierarchical' => false, 'orderby' => 'count'));
		foreach($sub_categories as $sub_category){
			if(has_category($sub_category->term_id)){
				$category = $sub_category;
			}
		}

		return (isset($category)) ? $category : null;
	}
}

if ( ! function_exists( 'get_adjacent_fukn_post' )) {
	function get_adjacent_fukn_post($adjacent, $args = array()){
		$previous = ($adjacent == 'next') ? false : true;
		return get_adjacent_post(false, '', $previous);
	}
}

if ( ! function_exists( 'get_latest_post' )) {
	function get_latest_post() {
		$posts = get_posts(array('posts_per_page' => 1));
		return $posts[0];
	}
}

if ( ! function_exists( 'get_current_url' )) {
	function get_current_url() {
		$url = 'http';
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') $url .= 's';
			$url .= '://';

		if ($_SERVER['SERVER_PORT'] != '80') {
			$url .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		} else {
			$url .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		}
		return $url;
	}
}

if ( ! function_exists( 'get_queried_page' )) {
	function get_queried_page($depth = 0){
		$curr_url = get_current_url();
		if($depth != -1) $curr_url = strtok($curr_url, '?');

		$curr_uri = str_replace(get_bloginfo('url'), '', $curr_url);
		
		if($depth){
			$curr_uri_ary = array_filter(explode('/', $curr_uri));
			$curr_uri = trailingslashit(implode('/', array_splice($curr_uri_ary, 0, $depth)));
		}
		
		$page = get_page_by_path($curr_uri);
		if($page) return $page;
		return null;
	}
}

if(!function_exists('get_attachment_id_from_url')) {
	function get_attachment_id_from_url( $attachment_url = '' ) {
	 	global $wpdb;
		return $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE guid='$attachment_url'");		
	}
}

if(!function_exists('get_excerpt')) {
	function get_excerpt($count = 100, $post_id = null){
		
		if($post_id) {
			$post = get_post($post_id);
		} else {
			global $post;
		}
		
		$excerpt = ( !empty($post->post_excerpt) ) ? $post->post_excerpt : get_the_content();
		$excerpt = strip_tags($excerpt);
		$excerpt = substr($excerpt, 0, $count);
		if( !empty($excerpt) ) $excerpt .= '...';
		return $excerpt;
	}
}

if(!function_exists('get_post_thumbnail_src')) {
	function get_post_thumbnail_src($size){
		global $post;
		$thumbnail_id = get_post_thumbnail_id();
		return get_image($thumbnail_id, $size);
	}
}

if(!function_exists('get_image')) {
	function get_image($id, $size){
		
		$image = wp_get_attachment_image_src($id, $size);

		if( !empty($image[0]) ) return $image[0];
		return;
	}
}

if ( ! function_exists( 'include_template_part' ) ) {
	function include_template_part($slug, $data = array()){
		
		$templates = array();
		$templates[] = "{$slug}.php";
		
		do_action( "get_template_part_{$slug}", $slug, $data );

		if( is_array($data) ) extract($data);
		
		include(locate_template($templates));		
	}
}

if ( ! function_exists( 'include_module' ) ) {
	function include_module($slug, $data = '') {
		include_template_part('inc/modules/'. $slug, $data);
	}
}

if ( ! function_exists( 'get_related_tag_posts_ids' )) {
	function get_related_tag_posts_ids( $post_id, $number = 5, $category = null ) {
		$related_ids = false;
		$post_ids = array();
		// get tag ids belonging to $post_id
		$tag_ids = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
		if ( $tag_ids ) {
			// get all posts that have the same tags

			$tax_query = array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'id',
					'terms'    => $tag_ids,
					'operator' => 'IN'
				)
			);

			if( $category ) {
				$tax_query[] = array(		
					'taxonomy' => 'category',
					'field'    => 'id',
					'terms'    => array($category),
					'operator' => 'IN'
				);
			}

			$tag_posts = get_posts(array(
				'posts_per_page' => -1, // return all posts 
				'no_found_rows'  => true, // no need for pagination
				'fields'         => 'ids', // only return ids
				'post__not_in'   => array( $post_id ), // exclude $post_id from results
				'tax_query'      => $tax_query
			));
			// loop through posts with the same tags
			if ( $tag_posts ) {
				$score = array();
				$i = 0;
				foreach ( $tag_posts as $tag_post ) {
					// get tags for related post
					$terms = wp_get_post_tags( $tag_post, array( 'fields' => 'ids' ) );
					$total_score = 0;
					foreach ( $terms as $term ) {
						if ( in_array( $term, $tag_ids ) ) {
							++$total_score;
						}
					}
					if ( $total_score > 0 ) {
						$score[$i]['ID'] = $tag_post;
						// add number $i for sorting 
						$score[$i]['score'] = array( $total_score, $i );
					}
					++$i;
				}
				// sort the related posts from high score to low score
				uasort( $score, 'sort_tag_score' );
				// get sorted related post ids
				$related_ids = wp_list_pluck( $score, 'ID' );
				// limit ids
				$related_ids = array_slice( $related_ids, 0, (int) $number );
			}
		}
		return $related_ids;
	}
}

if ( ! function_exists( 'sort_tag_score' )) {
	function sort_tag_score( $item1, $item2 ) {
		if ( $item1['score'][0] != $item2['score'][0] ) {
			return $item1['score'][0] < $item2['score'][0] ? 1 : -1;
		} else {
			return $item1['score'][1] < $item2['score'][1] ? -1 : 1; // ASC
		}
	}
}

if ( ! function_exists( 'widget_instance' )) {
	function widget_instance($instance_id, $args = array()) {
		global $wp_registered_widgets, $wp_registered_sidebars, $sidebars_widgets;

		// validation
		if ( !array_key_exists($instance_id, $wp_registered_widgets) ) {
			echo 'No widget found with that id'; return;
		}

		// find sidebar 
		foreach($sidebars_widgets as $sidebar => $sidebar_widget){
			foreach($sidebar_widget as $widget){
				if ($widget == $instance_id) $current_sidebar = $sidebar;
			}
		}

		$presentation = (isset($current_sidebar)) ? $wp_registered_sidebars[$current_sidebar] : array(
			'name' => '', 
			'id' => '',
			'description' => '',
			'class' => '',
			'before_widget'=> '',
			'after_widget'=> '',
			'before_title'=> '',
			'after_title' => ''
		);

		if( !empty($args) ) {
			$presentation = array_merge($presentation, $args);
		}

		$params = array_merge(
			array( array_merge( $presentation, array('instance_id' => $instance_id, 'widget_name' => $wp_registered_widgets[$instance_id]['name']) ) ),
			(array) $wp_registered_widgets[$instance_id]['params']
		);

		// Substitute HTML id and class attributes into before_widget
		$classname_ = '';
		
		foreach ( (array) $wp_registered_widgets[$instance_id]['classname'] as $cn ) {
			if ( is_string($cn) )
				$classname_ .= '_' . $cn;
			elseif ( is_object($cn) )
				$classname_ .= '_' . get_class($cn);
		}

		$classname_ = ltrim($classname_, '_');
		$params[0]['before_widget'] = sprintf($params[0]['before_widget'], $instance_id, $classname_);

		$params = apply_filters( 'dynamic_sidebar_params', $params ); // doesnt't add/minus from data

		$callback = $wp_registered_widgets[$instance_id]['callback'];

		if ( is_callable($callback) ) {
			call_user_func_array($callback, $params);
		}
	}
}
