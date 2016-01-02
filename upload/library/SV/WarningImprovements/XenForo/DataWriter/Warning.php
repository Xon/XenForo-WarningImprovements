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
        // capture warning & report objects for later when XenForo_DataWriter_User triggers
        SV_WarningImprovements_Globals::$warningObj = $this->getMergedData();
        SV_WarningImprovements_Globals::$reportObj = $this->_getReportModel()->getReportByContent(SV_WarningImprovements_Globals::$warningObj['content_type'], SV_WarningImprovements_Globals::$warningObj['content_id']);

        parent::_postSave();
    }

    protected function _postSaveAfterTransaction()
    {
        parent::_postSaveAfterTransaction();

        if (!$this->isInsert())
        {
            return;
        }

        $options = XenForo_Application::getOptions();

        if (SV_WarningImprovements_Globals::$SendWarningAlert)
        {
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

        if ($options->sv_post_warning_summary)
        {
            $dateStr = date($options->sv_warning_date_format);
            $this->postReply(SV_WarningImprovements_Globals::$warningObj, SV_WarningImprovements_Globals::$reportObj, $options->sv_post_warning_summary, $dateStr);
        }
    }

    protected function postReply(array $warning, array $report = null, $threadId, $dateStr)
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
        $warned_user = $this->_getUserModel()->getUserById($warning['user_id']);
        if (empty($warned_user))
        {
            return;
        }
        $warning['username'] = $warned_user['username'];
        $warning['report'] = empty($report) ? 'N/A' : XenForo_Link::buildPublicLink('full:reports', $report);
        $warning['date'] = $dateStr;
        $visitor = XenForo_Visitor::getInstance()->toArray();

        $message = new XenForo_Phrase('Warning_Summary_Message', $warning, false);
        $message = XenForo_Helper_String::autoLinkBbCode($message->render());

        $writer = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
        $writer->set('user_id', $visitor['user_id']);
        $writer->set('username', $visitor['username']);
        $writer->set('message', $message);
        $writer->set('message_state', $this->_getPostModel()->getPostInsertMessageState($thread, $forum));
        $writer->set('thread_id', $threadId);
        $writer->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
        $writer->setOption(XenForo_DataWriter_DiscussionMessage_Post::OPTION_MAX_TAGGED_USERS, XenForo_Permission::hasPermission($visitor['permissions'], 'general', 'maxTaggedUsers'));
        $writer->save();
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->getModelFromCache('XenForo_Model_Alert')->deleteAlerts('warning', $this->get('warning_id'));
    }

    protected function _getReportModel()
    {
        return $this->getModelFromCache('XenForo_Model_Report');
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
