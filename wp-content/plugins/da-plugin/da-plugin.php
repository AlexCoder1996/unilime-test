<?php
/**
 * Plugin Name: DA Internal links plugin
 * Description: Internal links plugin for Unilime
 * Author:      Dmitry Alekseev
 */


add_action( 'pre_post_update', 'da_post_save', 10, 2 );

function da_post_save($post_id, $post_data) {

	$da_post_content = $post_data['post_content'];
	$da_url = str_replace(
		array( 'http://', 'https://', 'http://www.', 'www.' ),
		'',
		get_bloginfo( 'wpurl' )
	) . '/';

	$da_count_internal_links = substr_count( $da_post_content, $da_url );
	$da_minimal_number_internal_links = get_option( 'number_internal_links' );

	if ( wp_is_post_revision( $post_id ) )
		return;

	if ( $post_data['post_type'] == 'post' || $post_data['post_type'] == 'page' ) {

		if ( $da_count_internal_links < $da_minimal_number_internal_links ) {

			update_option('da_notifications', json_encode(array('error', "Post/page contains less than $da_minimal_number_internal_links internal links.")));

			header( 'Location: '.get_edit_post_link( $post_id, 'redirect' ) );
			exit;
		}
	}
}


add_action( 'admin_notices', 'da_error_internal_links_notification' );

function da_error_internal_links_notification() {
	$notifications = get_option( 'da_notifications' );

	if ( !empty( $notifications ) ) {
		$notifications = json_decode( $notifications );

		switch ( $notifications[0] ) {
			case 'error':
			case 'updated':
			case 'update-nag':
				$class = $notifications[0];
				break;
			default:
				$class = 'error';
				break;
		}

		$is_dismissable = '';
		if ( isset( $notifications[2] ) && $notifications[2] == true )
			$is_dismissable = 'is_dismissable';

		echo '<div class="' . $class . ' notice ' . $is_dismissable . '">';
		echo '<p>'. $notifications[1] . '</p>';
		echo '</div>';

		update_option( 'da_notifications', false );
	}
}



/*
 *
 *  Functions for plugin setting page
 *
 */


add_action( 'admin_menu', 'da_plugin_menu_page', 25 );

function da_plugin_menu_page(){

	add_submenu_page(
		'options-general.php',
		'Internal links settings',
		'Internal links',
		'manage_options',
		'internal_links',
		'da_plugin_page_callback'
	);
}

function da_plugin_page_callback() {
	echo '<div class="wrap">
	<h1>' . get_admin_page_title() . '</h1>
	<form method="post" action="options.php">';

	settings_fields( 'da_plugin_settings' );
	do_settings_sections( 'internal_links' );
	submit_button();

	echo '</form></div>';
}



add_action( 'admin_init',  'da_plugin_fields' );

function da_plugin_fields(){

	register_setting(
		'da_plugin_settings',
		'number_internal_links',
		'absint'
	);

	add_settings_section(
		'plugin_section_settings_id',
		'',
		'',
		'internal_links'
	);

	add_settings_field(
		'number_internal_links',
		'Minimal number of internal links in post/page',
		'number_internal_links_field',
		'internal_links',
		'plugin_section_settings_id',
		array(
			'label_for' => 'number_internal_links',
			'class' => 'da-class',
			'name' => 'number_internal_links',
		)
	);

}

function number_internal_links_field( $args ){

	$value = get_option( $args[ 'name' ] );

	printf(
		'<input type="number" min="1" id="%s" name="%s" value="%d" />',
		esc_attr( $args[ 'name' ] ),
		esc_attr( $args[ 'name' ] ),
		absint( $value )
	);

}

