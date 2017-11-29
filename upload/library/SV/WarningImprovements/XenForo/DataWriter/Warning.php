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

    protected function _preSave()
    {
        if ($this->isInsert())
        {
            $warningDefinitionId = $this->get('warning_definition_id');
            $warningModel = $this->_getWarningModel();

            $warning = $warningModel->getWarningDefinitionById(
                $warningDefinitionId
            );

            $warningCategory = $warningModel->getWarningCategoryById(
                $warning['sv_warning_category_id']
            );

            if (empty($warningCategory) || !$warningModel->canViewWarningCategory($warningCategory))
            {
                $this->error(new XenForo_Phrase('sv_no_permission_to_give_warning'));
                return;
            }

            if ($warningDefinitionId == 0 && !empty($warning))
            {
                $dwInput = array();
                $warning = $warningModel->prepareWarningDefinition($warning);
                $dwInput['extra_user_group_ids'] = $warning['extra_user_group_ids'];
                if (!$warning['is_editable'])
                {
                    $dwInput['points'] = $warning['points_default'];
                    $dwInput['expiry_date'] = (
                        $warning['expiry_type'] === 'never' ? 0
                        : min(
                            pow(2,32) - 1,
                            strtotime('+' . $warning['expiry_default'] . ' ' . $warning['expiry_type'])
                        )
                    );
                }
                $this->bulkSet($dwInput);
            }
        }

        parent::_preSave();
    }

    protected function _postSave()
    {
        if ($this->isInsert() && $this->get('is_expired') == 1)
        {
            $this->deleteGuard = 1;
        }

        // capture warning & report objects for later when XenForo_DataWriter_User triggers
        SV_WarningImprovements_Globals::$warningObj = $this->getMergedData();
        SV_WarningImprovements_Globals::$reportObj = $this->_getReportModel()->getReportByContent(SV_WarningImprovements_Globals::$warningObj['content_type'], SV_WarningImprovements_Globals::$warningObj['content_id']);

        parent::_postSave();

        if ($this->isInsert() && $this->get('is_expired') == 1)
        {
            $this->_warningExpiredOrDeleted();
        }
    }

    protected $deleteGuard = 0;
    protected function _warningExpiredOrDeleted($isDelete = false)
    {
        if ($this->deleteGuard === 2)
        {
            return;
        }
        if ($this->deleteGuard === 1)
        {
            $this->deleteGuard = 2;
        }
        parent::_warningExpiredOrDeleted($isDelete);
    }

    protected function _postSaveAfterTransaction()
    {
        parent::_postSaveAfterTransaction();

        $this->_getWarningModel()->updatePendingExpiryFor($this->get('user_id'), true);

        if (!$this->isInsert())
        {
            return;
        }

        $options = XenForo_Application::getOptions();

        $userModel = $this->_getUserModel();
        $warned_user = $userModel->getUserById($this->get('user_id'), array(
            'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
        ));
        $warned_user['permissions'] = XenForo_Permission::unserializePermissions($warned_user['global_permission_cache']);

        if (SV_WarningImprovements_Globals::$SendWarningAlert)
        {
            $user_id = 0;
            $username = '';
            if ($warned_user)
            {
                if (XenForo_Permission::hasPermission($warned_user['permissions'], 'general', 'viewWarning_issuer') ||
                    XenForo_Permission::hasPermission($warned_user['permissions'], 'general', 'viewWarning'))
                {
                    $warning_user = $userModel->getUserById($this->get('warning_user_id'));
                    if ($warning_user && isset($warning_user['user_id']))
                    {
                        $user_id = $warning_user['user_id'];
                        $username  = $warning_user['username'];
                    }
                }
                else if ($options->sv_warningimprovements_warning_user)
                {
                    $warning_user = $userModel->getUserByName($options->sv_warningimprovements_warning_user);
                    if ($warning_user && isset($warning_user['user_id']))
                    {
                        $user_id = $warning_user['user_id'];
                        $username  = $warning_user['username'];
                    }
                }
            }
            XenForo_Model_Alert::alert(
                $this->get('user_id'),
                $user_id, $username,
                SV_WarningImprovements_AlertHandler_Warning::ContentType,
                $this->get('warning_id'),
                'warning');
        }

        if ($options->sv_post_warning_summary && $warned_user)
        {
            $dateStr = date($options->sv_warning_date_format);
            $this->postReply($warned_user, SV_WarningImprovements_Globals::$warningObj, SV_WarningImprovements_Globals::$reportObj, $options->sv_post_warning_summary, $dateStr);
        }
    }

    protected function postReply($warned_user, array $warning, $report = null, $threadId, $dateStr)
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

        $this->_getWarningModel()->updatePendingExpiryFor($this->get('user_id'), true);
        /** @var XenForo_Model_Alert $alertModel */
        $alertModel = $this->getModelFromCache('XenForo_Model_Alert');
        $alertModel->deleteAlerts('warning', $this->get('warning_id'));
    }

    /**
     * @return XenForo_Model|XenForo_Model_Warning|SV_WarningImprovements_XenForo_Model_Warning
     */
    protected function _getWarningModel()
    {
        return $this->getModelFromCache('XenForo_Model_Warning');
    }

    /**
     * @return XenForo_Model|XenForo_Model_Report
     */
    protected function _getReportModel()
    {
        return $this->getModelFromCache('XenForo_Model_Report');
    }

    /**
     * @return XenForo_Model|XenForo_Model_Forum
     */
    protected function _getForumModel()
    {
        return $this->getModelFromCache('XenForo_Model_Forum');
    }

    /**
     * @return XenForo_Model|XenForo_Model_Thread
     */
    protected function _getThreadModel()
    {
        return $this->getModelFromCache('XenForo_Model_Thread');
    }

    /**
     * @return XenForo_Model|XenForo_Model_Post
     */
    protected function _getPostModel()
    {
        return $this->getModelFromCache('XenForo_Model_Post');
    }
}
