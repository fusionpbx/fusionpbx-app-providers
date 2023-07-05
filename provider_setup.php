<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('provider_add')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the provider
	if (isset($_GET['id'])) {

		//get the provider id
			$id = $_GET['id'];

		//add the provider
			$provider = new providers;
			$provider->id = $id;
			$provider->setup();

		//set the add message
			message::add($text['message-add']);

		//redirect the user
			header("Location: providers.php");
			return;
	}

//provider selection
	$provider_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/*/resources/providers/settings.php");
	foreach ($provider_list as $setting_path) {
		include($setting_path);
	}

//installed providers
	$sql = "select provider_uuid from v_providers ";
	$database = new database;
	$database_providers = $database->select($sql, null, 'all');

//loop through installed providers
	$x = 0;
	foreach($array['providers'] as $row) {
		foreach ($database_providers as $field) {
			if ($row['provider_uuid'] == $field['provider_uuid']) {
				$array['providers'][$x]['provider_installed'] = 'true';
			}
		}
		$x++;
	}
	unset($sql);

//include header
	$document['title'] = $text['title-providers'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-providers']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'providers.php'.(is_numeric($page) ? '?page='.$page : null)]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

//providers
	foreach($array['providers'] as $row) {
		echo "<div class='row'>\n";
		echo "	<div class='col-sm-4' style='padding-top: 0px;'>\n";
		echo "		<br /><br />\n";
		if (file_exists($_SERVER["PROJECT_ROOT"]."/app/providers/resources/images/".$row['provider_name'].".png")) {
			echo "		<a href='".$row['provider_website']."' target='_blank'>\n";
			echo "			<img src='/app/providers/resources/images/".$row['provider_name'].".png' style='width: 200px;' class='center-block img-responsive'><br>\n";
			echo "		</a>\n";
		}
		else {
			echo "		<h2>".$row['provider_name']."</h2>\n";
		}
		echo "	</div>\n";
		echo "	<div class='col-sm-8' style='padding-top: 0px;'>\n";
		echo "		<br /><br />\n";
		echo "		<strong>".$text['label-features']."</strong><br />\n";
		echo "		".$row['provider_description']."\n";
		echo "		<br /><br />\n";
		echo "		<strong>".$text['label-region']."</strong><br />\n";
		echo "		".$row['provider_region']."\n";
		echo "		<br /><br />\n";
		//echo "		<strong>".$text['label-about']."</strong><br />\n";
		//echo "		".$row['provider_about']."\n";
		//echo "		<br /><br />\n";

		if (isset($row['provider_website']) && strlen($row['provider_website']) > 0) {
			echo "		<a href='".$row['provider_website']."' target='_blank'><button type=\"button\" class=\"btn btn-success\">".$text['button-website']."</button></a>\n";
		}
		//echo "		<a href='http://skye.tel/fusion-pricing' target='_blank'><button type=\"button\" class=\"btn btn-success\">".$text['button-pricing']."</button></a>\n";
		if (isset($row['provider_signup']) && strlen($row['provider_signup']) > 0) {
			echo "		<a href='".$row['provider_signup']."' target='_blank'><button type=\"button\" class=\"btn btn-primary\">".$text['button-signup']."</button></a>\n";
		}
		if ($row['provider_installed'] == 'true') {
			echo "			<a href=\"#\" onclick=\"\"><button type=\"button\" class=\"btn btn-success\">".$text['label-installed']."</button></a>\n";
			//echo "		<a href=\"provider_delete.php?provider_uuid=".$row['provider_uuid']."\" onclick=\"return confirm(".$text['confirm-delete'].")\"><button type=\"button\" class=\"btn btn-danger\">".$text['button-remove']."</button></a>\n";
		}
		else {
			//$text['confirm-setup'] = 'Are you sure you want to add this?';
			echo "		<a href=\"provider_setup.php?id=".md5($row['provider_name'])."\" onclick=\"return confirm(".$text['confirm-setup'].")\"><button type=\"button\" class=\"btn btn-success\">".$text['button-setup']."</button></a>\n";
			//echo "	<button type=\"button\" onclick=\"window.location='provider_setup.php?provider=skyetel'\" class=\"btn btn-primary\">".$text['button-setup']."</button>\n";
		}
		echo "	</div>\n";
		echo "</div>\n";
		echo "<div style='clear: both;'></div>\n";

		echo "<br/><br/><hr /><br/>\n";
	}

	echo "<br/><br/><hr /><br/>\n";

//include the footer
	require_once "resources/footer.php";

?>
