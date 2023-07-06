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
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('provider_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (!empty($_POST) && is_array($_POST['providers'])) {
		$action = $_POST['action'];
		$search = $_POST['search'] ?? null;
		$providers = $_POST['providers'];
	}

//process the http post data by action
	if (!empty($action) && is_array($providers) && @sizeof($providers) != 0) {

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: providers.php');
			exit;
		}

		//prepare the array
		//foreach ($providers as $row) {
		//	$array['providers'][$x]['checked'] = $row['checked'];
		//	$array['providers'][$x]['provider_uuid'] = $row['provider_uuid'];
		//	$array['providers'][$x]['provider_enabled'] = $row['provider_enabled'];
		//	$x++;
		//}

		//prepare the array
		$x = 0;
		foreach ($providers as $row) {
			$array[$x]['checked'] = $row['checked'] ?? null;
			$array[$x]['uuid'] = $row['provider_uuid'];
			//$array[$x]['provider_enabled'] = $row['provider_enabled'];
			$x++;
		}

		//prepare the database object
		$database = new database;
		$database->app_name = 'providers';
		$database->app_uuid = '35187839-237e-4271-b8a1-9b9c45dc8833';

		//send the array to the database class
		switch ($action) {
			case 'copy':
				if (permission_exists('provider_add')) {
					$obj = new providers;
					$obj->copy($array);
				}
				break;
			case 'toggle':
				if (permission_exists('provider_edit')) {
					$obj = new providers;
					$obj->toggle($array);
				}
				break;
			case 'delete':
				if (permission_exists('provider_delete')) {
					$obj = new providers;
					$obj->delete($array);
				}
				break;
		}

		//redirect the user
		header('Location: providers.php'.(!empty($search) ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

//add the search
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get the count
	$sql = "select count(provider_uuid) ";
	$sql .= "from v_providers ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	if (isset($search)) {
		$sql .= "and (";
		$sql .= "	lower(provider_name) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = !empty($search) ? "&search=".$search : null;
	$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select ";
	$sql .= "provider_uuid, ";
	$sql .= "provider_name, ";
	$sql .= "domain_uuid, ";
	$sql .= "cast(provider_enabled as text), ";
	$sql .= "provider_description ";
	$sql .= "from v_providers ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	if (isset($search)) {
		$sql .= "and (";
		$sql .= "	lower(provider_name) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	if (isset($_GET["order_by"]) && isset($_GET["order"])) {
		$sql .= order_by($order_by, $order, 'provider_name', 'asc');
	}
	else {
		$sql .= "order by provider_name asc ";
	}
	$sql .= limit_offset($rows_per_page, $offset);
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$providers = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-providers'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-providers']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('provider_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'provider_setup.php']);
	}
	if (permission_exists('provider_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-advanced'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'provider_edit.php']);
	}
	//if (permission_exists('provider_add') && $providers) {
	//	echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	//}
	//if (permission_exists('provider_edit') && $providers) {
	//	echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	//}
	if (permission_exists('provider_delete') && $providers) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search ?? '')."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>(!empty($search) ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'providers.php','style'=>(empty($search) ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	//if (permission_exists('provider_add') && $providers) {
	//	echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	//}
	//if (permission_exists('provider_edit') && $providers) {
	//	echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	//}
	if (permission_exists('provider_delete') && $providers) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-providers']."\n";
	echo "<br><br>\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search ?? '')."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('provider_add') || permission_exists('provider_edit') || permission_exists('provider_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(empty($providers) ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	echo th_order_by('provider_name', $text['label-provider_name'], $order_by, $order);
	echo th_order_by('provider_enabled', $text['label-provider_enabled'], $order_by, $order, null, "class='center'");
	echo "	<th class='hide-sm-dn'>".$text['label-provider_description']."</th>\n";
	if (permission_exists('provider_edit') && !empty($_SESSION['theme']['list_row_edit_button']['boolean']) && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($providers) && @sizeof($providers) != 0) {
		$x = 0;
		foreach ($providers as $row) {
			if (permission_exists('provider_edit')) {
				$list_row_url = "provider_edit.php?id=".urlencode($row['provider_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('provider_add') || permission_exists('provider_edit') || permission_exists('provider_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='providers[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='providers[$x][provider_uuid]' value='".escape($row['provider_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('provider_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['provider_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['provider_name']);
			}
			echo "	</td>\n";
			if (permission_exists('provider_edit')) {
				echo "	<td class='no-link center'>\n";
				echo "		<input type='hidden' name='number_translations[$x][provider_enabled]' value='".escape($row['provider_enabled'])."' />\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['provider_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['provider_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['provider_description'])."</td>\n";
			if (permission_exists('provider_edit') && !empty($_SESSION['theme']['list_row_edit_button']['boolean']) && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($providers);
	}

	echo "</table>\n";
	echo "<br>\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
