<?php

class SV_WarningImprovements_XenForo_ControllerPublic_Member extends XFCP_SV_WarningImprovements_XenForo_ControllerPublic_Member
{
	public function actionMember()
	{
        SV_WarningImprovements_Globals::$warning_user_id = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        return parent::actionMember();
    }

    public function actionWarn()
    {
        SV_WarningImprovements_Globals::$warning_user_id = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
        SV_WarningImprovements_Globals::$SendWarningAlert = $this->_input->filterSingle('send_warning_alert', XenForo_Input::BOOLEAN);

        return parent::actionWarn();
    }

    public function actionWarnings()
	{
        SV_WarningImprovements_Globals::$warning_user_id = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        return parent::actionWarnings();
    }
    
    public function actionCard()
    {
        SV_WarningImprovements_Globals::$warning_user_id = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        return parent::actionCard();
    }
}