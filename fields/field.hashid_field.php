<?php

if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once FACE . '/interface.exportablefield.php';
require_once FACE . '/interface.importablefield.php';
require_once EXTENSIONS . '/hashid_field/lib/class.entryqueryhashidadapter.php';

require_once EXTENSIONS . '/hashid_field/vendor/autoload.php';
use Hashids\Hashids;

class FieldHashid_field extends Field implements ExportableField
{
    public function __construct()
    {
        parent::__construct();
        $this->entryQueryFieldAdapter = new EntryQueryHashidAdapter($this);

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
        return Symphony::Database()
            ->create('tbl_entries_data_' . $this->get('id'))
            ->ifNotExists()
            ->fields([
                'id' => [
                    'type' => 'int(11)',
                    'auto' => true,
                ],
                'entry_id' => 'int(11)',
                'value' => 'varchar(255)',
            ])
            ->keys([
                'id' => 'primary',
                'entry_id' => 'unique',
                'value' => 'unique',
            ])
            ->execute()
            ->success();
    }

    /*-------------------------------------------------------------------------
        Settings
    -------------------------------------------------------------------------*/

    public function setFromPOST(array $settings = array())
    {
        parent::setFromPOST($settings);
    }

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        parent::displaySettingsPanel($wrapper, $errors);

        // Options wrapper
        $div = new XMLElement('div', null, array('class' => 'two columns'));
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
        if (!parent::commit()) {
            return false;
        }

        $id = $this->get('id');

        if ($id === false) {
            return false;
        }

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
        $hash = new Hashids($this->get('salt'), $this->get('length'));
        $hash = $hash->encode($entry_id);

        // Create hidden read-only input for storing the hash for submission
        $label = Widget::Label($this->get('label'));

        // Display the hash and appropriate messaging.
        $label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, $data['value'], 'text', array('readonly' => 'readonly') ));

        if (strlen($data['value']) === 0) {
            $label->appendChild('<p class="hash-field-box hash-info">'.__('The hash will be generated when the entry is saved.').'</p>');
        } elseif ($data['value'] !== $hash) {
            $label->appendChild('<p class="hash-field-box hash-warning">'.__('The hash will be replaced when the entry is saved.').'</p>');
        }

        // Error flagging
        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }
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
        $hash = new Hashids($this->get('salt'), $this->get('length'));
        $hash = $hash->encode($entry_id);

        $data['value'] = $hash;

        return $data;
    }

    /*-------------------------------------------------------------------------
        Input
    -------------------------------------------------------------------------*/

    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        return self::__OK__;
    }

    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;
        // return null now since the hash will be saved later
        // when the delegates are called
        return null;
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

        $hash = new Hashids($this->get('salt'), $this->get('length'));
        $value = $hash->encode($entry_id);

        // Save
        $result = Symphony::Database()->insert(
            array(
                'entry_id' => $entry_id,
                'value' => $value
            ),
            'tbl_entries_data_'.$field_id,
            true
        );

        // Update the entry object
        $entry->setData(
            $field_id,
            array(
                'value' => $value
            )
        );

        return $result;
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

        if (self::isFilterRegex($data[0])) {
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
            if (!is_array($data)) {
                $data = array($data);
            }

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

        if (strpos($value, 'not:') !== false) {
            $ret['equality'] = '!=';
            $ret['inclusion'] = 'NOT IN';
            $ret['value'] = trim(str_replace('not:', '', $value));
        }

        return $ret;
    }
}
