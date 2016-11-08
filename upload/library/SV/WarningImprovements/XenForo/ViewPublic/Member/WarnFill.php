<?php

class SV_WarningImprovements_XenForo_ViewPublic_Member_WarnFill extends XFCP_SV_WarningImprovements_XenForo_ViewPublic_Member_WarnFill
{
    public function renderJson()
    {
        $response = parent::renderJson();

        $warning = $this->_params['warning'];
        $options = XenForo_Application::getOptions();

        if($options->sv_warningimprovements_conversation_locked)
        {
            $response['formValues']['input[name=conversation_locked]'] = true;
        }
        if(!$options->sv_warningimprovements_conversation_send_default)
        {
            $response['formValues']['#startConversation'] = false;
        }

        return $response;
    }
}