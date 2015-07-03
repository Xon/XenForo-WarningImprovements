<?php

class SV_WarningImprovements_Route_Prefix_WarningAction implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithIntegerParam($routePath, $request, 'warning_action_id');
        return $router->getRouteMatch('SV_WarningImprovements_ControllerPublic_WarningAction', $action, 'members');
    }

    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'warning_action_id');
    }
}