<?php

class SV_WarningImprovements_XenForo_Model_Warning_Patch extends XFCP_SV_WarningImprovements_XenForo_Model_Warning_Patch
{
    protected function _userWarningPointsIncreased(
        $userId,
        $newPoints,
        $oldPoints
    ) {
        $actions = $this->getWarningActions();
        if (!$actions)
        {
            return;
        }

        $warningPoints = $this->getCategoryWarningPointsByUser($userId);

        $db = $this->_getDb();
        XenForo_Db::beginTransaction($db);

        foreach ($actions as $action)
        {
            $points = array(
                'old' => $oldPoints,
                'new' => $newPoints
            );

            $warningCategoryId = $action['sv_warning_category_id'];
            if (!empty($warningPoints[$warningCategoryId]))
            {
                $points = $warningPoints[$warningCategoryId];
            }

            if ($action['points'] <= $points['old'])
            {
                continue; // already triggered
            }
            elseif ($action['points'] > $points['new'])
            {
                continue; // no trigger yet
            }

            $this->triggerWarningAction($userId, $action);
        }

        XenForo_Db::commit($db);
    }

    protected function _userWarningPointsDecreased(
        $userId,
        $newPoints,
        $oldPoints
    ) {
        $triggers = $this->getUserWarningActionTriggers($userId);
        if (!$triggers)
        {
            return;
        }

        $warningActions = $this->getWarningActions();
        $warningPoints = $this->getCategoryWarningPointsByUser($userId);

        $db = $this->_getDb();
        XenForo_Db::beginTransaction($db);

        foreach ($triggers as $trigger)
        {
            $points = array(
                'old' => $oldPoints,
                'new' => $newPoints
            );

            $warningActionId = $trigger['warning_action_id'];
            if (!empty($warningActions[$warningActionId]))
            {
                $warningAction = $warningActions[$warningActionId];

                $warningCategoryId = $warningAction['sv_warning_category_id'];
                if (!empty($warningPoints[$warningCategoryId]))
                {
                    $points = $warningPoints[$warningCategoryId];
                }
            }

            if ($trigger['trigger_points'] > $points['new'])
            {
                // points have fallen below trigger, remove it
                $this->removeWarningActionTrigger($userId, $trigger);
            }
        }

        XenForo_Db::commit($db);
    }
}
