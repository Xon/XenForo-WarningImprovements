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
        $user = $this->getHelper('UserProfile')->assertUserProfileValidAndViewable($userId);

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
        SV_WarningImprovements_Globals::$captureWarning = true;
        SV_WarningImprovements_Globals::$scaleWarningExpiry = true;

        return parent::actionWarn();
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

    protected function _getWarningActionModel()
    {
        return $this->getModelFromCache('XenForo_Model_UserChangeTemp');
    }
}