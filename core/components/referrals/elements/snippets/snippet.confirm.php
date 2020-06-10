<?php
/** @var modX $modx */
/** @var array $scriptProperties */

$key = $modx->getOption('referralKey', $_POST, md5($modx->toJSON($scriptProperties)));
if (isset($_SESSION['referrals']['confirm'][$key])) {
    $scriptProperties = $_SESSION['referrals']['confirm'][$key];
} else {
    $_SESSION['referrals']['confirm'][$key] = $scriptProperties;
}

/** @var referrals $referrals */
if (!$referrals = $modx->getService('referrals', 'referrals', $modx->getOption('referrals_core_path', null,
        $modx->getOption('core_path') . 'components/referrals/') . 'model/referrals/', $scriptProperties)
) {
    return 'Could not load referrals class!';
}
$pdo = $modx->getService('pdoFetch');
$parser = $pdo ?? $modx;
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
$processed = false;

$tpl = $modx->getOption('tpl', $scriptProperties, 'referrals.memberZone');
$jsParams = $modx->getOption('jsParams', $scriptProperties, '');
$needOuter = $modx->getOption('needOuter', $scriptProperties, !$isAjax);

$data = [
    'key' => $key,
    'needOuter' => $needOuter,
    'sent' => false,
    'confirmed' => false,
    'balanceMoney' => 0,
    'balanceReferrals' => 0,
    'refId' => '',
    'msg' => '',
];

if (isset($_POST['referralsSendCode'])) {
    $fields = $_POST;
    unset($fields['referralsSendCode']);
    $result = $referrals->sendCode($fields);
    if ($result === true and !$isAjax) {
        $modx->sendRedirect($modx->makeUrl($modx->resource->get('id')));
    } elseif ($result !== true) {
        $data['msg'] = $result;
    }
    $processed = true;
}

if ($_POST['referralsConfirmCode'] ?? false) {
    $result = $referrals->confirmCode($_POST['referralsConfirmCode']);
    if ($result === true and !$isAjax) {
        $modx->sendRedirect($modx->makeUrl($modx->resource->get('id')));
    } elseif ($result !== true) {
        $data['msg'] = $result;
    }
    $processed = true;
}

if ($user = $referrals->getUser()) {

    if ($profile = $user->getOne('Profile')) {
        $extended = $profile->get('extended');
        $data['sent'] = $extended['referrals']['confirmCode'] ?? false;
    }

    $refUser = $modx->getObject('refUser', ['user' => $user->get('id')]);
    if ($refUser) {
        $data['refId'] = $refUser->get('refId');
        $data['confirmed'] = $refUser->get('confirmed');
        if ($account = $modx->getObject('refAccount', ['user' => $refUser->get('user'), 'type' => $referrals->config['accountMoney']])) {
            $data['balanceMoney'] = $account->get('balance');
        }
        if ($account = $modx->getObject('refAccount', ['user' => $refUser->get('user'), 'type' => $referrals->config['accountReferrals']])) {
            $data['balanceReferrals'] = $account->get('balance');
        }

    }
}

$html = $parser->getChunk($tpl, $data);

if ($isAjax && $processed) {
    @session_write_close();
    exit($modx->toJSON([
        'success' => empty($data['msg']),
        'html' => $html,
        'msg' => $data['msg'],
        'data' => $data,
    ]));
} else {
    $referrals->registerScripts($jsParams);
    return $html;
}