<?php

class SV_WarningImprovements_XenForo_DataWriter_Warning extends XFCP_SV_WarningImprovements_XenForo_DataWriter_Warning
{
    protected function _getFields()
    {
        $fields = parent::_getFields();

        if (isset($fields['xf_warning']) && isset($fields['xf_warning']['notes']))
        {
            $options = XenForo_Application::getOptions();
            if ($options->sv_wi_require_warning_notes)
            {
                unset($fields['xf_warning']['notes']['default']);
                $fields['xf_warning']['notes']['required'] = true;
            }
        }

        return $fields;
    }

    protected function _preSave()
    {
        $warning_definition_id = $this->get('warning_definition_id');
        if ($warning_definition_id == 0)
        {
            $warningModel = $this->_getWarningModel();
            $warning = $warningModel->getWarningDefinitionById($warning_definition_id);
            if (!empty($warning))
            {
                $dwInput = array();
                $warning = $warningModel->prepareWarningDefinition($warning);
                $dwInput['extra_user_group_ids'] = $warning['extra_user_group_ids'];
                if (!$warning['is_editable'])
                {
                    $dwInput['points'] = $warning['points_default'];
                    $dwInput['expiry_date'] = (
                        $warning['expiry_type'] == 'never' ? 0
                        : min(
                            pow(2,32) - 1,
                            strtotime('+' . $warning['expiry_default'] . ' ' . $warning['expiry_type'])
                        )
                    );
                }
                $this->bulkSet($dwInput);
			}
        }
        parent::_preSave();
    }

    protected function _postSaveAfterTransaction()
    {
        parent::_postSaveAfterTransaction();

        if (SV_WarningImprovements_Globals::$captureWarning)
        {
            SV_WarningImprovements_Globals::$warningObj = $this->getMergedData();
        }

        if ($this->isInsert() && SV_WarningImprovements_Globals::$SendWarningAlert)
        {
            $options = XenForo_Application::getOptions();
            $user_id = 0;
            $username = '';
            if (!$options->sv_warningimprovements_anonymise_alert)
            {
                $warning_user = $this->_getUserModel()->getUserById($this->get('warning_user_id'));
                if ($warning_user && isset($warning_user['user_id']))
                {
                    $user_id = $warning_user['user_id'];
                    $username  = $warning_user['username'];
                }
            }
            XenForo_Model_Alert::alert(
                $this->get('user_id'),
                $user_id, $username,
                SV_WarningImprovements_AlertHandler_Warning::ContentType,
                $this->get('warning_id'),
                'warning');
        }
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->getModelFromCache('XenForo_Model_Alert')->deleteAlerts('warning', $this->get('warning_id'));
    }

    protected function _getWarningModel()
    {
        return $this->getModelFromCache('XenForo_Model_Warning');
    }
}
