<?php

class SV_WarningImprovements_Install
{
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
