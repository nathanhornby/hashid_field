<?php

class extension_Hashid_field extends Extension
{
    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page'      => '/publish/edit/',
                'delegate'  => 'EntryPostEdit',
                'callback'  => 'compileBackendFields'
            ),
            array(
                'page'      => '/publish/new/',
                'delegate'  => 'EntryPostCreate',
                'callback'  => 'compileBackendFields'
            ),
            array(
                'page'      => '/xmlimporter/importers/run/',
                'delegate'  => 'XMLImporterEntryPostCreate',
                'callback'  => 'compileBackendFields'
            ),
            array(
                'page'      => '/xmlimporter/importers/run/',
                'delegate'  => 'XMLImporterEntryPostEdit',
                'callback'  => 'compileBackendFields'
            ),
            array(
                'page'      => '/system/preferences/',
                'delegate'  => 'AddCustomPreferenceFieldsets',
                'callback'  => 'addPreferences'
            ),
            array(
                'page'      => '/backend/',
                'delegate'  => 'InitaliseAdminPageHead',
                'callback'  => 'initaliseAdminPageHead'
            ),
            array(
                'page'      => '/frontend/',
                'delegate'  => 'EventPostSaveFilter',
                'callback'  => 'compileFrontendFields'
            )
        );
    }

    /*--------------------------------------------------------------------------
        Assets
    --------------------------------------------------------------------------*/

    public function initaliseAdminPageHead($context)
    {
        $callback = Symphony::Engine()->getPageCallback();

        // Add custom stylesheet to the publish page
        if ($callback['driver'] == 'publish' && $callback['context']['page'] != 'index') {
            Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/hashid_field/assets/publish.hashid_field.css');
        }
    }

    /*--------------------------------------------------------------------------
        Install/Uninstall
    --------------------------------------------------------------------------*/

    public function install()
    {
        try {
            // Create the field table in the database
            Symphony::Database()
                ->create('tbl_fields_hashid_field')
                ->ifNotExists()
                ->fields([
                    'id' => [
                        'type' => 'int(11)',
                        'auto' => true,
                    ],
                    'field_id' => 'int(11)',
                    'salt' => 'varchar(255)',
                    'length' => 'int(2)',
                ])
                ->keys([
                    'id' => 'primary',
                    'field_id' => 'unique',
                ])
                ->execute()
                ->success();

            // Add the default salt (the site name) and hash length to manifest/config.php
            Symphony::Configuration()->set('hash_salt', Symphony::Configuration()->get('sitename', 'general'), 'hashid_field');
            Symphony::Configuration()->set('hash_length', '5', 'hashid_field');
            Symphony::Configuration()->write();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function update($previousVersion = false)
    {
        if (version_compare($previousVersion, '2.0.0', '<')) {
            Symphony::Database()
                ->alter('tbl_fields_hashid_field')
                ->dropKey('field_id')
                ->addKey([
                    'field_id' => 'unique',
                ])
                ->execute()
                ->success();

            $hashFields = (new FieldManager)
                ->select()
                ->sort('id', 'asc')
                ->type('hashid_field')
                ->execute()
                ->rows();

            foreach ($hashFields as $field) {
                Symphony::Database()
                    ->alter('tbl_entries_data_' . $field->get('id'))
                    ->dropKey('value')
                    ->addKey([
                        'value' => 'unique',
                    ])
                    ->modify([
                        'value' => 'varchar(255)',
                    ])
                    ->execute()
                    ->success();
            }
        }

        if (version_compare($previousVersion, '2.0.1', '<')) {
            Symphony::Database()
                ->alter('tbl_fields_hashid_field')
                ->drop('size')
                ->execute()
                ->success();
        }

        if (version_compare($previousVersion, '2.0.2', '<')) {
            Symphony::Database()->query(
                "ALTER TABLE `tbl_fields_hashid_field`
                    MODIFY COLUMN `salt` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;"
            );

            $hashFields = FieldManager::fetch(null, null, 'ASC', 'id', 'hashid_field');
            foreach ($hashFields as $field) {
                Symphony::Database()->query(
                    "ALTER TABLE `tbl_entries_data_".$field->get('id')."`
                        MODIFY COLUMN `value` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;"
                );
            }
        }
    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            // Drop the field table from the database
            Symphony::Database()
                ->drop('tbl_fields_hashid_field')
                ->ifExists()
                ->execute()
                ->success();

            // Remove the field defaults from manifest/config.php
            Symphony::Configuration()->remove('hashid_field','hashid_field');
            Symphony::Configuration()->write();
            return true;
        }

        return false;
    }

    /*-------------------------------------------------------------------------
        Compiling
    -------------------------------------------------------------------------*/

    public function compileBackendFields($context)
    {
        $fields = $context['section']->fetchFields('hashid_field');

        foreach ($fields as $field) {
            $field->compile($context['entry']);
        }
    }

    public function compileFrontendFields($context)
    {
        $section = (new SectionManager)
            ->select()
            ->section($context['entry']->get('section_id'))
            ->execute()
            ->next();
        $fields = $section->fetchFields('hashid_field');

        foreach ($fields as $field) {
            $field->compile($context['entry']);
        }
    }

    /*-------------------------------------------------------------------------
        Preferences
    -------------------------------------------------------------------------*/

    public function addPreferences($context)
    {
        // Current settings
        $settings = Symphony::Configuration()->get('hashid_field');

        // The site name from manifest/config.php
        $sitename = Symphony::Configuration()->get('sitename', 'general');

        // Create Preferences fieldset
        $fieldset = new XMLElement('fieldset', '<legend>' . __('Hashid Field') . '</legend>', array('class' => 'settings'));
        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $fieldset->appendChild($group);

        // Default salt input
        $select = Widget::Input('settings[hashid_field][hash_salt]', $settings['hash_salt']);
        $label = Widget::Label(__('Default salt'), $select);
        $label->setAttribute('class', 'column');
        $group->appendChild($label);
        $help = new XMLElement('p', __('Set to your sitename by default.'), array('class' => 'help'));
        $label->appendChild($help);

        // Default hash length
        $select = Widget::Input('settings[hashid_field][hash_length]', $settings['hash_length'], 'number');
        $label = Widget::Label(__('Default hash length'), $select);
        $label->setAttribute('class', 'column');
        $group->appendChild($label);
        $help = new XMLElement('p', __('A maximum of 32 characters are saved for each hash.'), array('class' => 'help'));
        $label->appendChild($help);

        // Add the fields to the fieldset
        $context['wrapper']->appendChild($fieldset);
    }
}
