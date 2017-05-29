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
}
