<?php

class SV_WarningImprovements_XenForo_ViewPublic_Member_WarnFill extends XFCP_SV_WarningImprovements_XenForo_ViewPublic_Member_WarnFill
{
    public function renderJson()
    {
        $response = parent::renderJson();

        $warning = $this->_params['warning'];

        if(XenForo_Application::getOptions()->sv_warningimprovements_conversation_locked)
        {
            $response['formValues']['input[name=conversation_locked]'] = true;
        }

        return $response;
    }
}