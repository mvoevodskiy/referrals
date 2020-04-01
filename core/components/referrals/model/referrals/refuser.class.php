<?php
class refUser extends xPDOSimpleObject {


    public function save($cacheFlag= null)
    {
        $new = $this->isNew();
        if ($new && empty($this->createdon)) {
            $this->set('createdon', time());
        }
        if (parent::save($cacheFlag)) {
            if ($new) {
                refLog::write($this->xpdo, refLog::ACTION_REGISTER, $this->get('user'));
            }
            return true;
        }
        return false;
    }


}