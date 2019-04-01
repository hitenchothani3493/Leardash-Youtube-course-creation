<?php

class ldYtCoursesSettings {
	function __construct() 
    {	
    	add_action('admin_init', array(&$this, 'admin_init'));
        add_action('admin_menu', array(&$this, 'admin_menu'), 99);
	}

	function admin_init()
	{
		register_setting( 'ld-yt-courses', 'ld_yt_courses_settings' );
		add_settings_section( 'ld-yt-courses', '', array(&$this, 'section_intro'), 'ld-yt-courses' );

		add_settings_field( 'ldytcourses_api', __( 'Youtube API', 'ldytcourses' ), array(&$this, 'setting_youtube_api'), 'ld-yt-courses', 'ld-yt-courses' );
	}
	
	function admin_menu() 
	{
		$icon_url = plugins_url( '/images/favicon.png', __FILE__ );
		$page_hook = add_menu_page( __( 'Youtube Courses Settings', 'ldytcourses'), 'ldYtCourses', 'update_core', 'ld-yt-courses', array(&$this, 'settings_page'), $icon_url );
		add_submenu_page( 'ld-yt-courses', __( 'Settings', 'ldytcourses' ), __( 'Youtube Courses Settings', 'ldytcourses' ), 'update_core', 'ld-yt-courses', array(&$this, 'settings_page') );
	}
	
	function settings_page()
	{
		?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"></div>
			<h2><?php _e('Youtube Courses Settings', 'ldytcourses'); ?></h2>
			<?php if( isset($_GET['settings-updated']) && $_GET['settings-updated'] ){ ?>
			<div id="setting-error-settings_updated" class="updated settings-error"> 
				<p><strong><?php _e( 'Settings saved.', 'ldytcourses' ); ?></strong></p>
			</div>
			<?php } ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'ld-yt-courses' ); ?>
				<?php do_settings_sections( 'ld-yt-courses' ); ?>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'ldytcourses' ); ?>" /></p>
			</form>
		</div>
		<?php
	}
	
	function setting_youtube_api()
	{
		$options = get_option( 'ld_yt_courses_settings' );
		if( !isset($options['api_key']) )
			$options['api_key'] = '';
		
		echo '<input type="text" name="ld_yt_courses_settings[api_key]" class="regular-text" value="'. $options['api_key'] .'" />
		<p class="description">'. __('Genrate API from https://console.cloud.google.com/apis/', 'ldytcourses') . '</p>';
	}
}

?>