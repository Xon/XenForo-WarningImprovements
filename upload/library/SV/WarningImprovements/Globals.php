<?php

// This class is used to encapsulate global state between layers without using $GLOBAL[] or
// relying on the consumer being loaded correctly by the dynamic class autoloader
class SV_WarningImprovements_Globals
{
    public static $warning_user_id = null;
    public static $NotifyOnWarningAction = false;
    public static $SendWarningAlert = false;
    public static $warningObj = null;
    public static $reportObj = null;
    public static $scaleWarningExpiry = false;

    public static $warningInput = null;
    public static $warningDefinitionInput = null;
    public static $warningActionInput = null;

    private function __construct() {}
}
