<?php

if ($domains_processed == 1) {

	//set provider_setting_type to text if it is null
	$sql = "select * from v_provider_settings ";
	$sql .= "where provider_setting_type is null ";
	$provider_settings = $database->select($sql, null, 'all');
	if (is_array($provider_settings) && @sizeof($provider_settings) != 0) {
		foreach($provider_settings as $row) {
			$sql = "update v_provider_settings ";
			$sql .= "set provider_setting_type = 'text' ";
			$sql .= "where provider_setting_uuid = :provider_setting_uuid ";
			$parameters['provider_setting_uuid'] = $row['provider_setting_uuid'];
			$database->select($sql, $parameters, 'all');
		}
	}

}

?>
