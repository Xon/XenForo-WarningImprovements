<?php
class SV_WarningImprovements_XenForo_Model_Warning extends XFCP_SV_WarningImprovements_XenForo_Model_Warning
{
    public function getWarningByIds($warningIds)
    {
        return $this->fetchAllKeyed('
            SELECT warning.*, user.*, warn_user.username AS warn_username
            FROM xf_warning AS warning
            LEFT JOIN xf_user AS user ON (user.user_id = warning.user_id)
            LEFT JOIN xf_user AS warn_user ON (warn_user.user_id = warning.warning_user_id)
            WHERE warning.warning_id IN (' . $this->_getDb()->quote($warningIds) . ')
        ', 'warning_id');
    }

    public function getWarningDefaultById($id)
    {
        return $this->_getDb()->fetchRow('
            SELECT *
            FROM xf_sv_warning_default
            WHERE warning_default_id = ?
        ', $id);
    }

    public function getLastWarningDefault()
    {
        return $this->_getDb()->fetchOne('
            SELECT max(threshold_points)
            FROM xf_sv_warning_default
        ');
    }

    public function getWarningDefaultExtentions()
    {
        return $this->fetchAllKeyed('
            SELECT *
            FROM xf_sv_warning_default
            order by threshold_points
        ','warning_default_id');
    }

    public function getWarningDefaultExtention($warningCount, $warningTotals)
    {
        return $this->_getDb()->fetchRow('
            SELECT warning_default.*
            FROM xf_sv_warning_default AS warning_default
            WHERE ? >= warning_default.threshold_points AND
                  warning_default.active = 1
            order by threshold_points desc
            limit 1
        ', $warningTotals);
    }

    public function _getWarningTotals($userId)
    {
        return $this->_getDb()->fetchRow('
            SELECT count(points) AS `count`, sum(points) AS `total`
            FROM xf_warning
            WHERE user_id = ?
        ', $userId);
    }

    protected $_warningTotalsCache = array();

    public function prepareWarningDefinition(array $warning, $includeConversationInfo = false)
    {
        $warning = parent::prepareWarningDefinition($warning, $includeConversationInfo);

        if ($warning['expiry_type'] != 'never' &&
            SV_WarningImprovements_Globals::$scaleWarningExpiry &&
            SV_WarningImprovements_Globals::$warning_user_id)
        {
            $warning_user_id = SV_WarningImprovements_Globals::$warning_user_id;
            if (empty($this->_warningTotalsCache[$warning_user_id]))
            {
                $this->_warningTotalsCache[$warning_user_id] = $this->_getWarningTotals($warning_user_id);
            }
            $totals = $this->_warningTotalsCache[$warning_user_id];

            $row = $this->getWarningDefaultExtention($totals['count'], $totals['total']);

            if (!empty($row['expiry_extension']))
            {
                if ($row['expiry_type'] == 'never')
                {
                    $warning['expiry_type'] = $row['expiry_type'];
                    $warning['expiry_default'] = $row['expiry_extension'];
                }
                else if ($warning['expiry_type'] == $row['expiry_type'])
                {
                    $warning['expiry_default'] = $warning['expiry_default'] + $row['expiry_extension'];
                }
                else if ($warning['expiry_type'] == 'months' && $row['expiry_type'] == 'years')
                {
                    $warning['expiry_default'] = $warning['expiry_default'] + $row['expiry_extension'] * 12;
                }
                else if ($warning['expiry_type'] == 'years' && $row['expiry_type'] == 'months')
                {
                    $warning['expiry_default'] = $warning['expiry_default'] * 12 + $row['expiry_extension'];
                    $warning['expiry_type'] = 'months';
                }
                else
                {
                    $expiry_duration = $this->convertToDays($warning['expiry_type'], $warning['expiry_default']) +
                                                             $this->convertToDays($row['expiry_type'], $row['expiry_extension']);

                    $expiry_parts = $this->convertDaysToLargestType($expiry_duration);

                    $warning['expiry_type'] = $expiry_parts[0];
                    $warning['expiry_default'] = $expiry_parts[1];
                }
            }
        }
        return $warning;
    }

    protected function convertToDays($expiry_type, $expiry_duration)
    {
        switch($expiry_type)
        {
            case 'days':
                return $expiry_duration;
            case 'weeks':
                return $expiry_duration * 7;
            case 'months':
                return $expiry_duration * 30;
            case 'years':
                return $expiry_duration * 365;
        }
        XenForo_Error::logException(new Exception("Unknown expiry type: " . $expiry_type), false);
        return $expiry_duration;
    }

    protected function convertDaysToLargestType($expiry_duration)
    {
        if (($expiry_duration % 365) == 0)
            return array('years', $expiry_duration / 365);
        else if (($expiry_duration % 30) == 0)
            return array('months', $expiry_duration / 30);
        else if (($expiry_duration % 7) == 0)
            return array('weeks', $expiry_duration / 7);
        else
            return array('days', $expiry_duration);
    }
}
