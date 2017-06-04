<?php
/*
Plugin Name: Miscellany News Custom Author
Version: 1.0
License: GNU General Public License v3
Description: Custom authors! Woo!
Author: George Witteman
Author URI: http://www.georgewitteman.me/
*/

/**
 * Function to print the author link. If the "author" custom field is set
 * then that will be used with no link. If the "Co-Authors Plus" plugin is
 * enabled then it will use that function to get the authors link.
 * Otherwise, it will use the built in the_authors_link() function.
 */
function miscauthors_get_author_link($author_page = false) {
  // Get the "author" custom field
  $custom_author = get_post_meta(get_the_ID(), 'author', true);
  if($custom_author && !$author_page) {
    echo '<span>' . $custom_author . '</span>'; // Has "author" custom field
  } elseif (function_exists('coauthors_posts_links')) {
    coauthors_posts_links(); // "Co-Authors Plus" plugin
  } else {
    the_author_posts_link();
  }
}

/**
 * Wrapper function for printing the author link
 */
function miscellanynews_get_author_link( $author_page = false ) {
  miscauthors_get_author_link($author_page);
}

/**
 * Places the right author in the new authors column on the posts page
 */
function miscellanynews_filter_manage_posts_custom_column( $column, $post_id ) {
  if ($column == 'miscauthors'){
    global $post;
  	$custom_author = get_post_meta($post->ID, 'author', TRUE);
  	if($custom_author)
  		echo $custom_author;
  	else {
    	$authors = get_coauthors( $post->ID );
    	$count = 1;
    	foreach ( $authors as $author ) {
    		$args = array( 'author_name' => $author->user_nicename );
    		$author_filter_url = add_query_arg( array_map( 'rawurlencode', $args ), admin_url( 'edit.php' ) );
    		echo '<a href="' . esc_url( $author_filter_url ). '">' . esc_html( $author->display_name ). '</a>';
        if($count < count($authors)) {
          echo ', ';
        }
    		$count++;
    	}
    }
  }
}
add_action( 'manage_posts_custom_column', 'miscellanynews_filter_manage_posts_custom_column', 15, 2);

/**
 * Adds a column to the posts page
 */
function add_miscauthors_column($columns) {
  $new_columns = array();

	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		if ( 'title' === $key ) {
			$new_columns['miscauthors'] = __( 'Author(s)' );
		}
	}
  unset($new_columns['coauthors']);

	return $new_columns;
}
add_filter('manage_posts_columns' , 'add_miscauthors_column', 15, 1 );

/**
 * Replaces the_author() output with your custom entry or return the logged in
 * user if there is no custom entry
 */
function misc_author_byline( $author ) {
	global $post;
	$custom_author = get_post_meta($post->ID, 'author', TRUE);
	if($custom_author)
		return $custom_author;
	return $author;
}
add_filter('the_author','misc_author_byline');

/**
 * Add meta boxes for title length custom fields (metadata)
 */
function miscellanynews_add_meta_boxes() {
  add_meta_box('custom-author', "Custom Author Byline", "miscellanynews_custom_author_html", "post", "side", "low");
}
add_action( 'add_meta_boxes', 'miscellanynews_add_meta_boxes' );

/*
 * Generate html for custom author
 */
function miscellanynews_custom_author_html($post) {
	wp_nonce_field( '_miscellanynews_meta_box_nonce', 'miscellanynews_meta_box_nonce' ); ?>

  <p>
    <input type="text" name="author" value="<?php echo get_post_meta(get_the_ID(), 'author', true ); ?>">
    <br>
    <label for="author">Add a custom author name (other than your own) to override giving yourself credit for this post.</label>
  </p>

  <?php
}

/**
 * Save new values from the custom meta boxes
 */
function miscellanynews_meta_box_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['miscellanynews_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['miscellanynews_meta_box_nonce'], '_miscellanynews_meta_box_nonce' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	if ( isset( $_POST['author'] ) )
		update_post_meta( $post_id, 'author', esc_attr( $_POST['author'] ) );
}
add_action( 'save_post', 'miscellanynews_meta_box_save' );