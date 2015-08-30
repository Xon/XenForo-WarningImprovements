<?php

class SV_WarningImprovements_Install
{
    public static function removeOldAddons($addonsToUninstall)
    {
        $options = XenForo_Application::getOptions();
        $addonModel = XenForo_Model::create("XenForo_Model_AddOn");
        foreach($addonsToUninstall as $addonToUninstall => $keys)
        {
            $addon = $addonModel->getAddOnById($addonToUninstall);
            if (!empty($addon))
            {
                if(!empty($keys))
                foreach($keys as $old => $new)
                {
                    $val = $options->$old;
                    $options->set($new, $val);
                    $dw = XenForo_DataWriter::create('XenForo_DataWriter_Option', XenForo_DataWriter::ERROR_SILENT);
                    if ($dw->setExistingData($new))
                    {
                        $dw->set('option_value', $val);
                        return $dw->save();
                    }
                }

                $dw = XenForo_DataWriter::create('XenForo_DataWriter_AddOn');
                $dw->setExistingData($addonToUninstall);
                $dw->delete();
            }
        }
    }

    public static function modifyColumn($table, $column, $oldDefinition, $definition)
    {
        $db = XenForo_Application::get('db');
        $hasColumn = false;
        if (empty($oldDefinition))
        {
            $hasColumn = $db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $column);
        }
        else
        {
            $hasColumn = $db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ? and Type = ?', array($column,$oldDefinition));
        }

        if($hasColumn)
        {
            $db->query('ALTER TABLE `'.$table.'` MODIFY COLUMN `'.$column.'` '.$definition);
        }
    }

    public static function dropColumn($table, $column)
    {
        $db = XenForo_Application::get('db');
        if ($db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $column))
        {
            $db->query('ALTER TABLE `'.$table.'` drop COLUMN `'.$column.'` ');
        }
    }

    public static function addColumn($table, $column, $definition)
    {
        $db = XenForo_Application::get('db');
        if (!$db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $column))
        {
            $db->query('ALTER TABLE `'.$table.'` ADD COLUMN `'.$column.'` '.$definition);
        }
    }

    public static function addIndex($table, $index, array $columns)
    {
        $db = XenForo_Application::get('db');
        if (!$db->fetchRow('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', $index))
        {
            $cols = '(`'. implode('`,`', $columns). '`)';
            $db->query('ALTER TABLE `'.$table.'` add index `'.$index.'` '. $cols);
        }
    }

    public static function dropIndex($table, $index)
    {
        $db = XenForo_Application::get('db');
        if ($db->fetchRow('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', $index))
        {
            $db->query('ALTER TABLE `'.$table.'` drop index `'.$index.'` ');
        }
    }

    public static function renameColumn($table, $old_name, $new_name, $definition)
    {
        $db = XenForo_Application::get('db');
        if ($db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $old_name) &&
            !$db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $new_name))
        {
            $db->query('ALTER TABLE `'.$table.'` CHANGE COLUMN `'.$old_name.'` `'.$new_name.'` '. $definition);
        }
    }
}
