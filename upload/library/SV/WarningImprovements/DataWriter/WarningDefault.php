<?php

class SV_WarningImprovements_DataWriter_WarningDefault extends XenForo_DataWriter
{
    protected function _getFields()
    {
        return array(
            'xf_sv_warning_default' => array(
                'warning_default_id'    => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
                'threshold_points'      => array('type' => self::TYPE_UINT, 'required' => true, 'max' => 65535),
                'expiry_type'           => array('type' => self::TYPE_STRING, 'default' => 'never',
                        'allowedValues' => array('never', 'days', 'weeks', 'months', 'years')
                ),
                'expiry_extension'      => array('type' => self::TYPE_UINT, 'default' => 0, 'max' => 65535),
                'active'                => array('type' => self::TYPE_UINT, 'required' => true),
                )
        );
    }

    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data))
        {
            return false;
        }

        return array('xf_sv_warning_default' => $this->_getWarningModel()->getWarningDefaultById($id));
    }

    protected function _getUpdateCondition($tableName)
    {
        return 'warning_default_id = ' . $this->_db->quote($this->getExisting('warning_default_id'));
    }

    protected function _preSave()
    {
    }

    protected function _postSave()
    {
    }

    protected function _postDelete()
    {
    }

    /**
     * @return XenForo_Model|XenForo_Model_Warning|SV_WarningImprovements_XenForo_Model_Warning
     */
    protected function _getWarningModel()
    {
        return $this->getModelFromCache('XenForo_Model_Warning');
    }
}
