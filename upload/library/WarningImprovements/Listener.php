<?php

class SV_WarningImprovements_Listener
{
    const AddonNameSpace = 'SV_WarningImprovements';

    public static function install($existingAddOn, $addOnData)
    {
        $version = isset($existingAddOn['version_id']) ? $existingAddOn['version_id'] : 0;

        $db = XenForo_Application::getDb();
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

        return true;
    }

    public static function uninstall()
    {
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
