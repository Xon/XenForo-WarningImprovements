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
            return true;
        }
        if (self::$forcedReloadRecursionGuard)
        {
            return true;
        }

        // check order based on most likely criteria
        $pendingWarningExpiry = $visitor->sv_pending_warning_expiry;
        if ($pendingWarningExpiry !== null &&
            $pendingWarningExpiry <= XenForo_Application::$time &&
            $pendingWarningExpiry !== false // the add-on isn't fully installed yet
            )
        {
            /** @var SV_WarningImprovements_XenForo_Model_Warning $warningModel */
            $warningModel = XenForo_Model::create('XenForo_Model_Warning');
            if (is_callable(array($warningModel, 'processExpiredWarningsForUser')))
            {
                $hadExpiredWarnings = $warningModel->processExpiredWarningsForUser($userId, $visitor->is_banned);
                $warningModel->updatePendingExpiryFor($userId, $visitor->is_banned);
                if ($hadExpiredWarnings)
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
}
