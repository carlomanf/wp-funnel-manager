<?php
/**
 * Main plugin file
 */

/**
 * Main plugin class.
 */
namespace WP_Funnel_Manager;

class WP_Funnel_Manager
{

	/**
	 * Instantiate a WP_Funnel_Manager object.
	 *
	 * Don't call the constructor directly, use the `WP_Funnel_Manager::get_instance()`
	 * static method instead.
	 */
	public function __construct()
	{
	}

	/**
	 * Launch the initialization process.
	 *
	 * @since 1.0.3
	 */
	public function run()
	{
		// Load functions
		add_filter( 'quick_edit_dropdown_pages_args', array( $this, 'funnel_post_parent' ) );
		add_filter( 'page_row_actions', array( $this, 'funnel_interior_edit' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'funnel_interior_permalink' ), 10, 2 );
		add_action( 'init', array( $this, 'post_parent_query_var' ) );
		add_action( 'admin_menu', array( $this, 'remove_interiors' ) );
		add_action( 'pre_post_update', array( $this, 'interior_without_parent' ), 10, 2 );
		add_filter( 'admin_url', array( $this, 'new_interior' ) );
		add_filter( 'wp_insert_post_parent', array( $this, 'set_post_parent' ) );
		add_action( 'wp_trash_post', array( $this, 'trash_exterior_promote_interior' ) );

		// Load a funnel
		$funnel = new WPFM_Funnel();
	}

	public function remove_interiors()
	{
		remove_menu_page('edit.php?post_type=funnel_int');
	}

	/**
	 * Correct the post parent selector for funnel interiors
	 *
	 * @since 1.0.3
	 */
	public function funnel_post_parent( $args )
	{
		global $post;
		if ( 'funnel_int' !== $post->post_type )
			return $args;

		$args[ 'post_type' ] = 'funnel';
		return $args;
	}

	/**
	 * Add a link to view and edit funnel interiors
	 *
	 * @since 1.0.3
	 */
	public function funnel_interior_edit( $actions, $post ) {
		if ( 'funnel' != $post->post_type )
			return $actions;

		$url = admin_url( 'edit.php?post_type=funnel_int&post_parent=' . $post->ID );
		$actions['edit_interiors'] = '<a href="' . esc_url( $url ) . '">' . __( 'Edit Steps', 'wpfunnel' ) . '</a>';

		return $actions;
	}

	/**
	 * Correct the funnel interior permalink
	 *
	 * @since 1.0.4
	 */
	public function funnel_interior_permalink( $permalink, $post )
	{
		if ( 'funnel_int' !== $post->post_type )
			return $permalink;

		$replace = '';
		foreach ( $post->ancestors as $ancestor )
		{
			$replace = '/' . get_post( $ancestor )->post_name . $replace;
		}
		$replace .= '/';

		$permalink = str_replace( $replace, '/', $permalink );
		return $permalink;
	}

	/**
	 * Allow post parent as query var
	 * This is needed to view funnel interiors
	 *
	 * @since 1.0.3
	 */
	public function post_parent_query_var()
	{
		if ( !is_admin() )
			return;

		$GLOBALS['wp']->add_query_var( 'post_parent' );
	}

	/**
	 * Validates funnel relationship between exterior and interior
	 *
	 * @since 1.2.0
	 */
	private function validate_post_parent( $post_parent_id )
	{	
		// Use get variable as post parent if it's valid
		if ( !( $post = get_post( $post_parent_id ) ) )
			return false;

		if ( $post->post_type != 'funnel' )
			return false;

		return true;
	}

	/**
	 * Prevent interiors being saved without an exterior
	 * Hooked to pre_post_update action
	 *
	 * @since 1.1.0
	 */
	public function interior_without_parent( $post_id, $data )
	{
		if ( $data['post_type'] != 'funnel_int' )
			return;

		if ( empty( $data['post_parent'] ) || !$this->validate_post_parent( $data['post_parent'] ) )
		{
			wp_die( 'Funnel Interiors must be assigned to a Funnel. Please try again.' );
		}
	}

	/**
	 * Pass the post parent GET variable from edit.php to post-new.php
	 * Hooked to admin_url filter
	 *
	 * @since 1.1.0
	 */
	public function new_interior( $url )
	{
		// Validate post type
		if ( 'funnel_int' != get_query_var( 'post_type' ) )
			return $url;

		$post_parent = get_query_var( 'post_parent' );

		// Validate post parent
		if ( !$this->validate_post_parent( $post_parent ) )
			return $url;

		return $url . '&post_parent=' . $post_parent;
	}

	/**
	 * Set the post parent ID upon creation of auto-draft
	 * Hooked to wp_insert_post_parent filter
	 *
	 * @since 1.1.0
	 */
	public function set_post_parent( $post_parent_id )
	{
		// Validate post type
		if ( empty( $_GET['post_type'] ) || 'funnel_int' != $_GET['post_type'] )
			return $post_parent_id;

		// Validate post parent
		if ( empty( $_GET['post_parent'] ) || !$this->validate_post_parent( $_GET['post_parent'] ) )
			return $post_parent_id;

		return intval( $_GET['post_parent'] );
	}

	public function trash_exterior_promote_interior( $post_id )
	{
		if ( !( $exterior = get_post( $post_id ) ) || 'funnel' != $exterior->post_type )
			return;

		$interiors = get_posts( 'orderby=menu_order&order=ASC&post_status=any&post_type=funnel_int&post_parent=' . $post_id );
		if ( empty( $interiors[0] ) )
			return;

		wp_update_post( array( 'ID' => $interiors[0]->ID, 'post_type' => 'funnel' ) );
		wp_update_post( array( 'ID' => $interiors[0]->ID, 'menu_order' => 0 ) );
		wp_update_post( array( 'ID' => $interiors[0]->ID, 'post_parent' => 0 ) );

		foreach ( array_slice( $interiors, 1 ) as $interior )
		{
			wp_update_post( array( 'ID' => $interior->ID, 'post_parent' => $interiors[0]->ID ) );
		}
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @since 1.0.3
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain( 'wpfunnel', false, 'wp-funnel-manager/languages/' );
	}
}
