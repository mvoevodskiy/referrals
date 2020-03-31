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
    const ACTION_ANNUL = 'annul';
    const ACTION_ANNUL_REFERRAL = 'annul_referral';
    const ACTION_REWARD = 'reward';
    const ACTION_REVOKE = 'revoke';

    const STATUS_ACTIVE = 'active';
    const STATUS_REVOKED = 'revoked';
    const STATUS_ANNULLED = 'annulled';


    /**
     * @param array $args First element: xPDO. Second element: array with required keys: user, action, account; Optional keys: delta, balance, order, parent, status
     *
     * @return bool
     */
    public static function write(...$args)
    {
        $xpdo = array_shift($args);
        if (count($args) === 1) {
            extract($args[0]);
        } else {
            $action = $args[0];
            $user = $args[0];
            $account = $args[0];
            $delta = $args[0];
            $balance = $args[0];
            $order = $args[0];
        }
        if (!isset($parent) && !empty($order)) {
            $deltas = [$delta, -1 * $delta];
            /** @var refLog $parentLog */
            if ($parentLog = $xpdo->getObject('refLog', ['status' => self::STATUS_ACTIVE, 'delta:IN' => $deltas, 'parent' => 0, 'order' => $order])) {
                $parent = $parentLog->get('id');
            }
        }
        /** @var refLog $log */
        if ($log = $xpdo->newObject(self::class)) {
            if (!$user and $xpdo instanceof modX) {
                $user = $xpdo->user->id;
            }
            $log->fromArray([
                'user' => $user,
                'action' => $action,
                'account' => $account ?? 0,
                'delta' => $delta ?? 0,
                'balance' => $balance ?? 0,
                'order' => $order ?? 0,
                'parent' => $parent ?? 0,
                'status' => $status ?? self::STATUS_ACTIVE,
            ]);
            if ($log->save()) {
                if ($parent) {
                    self::revoke($xpdo, $parent);
                }
                return true;
            }
        }
        return false;
    }

    public static function annul(xPDO $xpdo, int $id)
    {
        return self::changeOneField($xpdo, $id, 'status', self::STATUS_ANNULLED);
    }

    public static function revoke(xPDO $xpdo, int $id)
    {
        return self::changeOneField($xpdo, $id, 'status', self::STATUS_REVOKED);
    }

    protected static function changeOneField(xPDO $xpdo, int $id, string $field, $value) {
        if ($log = $xpdo->getObject('refLog', $id)) {
            $log->set($field, $value);
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