<?php

class SV_WarningImprovements_ControllerPublic_WarningAction extends XenForo_ControllerPublic_Abstract
{
    public function actionIndex()
    {
        $warningActionId = $this->_input->filterSingle('warning_action_id', XenForo_Input::UINT);
        $warningAction = $this->_getWarningActionOrError($warningActionId);

        $user = $this->_getUserModel()->getUserById($warningAction['user_id']);
        if (empty($user))
        {
            return $this->responseError(new XenForo_Phrase('requested_member_not_found'));
        }

        $warningActionModel = $this->_getWarningActionModel();

        if (!$warningActionModel->canViewUserWarningActions($user))
        {
            return $this->responseNoPermission();
        }

        if (!empty($warningAction['discouraged']) && !$warningActionModel->canViewDiscouragedWarningActions($user))
        {
            return $this->responseNoPermission();
        }

        $viewParams = array(
            'warningAction' => $warningAction,
            'user' => $user,
            'canEditUserWarningActions' => $warningActionModel->canEditUserWarningActions($user),
            'redirect' => $this->getDynamicRedirect()
        );
        return $this->responseView('SV_WarningImprovements_ViewPublic_WarningAction_Info', 'sv_warning_actions_info', $viewParams);
    }

    public function actionExpire()
    {
        $warningActionId = $this->_input->filterSingle('warning_action_id', XenForo_Input::UINT);
        $warningAction = $this->_getWarningActionOrError($warningActionId);

        $user = $this->_getUserModel()->getUserById($warningAction['user_id']);
        if (empty($user))
        {
            return $this->responseError(new XenForo_Phrase('requested_member_not_found'));
        }

        $warningActionModel = $this->_getWarningActionModel();

        if (!$warningActionModel->canViewUserWarningActions($user) || !$warningActionModel->canEditUserWarningActions($user))
        {
            return $this->responseNoPermission();
        }

        if (!empty($warningAction['discouraged']) && !$warningActionModel->canViewDiscouragedWarningActions($user))
        {
            return $this->responseNoPermission();
        }

        if (!$this->isConfirmedPost())
        {
            return $this->responseReroute(__CLASS__, 'index');
        }

        $success = false;
        $expire = $this->_input->filterSingle('expire', XenForo_Input::STRING);
        switch($expire)
        {
            case 'now':
                $success = $warningActionModel->expireWarningAction($warningAction);
                break;
            case 'future':
                $expiryLength = $this->_input->filterSingle('expiry_length', XenForo_Input::UINT);
                $expiryUnit = $this->_input->filterSingle('expiry_unit', XenForo_Input::STRING);

                $expiryDate = strtotime("+$expiryLength $expiryUnit");
                $expiryDate = min(pow(2, 32) - 1, $expiryDate);
                if (!$expiryDate || $expiryDate <= XenForo_Application::$time)
                {
                    $success = $warningActionModel->expireWarningAction($warningAction);
                }
                else
                {
                    $success = $warningActionModel->updateWarningActionExpiryDate($warningAction, $expiryDate);
                }
                break;
        }
        if($success)
        {
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, $this->getDynamicRedirect());
        }
        else
        {
            return $this->responseReroute(__CLASS__, 'index');
        }
    }

    protected function _getWarningActionOrError($id)
    {
        return $this->_getWarningActionModel()->prepareWarningAction(
            $this->getRecordOrError($id, $this->_getWarningActionModel(), 'getWarningActionById',
            'sv_requested_warning_action_not_found'));
    }

    /**
     * @return XenForo_Model|XenForo_Model_UserChangeTemp|SV_WarningImprovements_XenForo_Model_UserChangeTemp
     */
    protected function _getWarningActionModel()
    {
        return $this->getModelFromCache('XenForo_Model_UserChangeTemp');
    }

    /**
     * @return XenForo_Model|XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }
}
