<?php

class SV_WarningImprovements_XenForo_DataWriter_UserBan extends XFCP_SV_WarningImprovements_XenForo_DataWriter_UserBan
{
    protected function _postSaveAfterTransaction()
    {
        parent::_postSaveAfterTransaction();

        if ($this->isInsert() || $this->isChanged('end_date'))
        {
            $this->_getWarningModel()->updatePendingExpiryFor($this->get('user_id'), true);
        }
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->_getWarningModel()->updatePendingExpiryFor($this->get('user_id'), true);
    }

    protected function _getWarningModel()
    {
        return $this->getModelFromCache('XenForo_Model_Warning');
    }
}
