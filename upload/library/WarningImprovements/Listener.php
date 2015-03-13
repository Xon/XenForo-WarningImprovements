<?php

class SV_WarningImprovements_Listener
{
    const AddonNameSpace = 'SV_WarningImprovements';

    public static function install($existingAddOn, $addOnData)
    {
        $version = isset($existingAddOn['version_id']) ? $existingAddOn['version_id'] : 0;
/*
        $db = XenForo_Application::getDb();

        SV_WarningImprovements_Install::addColumn("xf_warning", "sv_PauseExpireOnSuspended", "TINYINT NOT NULL DEFAULT 1");
        SV_WarningImprovements_Install::addColumn("xf_warning_definition", "sv_PauseExpireOnSuspended", "TINYINT NOT NULL DEFAULT 1");
        SV_WarningImprovements_Install::addColumn("xf_user_group", "sv_suspends", "TINYINT NOT NULL DEFAULT 0");


        if ($version < 1)
        {
            $db->query("update xf_user_group set sv_suspends = 1 where title like '%suspended%' or title like '%XF Ban%' or title like '%XF Ban%' or title = 'Banned';");
        }
*/
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
