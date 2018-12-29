<?php

/**
 * Main funnel class.
 */
class Funnel
{
	public function __construct()
	{
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	public function register_taxonomies()
	{
		register_taxonomy( 'funnel', array( 'wp_block', 'page' ), array(
			'hierarchical' => true,
			'labels'       => array(
				'name'          => __( 'Funnels', 'wpfunnel' ),
				'singular_name' => __( 'Funnel', 'wpfunnel' ),
			),
			'rewrite'        => array(
				'slug'         => 'funnel',
				'hierarchical' => true
			)
		) );
	}
}
