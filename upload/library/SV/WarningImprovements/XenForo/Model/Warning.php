<?php
class SV_WarningImprovements_XenForo_Model_Warning extends XFCP_SV_WarningImprovements_XenForo_Model_Warning
{
    /**
     * Cached warning categories array.
     *
     * @var array
     */
    protected $_warningCategories;

    /**
     * Cached user warning points array.
     *
     * @var array
     */
    protected $_userWarningPoints;

    public function isWarningCategory($warningCategory)
    {
        return (
            !(empty($warningCategory)) and
            is_array($warningCategory) and
            array_key_exists('warning_category_id', $warningCategory) and
            array_key_exists('parent_warning_category_id', $warningCategory)
        );
    }

    public function isWarningDefinition($warningDefinition)
    {
        return (
            !(empty($warningDefinition)) and
            is_array($warningDefinition) and
            array_key_exists('warning_definition_id', $warningDefinition) and
            array_key_exists('sv_warning_category_id', $warningDefinition)
        );
    }

    public function isWarningAction($warningAction)
    {
        return (
            !(empty($warningAction)) &&
            is_array($warningAction) &&
            array_key_exists('warning_action_id', $warningAction) &&
            array_key_exists('sv_warning_category_id', $warningAction)
        );
    }

    public function isWarningItemsArray($warningItems)
    {
        if (is_array($warningItems))
        {
            if (count($warningItems) == 0)
            {
                return true;
            }
            else
            {
                return (
                    $this->isWarningCategory(reset($warningItems)) or
                    $this->isWarningDefinition(reset($warningItems)) or
                    $this->isWarningAction(reset($warningItems))
                );
            }
        }
        else
        {
            return false;
        }
    }

    public function getWarningCategoryById($warningCategoryId)
    {
        return $this->_getDb()->fetchRow(
            'SELECT *
                FROM xf_sv_warning_category
                WHERE warning_category_id = ?',
            $warningCategoryId
        );
    }

    public function getWarningCategoryTitlePhraseName($warningCategoryId)
    {
        return 'sv_warning_category_'.$warningCategoryId.'_title';
    }

    public function getWarningCategories($fromCache = false)
    {
        if (!$fromCache || empty($this->_warningCategories))
        {
            $this->_warningCategories = $this->fetchAllKeyed(
                'SELECT *
                    FROM xf_sv_warning_category
                    ORDER BY parent_warning_category_id, display_order',
                'warning_category_id'
            );
        }

        return $this->_warningCategories;
    }

    public function getWarningCategoriesByParentId(
        $parentWarningCategoryId,
        array $warningCategories = null
    ) {
        if ($warningCategories !== null)
        {
            $children = array();

            foreach ($warningCategories as $warningCategoryId => $warningCategory)
            {
                if ($warningCategory['parent_warning_category_id'] === $parentWarningCategoryId)
                {
                    $children[$warningCategoryId] = $warningCategory;
                }
            }

            return $children;
        }

        return $this->fetchAllKeyed(
            'SELECT *
                FROM xf_sv_warning_category
                WHERE parent_warning_category_id = ?
                ORDER BY display_order',
            'warning_category_id',
            $parentWarningCategoryId
        );
    }

    public function getRootWarningCategoryByWarningItem(
        array $warningItem,
        array $warningCategories = null
    ) {
        if ($warningCategories === null)
        {
            $warningCategories = $this->prepareWarningCategories(
                $this->getWarningCategories(true)
            );
        }

        if ($this->isWarningCategory($warningItem))
        {
            $parentWarningCategoryId = $warningItem['parent_warning_category_id'];

            if ($parentWarningCategoryId === 0)
            {
                return $warningItem;
            }
        }
        elseif ($this->isWarningDefinition($warningItem))
        {
            $parentWarningCategoryId = $warningItem['sv_warning_category_id'];
        }

        $parentWarningCategory = $warningCategories[$parentWarningCategoryId];

        return $this->getRootWarningCategoryByWarningItem($parentWarningCategory);
    }

    public function getParentWarningCategoriesByWarningItem(
        $warningItem,
        $warningCategories = null,
        $parentWarningCategories = array()
    )
    {
        if ($warningCategories === null)
        {
            $warningCategories = $this->prepareWarningCategories(
                $this->getWarningCategories(true)
            );
        }

        $parentWarningCategoryId = 0;

        if ($this->isWarningCategory($warningItem))
        {
            $parentWarningCategoryId = $warningItem['parent_warning_category_id'];
        }
        elseif (
            $this->isWarningDefinition($warningItem) ||
            $this->isWarningAction($warningItem)
        )
        {
            $parentWarningCategoryId = $warningItem['sv_warning_category_id'];
        }

        if ($parentWarningCategoryId === 0)
        {
            return $parentWarningCategories;
        }

        $parentWarningCategory = $warningCategories[$parentWarningCategoryId];

        $parentWarningCategories[$parentWarningCategoryId] = $parentWarningCategory;

        return $this->getParentWarningCategoriesByWarningItem(
            $parentWarningCategory,
            $warningCategories,
            $parentWarningCategories
        );
    }

    public function getWarningCategoryOptions($rootOnly = false)
    {
        if (!$rootOnly)
        {
            $categories = $this->getWarningCategories();
            $categories = $this->calculateWarningItemsDepth($categories);
        }
        else
        {
            $categories = $this->getWarningCategoriesByParentId(0);
        }

        $categories = $this->prepareWarningCategories($categories);

        $options = array();

        foreach ($categories as $category)
        {
            $categoryId = $category['warning_category_id'];

            $options[$categoryId] = array(
                'value' => $categoryId,
                'label' => $category['title'],
                'depth' => (!$rootOnly ? $category['depth'] : 0)
            );
        }

        return $options;
    }

    public function canViewWarningCategory(
        array $warningCategory,
        array $warningCategories = null,
        array $viewingUser = null
    ) {
        if (empty($warningCategory['allowed_user_group_ids']))
        {
            return false;
        }

        $this->standardizeViewingUserReference($viewingUser);

        $allowedUserGroupIds = explode(
            ',',
            $warningCategory['allowed_user_group_ids']
        );
        $secondaryUserGroupIds = explode(
            ',',
            $viewingUser['secondary_group_ids']
        );
        $matchingSecondaryUserGroupIds = array_intersect(
            $allowedUserGroupIds,
            $secondaryUserGroupIds
        );

        if (!in_array($viewingUser['user_group_id'], $allowedUserGroupIds) &&
            empty($matchingSecondaryUserGroupIds)
        ) {
            return false;
        }

        $parentWarningCategoryId = $warningCategory['parent_warning_category_id'];
        if ($parentWarningCategoryId !== 0)
        {
            if ($warningCategories === null)
            {
                $warningCategories = $this->prepareWarningCategories(
                    $this->getWarningCategories(true)
                );
            }

            return $this->canViewWarningCategory(
                $warningCategories[$parentWarningCategoryId],
                $warningCategories,
                $viewingUser
            );
        }

        return true;
    }

    public function prepareWarningCategory(array $warningCategory)
    {
        if (!empty($warningCategory['warning_category_id']))
        {
            $warningCategory['title'] = new XenForo_Phrase(
                $this->getWarningCategoryTitlePhraseName(
                    $warningCategory['warning_category_id']
                )
            );
        }

        return $warningCategory;
    }

    public function prepareWarningCategories(array $warningCategories)
    {
        return array_map(
            array($this, 'prepareWarningCategory'),
            $warningCategories
        );
    }

    public function getWarningDefinitions()
    {
        $warningDefinitions = parent::getWarningDefinitions();

        uasort($warningDefinitions, function ($first, $second)
        {
            $key = 'sv_display_order';

            if ($first[$key] === $second[$key])
            {
                return 0;
            }

            return ($first[$key] < $second[$key]) ? -1 : 1;
        });

        return $warningDefinitions;
    }

    public function getWarningDefinitionsByCategoryId($warningCategoryId)
    {
        return $this->fetchAllKeyed(
            'SELECT *
                FROM xf_warning_definition
                WHERE sv_warning_category_id = ?
                ORDER BY sv_display_order',
            'warning_definition_id',
            $warningCategoryId
        );
    }

    public function canViewWarningDefinition(
        array $warningDefinition,
        array $warningCategories = null,
        array $viewingUser = null
    ) {
        $this->standardizeViewingUserReference($viewingUser);

        if ($warningCategories === null)
        {
            $warningCategories = $this->prepareWarningCategories(
                $this->getWarningCategories(true)
            );
        }

        $warningCategory = $warningCategories[
            $warningDefinition['sv_warning_category_id']
        ];

        if (!$this->canViewWarningCategory(
            $warningCategory,
            $warningCategories,
            $viewingUser
        )) {
            return false;
        }

        return true;
    }

    public function getWarningActions()
    {
        if (SV_WarningImprovements_Globals::$filterActionsByCategory &&
            SV_WarningImprovements_Globals::$warningDefinitionObj !== null
        ) {
            $parentCategories = $this->getParentWarningCategoriesByWarningItem(
                SV_WarningImprovements_Globals::$warningDefinitionObj
            );

            $categoryIds = array_merge(array(0), array_column(
                $parentCategories,
                'warning_category_id'
            ));

            return $this->fetchAllKeyed(
                'SELECT *
                    FROM xf_warning_action
                    WHERE sv_warning_category_id
                        IN ('.$this->_db->quote($categoryIds).')
                    ORDER BY points',
                'warning_action_id'
            );
        }

        return parent::getWarningActions();
    }

    public function getWarningActionsByCategoryId($warningCategoryId)
    {
        return $this->fetchAllKeyed(
            'SELECT *
                FROM xf_warning_action
                WHERE sv_warning_category_id = ?
                ORDER BY points',
            'warning_action_id',
            $warningCategoryId
        );
    }

    public function getWarningItems($filterViewable = false, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        $warningCategories = $this->prepareWarningCategories(
            $this->getWarningCategories()
        );
        $warningDefinitions = $this->prepareWarningDefinitions(
            $this->getWarningDefinitions()
        );

        $warningItems = array_merge($warningCategories, $warningDefinitions);

        if ($filterViewable)
        {
            $warningItems = $this->filterViewableWarningItems(
                $warningItems,
                $warningCategories,
                $viewingUser
            );
        }

        $warningItems = $this->sortWarningItems($warningItems);
        $warningItems = $this->calculateWarningItemsDepth($warningItems);

        return $warningItems;
    }

    public function getWarningItemsByParentId($warningCategoryId)
    {
        $warningCategories = $this->prepareWarningCategories(
            $this->getWarningCategoriesByParentId($warningCategoryId)
        );
        $warningDefinitions = $this->prepareWarningDefinitions(
            $this->getWarningDefinitionsByCategoryId($warningCategoryId)
        );
        $warningActions = $this->getWarningActionsByCategoryId(
            $warningCategoryId
        );

        return array_merge(
            $warningCategories,
            $warningDefinitions,
            $warningActions
        );
    }

    public function sortWarningItems(array $warningItems)
    {
        uasort($warningItems, function ($first, $second)
        {
            $keys = array('parent_warning_category_id', 'sv_warning_category_id');

            foreach ($keys as $key)
            {
                if (!isset($firstOrder) && isset($first[$key]))
                {
                    $firstOrder = $first[$key];
                }

                if (!isset($secondOrder) && isset($second[$key]))
                {
                    $secondOrder = $second[$key];
                }
            }

            if ($firstOrder === $secondOrder)
            {
                return 0;
            }

            return ($firstOrder < $secondOrder) ? -1 : 1;
        });

        uasort($warningItems, function ($first, $second)
        {
            $keys = array('display_order', 'sv_display_order');

            foreach ($keys as $key)
            {
                if (!isset($firstOrder) && isset($first[$key]))
                {
                    $firstOrder = $first[$key];
                }

                if (!isset($secondOrder) && isset($second[$key]))
                {
                    $secondOrder = $second[$key];
                }
            }

            if ($firstOrder === $secondOrder)
            {
                return 0;
            }

            return ($firstOrder < $secondOrder) ? -1 : 1;
        });

        return $warningItems;
    }

    public function calculateWarningItemsDepth(
        array &$warningItems,
        $parentId = 0,
        $depth = 0
    ) {
        $calculatedItems = array();

        foreach ($warningItems as $warningItemId => $warningItem)
        {
            if ($this->isWarningCategory($warningItem))
            {
                $itemParentId = $warningItem['parent_warning_category_id'];
            }
            elseif ($this->isWarningDefinition($warningItem))
            {
                $itemParentId = $warningItem['sv_warning_category_id'];
            }

            if ($itemParentId === $parentId)
            {
                $warningItem['depth'] = $depth;
                $calculatedItems[] = $warningItem;

                if ($this->isWarningCategory($warningItem))
                {
                    $calculatedItems = array_merge(
                        $calculatedItems,
                        $this->calculateWarningItemsDepth(
                            $warningItems,
                            $warningItem['warning_category_id'],
                            $depth + 1
                        )
                    );
                }

                unset($warningItems[$warningItemId]);
            }
        }

        return $calculatedItems;
    }

    public function filterViewableWarningItems(
        array $warningItems,
        array $warningCategories = null,
        array $viewingUser = null
    ) {
        $this->standardizeViewingUserReference($viewingUser);

        if ($warningCategories === null)
        {
            $warningCategories = $this->prepareWarningCategories(
                $this->getWarningCategories(true)
            );
        }

        foreach ($warningItems as $warningItemId => $warningItem)
        {
            if ($this->isWarningCategory($warningItem))
            {
                if (!$this->canViewWarningCategory(
                    $warningItem,
                    $warningCategories,
                    $viewingUser
                )) {
                    unset($warningItems[$warningItemId]);
                }
            }
            elseif ($this->isWarningDefinition($warningItem))
            {
                if (!$this->canViewWarningDefinition(
                    $warningItem,
                    $warningCategories,
                    $viewingUser
                )) {
                    unset($warningItems[$warningItemId]);
                }
            }
        }

        return $warningItems;
    }

    public function getWarningItemTree(array $warningItems = null)
    {
        if (!$this->isWarningItemsArray($warningItems))
        {
            $warningItems = $this->getWarningItems();
        }

        $tree = array();

        foreach ($warningItems as $warningItem)
        {
            $node = array();

            if ($this->isWarningCategory($warningItem))
            {
                $node['id'] = 'c'.$warningItem['warning_category_id'];
                $node['type'] = 'category';

                if ($warningItem['parent_warning_category_id'] !== 0)
                {
                    $node['parent'] = 'c'.$warningItem['parent_warning_category_id'];
                }
                else
                {
                    $node['parent'] = '#';
                }
                $node['state']['opened'] = 1;
                $node['a_attr']["href"] = XenForo_Link::buildAdminLink('warnings/category-edit', array(), array('warning_category_id' => $warningItem['warning_category_id']));
            }
            elseif ($this->isWarningDefinition($warningItem))
            {
                $node['id'] = 'd'.$warningItem['warning_definition_id'];
                $node['type'] = 'definition';
                $node['parent'] = 'c'.$warningItem['sv_warning_category_id'];
                $node['a_attr']["href"] = XenForo_Link::buildAdminLink('warnings/edit', $warningItem);
            }

            $node['text'] = $warningItem['title'];

            $tree[] = $node;
        }

        return $tree;
    }

    public function processWarningItemTreeItem(array $node)
    {
        return array(
            'type' => $node['type'],
            'id'   => (int)substr($node['id'], 1),
            'title' => $node['text']
        );
    }

    public function processWarningItemTree(array &$tree, $parentId = 0)
    {
        $warningItems = array();

        $displayOrder = 0;
        foreach ($tree as $branchId => $branch)
        {
            if (!is_int($branch['id']))
            {
                $branch['id'] = (int)substr($branch['id'], 1);
            }

            if (!is_int($branch['parent']))
            {
                if ($branch['parent'] != '#')
                {
                    $branch['parent'] = (int)substr($branch['parent'], 1);
                }
                else
                {
                    $branch['parent'] = 0;
                }
            }

            if ($branch['parent'] === $parentId)
            {
                $item = array(
                    'type'          => $branch['type'],
                    'id'            => $branch['id'],
                    'parent'        => $branch['parent'],
                    'display_order' => $displayOrder
                );
                $warningItems[] = $item;

                if ($branch['type'] == 'category')
                {
                    $warningItems = array_merge(
                        $warningItems,
                        $this->processWarningItemTree($tree, $branch['id'])
                    );
                }

                unset($branch[$branchId]);

                $displayOrder++;
            }
        }

        return $warningItems;
    }

    public function groupWarningItemsByRootWarningCategory(array $warningItems)
    {
        $warningCategories = array();

        foreach ($warningItems as $warningItem)
        {
            if ($this->isWarningCategory($warningItem))
            {
                $warningItemId = $warningItem['warning_category_id'];

                if ($warningItem['parent_warning_category_id'] === 0)
                {
                    $warningCategories[$warningItemId] = $warningItem;

                    continue;
                }
            }
            elseif ($this->isWarningDefinition($warningItem))
            {
                $warningItemId = $warningItem['warning_definition_id'];
            }

            $rootWarningCategory = $this->getRootWarningCategoryByWarningItem(
                $warningItem
            );
            $rootWarningCategoryId = $rootWarningCategory['warning_category_id'];
            $warningCategories[$rootWarningCategoryId]['children'][] = $warningItem;
        }

        foreach ($warningCategories as $warningCategoryId => $warningCategory)
        {
            if (empty($warningCategory['children']))
            {
                unset($warningCategories[$warningCategoryId]);
            }
        }

        return $warningCategories;
    }

    public function groupWarningItemsByWarningCategory(array $warningItems)
    {
        $warningCategories = array(0 => array());

        foreach ($warningItems as $warningItemId => $warningItem)
        {
            if ($this->isWarningCategory($warningItem))
            {
                $categoryId = $warningItem['warning_category_id'];
                $warningCategories[$categoryId] = $warningItem;
            }
            elseif ($this->isWarningDefinition($warningItem))
            {
                $definitionId = $warningItem['warning_definition_id'];
                $categoryId = $warningItem['sv_warning_category_id'];
                $warningCategories[$categoryId]['warnings'][$definitionId] = $warningItem;
            }
            elseif ($this->isWarningAction($warningItem))
            {
                $actionId= $warningItem['warning_action_id'];
                $categoryId = $warningItem['sv_warning_category_id'];
                $warningCategories[$categoryId]['actions'][$actionId] = $warningItem;
            }
        }

        foreach ($warningCategories as $warningCategoryId => $warningCategory)
        {
            if (empty($warningCategory['warnings']) &&
                empty($warningCategory['actions'])
            ) {
                unset($warningCategories[$warningCategoryId]);
            }
        }

        return $warningCategories;
    }

    public function getWarningByIds($warningIds)
    {
        if (empty($warningIds))
        {
            return array();
        }

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
        ', 'warning_default_id');
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

    protected $lastWarningAction = null;

    public function getUserWarningPointsByCategory($userId)
    {
        if (!empty($this->_userWarningPoints[$userId]))
        {
            return $this->_userWarningPoints[$userId];
        }

        $warningCategories = $this->getWarningCategories(true);
        $warningDefinitions = $this->getWarningDefinitions();
        $warnings = $this->getWarningsByUser($userId);

        if (SV_WarningImprovements_Globals::$warningObj !== null)
        {
            $newWarning = SV_WarningImprovements_Globals::$warningObj;
        }
        else
        {
            $newWarning = null;
        }

        $warningPoints = array();
        $warningPointsCumulative = array(
            0 => array(
                'old' => 0,
                'new' => 0
            )
        );

        foreach ($warnings as $warning)
        {
            if ($warning['is_expired'])
            {
                continue;
            }

            $warningDefinitionId = $warning['warning_definition_id'];

            if (empty($warningDefinitions[$warningDefinitionId]))
            {
                $warningCategoryId = false;
            }
            else
            {
                $warningDefinition = $warningDefinitions[$warningDefinitionId];
                $warningCategoryId = $warningDefinition['sv_warning_category_id'];

                if (empty($warningPoints[$warningCategoryId]))
                {
                    $warningPoints[$warningCategoryId]['old'] = 0;
                    $warningPoints[$warningCategoryId]['new'] = 0;
                }
            }

            if ($newWarning === null ||
                $warning['warning_id'] != $newWarning['warning_id'])
            {
                $warningPointsCumulative[0]['old'] += $warning['points'];

                if ($warningCategoryId)
                {
                    $warningPoints[$warningCategoryId]['old'] += $warning['points'];
                }
            }

            $warningPointsCumulative[0]['new'] += $warning['points'];

            if ($warningCategoryId)
            {
                $warningPoints[$warningCategoryId]['new'] += $warning['points'];
            }
        }

        foreach ($warningCategories as $warningCategoryId => $warningCategory)
        {
            if (empty($warningPoints[$warningCategoryId]))
            {
                $warningPoints[$warningCategoryId]['old'] = 0;
                $warningPoints[$warningCategoryId]['new'] = 0;
            }

            $oldPoints = $warningPoints[$warningCategoryId]['old'];
            $newPoints = $warningPoints[$warningCategoryId]['new'];

            $children = $this->getWarningCategoriesByParentId(
                $warningCategoryId,
                $warningCategories
            );

            foreach ($children as $childCategoryId => $child)
            {
                if (!empty($warningPoints[$childCategoryId]))
                {
                    $oldPoints += $warningPoints[$childCategoryId]['old'];
                    $newPoints += $warningPoints[$childCategoryId]['new'];
                }
            }

            $warningPointsCumulative[$warningCategoryId]['old'] = $oldPoints;
            $warningPointsCumulative[$warningCategoryId]['new'] = $newPoints;
        }

        $this->_userWarningPoints[$userId] = $warningPointsCumulative;

        return $warningPointsCumulative;
    }

    protected function _userWarningPointsIncreased(
        $userId,
        $newPoints,
        $oldPoints
    ) {
        SV_WarningImprovements_Globals::$filterActionsByCategory = true;

        // old points may vary by warning action (and be less than $oldPoints)
        parent::_userWarningPointsIncreased($userId, $newPoints, 0);

        SV_WarningImprovements_Globals::$filterActionsByCategory = false;

        // only do the last post action
        if ($this->lastWarningAction)
        {
            $posterUserId = empty($this->lastWarningAction['sv_post_as_user_id'])
                          ? null
                          : $this->lastWarningAction['sv_post_as_user_id'];

            $options = XenForo_Application::getOptions();
            $dateStr = date($options->sv_warning_date_format);
            // post a new thread
            if (!empty($this->lastWarningAction['sv_post_node_id']))
            {
                $this->postThread($this->lastWarningAction, $userId, $this->lastWarningAction['sv_post_node_id'], $posterUserId, SV_WarningImprovements_Globals::$warningObj, SV_WarningImprovements_Globals::$reportObj, $dateStr);
            }
            // post a reply
            else if (!empty($this->lastWarningAction['sv_post_thread_id']))
            {
                $this->postReply($this->lastWarningAction, $userId, $this->lastWarningAction['sv_post_thread_id'], $posterUserId, SV_WarningImprovements_Globals::$warningObj, SV_WarningImprovements_Globals::$reportObj, $dateStr);
            }
        }
    }

    public function triggerWarningAction($userId, array $action)
    {
        $userWarningPoints = $this->getUserWarningPointsByCategory($userId);
        $warningCategoryId = $action['sv_warning_category_id'];

        $points = $userWarningPoints[$warningCategoryId];
        if ($action['points'] <= $points['old'])
        {
            return false;
        }
        elseif ($action['points'] > $points['new'])
        {
            return false;
        }

        $triggerId = parent::triggerWarningAction($userId, $action);

        if (SV_WarningImprovements_Globals::$NotifyOnWarningAction &&
            (empty($this->lastWarningAction) || $action['points'] > $this->lastWarningAction['points']) &&
            (!empty($action['sv_post_node_id']) || !empty($action['sv_post_thread_id'])))
        {
            $this->lastWarningAction = $action;
        }

        return $triggerId;
    }

    protected function postReply(array $action, $userId, $threadId, $posterUserId, $warning, $report, $dateStr)
    {
        $thread = $this->_getThreadModel()->getThreadById($threadId);
        if (empty($thread))
        {
            return;
        }
        $forum = $this->_getForumModel()->getForumById($thread['node_id']);
        if (empty($forum))
        {
            return;
        }
        $user = $this->_getUserModel()->getUserById($userId);
        if (empty($user))
        {
            return;
        }
        if (empty($posterUserId))
        {
            $poster = XenForo_Visitor::getInstance()->toArray();
            $permissions = $poster['permissions'];
        }
        else
        {
            $poster = $this->_getUserModel()->getUserById($posterUserId,array(
                'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
            ));
            if (empty($poster))
            {
                return;
            }
            $permissions = XenForo_Permission::unserializePermissions($poster['global_permission_cache']);
        }
        $input = array(
            'username' => $user['username'],
            'points' => $user['warning_points'],
            'report' => empty($report) ? 'N/A' : XenForo_Link::buildPublicLink('full:reports', $report),
            'date' => $dateStr,
            'warning_points' => empty($warning) ? '0' : $warning['points'],
            'threshold' => $action['points'],
        );

        $message = new XenForo_Phrase('Warning_Thread_Message', $input, false);
        $message = XenForo_Helper_String::autoLinkBbCode($message->render());

        $writer = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
        $writer->set('user_id', $poster['user_id']);
        $writer->set('username', $poster['username']);
        $writer->set('message', $message);
        $writer->set('message_state', $this->_getPostModel()->getPostInsertMessageState($thread, $forum));
        $writer->set('thread_id', $threadId);
        $writer->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
        $writer->setOption(XenForo_DataWriter_DiscussionMessage_Post::OPTION_MAX_TAGGED_USERS, XenForo_Permission::hasPermission($permissions, 'general', 'maxTaggedUsers'));
        if (!empty($posterUserId))
        {
            $writer->setOption(XenForo_DataWriter_DiscussionMessage_Post::OPTION_IS_AUTOMATED, true);
        }
        $writer->save();
    }

    protected function postThread(array $action, $userId, $nodeId, $posterUserId, $warning, $report, $dateStr)
    {
        $forum = $this->_getForumModel()->getForumById($nodeId);
        if (empty($forum))
        {
            return;
        }
        $user = $this->_getUserModel()->getUserById($userId);
        if (empty($user))
        {
            return;
        }
        if (empty($posterUserId))
        {
            $poster = XenForo_Visitor::getInstance()->toArray();
            $permissions = $poster['permissions'];
        }
        else
        {
            $poster = $this->_getUserModel()->getUserById($posterUserId,array(
                'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
            ));
            if (empty($poster))
            {
                return;
            }
            $permissions = XenForo_Permission::unserializePermissions($poster['global_permission_cache']);
        }
        $input = array(
            'username' => $user['username'],
            'points' => $user['warning_points'],
            'report' => empty($report) ? 'N/A' : XenForo_Link::buildPublicLink('full:reports', $report),
            'date' => $dateStr,
            'warning_points' => empty($warning) ? '0' : $warning['points'],
            'threshold' => $action['points'],
        );

        $title = new XenForo_Phrase('Warning_Thread_Title', $input, false);
        $message = new XenForo_Phrase('Warning_Thread_Message', $input, false);
        $message = XenForo_Helper_String::autoLinkBbCode($message->render());

        $threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
        $threadDw->setOption(XenForo_DataWriter_Discussion::OPTION_TRIM_TITLE, true);
        $threadDw->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);
        $threadDw->bulkSet(array(
            'user_id' => $poster['user_id'],
            'username' => $poster['username'],
            'node_id' => $forum['node_id'],
            'discussion_state' => 'visible',
            'prefix_id' => $forum['default_prefix_id'],
            'title' => $title->render(),
        ));

        $postWriter = $threadDw->getFirstMessageDw();
        $postWriter->set('message', $message);
        $postWriter->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
        $postWriter->setOption(XenForo_DataWriter_DiscussionMessage_Post::OPTION_MAX_TAGGED_USERS, XenForo_Permission::hasPermission($permissions, 'general', 'maxTaggedUsers'));
        if (!empty($posterUserId))
        {
            $postWriter->setOption(XenForo_DataWriter_DiscussionMessage_Post::OPTION_IS_AUTOMATED, true);
        }
        $threadDw->save();
    }

    protected $warning_user = null;
    protected $viewer = null;

    public function prepareWarning(array $warning)
    {
        $warning = parent::prepareWarning($warning);

        if ($this->viewer === null)
        {
            $this->viewer = XenForo_Visitor::getInstance()->toArray();
        }
        $viewer = $this->viewer;

        if(!XenForo_Permission::hasPermission($viewer['permissions'], 'general', 'viewWarning'))
        {
            if (!empty($warning['content_title']))
            {
                $warning['content_title'] = XenForo_Helper_String::censorString($warning['content_title']);
            }
            $warning['notes'] = '';
            if (!empty($warning['expiry_date']))
            {
                $warning['expiry_date'] = $warning['expiry_date'] - ($warning['expiry_date'] % 3600) + 3600;
            }
        }

        if (!XenForo_Permission::hasPermission($viewer['permissions'], 'general', 'viewWarning_issuer') && !XenForo_Permission::hasPermission($viewer['permissions'], 'general', 'viewWarning'))
        {
            $anonymisedWarning = false;
            $options = XenForo_Application::getOptions();
            if ($options->sv_warningimprovements_warning_user)
            {
                if ($this->warning_user === null)
                {
                    $this->warning_user = $this->_getUserModel()->getUserByName($options->sv_warningimprovements_warning_user);
                    if (empty($this->warning_user))
                    {
                        $this->warning_user = array();
                    }
                }
                if (isset($this->warning_user['user_id']))
                {
                    $warning['warn_user_id'] = $this->warning_user['user_id'];
                    $warning['warn_username'] = $this->warning_user['username'];
                    $anonymisedWarning = true;
                }
            }
            if (!$anonymisedWarning)
            {
                $warning['warn_user_id'] = 0;
                $warning['warn_username'] = new XenForo_Phrase('WarningStaff');
            }
        }
        return $warning;
    }

    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }

    protected function _getForumModel()
    {
        return $this->getModelFromCache('XenForo_Model_Forum');
    }

    protected function _getThreadModel()
    {
        return $this->getModelFromCache('XenForo_Model_Thread');
    }

    protected function _getPostModel()
    {
        return $this->getModelFromCache('XenForo_Model_Post');
    }
}
