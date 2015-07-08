<?php

class SV_WarningImprovements_XenForo_DataWriter_ConversationMessage extends XFCP_SV_WarningImprovements_XenForo_DataWriter_ConversationMessage
{
    protected function _preSave()
    {
        if (SV_WarningImprovements_Globals::$warningObj)
        {
            $warning = SV_WarningImprovements_Globals::$warningObj;
            $replace = array(
                '{points}' => $warning['points'],
                '{warning_title}' => $warning['title'],
                '{warning_link}' => XenForo_Link::buildPublicLink('full:warnings', $warning),
            );

            $message = $this->get('message');
            $this->set('message', strtr((string)$message, $replace));
        }

        return parent::_preSave();
    }
}
