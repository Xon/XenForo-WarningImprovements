<?php

class SV_WarningImprovements_Installer
{
    public static function install($existingAddOn, $addOnData)
    {
        $version = isset($existingAddOn['version_id']) ? $existingAddOn['version_id'] : 0;

        $db = XenForo_Application::getDb();

        $addonsToUninstall = array('SV_AlertOnWarning' => array(),
                                   'SVViewOwnWarnings' => array());
        SV_Utils_Install::removeOldAddons($addonsToUninstall);

        if (!$db->fetchRow("SHOW TABLES LIKE 'xf_sv_warning_default'"))
        {
            $db->query("
                CREATE TABLE IF NOT EXISTS xf_sv_warning_default
                (
                    `warning_default_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `threshold_points` SMALLINT NOT NULL DEFAULT '0',
                    `expiry_type` ENUM('never','days','weeks','months','years') NOT NULL,
                    `expiry_extension` SMALLINT UNSIGNED NOT NULL,
                    `active` tinyint(3) unsigned NOT NULL DEFAULT '1',
                    PRIMARY KEY (`warning_default_id`),
                    KEY (`threshold_points`, `active`)
                ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
            ");
        }

        if (!$db->fetchRow("SHOW TABLES LIKE 'xf_sv_warning_category'"))
        {
            $db->query(
                'CREATE TABLE IF NOT EXISTS xf_sv_warning_category (
                    warning_category_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    parent_warning_category_id INT UNSIGNED NOT NULL DEFAULT 0,
                    display_order INT UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY (warning_category_id)
                ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci'
            );
        }

        SV_Utils_Install::addColumn(
            'xf_user_option',
            'sv_pending_warning_expiry',
            "INT UNSIGNED DEFAULT NULL"
        );

        SV_Utils_Install::addColumn(
            'xf_sv_warning_category',
            'allowed_user_group_ids',
            "VARBINARY(255) NOT NULL DEFAULT '2'"
        );

        SV_Utils_Install::addColumn(
            'xf_warning_definition',
            'sv_warning_category_id',
            'INT UNSIGNED NOT NULL DEFAULT 0'
        );
        SV_Utils_Install::addColumn(
            'xf_warning_definition',
            'sv_display_order',
            'INT UNSIGNED NOT NULL DEFAULT 0'
        );

        SV_Utils_Install::addColumn(
            'xf_warning_action',
            'sv_warning_category_id',
            'INT UNSIGNED NOT NULL DEFAULT 0'
        );

        if ($version == 0)
        {
            // insert the defaults for the custom warning. This can't be normally inserted so fiddle with the sql_mode
            $db->query("SET SESSION sql_mode='STRICT_ALL_TABLES,NO_AUTO_VALUE_ON_ZERO'");
            $db->query("insert ignore into xf_warning_definition
                    (warning_definition_id,points_default,expiry_type,expiry_default,extra_user_group_ids,is_editable)
                values
                    (0,1, 'months',1,'',1);
            ");
            $db->query("SET SESSION sql_mode='STRICT_ALL_TABLES'");
        }

        $requireDefault = true;
        // import from Waindigo/TH Warnings add-on
        if (SV_Utils_AddOn::addOnIsActive('Waindigo_Warnings'))
        {
            XenForo_Db::beginTransaction();

            $phraseModel = XenForo_Model::create("XenForo_Model_Phrase");
            // make sure the model is loaded before accessing the static properties
            XenForo_Model::create("XenForo_Model_User");
            // set default permission values for Registered group
            $user_group_id = XenForo_Model_User::$defaultRegisteredGroupId;

            // copy xf_warning_group => xf_sv_warning_category
            $warningGroups = $db->fetchAll('
                select *
                from xf_warning_group
            ');
            foreach($warningGroups as $warningGroup)
            {
                if ($warningGroup['warning_group_id'] == 1)
                {
                    $requireDefault = false;
                }
                $db->query("insert ignore into xf_sv_warning_category (warning_category_id, parent_warning_category_id, display_order, allowed_user_group_ids)
                    values (?, 0, ?, ?)
                ", array($warningGroup['warning_group_id'], $warningGroup['display_order'], $user_group_id));
                // copy the phrase
                $text = $phraseModel->getMasterPhraseValue('warning_group_'.$warningGroup['warning_group_id']);
                $phraseModel->insertOrUpdateMasterPhrase('sv_warning_category_'.$warningGroup['warning_group_id'].'_title', $text, '', array(),  array(
                    XenForo_DataWriter_Phrase::OPTION_REBUILD_LANGUAGE_CACHE => false,
                    XenForo_DataWriter_Phrase::OPTION_RECOMPILE_TEMPLATE => false
                ));
            }
            // update warning definitions
            $db->query('update xf_warning_definition
                set sv_warning_category_id = warning_group_id, sv_display_order = display_order
                where sv_warning_category_id = 0
            ');
            // update warning actions
            $warningActions = $db->fetchAll('
                select *
                from xf_warning_action
            ');
            foreach($warningActions as $warningAction)
            {
                $groups = array_filter(explode(',', $warningAction['warning_groups']));
                switch (count($groups))
                {
                    case 0:
                        continue;
                    case 1:
                        $group = reset($groups);
                        if ($group)
                        {
                            $db->query('update xf_warning_action
                                set sv_warning_category_id = ?
                                where warning_action_id = ?
                            ', array($group, $warningAction['warning_action_id']));
                        }
                        continue;
                    default:
                        break;
                }
                // copy a warning action for each category
                unset($warningAction['warning_action_id']);
                unset($warningAction['warning_groups']);
                $keys = array_keys($warningAction);
                foreach($groups as $group)
                {
                    $warningAction['sv_warning_category_id'] = $group;
                    $db->query("insert ignore into xf_warning_action (".implode(',', $keys).")
                        values (".implode(',', array_fill(0, count($keys), '?')).")
                    ", $warningAction);
               }
            }

            XenForo_Db::commit();
        }

        if ($requireDefault && $version < 1040000)
        {
            // make sure the model is loaded before accessing the static properties
            XenForo_Model::create("XenForo_Model_User");
            // set default permission values for Registered group
            $user_group_id = XenForo_Model_User::$defaultRegisteredGroupId;
            // create default warning category, do not use the data writer as that requires the rest of the add-on to be setup
            $db->query("insert ignore into xf_sv_warning_category (warning_category_id, parent_warning_category_id, display_order, allowed_user_group_ids)
                values (1, 0, 0, ?)
            ", array($user_group_id));
        }
        // set all warning definitions to be in default warning category, note; the phrase is defined in the XML
        $db->query('update xf_warning_definition
            set sv_warning_category_id = 1
            where sv_warning_category_id = 0 or
                  not exists (select *
                              from xf_sv_warning_category
                              where xf_warning_definition.sv_warning_category_id = xf_sv_warning_category.warning_category_id)
        ');

        $db->query("
            INSERT IGNORE INTO xf_content_type
                (content_type, addon_id, fields)
            VALUES
                ('".SV_WarningImprovements_AlertHandler_Warning::ContentType."', 'SV_WarningImprovements', '')
        ");

        $db->query("
            INSERT IGNORE INTO xf_content_type_field
                (content_type, field_name, field_value)
            VALUES
                ('".SV_WarningImprovements_AlertHandler_Warning::ContentType."', 'alert_handler_class', 'SV_WarningImprovements_AlertHandler_Warning')
        ");


        SV_Utils_Install::addColumn("xf_warning_action", "sv_post_node_id", "INT NOT NULL DEFAULT 0");
        SV_Utils_Install::addColumn("xf_warning_action", "sv_post_thread_id", "INT NOT NULL DEFAULT 0");
        SV_Utils_Install::addColumn("xf_warning_action", "sv_post_as_user_id", "INT");
/*
        SV_Utils_Install::addColumn("xf_warning", "sv_PauseExpireOnSuspended", "TINYINT NOT NULL DEFAULT 1");
        SV_Utils_Install::addColumn("xf_warning_definition", "sv_PauseExpireOnSuspended", "TINYINT NOT NULL DEFAULT 1");
        SV_Utils_Install::addColumn("xf_user_group", "sv_suspends", "TINYINT NOT NULL DEFAULT 0");


        if ($version < 1)
        {
            $db->query("update xf_user_group set sv_suspends = 1 where title like '%suspended%' or title like '%XF Ban%' or title like '%XF Ban%' or title = 'Banned';");
        }
*/

        XenForo_Application::defer('SV_WarningImprovements_Deferred_WarningActionFixup1050700', array());
        XenForo_Application::defer('SV_WarningImprovements_Deferred_InitializeWarningExpiry', array());

        return true;
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');

        $db->query("
            DELETE FROM xf_content_type_field
            WHERE xf_content_type_field.field_value = 'SV_WarningImprovements_AlertHandler_Warning'
        ");

        $db->query("
            DELETE FROM xf_content_type
            WHERE xf_content_type.addon_id = 'SV_WarningImprovements'
        ");

        $db->query("
            DELETE FROM xf_warning_definition
            WHERE warning_definition_id = 0
        ");

        $db->query("
            DROP TABLE IF EXISTS `xf_sv_warning_default`
        ");

        $db->query('DROP TABLE IF EXISTS xf_sv_warning_category');

        SV_Utils_Install::dropColumn(
            'xf_warning_definition',
            'sv_warning_category_id'
        );
        SV_Utils_Install::dropColumn(
            'xf_warning_definition',
            'sv_display_order'
        );

        SV_Utils_Install::dropColumn(
            'xf_warning_action',
            'sv_warning_category_id'
        );

        $db->query("
            DELETE FROM xf_permission_entry
            WHERE permission_group_id = 'general' and permission_id = 'sv_editWarningActions'
        ");
        $db->query("
            DELETE FROM xf_permission_entry
            WHERE permission_group_id = 'general' and permission_id = 'sv_showAllWarningActions'
        ");
        $db->query("
            DELETE FROM xf_permission_entry
            WHERE permission_group_id = 'general' and permission_id = 'sv_viewWarningActions'
        ");
        $db->query("
            DELETE FROM xf_permission_entry
            WHERE permission_group_id = 'general' and permission_id = 'viewWarning_issuer'
        ");

        SV_Utils_Install::dropColumn("xf_warning_action", "sv_post_node_id");
        SV_Utils_Install::dropColumn("xf_warning_action", "sv_post_thread_id");
        SV_Utils_Install::dropColumn("xf_warning_action", "sv_post_as_user_id");
/*
        SV_Utils_Install::dropColumn("xf_warning", "sv_PauseExpireOnSuspended");
        SV_Utils_Install::dropColumn("xf_warning_definition", "sv_PauseExpireOnSuspended");
        SV_Utils_Install::dropColumn("xf_user_group", "sv_suspends");
*/


        return true;
    }
}
