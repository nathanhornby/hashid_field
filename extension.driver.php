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
            Symphony::Database()->query(
                "CREATE TABLE IF NOT EXISTS `tbl_fields_hashid_field` (
                    `id` int(11) unsigned NOT NULL auto_increment,
                    `field_id` int(11) unsigned NOT NULL,
                    `salt` VARCHAR(255) NOT NULL,
                    `length` int(2) unsigned NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `field_id` (`field_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
            );

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
            Symphony::Database()->query(
                "ALTER TABLE `tbl_fields_hashid_field`
                    DROP KEY `field_id`,
                    ADD UNIQUE KEY `field_id` (`field_id`);"
            );

            $hashFields = FieldManager::fetch(null, null, 'ASC', 'id', 'hashid_field');
            foreach ($hashFields as $field) {
                Symphony::Database()->query(
                    "ALTER TABLE `tbl_entries_data_".$field->get('id')."`
                        DROP KEY `value`,
                        ADD UNIQUE KEY `value` (`value`),
                        MODIFY COLUMN `value` varchar(255) NOT NULL;"
                );
            }
        }

        if (version_compare($previousVersion, '2.0.1', '<')) {
            Symphony::Database()->query(
                "ALTER TABLE `tbl_fields_hashid_field`
                    DROP COLUMN `size`;"
             );
        }

    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            // Drop the field table from the database
            Symphony::Database()->query("DROP TABLE `tbl_fields_hashid_field`");

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
        $section = SectionManager::fetch($context['entry']->get('section_id'));
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
