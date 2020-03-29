<?php
class refAccountType extends xPDOSimpleObject {

    public function isSystem()
    {
        return $this->get('system');
    }

}