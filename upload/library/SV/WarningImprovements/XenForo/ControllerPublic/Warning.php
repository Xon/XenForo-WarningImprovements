<?php

class SV_WarningImprovements_XenForo_ControllerPublic_Warning extends XFCP_SV_WarningImprovements_XenForo_ControllerPublic_Warning
{
    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }

    public function actionIndex()
    {
        $warningId = $this->_input->filterSingle('warning_id', XenForo_Input::UINT);
        $warning = $this->_getWarningOrError($warningId);

        // ensure XenForo_Model_User is loaded, and thus SV_WarningImprovements_XenForo_Model_User
        $this->_getUserModel();

        $visitor = XenForo_Visitor::getInstance();
        if ($warning['user_id'] == $visitor['user_id'])
        {
            SV_WarningImprovements_XenForo_Model_User::$warning_user_id = $warning['user_id'];
        }

        $ret = parent::actionIndex();

        SV_WarningImprovements_XenForo_Model_User::$warning_user_id = null;

        return $ret;
    }
}
