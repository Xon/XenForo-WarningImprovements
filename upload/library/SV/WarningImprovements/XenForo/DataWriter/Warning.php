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

    protected function _postSave()
    {
        parent::_postSave();

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

}
