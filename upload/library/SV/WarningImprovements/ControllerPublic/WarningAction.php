<?php

class SV_WarningImprovements_ControllerPublic_WarningAction extends XenForo_ControllerPublic_Abstract
{
    public function actionIndex()
    {
        $userChangeTempId = $this->_input->filterSingle('warning_action_id', XenForo_Input::UINT);
        // warning actions => xf_user_change_temp
        throw new Exception("Not implemented");
    }

    public function actionExpire()
    {
        $userChangeTempId = $this->_input->filterSingle('warning_action_id', XenForo_Input::UINT);
        // warning actions => xf_user_change_temp
        throw new Exception("Not implemented");
    }

    protected function _getUserChangeTempModel()
    {
        return $this->getModelFromCache('XenForo_Model_UserChangeTemp');
    }

    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }
}