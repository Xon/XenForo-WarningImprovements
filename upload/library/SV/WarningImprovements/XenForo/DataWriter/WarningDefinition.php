<?php

class SV_WarningImprovements_XenForo_DataWriter_WarningDefinition extends XFCP_SV_WarningImprovements_XenForo_DataWriter_WarningDefinition
{
    const IS_CUSTOM = 'IS_CUSTOM';

    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_warning_definition'] += array(
            'sv_warning_category_id' => array(
                'type'         => self::TYPE_UINT,
                'verification' => array('$this', '_verifyWarningCategoryId')
            ),
            'sv_display_order' => array(
                'type' => self::TYPE_UINT
            ),
            'sv_custom_title' => array(
                'type' => self::TYPE_BOOLEAN,
                'default' => false,
            ),
        );

        return $fields;
    }

    protected function _getDefaultOptions()
    {
        return parent::_getDefaultOptions() + array(
            self::IS_CUSTOM => false
        );
    }

    protected function _getExistingData($data)
    {
        if ($data === 0 && $this->getOption(self::IS_CUSTOM))
        {
            $id = 0;
        }
        else if (!$id = $this->_getExistingPrimaryKey($data))
        {
            return false;
        }

        return array('xf_warning_definition' => $this->_getWarningModel()->getWarningDefinitionById($id));
    }

    protected function _preSave()
    {
        parent::_preSave();

        $warningDefinitionInput = SV_WarningImprovements_Globals::$warningDefinitionInput;

        if ($warningDefinitionInput !== null)
        {
            $this->bulkSet($warningDefinitionInput);
        }
    }

    protected function _verifyWarningCategoryId($warningCategoryId)
    {
        if (empty($warningCategoryId))
        {
            return false;
        }

        /** @var SV_WarningImprovements_XenForo_Model_Warning $warningModel */
        $warningModel = $this->_getWarningModel();
        $warningCategory = $warningModel->getWarningCategoryById(
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
