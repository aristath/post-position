<?php
/**
 * Plugin Name: Post Position
 * Description: Change the order of posts
 * Plugin URL: https://aristath.github.io
 * Author: Aristeides Stathopoulos
 * Author URI: https://aristath.github.io
 * Version: 1.0
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

//  don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main plugin class.
 *
 * @since 1.0
 */
class Aristath_Post_Position {
	
	/**
	 * The post-meta we'll be using.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $meta_key = 'frontpage-post-position';
	
	/**
	 * Should we avoid doubles?
	 *
	 * @access private
	 * @since 1.0
	 * @var bool
	 */
	private $avoid_doubles = true;
	
	/**
	 * Supported post-types.
	 *
	 * @access private
	 * @since 1.0
	 * @var array
	 */
	private $supported_post_types = array( 'post' );
	
	/**
	 * Conditions.
	 * An array of functions that will return bool values.
	 * This will affect WHEN the query will be altered.
	 *
	 * @access private
	 * @since 1.0
	 * @var array
	 */
	private $conditions = array( 'is_front_page' );
	
	/**
	 * How many posts will we include in the additional query?
	 *
	 * @access private
	 * @since 1.0
	 * @var int
	 */
	private $posts_per_page;
	
	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		
		// Get the initial $posts_per_page value.
		$this->posts_per_page = get_option( 'posts_per_page' );

		// Apply filters to change internal properties.
		foreach ( array( 'meta_key', 'avoid_doubles', 'supported_post_types', 'conditions', 'posts_per_page' ) as $arg ) {
			$this->$arg = apply_filters( 'post_position_' . $arg, $this->$arg );
		}
		
		// Add the metabox.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		
		// Save the post-meta.
		add_action( 'save_post', array( $this, 'save_post' ) );
		
		// Modify the query.
		add_action( 'wp_head', array( $this, 'modify_query' ) );
	}
	
	/**
	 * Adds the metabox.
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function add_meta_boxes() {
		
		add_meta_box( 
			'post_position', 
			esc_attr__( 'Post Position', 'frontpage-post-position' ), 
			array( $this, 'form_input' ), 
			$this->supported_post_types,
			'side'
		);
	}
	
	/**
	 * Add the metabox form.
	 *
	 * @since 1.0
	 * @access public
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function form_input( $post ) {
		$value = get_post_meta( $post->ID, $this->meta_key, true );
		echo '<input type="text" name="' . esc_attr( $this->meta_key ) . '" name="' . esc_attr( $this->meta_key ) . '" value="' . absint( $value ) . '">';
	}
	
	/**
	 * Save the post-meta.
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id The post-ID.
	 * @return void
	 */
	public function save_post( $post_id ) {
		if ( array_key_exists( $this->meta_key, $_POST ) && $_POST[ $this->meta_key ] ) {
			update_post_meta( $post_id, $this->meta_key, absint( wp_unslash( $_POST[ $this->meta_key ] ) ) );
		}
	}
	
	/**
	 * Modify the query and inject our posts.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function modify_query() {
		
		// Check the conditions to see if we'll modify the query rr not.
		$modify_query = false;
		foreach ( $this->conditions as $condition ) {
			if ( function_exists( $condition ) && $condition() ) {
				$modify_query = true;
			}
		}
		
		// Early exit if we don't want to modify the query.
		if ( ! $modify_query ) {
			return;
		}
		
		// Get the global $wp_query.
		global $wp_query;
			
		// Query for posts with valid post-meta.
		$ads_posts = new WP_Query( array(
			'post_type'      => 'post',
			'meta_key'       => $this->meta_key,
			'meta_value'     => 1,
			'meta_compare'   => '>=',
			'posts_per_page' => $this->posts_per_page
		) );
			
		$query_posts = $wp_query->posts;
		
		foreach ( $ads_posts->posts as $ads_post ) {
			$position = (int) get_post_meta( $ads_post->ID, $this->meta_key, true );
			if ( ! $position ) {
				continue;
			}
			
			// If we want to avoid doubles, we need to pluck out posts from the main query.
			if ( $this->avoid_doubles ) {
				foreach ( $query_posts as $key => $post ) {
					if ( $ads_post->ID === $post->ID ) {
						unset( $query_posts[ $key ] );
					}
				}
			}
			
			// Add the post to a specific position in the query results.
			array_splice( $query_posts, $position - 1, 0, array( $ads_post ) ); // Splice in at position.
		}
		
		// Update the main $wp_query object with the modified posts array.
		$wp_query->posts = $query_posts;
	}
}
new Aristath_Post_Position();
