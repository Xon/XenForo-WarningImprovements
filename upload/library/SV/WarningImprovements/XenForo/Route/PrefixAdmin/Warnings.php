<?php

class SV_WarningImprovements_XenForo_Route_PrefixAdmin_Warnings extends XFCP_SV_WarningImprovements_XenForo_Route_PrefixAdmin_Warnings
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		if (preg_match('#^default/(.*)$#i', $routePath, $match))
		{
			$action = 'default' . $router->resolveActionWithIntegerParam($match[1], $request, 'warning_default_id');
            return $router->getRouteMatch('XenForo_ControllerAdmin_Warning', $action, 'userWarnings');
		}
        return parent::match($routePath, $request, $router);
    }

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		if (preg_match('#^default/(.*)$#i', $action, $match))
		{
			return XenForo_Link::buildBasicLinkWithIntegerParam(
				"$outputPrefix/default", $match[1], $extension, $data, 'warning_default_id'
			);
		}
        return parent::buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, $extraParams);
    }
}