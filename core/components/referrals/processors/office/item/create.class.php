<?php

class referralsOfficeItemCreateProcessor extends modObjectCreateProcessor
{
    public $objectType = 'referralsItem';
    public $classKey = 'referralsItem';
    public $languageTopics = array('referrals');
    //public $permission = 'create';


    /**
     * @return bool
     */
    public function beforeSet()
    {
        $name = trim($this->getProperty('name'));
        if (empty($name)) {
            $this->modx->error->addField('name', $this->modx->lexicon('referrals_item_err_name'));
        } elseif ($this->modx->getCount($this->classKey, array('name' => $name))) {
            $this->modx->error->addField('name', $this->modx->lexicon('referrals_item_err_ae'));
        }

        return parent::beforeSet();
    }

}

return 'referralsOfficeItemCreateProcessor';