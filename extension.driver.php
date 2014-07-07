<?php

class extension_Hashid_field extends Extension
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
            Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/hashid_field/assets/publish.hashid_field.css');
        }
    }

    public function install()
    {
        try{
            Symphony::Database()->query(
                "CREATE TABLE IF NOT EXISTS `tbl_fields_hashid_field` (
                    `id` int(11) unsigned NOT NULL auto_increment,
                    `field_id` int(11) unsigned NOT NULL,
                    `size` int(3) unsigned NOT NULL,
                    `salt` VARCHAR(255) NOT NULL,
                    `length` int(2) unsigned NOT NULL,
                    PRIMARY KEY  (`id`),
                    KEY `field_id` (`field_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
            );
            Symphony::Configuration()->set('hash_salt', Symphony::Configuration()->get('sitename', 'general'), 'hashid_field');
            Symphony::Configuration()->set('hash_length', '5', 'hashid_field');
            Symphony::Configuration()->write();
        }
        catch(Exception $e){
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if(parent::uninstall() == true){
            Symphony::Database()->query("DROP TABLE `tbl_fields_hashid_field`");
            Symphony::Configuration()->remove('hashid_field','hashid_field');
            Symphony::Configuration()->write();
        }

        return false;
    }

    public function __addPreferences($context) {

        $settings = Symphony::Configuration()->get('hashid_field');

        $sitename = Symphony::Configuration()->get('sitename', 'general');

        $group = new XMLElement('fieldset', '<legend>' . __('Hashid Field') . '</legend>', array('class' => 'settings'));

        $select = Widget::Input('settings[hashid_field][hash_salt]', $settings['hash_salt']);
        $label = Widget::Label(__('Default salt'), $select);
        $group->appendChild($label);
        $help = new XMLElement('p', __('Set to your sitename by default.'), array('class' => 'help'));
        $group->appendChild($help);
        
        $select = Widget::Input('settings[hashid_field][hash_length]', $settings['hash_length']);
        $label = Widget::Label(__('Default hash length'), $select);
        $group->appendChild($label);
        $help = new XMLElement('p', __('A maximum of 32 characters are saved for each hash.'), array('class' => 'help'));
        $group->appendChild($help);

        $context['wrapper']->appendChild($group);
    }

}