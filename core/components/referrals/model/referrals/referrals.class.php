<?php

class referrals
{
    /** @var modX $modx */
    public $modx;
    public $config = [];

    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = array())
    {
        $this->modx =& $modx;

        $corePath = $this->modx->getOption('referrals_core_path', $config,
            $this->modx->getOption('core_path') . 'components/referrals/'
        );
        $assetsUrl = $this->modx->getOption('referrals_assets_url', $config,
            $this->modx->getOption('assets_url') . 'components/referrals/'
        );
        $connectorUrl = $assetsUrl . 'connector.php';

        $this->config = array_merge(array(
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $connectorUrl,

            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'templatesPath' => $corePath . 'elements/templates/',
            'chunkSuffix' => '.chunk.tpl',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'processorsPath' => $corePath . 'processors/',

            'accountReferrals' => $this->modx->getOption('referrals_account_type_id_referrals'),
            'accountMoney' => $this->modx->getOption('referrals_account_type_id_money'),

            'useLimit' => $this->modx->getOption('referrals_use_limit'),
            'levelRewards' => $modx->fromJSON($this->modx->getOption('referrals_level_rewards')),

            'confirmField' => $this->modx->getOption('referrals_use_limit_product_field', null, 'phone'),
            'useLimitProductField' => $this->modx->getOption('referrals_use_limit_product_field', null, '100%'),
            'levelRewardsProductField' => $modx->fromJSON($this->modx->getOption('referrals_level_rewards_productField', null, '{}')),

            'ttl' => [
                'cookie' => $this->modx->getOption('referrals_cookie_ttl', null, 30),
                'referral' => $this->modx->getOption('referrals_cookie_ttl', null, 1095),
                'reward' => $this->modx->getOption('referrals_cookie_ttl', null, 1095),
            ],
            'defaultTransport' => $this->modx->getOption('referrals_default_transport', null, 'sms'),
            'smsLogin' => $this->modx->getOption('referrals_sms_login'),
            'smsPassword' => $this->modx->getOption('referrals_sms_password'),

            'rewardRegisterMaster' => $this->modx->getOption('referrals_reward_register_master'),
            'rewardRegisterUser' => $this->modx->getOption('referrals_reward_register_user'),

            'orderStatuses' => [
                'decrease' => $this->modx->getOption('referrals_order_status_decrease'),
                'increase' => $this->modx->getOption('referrals_order_status_increase'),
                'reward' => $this->modx->getOption('referrals_order_status_reward'),
                'revoke' => $this->modx->getOption('referrals_order_status_revoke'),
            ],

            'frontendJS' => $this->modx->getOption('referrals_frontend_js'),
            'orderPropertiesElement' => $this->modx->getOption('referrals_order_properties_element', null, 'referrals'),
        ), $config);

        $this->modx->addPackage('referrals', $this->config['modelPath']);
        $this->modx->lexicon->load('referrals:default');
    }


    public function rewardRegister($user, $master)
    {
        $accountReferrals = $this->config['accountReferrals'];
        $accountMoney = $this->config['accountMoney'];
        $this->updateAccount($master, refLog::ACTION_REGISTER_REFERRAL, $accountReferrals, 1);
        if ($this->config['rewardRegisterMaster']) {
            $this->updateAccount($master, refLog::ACTION_REWARD_REGISTER, $accountMoney, $this->config['rewardRegisterMaster']);
        }
        if ($this->config['rewardRegisterUser']) {
            $this->updateAccount($user, refLog::ACTION_REWARD_REGISTER, $accountMoney, $this->config['rewardRegisterUser']);
        }
    }


    /**
     * @param msOrder $order
     */
    public function rewardOrder($order, $action = 'reward')
    {
        if (!$initiator = $this->modx->getObject('refUser', ['user' => $order->get('user_id')])) {
            return true;
        }

        $levelUsers = [];
        $rewardUsers = [];
        $products = [];
        $users = [];
        $multiply = 1;

        if ($action === refLog::ACTION_REWARD) {
            $productsTotal = 0;
            $orderTotal = $order->get('cost') - $order->get('delivery_cost');

            $defaultRewards = $this->config['levelRewards'];
            $defaultSelf = $defaultRewards['self'] ?? 0;
            unset($defaultRewards['self']);
            $levels = array_keys($defaultRewards);
            sort($levels);

            /** @var refUser $current */
            $current = $initiator;
            while ($current = $current->Master && count($users) < count($levels)) {
                $users[] = $current->get('user');
            }
            foreach ($levels as $i => $level) {
                $levelUsers[$level] = $users[$i];
            }

            $levels['self'] = $defaultSelf;
            $levelUsers['self'] = $order->get('user_id');

            /** @var msOrderProduct $oProduct */
            foreach ($order->getMany('Products') as $oProduct) {
                $productsTotal += $oProduct->get('cost');
                $pId = $oProduct->get('product_id');
                if (!isset($products[$pId])) {
                    $products[$pId] = ['all' => $defaultRewards, 'self' => $defaultSelf];
                    /** @var msProduct $product */
                    if ($product = $oProduct->get('Product')) {
                        $productRewards = $this->modx->fromJSON($product->get($this->config['levelRewardsProductField']));
                        $products[$pId] = is_array($productRewards) ? $productRewards : [];
                    }
                }
                foreach ($levelUsers as $level => $user) {
                    $rewardUsers[$user] = $rewardUsers[$user] ?? 0;
                    $rewardUsers[$user] += $this->getAbsAmount($products[$pId][$level] ?? $levels[$level],
                        $oProduct->get('cost'));
                }
            }

            if ($productsTotal < $orderTotal) {
                $multiply = $productsTotal / $orderTotal;
            }
        } elseif ($action === refLog::ACTION_REVOKE) {
            $logs = $this->modx->getCollection('refLog', ['order' => $order->get('id'), 'action' => refLog::ACTION_ORDER_INCREASE, 'status' => refLog::STATUS_ACTIVE]);
            foreach ($logs as $log) {
                $rewardUsers[$log->get('user')] = -1 * $log->get('amount');
            }
        }

        foreach ($rewardUsers as $rewardUser => $amount) {
            $reward = $amount * $multiply;
            if ($reward) {
                $this->updateAccount($rewardUser, $action, $reward, $order->get('id'));
            }
        }

        return true;
    }


    public function updateAccount($user, $action, $accountType, $delta, $order = 0, $parent = null)
    {
        if (!class_exists('refAccount')) {
            $this->modx->loadClass('refAccount');
        }
        $accountData = ['user' => $user, 'type' => $accountType];
        if (!$account = $this->modx->getObject('refAccount', $accountData)) {
            $account = $this->modx->newObject('refAccount', $accountData);
        }
        $account->set('balance', $account->get('balance') + $delta);
        if ($account->save()) {
            refLog::write($this->modx, [
                'action' => $action,
                'user' => $user,
                'account' => $account->get('id'),
                'delta' => $delta,
                'balance' => $account->get('balance'),
                'order' => $order,
                'parent' => $parent,
            ]);
        }
    }


    public function confirmCode($code, $user = null)
    {
        $result = true;
        /** @var modUser|null $user */
        if ($user = $this->getUser($user) and $profile = $user->getOne('Profile')) {
            $extended = $profile->get('extended');
            $sent = $extended['referrals']['confirmCode'];
            if ($code == $sent) {
                if (!$refUser = $this->modx->getObject('refUser', ['user' => $user->get('id')])) {
                    $refUser = $this->modx->newObject('refUser', ['user' => $user->get('id')]);
                }
                $refUser->set('confirmed', true);
                if ($refUser->save()) {
                    if ($refUser->get('master')) {
                        $this->rewardRegister($refUser->get('user'), $refUser->get('master'));
                    }
                } else {
                    $result = $this->modx->lexicon('referrals_user_error');
                }
            } else {
                $result = $this->modx->lexicon('referrals_code_invalid');
            }
        } else {
            $result = $this->modx->lexicon('referrals_code_invalid');
        }
        return $result;
    }


    public function sendCode(array $fields, $user = null)
    {
//        echo 'sending code. ';
        if ($user = $this->getUser($user) and $profile = $user->getOne('Profile')) {
            $code = $this->generateCode();
            $text = $this->generateCodeMessage($code);
            foreach ($fields as $field => $value) {
                if ($this->modx->getCount('modUserProfile', [$field => $value, 'internalKey:!=' => $user->get('id')])) {
                    return $this->modx->lexicon('referrals_multiply_err_' . $field);
                }
            }
            if ($this->send($fields, $text)) {
                foreach ($fields as $field => $value) {
                    $profile->set($field, $value);
                }
                $extended = $profile->get('extended');
                $extended['referrals']['confirmCode'] = $code;
                $extended['referrals']['sendTime'] = time();
                $profile->set('extended', $extended);
                return $profile->save();
            }
        }
        return false;
    }


    public function generateCode()
    {
        return rand(10000, 99999);
    }


    public function generateCodeMessage($code)
    {
        return $this->modx->lexicon('referrals_code_message', ['code' => $code]);
    }


    public function send($receivers, $text)
    {
        $result = '';
//        echo ' SEND ALL. ';
        foreach ($receivers as $field => $receiver) {
//            echo ' [FIELD] ' . $field . ' [RECEIVER] ' . $receiver;
            switch ($field) {
                case 'phone':
                    $receiver = $this->normalizeRusPhone($receiver);
                    $result = $this->sendSMS($receiver, $text);
                    break;

                case 'email':
                default:
                    break;
            }
        }
        return $result;

    }


    public function sendSMS($receiver, $text)
    {
//        echo ' SEND SMS. ';
        $url = 'https://smsc.ru/sys/send.php?login=' . urlencode($this->config['smsLogin'])
            . '&psw=' . urlencode($this->config['smsPassword'])
            . '&phones=' . urlencode($receiver)
            . '&mes=' . urlencode($text)
            . '&charset=utf-8'
            . '&fmt=3';

        $result = file_get_contents($url);
//        $this->modx->log(1, 'SEND SMS. URL: ' . $url . ' RESULT: ' . $result);
        $result = $this->modx->fromJSON($result);

        if (isset($result['error'])) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, '[REFERRALS] SEND SMS ERROR: ' . $result['error']);
        }
        return $result['id'] ?? false;
    }


    /**
     * @param null $user
     *
     * @return modUser|null
     */
    public function getUser($user = null)
    {
//        $this->modx->log(1, 'GET USER. USER TYPE IS ' . gettype($user));
        if (!$user) {
            $user = $this->modx->getAuthenticatedUser();
        } elseif (is_numeric($user)) {
            $user = $this->modx->getObject('modUser', $user);
        }
//        $this->modx->log(1, 'GET USER. USER TYPE IS ' . gettype($user));

        return $user;
    }

    public function getMasterUser($user = null)
    {
        if (!$user) {
            $user = $this->modx->getAuthenticatedUser();
        } elseif (is_numeric($user)) {
            $user = $this->modx->getObject('modUser', $user);
        }

        $this->modx->log(1, gettype($user));
        $result = ['master' => $user->id];
        if ($user) {
            $q = $this->modx->newQuery('refUser');
            $q->innerJoin('modUserProfile', 'Profile', 'Profile.internalKey = refUser.user');
            $q->select(['Profile.*', 'refUser.*']);
            $q->where(['refUser.master' => $user->get('id')]);
            $q->prepare();
            $q->stmt->execute();
            $result['referrals'] = $q->stmt->fetchAll(PDO::FETCH_ASSOC);

            $q = $this->modx->newQuery('refUser');
            $q->select(['COUNT(id) AS `count`', 'confirmed']);
            $q->where(['refUser.master' => $user->get('id')]);
            $q->groupby('confirmed');
            $q->sortby('confirmed');
            $q->prepare();
            $q->stmt->execute();
            $states = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($states as $state) {
                if ($state['confirmed']) {
                    $result['confirmed'] = $state['count'];
                } else {
                    $result['notConfirmed'] = $state['count'];
                }
            }

            $q = $this->modx->newQuery('msOrder');
            $q->innerJoin('refUser', 'refUser', 'msOrder.user_id = refUser.user');
            $q->select('SUM(cost) as cost');
            $q->where(['refUser.master' => $user->get('id')]);
            $q->prepare();
            $q->stmt->execute();
            $rawCost = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
            $result['paid'] = $rawCost ? $rawCost[0]['cost'] : 0;

        }

        return $result;
    }

    public function detachReferral($user = null)
    {
        return true;
        if (!$user) {
            $user = $this->modx->getAuthenticatedUser();
        } elseif (is_numeric($user)) {
            $user = $this->modx->getObject('modUser', $user);
        }

        $this->modx->log(1, gettype($user));
        $result = ['master' => $user->id];
        if ($user && $referral = $this->modx->getObject('refUser', ['user' => $user->get('id')])) {
            refLog::write($this->modx, ['user' => $user->id, 'action' => refLog::ACTION_DETACH, 'by' => $this->modx->user->id]);
            $referral->set('master', 0);
            return $referral->save();

        }
        return false;
    }

    public function attachReferral($master, $user)
    {
//        return true;
        if (is_numeric($master)) {
            $master = $this->modx->getObject('modUser', $master);
        }
        if (!$user) {
            $user = $this->modx->getAuthenticatedUser();
        } elseif (is_numeric($user)) {
            $user = $this->modx->getObject('modUser', $user);
        } elseif (is_string($user)) {
            $q = $this->modx->newQuery('modUser');
            $q->innerJoin('modUserProfile', 'Profile', 'Profile.internalKey = modUser.id');
            $q->where(['Profile.phone' => $user, 'OR:Profile.email:=' => $user]);
            $user = $this->modx->getObject('modUser', $q);
        }

        if ($user && $master) {
            if (!$this->modx->getObject('refUser', ['master' => $master->id, 'user' => $user->id])) {
                $ref = $this->modx->newObject('refUser', ['master' => $master->id, 'user' => $user->id]);
                if ($ref->save()) {
                    refLog::write($this->modx,
                        ['user' => $user->id, 'action' => refLog::ACTION_ATTACH, 'by' => $this->modx->user->id]);
                    return true;
                }
            }
        }
        return false;
    }




    public function getBalance($acType, $user = null)
    {
        if ($user = $this->getUser($user)
            and $ac = $this->modx->getObject('refAccount', ['user' => $user->get('id'), 'type' => $acType])
        ) {
            return $ac->get('balance');
        }
        return 0;
    }


    public function normalizeRusPhone($basePhone)
    {
        $phone = preg_replace("#[^\d]#", "", $basePhone);
        /** Добавление кода страны 7 для номеров без кода  */
        if (9000000000 <= $phone and $phone <= 9999999999) {
            $phone += 70000000000;
        }
        /** Замена 8 на 7 в начале номера */
        if (80000000000 <= $phone and $phone <= 89999999999) {
            $phone -= 10000000000;
        }

        if (70000000000 <= $phone and $phone <= 79999999999) {
            return '+' . (string)$phone;
        }
        return $basePhone;
    }


    public function mergeOrderOptions($orderProps, $newProps)
    {
        $propsElem = $this->config['orderPropertiesElement'];
        $properties = & $orderProps[$propsElem];

        if (!is_array($properties)) {
            $properties = array();
        }

        $properties = array_replace($properties, $newProps);

        return $orderProps;

    }


    public function getAvailableForUse($account = 0, $forOrder = false, $ctx = 'web')
    {
        $account = $account ?: $this->config['accountMoney'];
        if ($balance = $this->getBalance($account)) {
            /** @var miniShop2 $ms2 */
            if ($forOrder and $ms2 = $this->modx->getService('minishop2')) {
                $amount = 0;
                $ms2->initialize($ctx);
                $cart = $ms2->cart->get();
                foreach ($cart as $good) {
                    $useLimit = $this->config['useLimit'];
                    if ($product = $this->modx->getObject('msProduct', $good['id'])) {
                        $productLimit = $product->get($this->config['useLimitProductField']);
                        if (!empty($productLimit) || $productLimit === 0 || $productLimit === '0') {
                            $useLimit = $productLimit;
                        }
                    }
                    $amount += $this->getAbsAmount($useLimit, $good['price'] * $good['count']);
                }
                return min($balance, $amount);
            }
            return $balance;
        }
        return 0;
    }


    public function getAbsAmount($amount, $max)
    {
        if (strpos($amount, '%') !== false) {
            $amount = (int) substr($amount, 1);
            $amount = $max * $amount / 100;

        }
        return min(floatval($amount), $max);
    }


    public function registerScripts($jsParams = [])
    {
        if (is_string($jsParams) and !empty($jsParams) and $jsParams[0] == '{') {
            $jsParams = $this->modx->fromJSON($jsParams);
        } elseif (!is_array($jsParams)) {
            $jsParams = [];
        }
        $script = '<script type="text/javascript">
                window.Referrals = window.Referrals || {};';
        if ($jsParams) {
                $script .= ' Referrals.config =' . json_encode($jsParams, JSON_PRETTY_PRINT);
                $this->modx->log(1, json_encode($jsParams, JSON_PRETTY_PRINT));
        }
        $script .= '</script>';
        $this->modx->regClientScript($script);
        $this->modx->regClientScript(str_replace('[[+jsUrl]]', $this->config['jsUrl'], $this->config['frontendJS']));
    }

}