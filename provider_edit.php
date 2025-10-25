<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('provider_add') || permission_exists('provider_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//connect to the database
	$database = database::new();

//backwards compatibility for settings object
	if (empty($settings) || !($settings instanceof settings)) {
		$settings = new settings;
	}

//check for new switch style
	$new_switch_style = version_compare(software::version(), '5.5', '>=');

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$provider_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
		$export = $_REQUEST["export"] ?? null;
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (!empty($_POST) && is_array($_POST)) {
		$provider_name = $_POST["provider_name"];
		$provider_settings = $_POST["provider_settings"];
		$provider_addresses = $_POST["provider_addresses"];
		$domain_uuid = $_POST["domain_uuid"];
		$provider_enabled = $_POST["provider_enabled"] ?? 'false';
		$provider_description = $_POST["provider_description"];
	}

//process the user data and save it to the database
	if (!empty($_POST) && count($_POST) > 0 && (empty($_POST["persistformvar"]) || strlen($_POST["persistformvar"]) == 0)) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: providers.php');
				exit;
			}

		//process the http post data by submitted action
			if (!empty($_POST['action']) && strlen($_POST['action']) > 0) {

				//prepare the array(s)
				$x = 0;
				if (is_array($_POST['provider_settings'])) {
					foreach ($_POST['provider_settings'] as $row) {
						if (is_uuid($row['provider_setting_uuid']) && (!empty($row['checked']) && $row['checked'] === 'true')) {
							$array['providers'][$x]['checked'] = $row['checked'];
							$array['providers'][$x]['provider_settings'][]['provider_setting_uuid'] = $row['provider_setting_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (is_array($_POST['provider_addresses'])) {
					foreach ($_POST['provider_addresses'] as $row) {
						if (is_uuid($row['provider_address_uuid']) && (!empty($row['checked']) && $row['checked'] === 'true')) {
							$array['providers'][$x]['checked'] = $row['checked'];
							$array['providers'][$x]['provider_addresses'][]['provider_address_uuid'] = $row['provider_address_uuid'];
							$x++;
						}
					}
				}

				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('provider_add')) {
							$database->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('provider_delete')) {
							$database->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('provider_update')) {
							$database->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: provider_edit.php?id='.$id);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			//if (strlen($provider_name) == 0) { $msg .= $text['message-required']." ".$text['label-provider_name']."<br>\n"; }
			//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			//if (strlen($provider_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-provider_enabled']."<br>\n"; }
			//if (strlen($provider_description) == 0) { $msg .= $text['message-required']." ".$text['label-provider_description']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br>";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//add the provider_uuid
			if (!is_uuid($_POST["provider_uuid"])) {
				$provider_uuid = uuid();
			}

		//prepare the array
			$array['providers'][0]['provider_uuid'] = $provider_uuid;
			$array['providers'][0]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
			$array['providers'][0]['provider_name'] = $provider_name;
			$array['providers'][0]['provider_enabled'] = $provider_enabled;
			$array['providers'][0]['provider_description'] = $provider_description;
			$y = 0;
			if (is_array($provider_settings)) {
				foreach ($provider_settings as $row) {
					if (strlen($row['provider_setting_category']) > 0) {
						$array['providers'][0]['provider_settings'][$y]['application_uuid'] = $row["application_uuid"];
						$array['providers'][0]['provider_settings'][$y]['provider_setting_uuid'] = $row["provider_setting_uuid"];
						$array['providers'][0]['provider_settings'][$y]['provider_setting_category'] = $row["provider_setting_category"];
						$array['providers'][0]['provider_settings'][$y]['provider_setting_subcategory'] = $row["provider_setting_subcategory"];
						$array['providers'][0]['provider_settings'][$y]['provider_setting_type'] = $row["provider_setting_type"];
						$array['providers'][0]['provider_settings'][$y]['provider_setting_name'] = $row["provider_setting_name"];
						$array['providers'][0]['provider_settings'][$y]['provider_setting_value'] = $row["provider_setting_value"];
						//$array['providers'][0]['provider_settings'][$y]['provider_setting_order'] = $row["provider_setting_order"];
						$array['providers'][0]['provider_settings'][$y]['provider_setting_enabled'] = $row["provider_setting_enabled"];
						$array['providers'][0]['provider_settings'][$y]['provider_setting_description'] = $row["provider_setting_description"];
						$y++;
					}
				}
			}
			$y++;
			if (is_array($provider_addresses)) {
				foreach ($provider_addresses as $row) {
					if (strlen($row['provider_address_cidr']) > 0) {
						$array['providers'][0]['provider_addresses'][$y]['provider_address_uuid'] = $row["provider_address_uuid"];
						$array['providers'][0]['provider_addresses'][$y]['provider_address_cidr'] = $row["provider_address_cidr"];
						$array['providers'][0]['provider_addresses'][$y]['provider_address_enabled'] = $row["provider_address_enabled"];
						$array['providers'][0]['provider_addresses'][$y]['provider_address_description'] = $row["provider_address_description"];
						$y++;
					}
				}
			}

		//save the data
			$database->app_name = 'providers';
			$database->app_uuid = '35187839-237e-4271-b8a1-9b9c45dc8833';
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: providers.php');
				header('Location: provider_edit.php?id='.urlencode($provider_uuid));
				return;
			}
	}

//pre-populate the form
	if (!empty($_GET) && is_array($_GET) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$sql = "select ";
		$sql .= " provider_uuid, ";
		$sql .= " provider_name, ";
		//$sql .= " provider_settings, ";
		//$sql .= " provider_addresses, ";
		$sql .= " domain_uuid, ";
		$sql .= " cast(provider_enabled as text), ";
		$sql .= " provider_description ";
		$sql .= "from v_providers ";
		$sql .= "where provider_uuid = :provider_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['provider_uuid'] = $provider_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$provider_name = $row["provider_name"];
			//$provider_settings = $row["provider_settings"];
			//$provider_addresses = $row["provider_addresses"];
			$domain_uuid = $row["domain_uuid"];
			$provider_enabled = $row["provider_enabled"];
			$provider_description = $row["provider_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the child data
	if (!empty($provider_uuid) && is_uuid($provider_uuid)) {
		$sql = "select ";
		$sql .= " provider_address_uuid, ";
		$sql .= " provider_address_cidr, ";
		$sql .= " cast(provider_address_enabled as text), ";
		$sql .= " provider_address_description ";
		$sql .= "from v_provider_addresses ";
		$sql .= "where provider_uuid = :provider_uuid ";
		$sql .= "order by provider_address_cidr asc ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['provider_uuid'] = $provider_uuid;
		$provider_addresses = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $provider_address_uuid
	if (empty($provider_address_uuid) || !is_uuid($provider_address_uuid)) {
		$provider_address_uuid = uuid();
	}

//add an empty row
	//$x = is_array($provider_addresses) ? count($provider_addresses) : 0;
	//$provider_addresses[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
	//$provider_addresses[$x]['provider_uuid'] = $provider_uuid;
	//$provider_addresses[$x]['provider_address_uuid'] = uuid();
	//$provider_addresses[$x]['provider_address_cidr'] = '';
	//$provider_addresses[$x]['provider_address_enabled'] = '';
	//$provider_addresses[$x]['provider_address_description'] = '';

//get the child data
	if (!empty($provider_uuid) && is_uuid($provider_uuid)) {
		$sql = "select ";
		$sql .= " provider_setting_uuid, ";
		$sql .= " application_uuid, ";
		$sql .= " provider_setting_category, ";
		$sql .= " provider_setting_subcategory, ";
		$sql .= " provider_setting_type, ";
		$sql .= " provider_setting_name, ";
		$sql .= " provider_setting_value, ";
		//$sql .= " provider_setting_order, ";
		$sql .= " cast(provider_setting_enabled as text), ";
		$sql .= " provider_setting_description ";
		$sql .= "from v_provider_settings ";
		$sql .= "where provider_uuid = :provider_uuid ";
		$sql .= "order by provider_setting_category, provider_setting_subcategory, provider_setting_name asc ";
		$parameters['provider_uuid'] = $provider_uuid;
		$provider_settings = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $provider_setting_uuid
	if (empty($provider_setting_uuid) || !is_uuid($provider_setting_uuid)) {
		$provider_setting_uuid = uuid();
	}

//export provider settings
	if (isset($export) && $export == 'true') {
		//prepare the array
			$y = 0;
			$array['providers'][$y]['provider_uuid'] = $provider_uuid;
			$array['providers'][$y]['provider_name'] = $provider_name;
			$array['providers'][$y]['provider_enabled'] = $provider_enabled;
			$array['providers'][$y]['provider_description'] = $provider_description;

			if (is_array($provider_settings)) {
				$y = 0;
				foreach ($provider_settings as $row) {
					if (strlen($row['provider_setting_category']) > 0) {
						//export values except authentication details
						switch ($row['provider_setting_name']) {
						case "http_auth_username":
							$provider_setting_value = '';
							break;
						case "http_auth_password":
							$provider_setting_value = '';
							break;
						default:
							$provider_setting_value = $row["provider_setting_value"];
						}

						//add to the array
						$array['provider_settings'][$y]['provider_uuid'] = $provider_uuid;
						$array['provider_settings'][$y]['application_uuid'] = $row["application_uuid"];
						$array['provider_settings'][$y]['provider_setting_uuid'] = $row["provider_setting_uuid"];
						$array['provider_settings'][$y]['provider_setting_category'] = $row["provider_setting_category"];
						$array['provider_settings'][$y]['provider_setting_subcategory'] = $row["provider_setting_subcategory"];
						$array['provider_settings'][$y]['provider_setting_type'] = $row["provider_setting_type"];
						$array['provider_settings'][$y]['provider_setting_name'] = $row["provider_setting_name"];
						$array['provider_settings'][$y]['provider_setting_value'] = $provider_setting_value;
						$array['provider_settings'][$y]['provider_setting_order'] = $row["provider_setting_order"];
						$array['provider_settings'][$y]['provider_setting_enabled'] = $row["provider_setting_enabled"];
						$array['provider_settings'][$y]['provider_setting_description'] = $row["provider_setting_description"];
						$y++;
					}
				}
			}
			if (is_array($provider_addresses)) {
				$y = 0;
				foreach ($provider_addresses as $row) {
					if (strlen($row['provider_address_cidr']) > 0) {
						$array['provider_addresses'][$y]['provider_uuid'] = $provider_uuid;
						$array['provider_addresses'][$y]['provider_address_uuid'] = $row["provider_address_uuid"];
						$array['provider_addresses'][$y]['provider_address_cidr'] = $row["provider_address_cidr"];
						$array['provider_addresses'][$y]['provider_address_enabled'] = $row["provider_address_enabled"];
						$array['provider_addresses'][$y]['provider_address_description'] = $row["provider_address_description"];
						$y++;
					}
				}
			}
			//view_array($array);

			echo "<textarea style=\"width: 100%; max-width: 100%; height: 100%; max-height: 100%;\">\n";
			if (is_array($array['providers'])) {
				echo "\$x = 0;\n";
				foreach ($array['providers'] as $row) {
					foreach ($row as $key => $value) {
						echo "\$array['providers'][\$x]['{$key}'] = '{$value}';\n";
					}
				}
			}
			if (is_array($array['provider_settings'] )) {
				echo "\$y = 0;\n";
				$count = count($array['provider_settings']);
				$i = 1;
				foreach ($array['provider_settings'] as $row) {
					foreach ($row as $key => $value) {
						echo "\$array['providers'][\$x]['provider_settings'][\$y]['{$key}'] = '{$value}';\n";
					}
					if ($i < $count) {
						echo "\$y++;\n";
					}
					$i++;
				}
			}
			if (is_array($array['provider_addresses'] )) {
				$y = 0;
				echo "\$y = 0;\n";
				$count = count($array['provider_addresses']);
				$i = 1;
				foreach ($array['provider_addresses'] as $row) {
					foreach ($row as $key => $value) {
						echo "\$array['providers'][\$x]['provider_addresses'][\$y]['{$key}'] = '{$value}';\n";
					}
					if ($i < $count) {
						echo "\$y++;\n";
					}
					$i++;
				}
			}
			echo "</textarea>\n";
			exit;
	}

//add an empty row
	//$x = is_array($provider_settings) ? count($provider_settings) : 0;

//add an empty row(s) to the provider settings array
	if (!empty($provider_settings) && is_array($provider_settings) && count($provider_settings) == 0) {
		if (isset($_SESSION['providers']['setting_add_rows']['numeric'])) {
			$rows = $_SESSION['providers']['setting_add_rows']['numeric'];
		}
		else {
			$rows = 15;
		}
		$id = 0;
	}
	if (!empty($provider_settings) && is_array($provider_settings) && count($provider_settings) > 0) {
		if (isset($_SESSION['providers']['setting_edit_rows']['numeric'])) {
			$rows = $_SESSION['providers']['setting_edit_rows']['numeric'];
		}
		else {
			$rows = 1;
		}
		$id = count($provider_settings)+1;
	}
	if (!empty($rows) && is_array($rows) && @sizeof($rows) != 0) {
		for ($x = 0; $x < $rows; $x++) {
			$provider_settings[$id]['domain_uuid'] = null;
			$provider_settings[$id]['provider_uuid'] = $provider_uuid;
			$provider_settings[$id]['provider_setting_uuid'] = uuid();
			$provider_settings[$id]['provider_setting_category'] = '';
			$provider_settings[$id]['provider_setting_subcategory'] = '';
			$provider_settings[$id]['provider_setting_type'] = '';
			$provider_settings[$id]['provider_setting_name'] = '';
			$provider_settings[$id]['provider_setting_value'] = '';
			//$provider_settings[$id]['provider_setting_order'] = '';
			$provider_settings[$id]['provider_setting_enabled'] = 'true';
			$provider_settings[$id]['provider_setting_description'] = '';
			$id++;
		}
	}

//add an empty row(s) to the provider addresses array
	if (!empty($provider_addresses) && is_array($provider_addresses) && count($provider_addresses) == 0) {
		if (isset($_SESSION['providers']['address_add_rows']['numeric'])) {
			$rows = $_SESSION['providers']['address_add_rows']['numeric'];
		}
		else {
			$rows = 5;
		}
		$id = 0;
	}
	if (!empty($provider_addresses) && is_array($provider_addresses) && count($provider_addresses) > 0) {
		if (isset($_SESSION['providers']['address_edit_rows']['numeric'])) {
			$rows = $_SESSION['providers']['address_edit_rows']['numeric'];
		}
		else {
			$rows = 1;
		}
		$id = count($provider_addresses)+1;
	}
	if (!empty($rows) && is_array($rows) && @sizeof($rows) != 0) {
		for ($x = 0; $x < $rows; $x++) {
			$provider_addresses[$id]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
			$provider_addresses[$id]['provider_uuid'] = $provider_uuid;
			$provider_addresses[$id]['provider_address_uuid'] = uuid();
			$provider_addresses[$id]['provider_address_cidr'] = '';
			$provider_addresses[$id]['provider_address_enabled'] = 'true';
			$provider_addresses[$id]['provider_address_description'] = '';
			$id++;
		}
	}

//get the $apps array from the installed apps from the core and mod directories
	$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
	$x = 0;
	foreach ($config_list as &$config_path) {
		include($config_path);
		$x++;
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-provider'];
	require_once "resources/header.php";

//header sets a global switch style
	global $input_toggle_style_switch;

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='provider_uuid' value='".escape($provider_uuid ?? '')."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-provider']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'providers.php']);
	if ($action == 'update') {
		if (permission_exists('provider_setting_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('provider_setting_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-providers']."\n";
	echo "<br><br>\n";

	if ($action == 'update') {
		if (permission_exists('provider_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('provider_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-provider_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='provider_name' maxlength='255' value='".escape($provider_name ?? '')."'>\n";
	echo "<br>\n";
	echo $text['description-provider_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-provider_settings']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable'>".$text['label-application']."</td>\n";
	echo "			<td class='vtable'>".$text['label-provider_setting_category']."</td>\n";
	echo "			<td class='vtable'>".$text['label-provider_setting_subcategory']."</td>\n";
	echo "			<td class='vtable'>".$text['label-provider_setting_type']."</td>\n";
	echo "			<td class='vtable'>".$text['label-provider_setting_name']."</td>\n";
	echo "			<td class='vtable'>".$text['label-provider_setting_value']."</td>\n";
	//echo "		<td class='vtable'>".$text['label-provider_setting_order']."</td>\n";
	echo "			<td class='vtable'>".$text['label-provider_setting_enabled']."</td>\n";
	echo "			<td class='vtable'>".$text['label-provider_setting_description']."</td>\n";
	//if (is_array($provider_settings) && @sizeof($provider_settings) > 1 && permission_exists('provider_setting_delete')) {
	//	echo "		<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_details', 'delete_toggle_details');\" onmouseout=\"swap_display('delete_label_details', 'delete_toggle_details');\">\n";
	//	echo "			<span id='delete_label_details'>".$text['label-action']."</span>\n";
	//	echo "			<span id='delete_toggle_details'><input type='checkbox' id='checkbox_all_details' name='checkbox_all' onclick=\"edit_all_toggle('details'); checkbox_on_change(this);\"></span>\n";
	//	echo "		</td>\n";
	//}
	echo "		</tr>\n";
// 	echo "<tr><td colspan='50'>";
// 	view_array($apps, 0);
// 	echo "</td></tr>\n";
	$x = 0;
	$provider_settings[] = null; // blank row
	if (!empty($provider_settings) && is_array($provider_settings) && @sizeof($provider_settings) != 0) {
		foreach ($provider_settings as $row) {
			echo "		<tr>\n";
			//echo "			<input type='hidden' name='provider_settings[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
			echo "			<input type='hidden' name='provider_settings[$x][provider_uuid]' value=\"".escape($row["provider_uuid"] ?? $provider_uuid ?? uuid())."\">\n";
			echo "			<input type='hidden' name='provider_settings[$x][provider_setting_uuid]' value=\"".escape($row["provider_setting_uuid"] ?? uuid())."\">\n";
			echo "			<td class='formfld'>\n";
			echo "				<select class='formfld' name='provider_settings[$x][application_uuid]'>\n";
			echo "					<option value=''></option>\n";
			if (!empty($apps) && is_array($apps) && @sizeof($apps) != 0) {
				foreach ($apps as $app) {
					if (!empty($app['uuid']) && is_uuid($app['uuid'])) {
						echo "		<option value='".$app['uuid']."' ".(!empty($row['application_uuid']) && $app['uuid'] == $row['application_uuid'] ? "selected='selected'" : null).">".escape($app['name'] ?? '')."</option>\n";
					}
				}
			}
			echo "				</select>\n";
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			echo "				<input class='formfld' style='width: 120px;' type='text' name='provider_settings[$x][provider_setting_category]' maxlength='255' value=\"".escape($row["provider_setting_category"] ?? '')."\">\n";
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			echo "				<input class='formfld' style='width: 120px;' type='text' name='provider_settings[$x][provider_setting_subcategory]' maxlength='255' value=\"".escape($row["provider_setting_subcategory"] ?? '')."\">\n";
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			echo "				<select class='formfld' name='provider_settings[$x][provider_setting_type]'\">\n";
			if ($row["provider_setting_type"] == "text") {
				echo "					<option value='text' selected='selected'>Text</option>\n";
			}
			else {
				echo "					<option value='text'>Text</option>\n";
			}
			if ($row["provider_setting_type"] == "array") {
				echo "					<option value='array' selected='selected'>Array</option>\n";
			}
			else {
				echo "					<option value='array'>Array</option>\n";
			}
			echo "				</select>\n";
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			echo "				<input class='formfld' type='text' name='provider_settings[$x][provider_setting_name]' maxlength='255' value=\"".escape($row["provider_setting_name"] ?? '')."\">\n";
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			if (substr($row["provider_setting_name"] ?? '', -8) == 'username' || substr($row["provider_setting_name"] ?? '', -8) == 'password') {
				echo "				<input class='formfld password' type='password' name='provider_settings[$x][provider_setting_value]' autocomplete='new-password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' value=\"".escape($row["provider_setting_value"] ?? '')."\">\n";
			}
			else {
				echo "				<input class='formfld' type='text' name='provider_settings[$x][provider_setting_value]' maxlength='255' value=\"".escape($row["provider_setting_value"] ?? '')."\">\n";
			}
			echo "			</td>\n";
			/*
			echo "			<td class='formfld'>\n";
			echo "				<select name='provider_settings[$x][provider_setting_order]' class='formfld'>\n";
			$i=0;
			while ($i<=999) {
				$selected = ($i == $row["provider_setting_order"]) ? "selected" : null;
				if (strlen($i) == 1) {
					echo "					<option value='00$i' ".$selected.">00$i</option>\n";
				}
				if (strlen($i) == 2) {
					echo "					<option value='0$i' ".$selected.">0$i</option>\n";
				}
				if (strlen($i) == 3) {
					echo "					<option value='$i' ".$selected.">$i</option>\n";
				}
				$i++;
			}
			echo "				</select>\n";
			echo "			</td>\n";
			*/
			echo "			<td class='formfld'>\n";
			if ($new_switch_style) {
				if ($input_toggle_style_switch) {
					echo "	<span class='switch'>\n";
				}
				echo "		<select class='formfld' id='provider_settings_".$x."' name='provider_settings[$x][provider_setting_enabled]'>\n";
				echo "			<option value='true'>".$text['option-true']."</option>\n";
				echo "			<option value='false' ".($row['provider_setting_enabled'] == 'false' ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
				echo "		</select>\n";
				if ($input_toggle_style_switch) {
					echo "		<span class='slider'></span>\n";
					echo "	</span>\n";
				}
			}
			else {
				if (substr($settings->get('theme', 'input_toggle_style', ''), 0, 6) == 'switch') {
					echo "			<label class='switch'>\n";
					echo "				<input type='checkbox' name='provider_settings[$x][provider_setting_enabled]' value='true' ".(!empty($row['provider_setting_enabled']) && $row['provider_setting_enabled'] == 'true' ? "checked='checked'" : '').">\n";
					echo "				<span class='slider'></span>\n";
					echo "			</label>\n";
				}
				else {
					echo "			<select class='formfld' name='provider_settings[$x][provider_setting_enabled]'>\n";
					echo "				<option value='true'>".$text['option-true']."</option>\n";
					echo "				<option value='false' ".(empty($row['provider_setting_enabled']) || $row['provider_setting_enabled'] == 'false' ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
					echo "			</select>\n";
				}
			}
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			echo "				<input class='formfld' type='text' name='provider_settings[$x][provider_setting_description]' maxlength='255' value=\"".escape($row["provider_setting_description"] ?? '')."\">\n";
			echo "			</td>\n";
			if (is_array($provider_settings) && @sizeof($provider_settings) > 1 && permission_exists('provider_setting_delete')) {
				if (!empty($row['provider_setting_uuid']) && is_uuid($row['provider_setting_uuid'])) {
					echo "		<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
					echo "			<input type='checkbox' name='provider_settings[".$x."][checked]' value='true' class='chk_delete checkbox_details' onclick=\"checkbox_on_change(this);\">\n";
					echo "		</td>\n";
				}
				else {
					echo "		<td></td>\n";
				}
			}
			echo "		</tr>\n";
			$x++;
		}
	}
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-provider_addresses']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable'>".$text['label-provider_address_cidr']."</td>\n";
	echo "			<td class='vtable'>".$text['label-provider_address_enabled']."</td>\n";
	echo "			<td class='vtable'>".$text['label-provider_address_description']."</td>\n";
	//if (is_array($provider_addresses) && @sizeof($provider_addresses) > 1 && permission_exists('provider_address_delete')) {
	//	echo "		<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_details', 'delete_toggle_details');\" onmouseout=\"swap_display('delete_label_details', 'delete_toggle_details');\">\n";
	//	echo "			<span id='delete_label_details'>".$text['label-action']."</span>\n";
	//	echo "			<span id='delete_toggle_details'><input type='checkbox' id='checkbox_all_details' name='checkbox_all' onclick=\"edit_all_toggle('details'); checkbox_on_change(this);\"></span>\n";
	//	echo "		</td>\n";
	//}
	echo "		</tr>\n";
	$provider_addresses[] = null; // blank row
	if (!empty($provider_addresses) && is_array($provider_addresses) && @sizeof($provider_addresses) != 0) {
		foreach ($provider_addresses as $x => $row) {
			echo "		<tr>\n";
			echo "			<input type='hidden' name='provider_addresses[$x][domain_uuid]' value=\"".escape($row["domain_uuid"] ?? '')."\">\n";
			echo "			<input type='hidden' name='provider_addresses[$x][provider_uuid]' value=\"".escape($row["provider_uuid"] ?? $provider_uuid ?? uuid())."\">\n";
			echo "			<input type='hidden' name='provider_addresses[$x][provider_address_uuid]' value=\"".escape($row["provider_address_uuid"] ?? uuid())."\">\n";
			echo "			<td class='formfld'>\n";
			echo "				<input class='formfld' type='text' name='provider_addresses[$x][provider_address_cidr]' maxlength='255' value=\"".escape($row["provider_address_cidr"] ?? '')."\">\n";
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			if ($new_switch_style) {
				if ($input_toggle_style_switch) {
					echo "	<span class='switch'>\n";
				}
				echo "		<select class='formfld' id='provider_address_enabled_".$x."' name='provider_addresses[$x][provider_address_enabled]'>\n";
				echo "			<option value='true' >".$text['option-true']."</option>\n";
				echo "			<option value='false' ".($row['provider_address_enabled'] == 'false' ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
				echo "		</select>\n";
				if ($input_toggle_style_switch) {
					echo "		<span class='slider'></span>\n";
					echo "	</span>\n";
				}
			}
			else {
				if (substr($settings->get('theme', 'input_toggle_style', ''), 0, 6) == 'switch') {
					echo "			<label class='switch'>\n";
					echo "				<input type='checkbox' name='provider_addresses[$x][provider_address_enabled]' value='true' ".(!empty($row['provider_address_enabled']) && $row['provider_address_enabled'] == 'true' ? "checked='checked'" : '').">\n";
					echo "				<span class='slider'></span>\n";
					echo "			</label>\n";
				}
				else {
					echo "			<select class='formfld' name='provider_addresses[$x][provider_address_enabled]'>\n";
					echo "				<option value='true'>".$text['option-true']."</option>\n";
					echo "				<option value='false' ".(empty($row['provider_address_enabled']) || $row['provider_address_enabled'] == 'false' ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
					echo "			</select>\n";
				}
			}
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			echo "				<input class='formfld' type='text' name='provider_addresses[$x][provider_address_description]' maxlength='255' value=\"".escape($row["provider_address_description"] ?? '')."\">\n";
			echo "			</td>\n";
			if (is_array($provider_addresses) && @sizeof($provider_addresses) > 1 && permission_exists('provider_address_delete')) {
				if (!empty($row['provider_address_uuid']) && is_uuid($row['provider_address_uuid'])) {
					echo "		<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
					echo "			<input type='checkbox' name='provider_addresses[".$x."][checked]' value='true' class='chk_delete checkbox_details' onclick=\"checkbox_on_change(this);\">\n";
					echo "		</td>\n";
				}
				else {
					echo "		<td></td>\n";
				}
			}
			echo "		</tr>\n";
		}
	}
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-domain_uuid']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='domain_uuid'>\n";
	echo "		<option value='' ".(empty($domain_uuid) || !is_uuid($domain_uuid) ? "selected='selected'" : '').">".$text['label-global']."</option>\n";
	foreach ($_SESSION['domains'] as $row) {
		echo "	<option value='".$row['domain_uuid']."' ".($row['domain_uuid'] == $domain_uuid ? "selected='selected'" : '').">".escape($row['domain_name'])."</option>\n";
	}
	echo "	</select>\n";
	echo "<br>\n";
	echo ($text['description-domain_uuid'] ?? '')."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-provider_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";

	if ($new_switch_style) {
		if ($input_toggle_style_switch) {
			echo "	<span class='switch'>\n";
		}
		echo "		<select class='formfld' id='provider_enabled' name='provider_enabled'>\n";
		echo "			<option value='true' >".$text['option-true']."</option>\n";
		echo "			<option value='false' ".($provider_enabled == 'false' ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
		echo "		</select>\n";
		if ($input_toggle_style_switch) {
			echo "		<span class='slider'></span>\n";
			echo "	</span>\n";
		}
	} else {
		if (substr($settings->get('theme', 'input_toggle_style', ''), 0, 6) == 'switch') {
			echo "	<label class='switch'>\n";
			echo "		<input type='checkbox' id='provider_enabled' name='provider_enabled' value='true' ".(!empty($provider_enabled) && $provider_enabled == 'true' ? "checked='checked'" : '').">\n";
			echo "		<span class='slider'></span>\n";
			echo "	</label>\n";
		}
		else {
			echo "	<select class='formfld' id='provider_enabled' name='provider_enabled'>\n";
			echo "		<option value='true'>".$text['option-true']."</option>\n";
			echo "		<option value='false' ".(!empty($provider_enabled) && $provider_enabled == 'false' ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
			echo "	</select>\n";
		}
	}

	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-provider_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<textarea class='formfld' name='provider_description' style='width: 185px; height: 80px;'>".($provider_description ?? '')."</textarea>\n";
	echo "<br>\n";
	echo $text['description-provider_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>\n";
	echo "<br><br>";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";
