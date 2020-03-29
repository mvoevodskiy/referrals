<?php
class refAccount extends xPDOSimpleObject {

    public function remove(array $ancestors = array())
    {
        /** @var refAccountType $type */
        if ($type = $this->getOne('Type') and $type->isSystem()) {
            return false;
        }
        return parent::remove($ancestors);
    }

}