<?php

class SV_WarningImprovements_XenForo_DataWriter_WarningDefinition extends XFCP_SV_WarningImprovements_XenForo_DataWriter_WarningDefinition
{
    const IS_CUSTOM = 'IS_CUSTOM';


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
}