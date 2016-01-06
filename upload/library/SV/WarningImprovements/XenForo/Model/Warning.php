<?php
class SV_WarningImprovements_XenForo_Model_Warning extends XFCP_SV_WarningImprovements_XenForo_Model_Warning
{
    public function getWarningByIds($warningIds)
    {
        return $this->fetchAllKeyed('
            SELECT warning.*, user.*, warn_user.username AS warn_username
            FROM xf_warning AS warning
            LEFT JOIN xf_user AS user ON (user.user_id = warning.user_id)
            LEFT JOIN xf_user AS warn_user ON (warn_user.user_id = warning.warning_user_id)
            WHERE warning.warning_id IN (' . $this->_getDb()->quote($warningIds) . ')
        ', 'warning_id');
    }

    public function getWarningDefaultById($id)
    {
        return $this->_getDb()->fetchRow('
            SELECT *
            FROM xf_sv_warning_default
            WHERE warning_default_id = ?
        ', $id);
    }

    public function getLastWarningDefault()
    {
        return $this->_getDb()->fetchOne('
            SELECT max(threshold_points)
            FROM xf_sv_warning_default
        ');
    }

    public function getWarningDefaultExtentions()
    {
        return $this->fetchAllKeyed('
            SELECT *
            FROM xf_sv_warning_default
            order by threshold_points
        ','warning_default_id');
    }

    public function getWarningDefaultExtention($warningCount, $warningTotals)
    {
        return $this->_getDb()->fetchRow('
            SELECT warning_default.*
            FROM xf_sv_warning_default AS warning_default
            WHERE ? >= warning_default.threshold_points AND
                  warning_default.active = 1
            order by threshold_points desc
            limit 1
        ', $warningTotals);
    }

    public function _getWarningTotals($userId)
    {
        return $this->_getDb()->fetchRow('
            SELECT count(points) AS `count`, sum(points) AS `total`
            FROM xf_warning
            WHERE user_id = ?
        ', $userId);
    }

    protected $_warningTotalsCache = array();

    public function prepareWarningDefinition(array $warning, $includeConversationInfo = false)
    {
        $warning = parent::prepareWarningDefinition($warning, $includeConversationInfo);

        if ($warning['expiry_type'] != 'never' &&
            SV_WarningImprovements_Globals::$scaleWarningExpiry &&
            SV_WarningImprovements_Globals::$warning_user_id)
        {
            $warning_user_id = SV_WarningImprovements_Globals::$warning_user_id;
            if (empty($this->_warningTotalsCache[$warning_user_id]))
            {
                $this->_warningTotalsCache[$warning_user_id] = $this->_getWarningTotals($warning_user_id);
            }
            $totals = $this->_warningTotalsCache[$warning_user_id];

            $row = $this->getWarningDefaultExtention($totals['count'], $totals['total']);

            if (!empty($row['expiry_extension']))
            {
                if ($row['expiry_type'] == 'never')
                {
                    $warning['expiry_type'] = $row['expiry_type'];
                    $warning['expiry_default'] = $row['expiry_extension'];
                }
                else if ($warning['expiry_type'] == $row['expiry_type'])
                {
                    $warning['expiry_default'] = $warning['expiry_default'] + $row['expiry_extension'];
                }
                else if ($warning['expiry_type'] == 'months' && $row['expiry_type'] == 'years')
                {
                    $warning['expiry_default'] = $warning['expiry_default'] + $row['expiry_extension'] * 12;
                }
                else if ($warning['expiry_type'] == 'years' && $row['expiry_type'] == 'months')
                {
                    $warning['expiry_default'] = $warning['expiry_default'] * 12 + $row['expiry_extension'];
                    $warning['expiry_type'] = 'months';
                }
                else
                {
                    $expiry_duration = $this->convertToDays($warning['expiry_type'], $warning['expiry_default']) +
                                                             $this->convertToDays($row['expiry_type'], $row['expiry_extension']);

                    $expiry_parts = $this->convertDaysToLargestType($expiry_duration);

                    $warning['expiry_type'] = $expiry_parts[0];
                    $warning['expiry_default'] = $expiry_parts[1];
                }
            }
        }
        return $warning;
    }

    protected function convertToDays($expiry_type, $expiry_duration)
    {
        switch($expiry_type)
        {
            case 'days':
                return $expiry_duration;
            case 'weeks':
                return $expiry_duration * 7;
            case 'months':
                return $expiry_duration * 30;
            case 'years':
                return $expiry_duration * 365;
        }
        XenForo_Error::logException(new Exception("Unknown expiry type: " . $expiry_type), false);
        return $expiry_duration;
    }

    protected function convertDaysToLargestType($expiry_duration)
    {
        if (($expiry_duration % 365) == 0)
            return array('years', $expiry_duration / 365);
        else if (($expiry_duration % 30) == 0)
            return array('months', $expiry_duration / 30);
        else if (($expiry_duration % 7) == 0)
            return array('weeks', $expiry_duration / 7);
        else
            return array('days', $expiry_duration);
    }

    protected $lastWarningAction = null;

    protected function _userWarningPointsIncreased($userId, $newPoints, $oldPoints)
    {
        parent::_userWarningPointsIncreased($userId, $newPoints, $oldPoints);
        // only do the last post action
        if ($this->lastWarningAction)
        {
            $posterUserId = empty($this->lastWarningAction['sv_post_as_user_id'])
                          ? null
                          : $this->lastWarningAction['sv_post_as_user_id'];

            $options = XenForo_Application::getOptions();
            $dateStr = date($options->sv_warning_date_format);
            // post a new thread
            if (!empty($this->lastWarningAction['sv_post_node_id']))
            {
                $this->postThread($this->lastWarningAction, $userId, $this->lastWarningAction['sv_post_node_id'], $posterUserId, SV_WarningImprovements_Globals::$warningObj, SV_WarningImprovements_Globals::$reportObj, $dateStr);
            }
            // post a reply
            else if (!empty($this->lastWarningAction['sv_post_thread_id']))
            {
                $this->postReply($this->lastWarningAction, $userId, $this->lastWarningAction['sv_post_thread_id'], $posterUserId, SV_WarningImprovements_Globals::$warningObj, SV_WarningImprovements_Globals::$reportObj, $dateStr);
            }
        }
    }

    public function triggerWarningAction($userId, array $action)
    {
        $triggerId = parent::triggerWarningAction($userId, $action);
        if (SV_WarningImprovements_Globals::$NotifyOnWarningAction &&
            (empty($this->lastWarningAction) || $action['points'] > $this->lastWarningAction['points']) &&
            (!empty($action['sv_post_node_id']) || !empty($action['sv_post_thread_id'])))
        {
            $this->lastWarningAction = $action;
        }
        return $triggerId;
    }

    protected function postReply(array $action, $userId, $threadId, $posterUserId, $warning, $report, $dateStr)
    {
        $thread = $this->_getThreadModel()->getThreadById($threadId);
        if (empty($thread))
        {
            return;
        }
        $forum = $this->_getForumModel()->getForumById($thread['node_id']);
        if (empty($forum))
        {
            return;
        }
        $user = $this->_getUserModel()->getUserById($userId);
        if (empty($user))
        {
            return;
        }
        if (empty($posterUserId))
        {
            $poster = XenForo_Visitor::getInstance()->toArray();
            $permissions = $poster['permissions'];
        }
        else
        {
            $poster = $this->_getUserModel()->getUserById($posterUserId,array(
                'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
            ));
            if (empty($poster))
            {
                return;
            }
            $permissions = XenForo_Permission::unserializePermissions($poster['global_permission_cache']);
        }
        $input = array(
            'username' => $user['username'],
            'points' => $user['warning_points'],
            'report' => empty($report) ? 'N/A' : XenForo_Link::buildPublicLink('full:reports', $report),
            'date' => $dateStr,
            'warning_points' => empty($warning) ? '0' : $warning['points'],
            'threshold' => $action['points'],
        );

        $message = new XenForo_Phrase('Warning_Thread_Message', $input, false);
        $message = XenForo_Helper_String::autoLinkBbCode($message->render());

        $writer = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
        $writer->set('user_id', $poster['user_id']);
        $writer->set('username', $poster['username']);
        $writer->set('message', $message);
        $writer->set('message_state', $this->_getPostModel()->getPostInsertMessageState($thread, $forum));
        $writer->set('thread_id', $threadId);
        $writer->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
        $writer->setOption(XenForo_DataWriter_DiscussionMessage_Post::OPTION_MAX_TAGGED_USERS, XenForo_Permission::hasPermission($permissions, 'general', 'maxTaggedUsers'));
        if (!empty($posterUserId))
        {
            $writer->setOption(XenForo_DataWriter_DiscussionMessage_Post::OPTION_IS_AUTOMATED, true);
        }
        $writer->save();
    }

    protected function postThread(array $action, $userId, $nodeId, $posterUserId, $warning, $report, $dateStr)
    {
        $forum = $this->_getForumModel()->getForumById($nodeId);
        if (empty($forum))
        {
            return;
        }
        $user = $this->_getUserModel()->getUserById($userId);
        if (empty($user))
        {
            return;
        }
        if (empty($posterUserId))
        {
            $poster = XenForo_Visitor::getInstance()->toArray();
            $permissions = $poster['permissions'];
        }
        else
        {
            $poster = $this->_getUserModel()->getUserById($posterUserId,array(
                'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
            ));
            if (empty($poster))
            {
                return;
            }
            $permissions = XenForo_Permission::unserializePermissions($poster['global_permission_cache']);
        }
        $input = array(
            'username' => $user['username'],
            'points' => $user['warning_points'],
            'report' => empty($report) ? 'N/A' : XenForo_Link::buildPublicLink('full:reports', $report),
            'date' => $dateStr,
            'warning_points' => empty($warning) ? '0' : $warning['points'],
            'threshold' => $action['points'],
        );

        $title = new XenForo_Phrase('Warning_Thread_Title', $input, false);
        $message = new XenForo_Phrase('Warning_Thread_Message', $input, false);
        $message = XenForo_Helper_String::autoLinkBbCode($message->render());

        $threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
        $threadDw->setOption(XenForo_DataWriter_Discussion::OPTION_TRIM_TITLE, true);
        $threadDw->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);
        $threadDw->bulkSet(array(
            'user_id' => $poster['user_id'],
            'username' => $poster['username'],
            'node_id' => $forum['node_id'],
            'discussion_state' => 'visible',
            'prefix_id' => $forum['default_prefix_id'],
            'title' => $title->render(),
        ));

        $postWriter = $threadDw->getFirstMessageDw();
        $postWriter->set('message', $message);
        $postWriter->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
        $postWriter->setOption(XenForo_DataWriter_DiscussionMessage_Post::OPTION_MAX_TAGGED_USERS, XenForo_Permission::hasPermission($permissions, 'general', 'maxTaggedUsers'));
        if (!empty($posterUserId))
        {
            $postWriter->setOption(XenForo_DataWriter_DiscussionMessage_Post::OPTION_IS_AUTOMATED, true);
        }
        $threadDw->save();
    }

    protected $warning_user = null;
    protected $viewer = null;

    public function prepareWarning(array $warning)
    {
        $warning = parent::prepareWarning($warning);

        if ($this->viewer === null)
        {
            $this->viewer = XenForo_Visitor::getInstance()->toArray();
        }
        $viewer = $this->viewer;

        if(!XenForo_Permission::hasPermission($viewer['permissions'], 'general', 'viewWarning'))
        {
            if (!empty($warning['content_title']))
            {
                $warning['content_title'] = XenForo_Helper_String::censorString($warning['content_title']);
            }
            $warning['notes'] = '';
            $warning['expiry_date'] = $warning['expiry_date'] - ($warning['expiry_date'] % 3600) + 3600;
        }

        if (!XenForo_Permission::hasPermission($viewer['permissions'], 'general', 'viewWarning_issuer') && !XenForo_Permission::hasPermission($viewer['permissions'], 'general', 'viewWarning'))
        {
            $anonymisedWarning = false;
            $options = XenForo_Application::getOptions();
            if ($options->sv_warningimprovements_warning_user)
            {
                if ($this->warning_user === null)
                {
                    $this->warning_user = $this->_getUserModel()->getUserByName($options->sv_warningimprovements_warning_user);
                    if (empty($this->warning_user))
                    {
                        $this->warning_user = array();
                    }
                }
                if (isset($this->warning_user['user_id']))
                {
                    $warning['warn_user_id'] = $this->warning_user['user_id'];
                    $warning['warn_username'] = $this->warning_user['username'];
                    $anonymisedWarning = true;
                }
            }
            if (!$anonymisedWarning)
            {
                $warning['warn_user_id'] = 0;
                $warning['warn_username'] = new XenForo_Phrase('WarningStaff');
            }
        }
        return $warning;
    }

    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }

    protected function _getForumModel()
    {
        return $this->getModelFromCache('XenForo_Model_Forum');
    }

    protected function _getThreadModel()
    {
        return $this->getModelFromCache('XenForo_Model_Thread');
    }

    protected function _getPostModel()
    {
        return $this->getModelFromCache('XenForo_Model_Post');
    }
}
