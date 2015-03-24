<?php

class SV_WarningImprovements_XenForo_ControllerPublic_Member extends XFCP_SV_WarningImprovements_XenForo_ControllerPublic_Member
{
    public function actionWarn()
    {
        // ensure XenForo_DataWriter_Warning is loaded, and thus SV_WarningImprovements_XenForo_DataWriter_Warning
        XenForo_DataWriter::create("XenForo_DataWriter_Warning");

        SV_WarningImprovements_XenForo_DataWriter_Warning::$SendWarningAlert = $this->_input->filterSingle('send_warning_alert', XenForo_Input::BOOLEAN);

        return parent::actionWarn();    
    }
}