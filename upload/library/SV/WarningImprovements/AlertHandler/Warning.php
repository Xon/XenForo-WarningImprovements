<?php

class SV_WarningImprovements_AlertHandler_Warning extends XenForo_AlertHandler_Abstract
{
    const ContentType = 'warning_alert';

    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
        /** @var SV_WarningImprovements_XenForo_Model_Warning $warningModel */
        $warningModel = $model->getModelFromCache('XenForo_Model_Warning');
        return $warningModel->getWarningByIds($contentIds);
    }

    public function canViewAlert(array $alert, $content, array $viewingUser)
    {
        return true;
    }
}
