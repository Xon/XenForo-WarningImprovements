<?php

class SV_WarningImprovements_XenForo_ControllerAdmin_Warning extends XFCP_SV_WarningImprovements_XenForo_ControllerAdmin_Warning
{
    public function actionIndex()
    {
        $view = parent::actionIndex();
        $view->params['warningEscalatingDefaults'] = $this->_getWarningModel()->getWarningDefaultExtentions();
        return $view;
    }

    var $_set_custom_warning = false;

    public function actionEdit()
    {
        $warningDefinitionId = $this->_input->filterSingle('warning_definition_id', XenForo_Input::UINT);
        $this->_set_custom_warning = empty($warningDefinitionId);
        $view = parent::actionEdit();
        if ($this->_set_custom_warning)
        {
            $masterValues = $this->_getWarningModel()->getWarningDefinitionMasterPhraseValues($warningDefinitionId);
            $view->params['masterTitle'] = $masterValues['title'];
            $view->params['masterConversationTitle'] = $masterValues['conversationTitle'];
            $view->params['masterConversationText'] = $masterValues['conversationText'];
            $this->_set_custom_warning = false;
        }
        return $view;
    }

    protected function _getWarningAddEditResponse(array $warning)
    {
        $warning['is_custom'] = $this->_set_custom_warning;
        return parent::_getWarningAddEditResponse($warning);
    }

    public function actionSave()
    {
        $warningDefinitionId = $this->_input->filterSingle('warning_definition_id', XenForo_Input::UINT);
        $is_custom = $this->_input->filterSingle('is_custom', XenForo_Input::UINT);
        if ($warningDefinitionId == 0 && $is_custom)
        {
            $dwInput = $this->_input->filter(array(
                'points_default' => XenForo_Input::UINT,
                'expiry_type' => XenForo_Input::STRING,
                'expiry_default' => XenForo_Input::UINT,
                'extra_user_group_ids' => array(XenForo_Input::UINT, 'array' => true),
                'is_editable' => XenForo_Input::UINT
            ));
            $phrases = $this->_input->filter(array(
                'title' => XenForo_Input::STRING,
                'conversationTitle' => XenForo_Input::STRING,
                'conversationText' => XenForo_Input::STRING
            ));

            if ($this->_input->filterSingle('expiry_type_base', XenForo_Input::STRING) == 'never')
            {
                $dwInput['expiry_type'] = 'never';
            }

            $dw = XenForo_DataWriter::create('XenForo_DataWriter_WarningDefinition');
            $dw->setOption(SV_WarningImprovements_XenForo_DataWriter_WarningDefinition::IS_CUSTOM, 1);
            $dw->setExistingData($warningDefinitionId);
            $dw->bulkSet($dwInput);
            $dw->setExtraData(XenForo_DataWriter_WarningDefinition::DATA_TITLE, $phrases['title']);
            $dw->setExtraData(XenForo_DataWriter_WarningDefinition::DATA_CONVERSATION_TITLE, $phrases['conversationTitle']);
            $dw->setExtraData(XenForo_DataWriter_WarningDefinition::DATA_CONVERSATION_TEXT, $phrases['conversationText']);
            $dw->save();

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('warnings') . '#_warning-' . $dw->get('warning_definition_id')
            );
        }
        return parent::actionSave();
    }

    protected function _getDefaultAddEditResponse(array $warningDefault)
    {
        $viewParams = array(
            'default' => $warningDefault,
        );
        return $this->responseView('XenForo_ViewAdmin_Warning_DefaultEdit', 'sv_warningimprovements_warning_default_edit', $viewParams);
    }

    public function actionDefaultAdd()
    {
        return $this->_getDefaultAddEditResponse(array(
            'threshold_points' => $this->_getWarningModel()->getLastWarningDefault() + 100,
            'expiry_extension' => 1,
            'expiry_type' => 'days',
            'active' => 1,
        ));
    }

    public function actionDefaultEdit()
    {
        $warningDefaultId = $this->_input->filterSingle('warning_default_id', XenForo_Input::UINT);
        $action = $this->_getWarningDefaultOrError($warningDefaultId);

        return $this->_getDefaultAddEditResponse($action);
    }

    public function actionDefaultSave()
    {
        $warningDefaultId = $this->_input->filterSingle('warning_default_id', XenForo_Input::UINT);

        $dwInput = $this->_input->filter(array(
            'threshold_points' => XenForo_Input::UINT,
            'expiry_extension' => XenForo_Input::UINT,
            'expiry_type' => XenForo_Input::STRING,
            'active' => XenForo_Input::BOOLEAN,
        ));

        $dw = XenForo_DataWriter::create('SV_WarningImprovements_DataWriter_WarningDefault');
        if ($warningDefaultId)
        {
            $dw->setExistingData($warningDefaultId);
        }
        $dw->bulkSet($dwInput);
        $dw->save();

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('warnings') . '#_warning_default-' . $dw->get('warning_default_id')
        );
    }

    public function actionDefaultDelete()
    {
        if ($this->isConfirmedPost())
        {
            return $this->_deleteData(
                'SV_WarningImprovements_DataWriter_WarningDefault', 'warning_default_id',
                XenForo_Link::buildAdminLink('warnings')
            );
        }
        else
        {
            $warningDefaultId = $this->_input->filterSingle('warning_default_id', XenForo_Input::UINT);
            $default = $this->_getWarningDefaultOrError($warningDefaultId);

            $viewParams = array(
                'default' => $default
            );

            return $this->responseView('XenForo_ViewAdmin_Warning_DefaultDelete', 'sv_warningimprovements_warning_default_delete', $viewParams);
        }
    }

    protected function _getWarningDefaultOrError($id)
    {
        $result = $this->getRecordOrError(
            $id, $this->_getWarningModel(), 'getWarningDefaultById',
            'sv_requested_warning_default_not_found'
        );

        return $result;
    }

    public function actionActionSave()
    {
		SV_WarningImprovements_Globals::$warningActionInput = $this->_input->filter(array(
			'sv_post_node_id' => XenForo_Input::UINT,
			'sv_post_thread_id' => XenForo_Input::UINT,
            'sv_post_as_user_id' => XenForo_Input::UINT,
		));
        return parent::actionActionSave();
    }
}