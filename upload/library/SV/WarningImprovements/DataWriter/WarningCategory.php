<?php

class SV_WarningImprovements_DataWriter_WarningCategory extends XenForo_DataWriter
{
    const DATA_TITLE = 'phraseTitle';

    protected function _getFields()
    {
        return array(
            'xf_sv_warning_category' => array(
                'warning_category_id' => array(
                    'type'          => self::TYPE_UINT,
                    'autoIncrement' => true
                ),
                'parent_warning_category_id' => array(
                    'type'         => self::TYPE_UINT,
                    'default'      => 0,
                    'verification' => array(
                        '$this',
                        '_verifyParentWarningCategory'
                    )
                ),
                'display_order' => array(
                    'type'    => self::TYPE_UINT,
                    'default' => 0
                ),
                'allowed_user_group_ids' => array(
                    'type'         => self::TYPE_UNKNOWN,
                    'default'      => '2',
                    'verification' => array(
                        'XenForo_DataWriter_Helper_User',
                        'verifyExtraUserGroupIds'
                    )
                )
            )
        );
    }

    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'warning_category_id')) {
            return false;
        }

        $warningCategory = $this->_getWarningModel()
            ->getWarningCategoryById($id);

        return array('xf_sv_warning_category' => $warningCategory);
    }

    protected function _getUpdateCondition($tableName)
    {
        $warningCategoryId = $this->_db->quote($this->getExisting(
            'warning_category_id'
        ));

        return 'warning_category_id = '.$warningCategoryId;
    }

    protected function _preSave()
    {
        $titlePhrase = $this->getExtraData(self::DATA_TITLE);

        if ($titlePhrase !== null && strlen($titlePhrase) == 0) {
            $this->error(
                new XenForo_Phrase('please_enter_valid_title'),
                'title'
            );
        }
    }

    protected function _postSave()
    {
        $warningCategoryId = $this->get('warning_category_id');

        $titlePhrase = $this->getExtraData(self::DATA_TITLE);

        if ($titlePhrase !== null) {
            $this->_insertOrUpdateMasterPhrase(
                $this->_getTitlePhraseName($warningCategoryId),
                $titlePhrase,
                ''
            );
        }
    }

    protected function _postDelete()
    {
        $warningCategoryId = $this->get('warning_category_id');

        $this->_deleteMasterPhrase($this->_getTitlePhraseName(
            $warningCategoryId
        ));

        $warningModel = $this->_getWarningModel();

        $children = $warningModel->getWarningItemsByParentId($warningCategoryId);

        foreach ($children as $child) {
            if ($warningModel->isWarningCategory($child)) {
                $dw = XenForo_DataWriter::create(
                    'SV_WarningImprovements_DataWriter_WarningCategory'
                );
                $dw->setExistingData($child['warning_category_id']);
                $dw->delete();
            } elseif ($warningModel->isWarningDefinition($child)) {
                $dw = XenForo_DataWriter::create(
                    'XenForo_DataWriter_WarningDefinition'
                );

                if ($child['warning_definition_id'] !== 0) {
                    $dw->setExistingData($child['warning_definition_id']);
                    $dw->delete();
                } else {
                    $dw->setOption(
                        SV_WarningImprovements_XenForo_DataWriter_WarningDefinition::IS_CUSTOM,
                        1
                    );
                    $dw->setExistingData($child['warning_definition_id']);

                    $warningCategories = $warningModel->getWarningCategories();
                    $firstCategory = reset($warningCategories);
                    $dw->set(
                        'sv_warning_category_id',
                        $firstCategory['warning_category_id']
                    );
                    $dw->save();
                }
            }
        }
    }

    protected function _verifyParentWarningCategory($parentWarningCategoryId)
    {
        if ($parentWarningCategoryId === 0) {
            return true;
        }

        $warningModel = $this->_getWarningModel();

        if ($this->isInsert() ||
            empty($warningModel->getWarningCategoriesByParentId(
                $this->get('warning_category_id')
            ))
        ) {
            $parentWarningCategory = $warningModel->getWarningCategoryById(
                $parentWarningCategoryId
            );

            if (!empty($parentWarningCategory)) {
                if ($parentWarningCategory['parent_warning_category_id'] === 0) {
                    return true;
                }
            }
        }

        $this->error(
            new XenForo_Phrase('sv_please_enter_valid_warning_category_id'),
            'parent_warning_category_id'
        );

        return false;
    }

    protected function _getTitlePhraseName($id)
    {
        return $this->_getWarningModel()->getWarningCategoryTitlePhraseName($id);
    }

    protected function _getWarningModel()
    {
        return $this->getModelFromCache('XenForo_Model_Warning');
    }
}
