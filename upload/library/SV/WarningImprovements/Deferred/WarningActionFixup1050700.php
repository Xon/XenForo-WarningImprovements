<?php

class SV_WarningImprovements_Deferred_WarningActionFixup1050700 extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $increment = 500;
        $action_trigger_id = isset($data['action_trigger_id']) ? $data['action_trigger_id'] : -1;
        $fixedUsers = isset($data['users']) ? $data['users'] : array();

        $db = XenForo_Application::getDb();

        /** @var XenForo_Model_User $userModel */
        $userModel = XenForo_Model::create('XenForo_Model_User');
        /** @var SV_WarningImprovements_XenForo_Model_Warning $warningModel */
        $warningModel = XenForo_Model::create('XenForo_Model_Warning');

        $warningActions = $userModel->fetchAllKeyed("
            SELECT *
            FROM xf_warning_action
        ", 'warning_action_id');
        if (empty($warningActions))
        {
            return false;
        }

        $actionTriggers = $db->fetchAll("
            SELECT *
            FROM xf_warning_action_trigger
            WHERE action_trigger_id > ?
            ORDER BY action_trigger_id
            LIMIT " . $increment . "
        ", array($action_trigger_id));

        if (empty($actionTriggers))
        {
            return false;
        }

        $userIds = array_unique(XenForo_Application::arrayColumn($actionTriggers, 'user_id'));
        if (empty($userIds))
        {
            return false;
        }
        $users = $userModel->getUsersByIds($userIds);
        if (empty($users))
        {
            return false;
        }

        // ensure point totals are sane
        $db->query("
            update xf_user
            set warning_points = coalesce((select sum(coalesce(points,0))
                 from xf_warning
                 where
                    is_expired = 0 and
                    xf_warning.user_id = xf_user.user_id
                ),0)
            where user_id in (".$db->quote($userIds).")
        ");
        // cleanup any expired warnings
        $warningModel->processExpiredWarnings();

        $s = microtime(true);
        foreach ($actionTriggers as $actionTrigger)
        {
            if (isset($fixedUsers[$actionTrigger['user_id']]))
            {
                continue;
            }
            $fixedUsers[$actionTrigger['user_id']] = true;
            if (empty($users[$actionTrigger['user_id']]))
            {
                continue;
            }
            $user = $users[$actionTrigger['user_id']];
            if (empty($warningActions[$actionTrigger['warning_action_id']]))
            {
                continue;
            }
            $warningAction = $warningActions[$actionTrigger['warning_action_id']];
            if ($warningAction['action_length_type'] != 'points')
            {
                continue;
            }
            if ($user['warning_points'] < $actionTrigger['trigger_points'])
            {
                XenForo_Error::logException(new Exception("Fixing warnings for user:{$user['username']} - {$user['user_id']}"), false);
                $warningModel->userWarningPointsChanged($user['user_id'], $user['warning_points'], $user['warning_points'] + 1);
            }

            $action_trigger_id = $actionTrigger['action_trigger_id'];

            if ($targetRunTime && microtime(true) - $s > $targetRunTime)
            {
                break;
            }
        }

        if ($action_trigger_id <= 0)
        {
            return false;
        }

        return array('action_trigger_id' => $action_trigger_id, 'users' => $fixedUsers);
    }
}
