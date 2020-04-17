<?php
/** @var modX $modx */
/** @var array $scriptProperties */

/** @var referrals $referrals */
if (!$referrals = $modx->getService('referrals', 'referrals', $modx->getOption('referrals_core_path', null,
        $modx->getOption('core_path') . 'components/referrals/') . 'model/referrals/', $scriptProperties)
) {
    return 'Could not load referrals class!';
}
/** @var pdoFetch $pdoTools*/
$pdoTools = $modx->getService('pdoFetch');
/** @var miniShop2 $ms2 */
$ms2 = $modx->getService('minishop2');

$tpl = $modx->getOption('tpl', $scriptProperties, 'tpl.referrals.pay');
$ctx = $modx->getOption('ctx', $scriptProperties, $modx->context->key);
$groups = $modx->getOption('groups', $scriptProperties, '');
$accountId = $modx->getOption('accountId', $scriptProperties, $referrals->config['accountMoney']);
$jsParams = $modx->getOption('jsParams', $scriptProperties, '');

$ms2->initialize($ctx);
$key = md5($modx->toJSON($scriptProperties));
$_SESSION['referrals']['pay'][$key] = $scriptProperties;

if (!$user = $referrals->getUser()) {
    return;
}
if (!empty($groups)) {
    $sameGroup = false;
    $groups = explode(',', $groups);
    $userGroups = $user->getUserGroupNames();
    foreach ($groups as $group) {
        if (in_array(trim($group), $userGroups)) {
            $sameGroup = true;
            break;
        }
    }
    if (!$sameGroup) {
        return;
    }
}


$output = '';
$data = [
    'key' => $key,
    'balance' => 0,
    'max' => 0,
    'ctx' => $ctx,
];
$cart_status = $ms2->cart->status();

$data['balance'] = $referrals->getBalance($accountId);
$data['max'] = $referrals->getAvailableForUse($accountId, true, $ctx);
if (empty($jsParams) and !$data['max']) {
    return;
}
$output = $pdoTools->getChunk($tpl, $data);
$referrals->registerScripts($jsParams);

return $output;