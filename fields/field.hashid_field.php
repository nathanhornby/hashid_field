<?php

	require_once TOOLKIT . '/class.xsltprocess.php';
	require_once FACE . '/interface.exportablefield.php';
	require_once FACE . '/interface.importablefield.php';

	class FieldHashid_field extends Field implements ExportableField, ImportableField {
		public function __construct(){
			parent::__construct();
			$this->_name = __('Hashid');
			$this->_required = true;

			$default_settings = Symphony::Configuration()->get('hashid_field');

			$this->set('salt', $default_settings['hash_salt']);
			$this->set('length', $default_settings['hash_length']);
			$this->set('required', 'no');
			$this->set('location', 'sidebar');
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function canFilter(){
			return true;
		}

		public function canPrePopulate(){
			return true;
		}

		public function isSortable(){
			return false;
		}

		public function allowDatasourceOutputGrouping(){
			return false;
		}

		public function allowDatasourceParamOutput(){
			return true;
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function createTable(){
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` varchar(32) default NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function setFromPOST(array $settings = array()) {
			parent::setFromPOST($settings);
		}

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			// Options
			$div = new XMLElement('div', NULL, array('class' => 'two columns'));
			$wrapper->appendChild($div);

			// Hash salt
			$label = Widget::Label(__('Hash salt'));
			$input = Widget::Input('fields['.$this->get('sortorder').'][salt]', (string)$this->get('salt'));
			$label->setAttribute('class', 'column');
			$label->appendChild($input);
			$div->appendChild($label);

			// Hash length
			$label = Widget::Label(__('Hash length'));
			$input = Widget::Input('fields['.$this->get('sortorder').'][length]', (string)$this->get('length'));
			$label->setAttribute('class', 'column');
			$label->appendChild($input);
			$div->appendChild($label);

			// Requirements and table display
			$this->appendStatusFooter($wrapper);
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();
			$fields['field_id'] = $id;
			$fields['length'] = max(1, (int)$this->get('length'));
			$fields['salt'] = $this->get('salt');

			if(!FieldManager::saveSettings($id, $fields)) return false;
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){

			require_once EXTENSIONS . '/hashid_field/lib/Hashids.php';
			
			$hash = new Hashids\Hashids( $this->get('salt') , $this->get('length') );
			$hash = $hash->encrypt($entry_id);

			// $value = General::sanitize(isset($data['value']) ? $data['value'] : $hash); // Fixed hash
			$value = $hash; // Dynamic hash

			$label = Widget::Label($this->get('label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL), 'text', array('readonly' => 'readonly') ));

			// If the hash exists display it, otherwise, display a message.
			if(strlen($value) != 0){
				$label->appendChild('<p class="hash-field-box hash">'.$value.'</p>');
			}else{
				$label->appendChild('<p class="hash-field-box hash-info">The hash will be genereated when the entry is saved.</p>');
			};
			if($data['value'] != $hash && $data['value'] != NULL){
				$label->appendChild('<p class="hash-field-box hash-warning">This hash will be regenereated when the entry is saved.</p>');
			};

			if($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
			else $wrapper->appendChild($label);
			
		}

		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			$message = NULL;

			if(is_array($data) && isset($data['value'])) {
				$data = $data['value'];
			}

			if($this->get('required') == 'yes' && strlen(trim($data)) == 0){
				$message = __('‘%s’ is a required field.', array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = null) {
			$status = self::__OK__;

			if (strlen(trim($data)) == 0) return array();

			$result = array( 'value' => $data );

			return $result;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null){
			$value = $data['value'];

			if($encode === true){
				$value = General::sanitize($value);
			}

			else{
				include_once(TOOLKIT . '/class.xsltprocess.php');

				if(!General::validateXML($data['value'], $errors, false, new XsltProcess)){
					$value = html_entity_decode($data['value'], ENT_QUOTES, 'UTF-8');

					if(!General::validateXML($value, $errors, false, new XsltProcess)){
						$value = General::sanitize($data['value']);
					}
				}
			}

			$wrapper->appendChild(new XMLElement($this->get('element_name'), $value));
		}

	/*-------------------------------------------------------------------------
		Import:
	-------------------------------------------------------------------------*/

		public function getImportModes() {
			return array(
				'getValue' =>		ImportableField::STRING_VALUE,
				'getPostdata' =>	ImportableField::ARRAY_VALUE
			);
		}

		public function prepareImportValue($data, $mode, $entry_id = null) {
			$message = $status = null;
			$modes = (object)$this->getImportModes();

			if($mode === $modes->getValue) {
				return $data;
			}
			else if($mode === $modes->getPostdata) {
				return $this->processRawFieldData($data, $status, $message, true, $entry_id);
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Export:
	-------------------------------------------------------------------------*/

		public function getExportModes() {
			return array(
				'getUnformatted' =>	ExportableField::UNFORMATTED,
				'getPostdata' =>	ExportableField::POSTDATA
			);
		}

		public function prepareExportValue($data, $mode, $entry_id = null) {
			$modes = (object)$this->getExportModes();

			// Export unformatted:
			if ($mode === $modes->getUnformatted || $mode === $modes->getPostdata) {
				return isset($data['value'])
					? $data['value']
					: null;
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (self::isFilterRegex($data[0])) {
				$this->buildRegexSQL($data[0], array('value'), $joins, $where);
			}
			else if ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND (
							t{$field_id}_{$this->_key}.value = '{$value}'
						)
					";
				}
			}

			else {
				if (!is_array($data)) $data = array($data);

				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}

				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.value IN ('{$data}')
					)
				";
			}

			return true;
		}

	}