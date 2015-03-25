<?php

// This class is used to encapsulate global state between layers without using $GLOBAL[] or
// relying on the consumer being loaded correctly by the dynamic class autoloader
class SV_WarningImprovements_Globals
{
    public static $warning_user_id = null;
    public static $SendWarningAlert = false;

    private function__construct() {}
}