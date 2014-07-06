<?php

class extension_Hash_field extends Extension
{

    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/system/preferences/',
                'delegate' => 'AddCustomPreferenceFieldsets',
                'callback' => '__addPreferences'
            ),
            array(
                'page' => '/system/preferences/',
                'delegate' => 'Save',
                'callback' => '__savePreferences'
            ),
            array(
                'page' => '/backend/',
                'delegate' => 'InitaliseAdminPageHead',
                'callback' => 'initaliseAdminPageHead'
            )
        );
    }

    public function initaliseAdminPageHead($context)
    {
        $callback = Symphony::Engine()->getPageCallback();

        if($callback['driver'] == 'publish' && $callback['context']['page'] != 'index') {
            Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/hash_field/assets/publish.hash_field.css');
        }
    }

    public function install()
    {
        Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_fields_hash_field` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `field_id` int(11) unsigned NOT NULL,
                `size` int(3) unsigned NOT NULL,
                PRIMARY KEY  (`id`),
                KEY `field_id` (`field_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );

        Symphony::Configuration()->set('hash_salt', Symphony::Configuration()->get('sitename', 'general'), 'hash_field');
        Symphony::Configuration()->set('hash_length', '5', 'hash_field');
        Symphony::Configuration()->write();
    }

    public function uninstall()
    {
        Symphony::Database()->query("DROP TABLE `tbl_fields_hash_field`");
        Symphony::Configuration()->remove('hash_field','hash_field');
        Symphony::Configuration()->write();
    }

    public function __addPreferences($context) {

        $settings = Symphony::Configuration()->get('hash_field');

        $sitename = Symphony::Configuration()->get('sitename', 'general');

        // Add fieldset
        $group = new XMLElement('fieldset', '<legend>' . __('Hash Field') . '</legend>', array('class' => 'settings'));

        $select = Widget::Input('settings[hash_field][hash_salt]', $settings['hash_salt']);
        $label = Widget::Label(__('Salt'), $select);
        $group->appendChild($label);
        $help = new XMLElement('p', __('Set to your sitename by default.'), array('class' => 'help'));
        $group->appendChild($help);
        
        $select = Widget::Input('settings[hash_field][hash_length]', $settings['hash_length']);
        $label = Widget::Label(__('Minimum hash length'), $select);
        $group->appendChild($label);
        $help = new XMLElement('p', __('A maximum of 32 characters are saved for each hash.'), array('class' => 'help'));
        $group->appendChild($help);

        $context['wrapper']->appendChild($group);
    }

}