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
            if (!$this->get('refId') && $profile = $this->getOne('UserProfile')) {
                $prefix = substr($profile->get('fullname'), 0, 3);
                if (!$prefix) {
                    $prefix = substr($profile->User->username, 0, 3);
                }
                $this->set('refId', /*$this->get('id') . */mb_strtoupper($prefix) . $this->get('user'));
                $this->save();
            }
            return true;
        }
        return false;
    }


}