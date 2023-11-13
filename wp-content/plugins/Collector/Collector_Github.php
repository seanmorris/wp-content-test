<?php

const COLLECTOR_GITHUB_ACCEPT_PATH = '/wp-admin/?page=collector_github_accept';

function collector_render_github_login_page()
{
	$ghapi = get_option('collector_settings') ?? []; ?>
	<button id = "collector-github-login">Login with Github</button>
	<script src = "/wp-content/plugins/Collector/collector-ghapi.js" type = "text/javascript"></script>
<?php
}

function collector_github_accept()
{
	$ghapi = get_option('collector_settings') ?? [];

	$authUrl = 'https://github.com/login/oauth/access_token';
	$headers = 'Accept: application/json';
	$content = [
		'client_id' => $ghapi['collector_ghapi_client_id'] ?? '',
		'client_secret' =>  $ghapi['collector_ghapi_secret'] ?? '',
		'redirect_uri' => 'http://' . $_SERVER['HTTP_HOST'] . COLLECTOR_GITHUB_ACCEPT_PATH,
		'code' => $_GET['code'] ?? '',
		'state' => '---'
	];

	$options = ['http' => [
		'max_redirects' => '0',
		'content' => http_build_query($content),
		'header' => $headers,
		'method' => 'POST',
	]];

	$context = stream_context_create($options);
	$json = file_get_contents($authUrl, false, $context);

	$token = json_decode($json); ?>
	<script type = "text/javascript">
		opener.postMessage(
			{type: 'ghapi-token', token: <?=json_encode($token);?>},
			new URL(document.referrer).origin
		);
		console.log(<?=json_encode($token);?>, new URL(document.referrer).origin);
	</script>
<?php
}

