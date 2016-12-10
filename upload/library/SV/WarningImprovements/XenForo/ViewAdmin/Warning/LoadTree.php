<?php

class SV_WarningImprovements_XenForo_ViewAdmin_Warning_LoadTree extends XFCP_SV_WarningImprovements_XenForo_ViewAdmin_Warning_LoadTree
{
    public function renderJson()
    {
        return XenForo_ViewRenderer_Json::jsonEncodeForOutput(array(
            'tree' => $this->_params['tree']
        ));
    }
}
