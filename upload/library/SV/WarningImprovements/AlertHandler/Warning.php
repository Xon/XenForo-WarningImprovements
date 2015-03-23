<?php

class SV_WarningImprovements_AlertHandler_Warning extends XenForo_AlertHandler_Abstract
{
    const ContentType = 'warning_alert';

    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
         return $model->getModelFromCache('XenForo_Model_Warning')->getWarningByIds($contentIds);
    }

    public function canViewAlert(array $alert, $content, array $viewingUser)
    {
        return true;
    }
}
