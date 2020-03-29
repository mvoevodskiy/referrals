<?php
class refLog extends xPDOSimpleObject {

    const ACTION_REGISTER = 'register';
    const ACTION_REGISTER_REFERRAL = 'register_referral';
    const ACTION_CONFIRM = 'confirm';
    const ACTION_DECREASE = 'decrease';
    const ACTION_INCREASE = 'increase';
    const ACTION_ORDER_INCREASE = 'order_increase';
    const ACTION_ORDER_DECREASE = 'order_decrease';
    const ACTION_BAN = 'ban';
    const ACTION_UNBAN = 'unban';
    const ACTION_REWARD_REGISTER = 'reward_register';


    public static function write(xpdo $xpdo, string $action, int $user, $account = 0, $delta = 0, $balance = 0, $order = 0)
    {
        if ($log = $xpdo->newObject(self::class)) {
            if (!$user and $xpdo instanceof modX) {
                $user = $xpdo->user->id;
            }
            $log->fromArray([
                'user' => $user,
                'action' => $action,
                'account' => $account,
                'delta' => $delta,
                'balance' => $balance,
                'order' => $order,
            ]);
            return $log->save();
        }
        return false;
    }


    public function save($cacheFlag = null)
    {
        if (!$this->get('occurred')) {
            $this->set('occurred', date('Y-m-d H:i:s'));
        }
        return parent::save();
    }

}