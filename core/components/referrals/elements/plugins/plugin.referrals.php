<?php
/** @var modX $modx */

if ($modx->context->key === 'mgr') {
    return;
}

$session = & $_SESSION['referrals'];
$key = $modx->getOption('referralKey', $_REQUEST, false);

//$modx->log(1, $modx->user->id . ' ' . $modx->event->name . ' . ' . 'REF PLUGIN. COUNT SP ' . count($scriptProperties));
if ($key) {
    $scriptProperties = array_merge($scriptProperties, $_SESSION['referrals']['pay'][$key] ?? []);
//    $modx->log(1, $modx->user->id . ' ' . $modx->event->name . ' . ' . 'REF PLUGIN. SP FROM SESSION! ');
}
//$modx->log(1, $modx->user->id . ' ' . $modx->event->name . ' . ' . 'REF PLUGIN. COUNT SP ' . count($scriptProperties));
//$modx->log(1, $modx->user->id . ' ' . $modx->event->name . ' . ' . 'REF PLUGIN. APPLY ACCOUNT FROM SESSION ' . print_r($session['applyAccount'], 1));

/** @var referrals $referrals */
if (!$referrals = $modx->getService('referrals', 'referrals', $modx->getOption('referrals_core_path', null,
        $modx->getOption('core_path') . 'components/referrals/') . 'model/referrals/', $scriptProperties)
) {
    return 'Could not load referrals class!';
}
//$modx->log(1, $modx->user->id . ' ' . $modx->event->name . ' . ' . 'REF PLUGIN. REF CTX ' . print_r($referrals->config['ctx'], 1));

$modx->loadClass('refLog');
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
$accountId = $scriptProperties['accountId'] ?? $referrals->config['accountMoney'];

$defaultApplyAccount = ['sum' => 0, 'type' => $accountId];
//$modx->log(1, $modx->user->id . ' ' . $modx->event->name . ' . ' . 'REF PLUGIN. DEF APPLY ACCONT ' . print_r($defaultApplyAccount, 1) . ' POST ' . print_r($_POST, 1));

switch ($modx->event->name) {
    case 'OnHandleRequest':

        if ($modx->context->key == 'mgr') {
            return true;
        }

        /**
         *
         * Установка cookie
         *
         */
        /** @var referrals $ref */
        // $ref = $modx->getService('referrals');
        $ref = $referrals;
        $modx->loadClass('refLog');
        // $modx->loadClass('referrals');

        $referrals->updateCookieFromGet();

        /**
         *
         * Установка величины скидки к заказу
         *
         */
        $applyRefAccount = (int)$modx->getOption('referralApplyAccount', $_POST, -1);
        if (empty($_POST['ms2_action']) && $applyRefAccount >= 0) {
            $ctx = $modx->getOption('msCtx', $scriptProperties, $modx->getOption('ctx', $scriptProperties, $modx->context->key));
            /** @var miniShop2 $ms2 */
            $ms2 = $modx->getService('minishop2');
            $ms2->initialize($ctx);

            $accountId = $modx->getOption('accountId', $scriptProperties, $referrals->config['accountMoney']);

            $output = '';
            $data = ['success' => false, 'data' => []];
            $available = $referrals->getAvailableForUse($accountId, true, $ctx);

            if ($applyRefAccount <= $available) {
                $session['applyAccount'] = [
                    'sum' => $applyRefAccount,
                    'type' => $accountId
                ];
                $data['success'] = true;
            } else {
                $data['msg'] = 'Доступно для списания не более ' . $available . ' руб.';
            }
            $data['data']['available'] = $available;
            $data['data']['applied'] = $applyRefAccount;
            @session_write_close();
            exit($modx->toJSON($data));
        }

        if (isset($_POST['referralsSendCode']) or isset($_POST['referralsConfirmCode'])) {
            $modx->runSnippet('referralsConfirm');
        }

        $action = $_POST['referrals_action'] ?? false;
        $result = [];
        if ($action) {
            $modx->getService('error', 'error.modError');
            $modx->setLogLevel(modX::LOG_LEVEL_ERROR);
            $modx->setLogTarget('FILE');
            $modx->error->message = null;
            switch ($action) {
                case 'manage/master/details':
                    $result = $referrals->getMasterUser((int) $_POST['id']);
                    break;

                case 'manage/referral/detach':
                    $result = $referrals->detachReferral((int) $_POST['id']);
                    break;

                case 'manage/referral/attach':
                    $result = $referrals->attachReferral((int) $_POST['master'], (string) $_POST['email']);
                    break;

                case 'available/get':
                    if ($modx->user->id == 1739) {
                        $modx->log(1, print_r($referrals->config, 1));
                    }
                    $result = [
                        'balance' => $referrals->getBalance($accountId),
                        'available' => $referrals->getAvailableForUse($accountId, true),
                    ];
                    break;

                case 'user/check':
                    $result = $modx->getCount('refUser', ['refId' => (string) $modx->getOption('refId', $_POST, '')]);
                    break;

                case 'master/update':
                    $result = $referrals->updateMasterFromCode((string) $modx->getOption('refId', $_POST, ''));
                    break;

            }

            $result = $result === true || is_array($result) ? $modx->error->success('', $result) : $modx->error->failure(is_string($result) ? $result : '');
            @session_write_close();
            exit($modx->toJSON($result));
        }


        break;

    case 'OnUserProfileSave':
        /** @var modUserProfile $userprofile */
        $varCookie = $modx->getOption('referrals_var_cookie');
        $master = (int) ($_COOKIE[$varCookie] ?? 0);
        if (isset($mode) && $mode == modSystemEvent::MODE_NEW && $master) {
            if ($modx->getCount('refUser', ['user' => $master, 'confirmed' => true])) {
                /** @var refUser $refUser */
                $refUser = $modx->newObject('refUser', [
                    'user' => $userprofile->get('internalKey'),
                    'master' => $master,
                    'ctx' => $referrals->config['ctx'],
                ]);
                if ($refUser->save()) {
//                    $modx->getService('referrals');
                    setcookie($varCookie, '');
                }
            }

        }

        break;

    case 'msOnGetOrderCost':
        $applySum = $session['applyAccount']['sum'] ?? 0;
//                $modx->log(1, 'APPLY ACCOUNT: ' . $applySum);
        $cost = $modx->event->returnedValues['cost'] ?? $cost;

        if ($cost && $applySum > $cost) {
            $applySum = $cost;
            $session['applyAccount']['sum'] = $cost;
        }
        if ($cost && $applySum) {
            $modx->event->returnedValues['cost'] = $cost - $applySum;
//            $modx->log(1, 'NEW COST: ' . $modx->event->returnedValues['cost']);
        }
        break;

    case 'msOnBeforeCreateOrder':
        $account = $session['applyAccount'] ?? $defaultApplyAccount;
        /** @var int $sum */
        /** @var int $type */
        extract($account);
        /** @var msOrder $msOrder */
        $properties = $msOrder->get('properties');
        $msOrder->set('properties', $referrals->mergeOrderOptions($properties, ['useFromAccount' => $account]));

        break;

    case 'msOnCreateOrder':
        /** @var int $sum */
        /** @var int $type */
//        $propsElem = $referrals->config['orderPropertiesElement'];
//        /** @var msOrder $msOrder */
//        $properties = $msOrder->get('properties');
//        $refProperties = $properties[$propsElem];
//        if ($refProperties['useFromAccount'] && $sum = $refProperties['useFromAccount']['sum']) {
//            $delivery = $msOrder->get('delivery_cost');
//            if ($sum) {
//                $msOrder->set('delivery_cost', $delivery + $sum);
//                $msOrder->save();
//            }
//        }

        break;

    case 'msOnChangeOrderStatus':
        /** @var int $sum */
        /** @var int $type */
        $propsElem = $referrals->config['orderPropertiesElement'];
        /** @var msOrder $order */
        $properties = $order->get('properties');
        $refProperties = $properties[$propsElem];
        if ($refProperties['useFromAccount']) {
            $action = '';
            $type = $refProperties['useFromAccount']['type'];
            $sum = $refProperties['useFromAccount']['sum'];
            $delivery = $order->get('delivery_cost');
            /** @var modUser $user */
            $user = $order->getOne('User');
            $userId = $user ? $user->get('id') : 0;

            switch ($status) {
                case $referrals->config['orderStatuses']['decrease']:
                    $sum = -$sum;
                    $action = refLog::ACTION_ORDER_DECREASE;
                    break;

                case $referrals->config['orderStatuses']['increase']:
                    $action = refLog::ACTION_ORDER_INCREASE;
                    break;

                default:
                    $sum = 0;
            }
            if ($sum and $action) {
                $referrals->updateAccount($userId, $action, $type, $sum, $order->get('id'));
            }

            switch ($status) {
                case $referrals->config['orderStatuses']['reward']:
                    $rewardAction = refLog::ACTION_REWARD;
                    break;

                case $referrals->config['orderStatuses']['revoke']:
                    $rewardAction = refLog::ACTION_REVOKE;
                    break;
            }
            if (!empty($rewardAction)) {
                $referrals->rewardOrder($order, $rewardAction);
            }
        }

        break;

    case 'msOnEmptyCart':
        $session['applyAccount'] = $defaultApplyAccount;

}