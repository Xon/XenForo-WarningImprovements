<?php

class SV_WarningImprovements_Option_UserName
{
    public static function verifyOption_AllowNone(&$option, XenForo_DataWriter $dw, $fieldName)
    {
        $opt = trim($option);
        if ($opt == '')
        {
            $otpion = $opt;
            return true;
        }

        return self::verifyOption($option, $dw, $fieldName);
    }

    public static function verifyOption(&$option, XenForo_DataWriter $dw, $fieldName)
    {
        $user = XenForo_Model::create("XenForo_Model_User")->getUserByName($option, array());

        if (!empty($user['username']))
        {
            $option = $user['username'];
            return true;
        }

        $dw->error(new XenForo_Phrase('svwi_the_user_x_could_not_be_found', array('name' => $option)), $fieldName);
        return false;
    }
}