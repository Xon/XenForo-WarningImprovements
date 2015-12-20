<?php

class SV_WarningImprovements_XenForo_DataWriter_WarningAction extends XFCP_SV_WarningImprovements_XenForo_DataWriter_WarningAction
{
    protected function _getFields()
    {
        $fields = parent::_getFields();
        $fields['xf_warning_action']['sv_post_node_id'] = array('type' => self::TYPE_UINT, 'default' => 0);
        $fields['xf_warning_action']['sv_post_thread_id'] = array('type' => self::TYPE_UINT, 'default' => 0);
        return $fields;
    }

    protected function _preSave()
    {
        if (SV_WarningImprovements_Globals::$warningActionInput)
        {
            $this->bulkSet(SV_WarningImprovements_Globals::$warningActionInput);
        }
    }
}