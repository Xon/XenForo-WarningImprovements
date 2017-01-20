<?php

class SV_WarningImprovements_Listener
{
    const AddonNameSpace = 'SV_WarningImprovements_';

    public static function load_class($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace.$class;
    }

    public static function load_class_patch($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace.$class.'_Patch';
    }
}
