<?php
function collector_settings_init()
{
	register_setting( 'collector', 'collector_settings' );

	add_settings_section(
		'collector_section_github',
		__('Github API', 'collector'),
		'collector_section_github_api_callback',
		'collector'
	);

	add_settings_field(
		'collector_field_ghapi_client_id',
		__( 'GitHub API Client ID', 'collector' ),
		'collector_field_cb',
		'collector',
		'collector_section_github',
		[
			'label_for'         => 'collector_ghapi_client_id',
			'class'             => 'collector_row',
			'collector_custom_data' => 'custom',
		]
	);

	add_settings_field(
		'collector_field_ghapi_secret',
		__( 'GitHub API Secret', 'collector' ),
		'collector_field_cb',
		'collector',
		'collector_section_github',
		[
			'label_for'         => 'collector_ghapi_secret',
			'class'             => 'collector_row',
			'collector_custom_data' => 'custom',
		]
	);

	add_settings_field(
		'collector_ghapi_repo_name',
		__( 'GitHub Repository Name', 'collector' ),
		'collector_field_cb',
		'collector',
		'collector_section_github',
		[
			'label_for'         => 'collector_ghapi_repo_name',
			'class'             => 'collector_row',
			'collector_custom_data' => 'custom',
		]
	);

	add_settings_field(
		'collector_ghapi_repo_branch',
		__( 'GitHub Repository Branch', 'collector' ),
		'collector_field_cb',
		'collector',
		'collector_section_github',
		[
			'label_for'         => 'collector_ghapi_repo_branch',
			'class'             => 'collector_row',
			'collector_custom_data' => 'custom',
		]
	);
}

function collector_render_settings_page()
{?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields('collector');
			do_settings_sections('collector');
			submit_button('Save Settings');
			?>
		</form>
	</div>
<?php
}

function collector_section_github_api_callback($args)
{?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"></p>
<?php
}

function collector_field_cb($args)
{
	$options = get_option( 'collector_settings' ); ?>
	<input
		id="<?=esc_attr($args['label_for']);?>"
		type="text"
		class="regular-text"
		data-custom="<?=esc_attr($args['collector_custom_data']);?>"
		name="collector_settings[<?=esc_attr($args['label_for']); ?>]"
		value="<?=esc_attr($options[ $args['label_for'] ]??'');?>"
	>
<?php
}
