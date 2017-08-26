<?php

class SV_WarningImprovements_Deferred_InitializeWarningExpiry extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $data = array_merge(array(
            'position' => 0,
            'batch' => 1000
        ), $data);
        $data['batch'] = max(1, $data['batch']);

        /* @var $userModel XenForo_Model_User */
        $userModel = XenForo_Model::create('XenForo_Model_User');

        $userIds = $userModel->getUserIdsInRange($data['position'], $data['batch']);
        if (empty($userIds))
        {
            return true;
        }

        $s = microtime(true);
        foreach ($userIds AS $userId)
        {
            $data['position'] = $userId;

            $warningModel->updatePendingExpiryFor($userId, true);

            if ($targetRunTime && microtime(true) - $s > $targetRunTime)
            {
                break;
            }
        }

        $actionPhrase = new XenForo_Phrase('rebuilding');
        $typePhrase = new XenForo_Phrase('users');
        $status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

        return $data;
    }
}
