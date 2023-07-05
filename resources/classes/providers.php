<?php

/**
 * providers class
 *
 * @method null delete
 * @method null toggle
 * @method null copy
 * @method null setup
 */
if (!class_exists('providers')) {
	class providers {

		/**
		* declare the variables
		*/
		private $app_name;
		private $app_uuid;
		private $name;
		private $table;
		private $toggle_field;
		private $toggle_values;
		private $description_field;
		private $location;
		public  $id;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
				$this->app_name = 'providers';
				$this->app_uuid = '35187839-237e-4271-b8a1-9b9c45dc8833';
				$this->name = 'provider';
				$this->table = 'providers';
				$this->toggle_field = 'provider_enabled';
				$this->toggle_values = ['true','false'];
				$this->description_field = 'provider_description';
				$this->location = 'providers.php';
		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * delete rows from the database
		 */
		public function delete($records) {
			if (permission_exists($this->name.'_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								//add to the array
									if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
										$array['providers'][$x]['provider_uuid'] = $record['uuid'];
										$array['provider_settings'][$x]['provider_uuid'] = $record['uuid'];
										$array['provider_addresses'][$x]['provider_uuid'] = $record['uuid'];
									}

								//increment the id
									$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		/**
		 * toggle a field between two values
		 */
		public function toggle($records) {
			if (permission_exists($this->name.'_edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {
						//get current toggle state
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters ?? null, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($states as $uuid => $state) {
								//create the array
									$array[$this->table][$x][$this->name.'_uuid'] = $uuid;
									$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

								//increment the id
									$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $states);
					}
			}
		}

		/**
		 * setup the provider
		 */
		public function setup() {

			//provider selection
				$provider_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/*/resources/providers/settings.php");
				foreach ($provider_list as $setting_path) {
					include($setting_path);
				}
				$providers = $array['providers'];
				unset($array);

			//get the array
				$x = 0;
				foreach ($providers as $row) {
					if (md5($row['provider_name']) == $this->id) {
						if ($row['provider_name'] == 'Add a Provider') {
							$provider_name = 'Provider';
						}
						else {
							$provider_name = $row['provider_name'];
						}
						$array['providers'][$x]['provider_uuid'] = $row['provider_uuid'];
						$array['providers'][$x]['provider_name'] = $provider_name;
						$array['providers'][$x]['provider_enabled'] = $row['provider_enabled'];
						$array['providers'][$x]['provider_description'] = '';
						$provider = $row;
					}
				}

			//add the provider settings
				$y = 0;
				foreach ($provider['provider_settings'] as $row) {
					$array['providers'][$x]['provider_settings'][$y]['provider_uuid'] = $row['provider_uuid'];
					$array['providers'][$x]['provider_settings'][$y]['application_uuid'] = $row['application_uuid'];
					$array['providers'][$x]['provider_settings'][$y]['provider_setting_uuid'] = $row['provider_setting_uuid'];
					$array['providers'][$x]['provider_settings'][$y]['provider_setting_category'] = $row['provider_setting_category'];
					$array['providers'][$x]['provider_settings'][$y]['provider_setting_subcategory'] = $row['provider_setting_subcategory'];
					$array['providers'][$x]['provider_settings'][$y]['provider_setting_type'] = $row['provider_setting_type'];
					$array['providers'][$x]['provider_settings'][$y]['provider_setting_name'] = $row['provider_setting_name'];
					$array['providers'][$x]['provider_settings'][$y]['provider_setting_value'] = $row['provider_setting_value'];
					$array['providers'][$x]['provider_settings'][$y]['provider_setting_order'] = $row['provider_setting_order'];
					$array['providers'][$x]['provider_settings'][$y]['provider_setting_enabled'] = $row['provider_setting_enabled'];
					$array['providers'][$x]['provider_settings'][$y]['provider_setting_description'] = $row['provider_setting_description'];
					$y++;
				}

			//add the provider addresses
				$y = 0;
				foreach ($provider['provider_addresses'] as $row) {
					$array['providers'][$x]['provider_addresses'][$y]['provider_uuid'] = $row['provider_uuid'];
					$array['providers'][$x]['provider_addresses'][$y]['provider_address_uuid'] = $row['provider_address_uuid'];
					$array['providers'][$x]['provider_addresses'][$y]['provider_address_cidr'] = $row['provider_address_cidr'];
					$array['providers'][$x]['provider_addresses'][$y]['provider_address_enabled'] = $row['provider_address_enabled'];
					$array['providers'][$x]['provider_addresses'][$y]['provider_address_description'] = $row['provider_address_description'];
					$y++;
				}

			//save to the data
				$database = new database;
				$database->app_name = 'providers';
				$database->app_uuid = '35187839-237e-4271-b8a1-9b9c45dc8833';
				$database->save($array);
				//$message = $database->message;
				//view_array($message);
				unset($array);

		}

	}
}

?>
