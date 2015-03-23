<?php
class SV_WarningImprovements_XenForo_Model_User extends XFCP_SV_WarningImprovements_XenForo_Model_User
{
    public static $warning_user_id = null;

    public function canViewWarnings(&$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (preg_match('/members\/.*?\.(\d+)\//i', $_SERVER['REQUEST_URI'], $matches))
        {
            if ($matches[1]==$viewingUser['user_id'])
            {
                return true;
            }
        }
        else if (!empty(self::$warning_user_id) && self::$warning_user_id == $viewingUser['user_id'])
        {
            return true;
        }

        return parent::canViewWarnings($errorPhraseKey, $viewingUser);
    }
}





