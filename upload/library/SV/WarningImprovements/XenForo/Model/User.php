<?php
class SV_WarningImprovements_XenForo_Model_User extends XFCP_SV_WarningImprovements_XenForo_Model_User
{
    public function canViewWarnings(&$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (!empty(SV_WarningImprovements_Globals::$warning_user_id) && SV_WarningImprovements_Globals::$warning_user_id == $viewingUser['user_id'])
        {
            return true;
        }

        return parent::canViewWarnings($errorPhraseKey, $viewingUser);
    }
}





