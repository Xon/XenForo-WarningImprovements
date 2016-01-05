<?php

class SV_WarningImprovements_XenForo_ControllerPublic_Warning extends XFCP_SV_WarningImprovements_XenForo_ControllerPublic_Warning
{
    public function actionIndex()
    {
        $warningId = $this->_input->filterSingle('warning_id', XenForo_Input::UINT);
        $warning = $this->_getWarningOrError($warningId);

        $visitor = XenForo_Visitor::getInstance();
        if ($warning['user_id'] == $visitor['user_id'])
        {
            SV_WarningImprovements_Globals::$warning_user_id = $warning['user_id'];
        }

        $response = parent::actionIndex();

        SV_WarningImprovements_Globals::$warning_user_id = null;

        return $response;
    }
}
