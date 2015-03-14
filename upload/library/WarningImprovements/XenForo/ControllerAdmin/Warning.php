<?php

class SV_WarningImprovements_XenForo_ControllerAdmin_Warning extends XFCP_SV_WarningImprovements_XenForo_ControllerAdmin_Warning
{
    public function actionEdit()
    {
        $warningDefinitionId = $this->_input->filterSingle('warning_definition_id', XenForo_Input::UINT);
        $warning = $this->_getWarningDefinitionOrError($warningDefinitionId);

        $warning['is_custom'] = true;

        $view = $this->_getWarningAddEditResponse($warning);

        $masterValues = $this->_getWarningModel()->getWarningDefinitionMasterPhraseValues($warning['warning_definition_id']);
        $view->params['masterTitle'] = $masterValues['title'];
        $view->params['masterConversationTitle'] = $masterValues['conversationTitle'];
        $view->params['masterConversationText'] = $masterValues['conversationText'];

        return $view;
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
}