<?php

class SV_WarningImprovements_Criteria
{
    public static function criteriaUser($rule, array $data, array $user, &$returnValue)
    {
        switch ($rule)
        {
            case 'warning_points_l':
                if ($user['warning_points'] >= $data['points'])
                {
                    $returnValue = true;
                }
                break;

            case 'warning_points_m':
                if ($user['warning_points'] <= $data['points'])
                {
                    $returnValue = true;
                }
                break;
        }
    }
}