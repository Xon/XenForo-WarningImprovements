<?php

class SV_WarningImprovements_Listener
{
    const AddonNameSpace = 'SV_WarningImprovements';

    public static function install($existingAddOn, $addOnData)
    {
        $version = isset($existingAddOn['version_id']) ? $existingAddOn['version_id'] : 0;

        $db = XenForo_Application::getDb();
        $options = XenForo_Application::getOptions();

        $addonModel = XenForo_Model::create("XenForo_Model_AddOn");
        $addonsToUninstall = array('SV_AlertOnWarning' => array("sv_alert_warning_anonymise" => "sv_warning_anonymise"),
                                   'SVViewOwnWarnings' => array());
        foreach($addonsToUninstall as $addonToUninstall => $keys)
        {
            $addon = $addonModel->getAddOnById($addonToUninstall);
            XenForo_Error::debug(var_export($addon,true));
            if (!empty($addon))
            {
                if(!empty($keys))
                foreach($keys as $old => $new)
                {
                    $options->$new = $options->$old;
                }

                $dw = XenForo_DataWriter::create('XenForo_DataWriter_AddOn');
                $dw->setExistingData($addonToUninstall);
                $dw->delete();
            }
        }

/*


        SV_WarningImprovements_Install::addColumn("xf_warning", "sv_PauseExpireOnSuspended", "TINYINT NOT NULL DEFAULT 1");
        SV_WarningImprovements_Install::addColumn("xf_warning_definition", "sv_PauseExpireOnSuspended", "TINYINT NOT NULL DEFAULT 1");
        SV_WarningImprovements_Install::addColumn("xf_user_group", "sv_suspends", "TINYINT NOT NULL DEFAULT 0");


        if ($version < 1)
        {
            $db->query("update xf_user_group set sv_suspends = 1 where title like '%suspended%' or title like '%XF Ban%' or title like '%XF Ban%' or title = 'Banned';");
        }
*/

        // insert the defaults for the custom warning. This can't be normally inserted so fiddle with the sql_mode
        $db->query("SET SESSION sql_mode='STRICT_ALL_TABLES,NO_AUTO_VALUE_ON_ZERO'");
        $db->query("insert ignore into xf_warning_definition
                (warning_definition_id,points_default,expiry_type,expiry_default,extra_user_group_ids,is_editable)
            values
                (0,1, 'months',1,'',1);
        ");
        $db->query("SET SESSION sql_mode='STRICT_ALL_TABLES'");

        $db = XenForo_Application::getDb();

        $db->query("
            INSERT IGNORE INTO xf_content_type
                (content_type, addon_id, fields)
            VALUES
                ('".SV_WarningImprovements_AlertHandler_Warning::ContentType."', '".self::AddonNameSpace."', '')
        ");

        $db->query("
            INSERT IGNORE INTO xf_content_type_field
                (content_type, field_name, field_value)
            VALUES
                ('".SV_WarningImprovements_AlertHandler_Warning::ContentType."', 'alert_handler_class', 'SV_WarningImprovements_AlertHandler_Warning')
        ");

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
            WHERE xf_content_type.addon_id = '".self::AddonNameSpace."'
        ");

/*
        SV_WarningImprovements_Install::dropColumn("xf_warning", "sv_PauseExpireOnSuspended");
        SV_WarningImprovements_Install::dropColumn("xf_warning_definition", "sv_PauseExpireOnSuspended");
        SV_WarningImprovements_Install::dropColumn("xf_user_group", "sv_suspends");
*/
        return true;
    }

    public static function load_class($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace.'_'.$class;
    }
}
