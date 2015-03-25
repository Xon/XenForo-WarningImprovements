<?php

class SV_WarningImprovements_XenForo_ControllerPublic_Member extends XFCP_SV_WarningImprovements_XenForo_ControllerPublic_Member
{
    public function actionWarn()
    {
        SV_WarningImprovements_Globals::$SendWarningAlert = $this->_input->filterSingle('send_warning_alert', XenForo_Input::BOOLEAN);

        return parent::actionWarn();    
    }
}