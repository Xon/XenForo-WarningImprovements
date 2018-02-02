<?php

class SV_WarningImprovements_XenForo_ControllerPublic_Member extends XFCP_SV_WarningImprovements_XenForo_ControllerPublic_Member
{
    public function actionMember()
    {
        SV_WarningImprovements_Globals::$warning_user_id = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        $response = parent::actionMember();

        if ($response instanceof XenForo_ControllerResponse_View)
        {
            $warningActionModel = $this->_getWarningActionModel();
            $user = $response->params['user'];
            if ($warningActionModel->canViewUserWarningActions($user))
            {
                $showAll = $warningActionModel->canViewNonSummaryUserWarningActions($user);
                $showDiscouraged = $warningActionModel->canViewDiscouragedWarningActions($user);
                $response->params['warningActionsCount'] = $warningActionModel->countWarningActionsByUser($user['user_id'], $showAll, $showDiscouraged);
                $response->params['canViewWarningActions'] = true;
            }
        }

        return $response;
    }

    public function actionWarningActions()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
        /** @var XenForo_ControllerHelper_UserProfile $userProfileHelper */
        $userProfileHelper = $this->getHelper('UserProfile');
        $user = $userProfileHelper->assertUserProfileValidAndViewable($userId);


        $warningActionModel = $this->_getWarningActionModel();
        if (!$warningActionModel->canViewUserWarningActions($user))
        {
            return $this->responseNoPermission();
        }

        $showAll = $warningActionModel->canViewNonSummaryUserWarningActions($user);
        $showDiscouraged = $warningActionModel->canViewDiscouragedWarningActions($user);

        $warningActions = $warningActionModel->getWarningActionsByUser($user['user_id'], $showAll, $showDiscouraged);
        if (!$warningActions)
        {
            return $this->responseMessage(new XenForo_Phrase('sv_this_user_has_no_warning_actions'));
        }

        $warningActions = $warningActionModel->prepareWarningActions($warningActions);

        $viewParams = array
        (
            'user' => $user,
            'warningActions' => $warningActions
        );
        return $this->responseView('SV_WarningImprovements_ViewPublic_Member_WarningActions', 'sv_member_warning_actions', $viewParams);

    }

    public function actionWarn()
    {
        SV_WarningImprovements_Globals::$warning_user_id = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
        SV_WarningImprovements_Globals::$SendWarningAlert = $this->_input->filterSingle('send_warning_alert', XenForo_Input::BOOLEAN);
        SV_WarningImprovements_Globals::$warningInput = $this->_input->filter([
            'title' => XenForo_Input::STRING,
        ]);
        SV_WarningImprovements_Globals::$scaleWarningExpiry = true;
        SV_WarningImprovements_Globals::$NotifyOnWarningAction = true;

        $response = parent::actionWarn();

        if (!$response instanceof XenForo_ControllerResponse_View)
        {
            if ($response instanceof XenForo_ControllerResponse_Redirect)
            {
                if ($response->redirectMessage === null)
                {
                    $response->redirectMessage = new XenForo_Phrase('sv_issued_warning');
                }
                return $response;
            }

            if ($response instanceof XenForo_ControllerResponse_Reroute &&
                $response->controllerName === 'XenForo_ControllerPublic_Error' &&
                $response->action === 'noPermission')
            {
                $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
                /** @var XenForo_ControllerHelper_UserProfile $userHelper */
                $userHelper = $this->getHelper('UserProfile');
                $user = $userHelper->getUserOrError($userId);

                // this happens if the warning already exists
                $contentInput = $this->_input->filter(
                    [
                        'content_type' => XenForo_Input::STRING,
                        'content_id'   => XenForo_Input::UINT
                    ]
                );

                if (!$contentInput['content_type'])
                {
                    $contentInput['content_type'] = 'user';
                    $contentInput['content_id'] = $userId;
                }

                /* @var $warningModel XenForo_Model_Warning */
                $warningModel = $this->getModelFromCache('XenForo_Model_Warning');

                $warningHandler = $warningModel->getWarningHandler($contentInput['content_type']);
                if (!$warningHandler)
                {
                    return $response;
                }

                /** @var array|bool $content */
                $content = $warningHandler->getContent($contentInput['content_id']);
                if ($content && $warningHandler->canView($content) && !empty($content['warning_id']))
                {
                    $url = $warningHandler->getContentUrl($content);
                    if ($url)
                    {
                        return $this->responseRedirect(
                            XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
                            $url,
                            new XenForo_Phrase('sv_content_already_warned')
                        );
                    }
                }
            }

            return $response;
        }

        $viewParams = &$response->params;

        /** @var SV_WarningImprovements_XenForo_Model_Warning $warningModel */
        $warningModel = $this->getModelFromCache('XenForo_Model_Warning');
        $warningItems = $warningModel->getWarningItems(true);

        if (empty($warningItems))
        {
            return $this->responseError(new XenForo_Phrase('sv_no_permission_to_give_warnings'), 403);
        }

        $warningCategories = $warningModel->groupWarningItemsByWarningCategory(
            $warningItems
        );
        $rootWarningCategories = $warningModel
            ->groupWarningItemsByRootWarningCategory($warningItems);

        $viewParams['warningCategories'] = $warningCategories;
        $viewParams['rootWarningCategories'] = $rootWarningCategories;

        return $response;
    }

    public function actionWarnings()
    {
        SV_WarningImprovements_Globals::$warning_user_id = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        return parent::actionWarnings();
    }

    public function actionCard()
    {
        SV_WarningImprovements_Globals::$warning_user_id = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        return parent::actionCard();
    }

    /**
     * @return XenForo_Model|XenForo_Model_UserChangeTemp|SV_WarningImprovements_XenForo_Model_UserChangeTemp
     */
    protected function _getWarningActionModel()
    {
        return $this->getModelFromCache('XenForo_Model_UserChangeTemp');
    }
}
