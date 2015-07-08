<?php
class SV_WarningImprovements_XenForo_Model_Conversation extends XFCP_SV_WarningImprovements_XenForo_Model_Conversation
{
    protected $sv_force_email_for_user_id = null;
    protected $sv_respect_receive_admin_email = true;

    public function insertConversationAlert(array $conversation, array $alertUser, $action,
        array $triggerUser = null, array $extraData = null, array &$messageInfo = null
    )
    {
        if (SV_WarningImprovements_Globals::$warningObj && SV_WarningImprovements_Globals::$warningObj['user_id'] == $alertUser['user_id'])
        {
            if ($this->sv_force_email_for_user_id === null)
            {
                $this->sv_force_email_for_user_id = 0;
                $options = XenForo_Application::getOptions();
                if ($options->sv_force_conversation_email_on_warning)
                {
                    $this->sv_respect_receive_admin_email = $options->sv_respect_receive_admin_email_on_warning;
                    $this->sv_force_email_for_user_id = SV_WarningImprovements_Globals::$warningObj['user_id'];
                }
                if ($options->sv_only_force_warning_email_on_banned && !$alertUser['is_banned'])
                {
                    $this->sv_force_email_for_user_id = 0;
                }
                XenForo_Application::get('options')->emailConversationIncludeMessage = true;
            }
            if ($this->sv_force_email_for_user_id)
            {
                if ($this->sv_respect_receive_admin_email)
                {
                    $alertUser['email_on_conversation'] = $alertUser['receive_admin_email'];
                }
                else
                {
                    $alertUser['email_on_conversation'] = true;
                }
                $alertUser['is_banned'] = false;
            }
        }

        parent::insertConversationAlert($conversation, $alertUser, $action, $triggerUser, $extraData, $messageInfo);
    }
}
