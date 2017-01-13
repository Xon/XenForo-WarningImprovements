<?php

class SV_WarningImprovements_XenForo_DataWriter_WarningAction extends XFCP_SV_WarningImprovements_XenForo_DataWriter_WarningAction
{
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_warning_action']['sv_post_node_id'] = array(
            'type'    => self::TYPE_UINT,
            'default' => 0
        );
        $fields['xf_warning_action']['sv_post_thread_id'] = array(
            'type'    => self::TYPE_UINT,
            'default' => 0
        );
        $fields['xf_warning_action']['sv_post_as_user_id'] = array(
            'type'    => self::TYPE_UINT,
            'default' => 0
        );
        $fields['xf_warning_action']['sv_warning_category_id'] = array(
            'type'         => self::TYPE_UINT,
            'default'      => 0,
            'verification' => ['$this', '_verifyWarningCategoryId']
        );

        return $fields;
    }

    protected function _preSave()
    {
        if (SV_WarningImprovements_Globals::$warningActionInput)
        {
            $this->bulkSet(SV_WarningImprovements_Globals::$warningActionInput);
        }
    }

    protected function _verifyWarningCategoryId($warningCategoryId)
    {
        if (empty($warningCategoryId))
        {
            return false;
        }

        $warningCategory = $this->_getWarningModel()->getWarningCategoryById(
            $warningCategoryId
        );

        if (!empty($warningCategory))
        {
            return true;
        }

        $this->error(
            new XenForo_Phrase('sv_please_enter_valid_warning_category_id'),
            'sv_warning_category_id'
        );

        return false;
    }
}
