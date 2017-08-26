<?php

class SV_WarningImprovements_Listener
{
    public static function load_class($class, array &$extend)
    {
        $extend[] = 'SV_WarningImprovements_' . $class;
    }

    public static function load_class_patch($class, array &$extend)
    {
        $extend[] = 'SV_WarningImprovements_' . $class . '_Patch';
    }

    protected static $forcedReloadRecursionGuard = false;
    public static function visitor_setup(XenForo_Visitor &$visitor)
    {
        $userId = $visitor->user_id;
        if (empty($userId))
        {
            return;
        }
        if (self::$forcedReloadRecursionGuard)
        {
            return;
        }

        $pendingWarningExpiry = $visitor->sv_pending_warning_expiry;
        if ($pendingWarningExpiry !== false &&
            $pendingWarningExpiry !== null &&
            $pendingWarningExpiry <= XenForo_Application::$time)
        {

            $warningModel = XenForo_Model::create('XenForo_Model_Warning');
            if (is_callable(array($warningModel, 'processExpiredWarningsForUser')) &&
                $warningModel->processExpiredWarningsForUser($userId, $visitor->is_banned, true))
            {
                // reinitialize the visitor with the same options
                self::$forcedReloadRecursionGuard = true;
                XenForo_Visitor::setup($userId, XenForo_Visitor::getVisitorSetupOptions());
                // abort calling other visitor_setup, as we are going to trigger it again.
                return false;
            }
        }
    }
}
