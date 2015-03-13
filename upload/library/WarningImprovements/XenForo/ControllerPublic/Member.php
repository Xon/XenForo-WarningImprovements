<?php


class SV_WarningImprovements_XenForo_ControllerPublic_Member extends XFCP_SV_WarningImprovements_XenForo_ControllerPublic_Member
{
    public function actionWarn()
    {
        $result = parent::actionWarn();

        if ($result instanceOf XenForo_ViewPublic_Member_WarnFill)
        {
            // filler result
            $choice = $this->_input->filterSingle('choice', XenForo_Input::UINT);
            if ($choice == 0)
            {
                $warning = array(
                    'warning_definition_id' => 0,
                    'points_default' => 1000,
                    'expiry_type' => 'months',
                    'expiry_default' => 1,
                    'extra_user_group_ids' => '',
                    'is_editable' => 1,
                    'title' => '',
                    'conversationTitle' => '',
                    'conversationMessage' => ''
                );
                
                $result->setParams(array('warning' => $warning));
            }
        }

        return $result;
    }
}