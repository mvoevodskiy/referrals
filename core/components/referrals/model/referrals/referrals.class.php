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
            'maxPercentForUse' => $this->modx->getOption('referrals_max_percent_for_use'),

            'defaultTransport' => $this->modx->getOption('referrals_default_transport', null, 'sms'),
            'smsLogin' => $this->modx->getOption('referrals_sms_login'),
            'smsPassword' => $this->modx->getOption('referrals_sms_password'),

            'rewardRegisterMaster' => $this->modx->getOption('referrals_reward_register_master'),
            'rewardRegisterUser' => $this->modx->getOption('referrals_reward_register_user'),

            'orderStatuses' => [
                'decrease' => $this->modx->getOption('referrals_order_status_decrease'),
                'increase' => $this->modx->getOption('referrals_order_status_increase'),
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


    public function updateAccount($user, $action, $accountType, $delta, $order = 0)
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
            refLog::write($this->modx, $action, $user, $account->get('id'), $delta, $account->get('balance'), $order);
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
                $ms2->initialize($ctx);
                $orderCost = $ms2->order->getCost(true, true);
                $this->modx->log(1, 'ORDER COST: ' . $orderCost);
                return min($balance, ( (float) $orderCost * ((float) $this->config['maxPercentForUse']) / 100));
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