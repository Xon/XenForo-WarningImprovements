<?php

class SV_WarningImprovements_XenForo_Model_UserChangeTemp extends XFCP_SV_WarningImprovements_XenForo_Model_UserChangeTemp
{
    public function expireWarningAction(array $warningAction)
    {
        return $this->expireTempUserChange($warningAction);
    }

    public function expireTempUserChange(array $change)
    {
        $ret = parent::expireTempUserChange($change);
        $this->_getWarningModel()->updatePendingExpiryFor($change['user_id'], true);
        return $ret;
    }

    public function updateWarningActionExpiryDate(array $warningAction, $expiryDate)
    {
        $res = $this->_getDb()->query('
            UPDATE xf_user_change_temp
            SET expiry_date = ?
            WHERE user_change_temp_id = ?
        ', array($expiryDate, $warningAction['user_change_temp_id']));
        $rowCount = $res->rowCount();

        if($rowCount)
        {
            $this->_getWarningModel()->updatePendingExpiryFor($warningAction['user_id'], true);
        }

        return $rowCount;
    }

    public function getWarningActionById($id)
    {
        return $this->_getDb()->fetchRow('
            SELECT xf_user_change_temp.*, user_change_temp_id as warning_action_id
            FROM xf_user_change_temp
            WHERE user_change_temp_id = ? and change_key like \'warning_action_%\'
        ', $id);
    }

    public function countWarningActionsByUser($userId, $showAll = false, $showDiscouraged = false)
    {
        $sql = '';
        if (!$showDiscouraged)
        {
            $sql .= " and action_type <> 'field' and action_modifier <> 'is_discouraged' ";
        }

        if ($showAll)
        {
            $select = 'user_change_temp_id';
        }
        else
        {
            $select = 'distinct action_type, new_value';
        }

        return $this->_getDb()->fetchOne('
            SELECT COUNT(' . $select . ')
            FROM xf_user_change_temp
            WHERE user_id = ? ' . $sql . ' and change_key like \'warning_action_%\'
        ', $userId);
    }

    public function getWarningActionsByUser($userId, $showAll = false, $showDiscouraged = false, $onlyExpired = false)
    {
        $sql = '';
        if (!$showDiscouraged)
        {
            $sql .= " and action_type <> 'field' and action_modifier <> 'is_discouraged' ";
        }

        if ($showAll)
        {
            $where = 'user_id = ? ' . $sql;
        }
        else
        {
            $where = ' user_change_temp_id in
                (
                    select max(user_change_temp_id)
                    from xf_user_change_temp
                    where user_id = ? ' . $sql . '
                    group by action_type, new_value
                )
            ';
        }
        if($onlyExpired)
        {
            $where .= ' and expiry_date is not null and expiry_date > 0 and expiry_date < '. intval(XenForo_Application::$time) . ' ';
        }

        return $this->fetchAllKeyed('
            SELECT xf_user_change_temp.*, user_change_temp_id as warning_action_id,
                IFNULL(expiry_date, 0xFFFFFFFF) as expiry_date_sort
            FROM xf_user_change_temp
            WHERE ' . $where . ' and change_key like \'warning_action_%\'
            ORDER BY expiry_date_sort DESC
        ', 'warning_action_id', array($userId));
    }

    public function prepareWarningActions(array $warningActions)
    {
        $actions = array();
        foreach($warningActions as $warningAction)
        {
            $warning_action = $this->prepareWarningAction($warningAction);
            if (!empty($warning_action))
            {
                $actions[] = $warning_action;
            }
        }
        return $actions;
    }

    public function prepareWarningAction(array $warningAction)
    {
        $warning_type = array
        (
            'new_value' => $warningAction['new_value'],
            'old_value' => $warningAction['old_value'],
        );

        switch ($warningAction['action_type'])
        {
            case 'groups':
                $warning_type['field'] = 'secondary_group_ids';
                break;
            case 'field':
                if ($warningAction['action_modifier'] == 'is_discouraged')
                {
                    $warning_type['field'] = $warningAction['action_modifier'];
                    break;
                }
                return null;
            default:
                return null;
        }

        $warning_type = $this->_getHelper()->prepareField($warning_type);

        switch ($warningAction['action_type'])
        {
            case 'groups':
                static $added_to_user_groups_phrase = null;
                if (empty($added_to_user_groups_phrase))
                {
                    $added_to_user_groups_phrase = new XenForo_Phrase('sv_warning_action_added_to_user_groups');
                }
                $warningAction['name'] = $added_to_user_groups_phrase;
                $warningAction['result'] = $warning_type['new_value'];
                break;
            case 'field':
                // This means "discouraged", but it could really be any field on the account forcebly changed.
                static $discouraged_phrase = null;
                if (empty($discouraged_phrase))
                {
                    $discouraged_phrase = new XenForo_Phrase('discouraged');
                }
                $warningAction['discouraged'] = true;
                $warningAction['name'] = $discouraged_phrase;
                $warningAction['result'] = $warning_type['new_value'];
                break;
            default:
                // unknown
                return null;
        }

        // round up to the nearest hour
        $expiry_date = $warningAction['expiry_date'];
        if (!empty($expiry_date))
        {
            $prev_hour = $expiry_date - ($expiry_date % 3600);
            $expiry_date = $prev_hour + 3600;
        }
        $warningAction['expiry_date_rounded'] = $expiry_date;

        return $warningAction;
    }

    public function canViewDiscouragedWarningActions(array $user, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        $show_discouraged_warning_actions = XenForo_Application::getOptions()->sv_show_discouraged_warning_actions;
        switch($show_discouraged_warning_actions)
        {
            case 0: // Admin/Mod/User
                return $viewingUser['is_admin'] || $viewingUser['is_moderator'] || ($user['user_id'] == $viewingUser['user_id']);
            case 1: // Admin/Mod
                return $viewingUser['is_admin'] || $viewingUser['is_moderator'];
            case 2: // Admin
                return $viewingUser['is_admin'];
            case 3:
            default: // None
                return false;
        }
    }

    public function canViewNonSummaryUserWarningActions(array $user, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'sv_showAllWarningActions');
    }

    public function canViewUserWarningActions(array $user, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        if ($user['user_id'] == $viewingUser['user_id'])
        {
            return XenForo_Application::getOptions()->sv_view_own_warnings;
        }

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'sv_viewWarningActions');
    }

    public function canEditUserWarningActions(array $user, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'sv_editWarningActions');
    }

    protected function _getHelper()
    {
        return $this->getModelFromCache('XenForo_Helper_UserChangeLog');
    }

    protected function _getWarningModel()
    {
        return $this->getModelFromCache('XenForo_Model_Warning');
    }
}