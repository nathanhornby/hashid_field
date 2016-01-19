<?php

if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once EXTENSIONS . '/hashid_field/lib/Hashids.php';
require_once FACE . '/interface.exportablefield.php';
require_once FACE . '/interface.importablefield.php';

class FieldHashid_field extends Field implements ExportableField, ImportableField
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Hashid');
        $this->_required = false;

        $default_settings = Symphony::Configuration()->get('hashid_field');

        $this->set('salt', $default_settings['hash_salt']);
        $this->set('length', $default_settings['hash_length']);
        $this->set('required', 'no');
        $this->set('location', 'sidebar');
    }

    /*-------------------------------------------------------------------------
        Definition
    -------------------------------------------------------------------------*/

    public function canToggle()
    {
        return true;
    }
    public function canFilter()
    {
        return true;
    }
    public function canPrePopulate()
    {
        return true;
    }
    public function isSortable()
    {
        return false;
    }
    public function allowDatasourceOutputGrouping()
    {
        return false;
    }
    public function allowDatasourceParamOutput()
    {
        return true;
    }

    /*-------------------------------------------------------------------------
        Setup
    -------------------------------------------------------------------------*/

    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_".$this->get('id')."` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `entry_id` int(11) unsigned NOT NULL,
                `value` varchar(32) default NULL,
                PRIMARY KEY  (`id`),
                UNIQUE KEY `entry_id` (`entry_id`),
                KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );
    }

    /*-------------------------------------------------------------------------
        Settings
    -------------------------------------------------------------------------*/

    public function setFromPOST( array $settings = array() )
    {
        parent::setFromPOST($settings);
    }

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        parent::displaySettingsPanel($wrapper, $errors);

        // Options wrapper
        $div = new XMLElement('div', NULL, array('class' => 'two columns'));
        $wrapper->appendChild($div);

        // Hash salt input
        $label = Widget::Label(__('Hash salt'));
        $input = Widget::Input('fields['.$this->get('sortorder').'][salt]', (string) $this->get('salt'));
        $label->setAttribute('class', 'column');
        $label->appendChild($input);
        $div->appendChild($label);

        // Hash length input
        $label = Widget::Label(__('Hash length'));
        $input = Widget::Input('fields['.$this->get('sortorder').'][length]', (string) $this->get('length'), 'number');
        $label->setAttribute('class', 'column');
        $label->appendChild($input);
        $div->appendChild($label);

        // Add the fields to the wrapper
        $this->appendStatusFooter($wrapper);
    }

    public function commit()
    {
        if(!parent::commit()) return false;

        $id = $this->get('id');

        if($id === false) return false;

        $fields = array();
        $fields['field_id'] = $id;
        $fields['length'] = max(1, (int) $this->get('length'));
        $fields['salt'] = $this->get('salt');

        return FieldManager::saveSettings($id, $fields);
    }

    /*-------------------------------------------------------------------------
        Publish
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        // Generate hash from entry ID
        $hash = new Hashids\Hashids( $this->get('salt') , $this->get('length') );
        $hash = $hash->encrypt($entry_id);

        // Create hidden read-only input for storing the hash for submission
        $label = Widget::Label($this->get('label'));

        // Display the hash and appropriate messaging.
        if (strlen($hash) != 0) {
            $label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, $hash, 'text', array('readonly' => 'readonly') ));
        } else {
            $label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, NULL, 'text', array('readonly' => 'readonly') ));
            $label->appendChild('<p class="hash-field-box hash-info">The hash will be generated when the entry is created.</p>');
        };
        if ($data['value'] != $hash && $data['value'] != NULL) {
            $label->appendChild('<p class="hash-field-box hash-warning">The current hash <strong>'.$data['value'].'</strong> will be replaced with the new hash when the entry is saved.</p>');
        };

        // Error flagging
        if ($flagWithError != NULL) {
            $wrapper->appendChild( Widget::Error($label, $flagWithError) );
        } else {
            $wrapper->appendChild($label);
        }
    }

    public function prepareTextValue($data, $entry_id = null)
    {
        if (!$entry_id) {
            return null;
        }
        // Generate hash from entry ID
        $hash = new Hashids\Hashids( $this->get('salt') , $this->get('length') );

        return $hash->encrypt($entry_id);
    }

    /*-------------------------------------------------------------------------
        Publish toggle
    -------------------------------------------------------------------------*/

    public function getToggleStates()
    {
        return array(
            'regenerate' => __('Regenerate hash')
        );
    }
    public function toggleFieldData(array $data, $newState, $entry_id = null)
    {
        $hash = new Hashids\Hashids( $this->get('salt') , $this->get('length') );
        $hash = $hash->encrypt($entry_id);

        $data['value'] = $hash;

        return $data;
    }

    /*-------------------------------------------------------------------------
        Input
    -------------------------------------------------------------------------*/

    public function checkPostFieldData($data, &$message, $entry_id=NULL)
    {
        $driver = Symphony::ExtensionManager()->create('hashid_field');
        $driver->registerField($this);

        return self::__OK__;
    }

    public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;

        if ( strlen( trim($data) ) == 0 ) return array();

        $result = array('value' => $data);

        return $result;
    }

    /*-------------------------------------------------------------------------
        Output
    -------------------------------------------------------------------------*/

    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        $wrapper->appendChild(new XMLElement($this->get('element_name'), $data['value'], array('salt'=>$this->get('salt'),'length'=>$this->get('length'))));
    }

    /*-------------------------------------------------------------------------
        Compile
    -------------------------------------------------------------------------*/

    public function compile(&$entry)
    {
        $entry_id = $entry->get('id');
        $field_id = $this->get('id');
        $data = $entry->getData($field_id);

        if (empty($data) || !isset($data['value']) || empty($data['value'])) {
            $hash = new Hashids\Hashids( $this->get('salt') , $this->get('length') );
            $hash = $hash->encrypt($entry_id);
            $result = Symphony::Database()->insert(array('value' => $hash, 'entry_id' => $entry_id), "tbl_entries_data_".$field_id );

            return $hash;
        }

        return $data['value'];
    }

    /*-------------------------------------------------------------------------
        Import
    -------------------------------------------------------------------------*/

    public function getImportModes()
    {
        return array(
            'getValue'     =>  ImportableField::STRING_VALUE,
            'getPostdata'  =>  ImportableField::ARRAY_VALUE
        );
    }

    public function prepareImportValue($data, $mode, $entry_id = null)
    {
        $message = $status = null;
        $modes = (object) $this->getImportModes();

        if ($mode === $modes->getValue) {
            return $data;
        } elseif ($mode === $modes->getPostdata) {
            return $this->processRawFieldData($data, $status, $message, true, $entry_id);
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Export
    -------------------------------------------------------------------------*/

    public function getExportModes()
    {
        return array(
            'getUnformatted'  =>  ExportableField::UNFORMATTED,
            'getPostdata'     =>  ExportableField::POSTDATA
        );
    }

    public function prepareExportValue($data, $mode, $entry_id = null)
    {
        $modes = (object) $this->getExportModes();

        // Export unformatted
        if ($mode === $modes->getUnformatted || $mode === $modes->getPostdata) {
            return isset($data['value']) ? $data['value'] : null;
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Filtering
    -------------------------------------------------------------------------*/

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');

        if ( self::isFilterRegex($data[0]) ) {
            $this->buildRegexSQL($data[0], array('value'), $joins, $where);
        } elseif ($andOperation) {
            foreach ($data as $value) {
                $this->_key++;

                $value = $this->separateValueFromModifier($this->cleanValue($value));
                $equality = $value['equality'];
                $value = $value['value'];
                $joins .= "
                    LEFT JOIN
                    `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                    ON (e.id = t{$field_id}_{$this->_key}.entry_id)
                ";
                $where .= "
                    AND (
                    t{$field_id}_{$this->_key}.value $equality '{$value}'
                )";
            }
        } else {
            if( !is_array($data) ) $data = array($data);

            foreach ($data as &$value) {
                $value = $this->cleanValue($value);
            }

            $this->_key++;
            $separatedData = $this->separateValueFromModifier($data[0]);
            $data[0] = $separatedData['value'];
            $data = implode("', '", $data);
            $inclusion = $separatedData['inclusion'];
            $joins .= "
                LEFT JOIN
                `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                ON (e.id = t{$field_id}_{$this->_key}.entry_id)
            ";
            $where .= "
                AND (
                t{$field_id}_{$this->_key}.value $inclusion ('{$data}')
                )
            ";
        }

        return true;
    }

    private function separateValueFromModifier($value)
    {
        $ret = array(
            'value' => $value,
            'equality' => '=',
            'inclusion' => 'in'
        );

        if (strpos($value, 'not:') !== FALSE) {
            $ret['equality'] = '!=';
            $ret['inclusion'] = 'NOT IN';
            $ret['value'] = trim(str_replace('not:', '', $value));
        }

        return $ret;
    }
}
