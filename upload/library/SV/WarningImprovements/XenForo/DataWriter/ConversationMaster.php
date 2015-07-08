<?php

class SV_WarningImprovements_XenForo_DataWriter_ConversationMaster extends XFCP_SV_WarningImprovements_XenForo_DataWriter_ConversationMaster
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

            $title = $this->get('title');
            $this->set('title', strtr((string)$title, $replace));

            $message = $this->getExtraData(self::DATA_MESSAGE);
            $this->setExtraData(self::DATA_MESSAGE, strtr((string)$message, $replace));
        }

        return parent::_preSave();
    }
}
