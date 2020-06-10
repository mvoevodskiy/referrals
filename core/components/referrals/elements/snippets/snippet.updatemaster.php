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
$msCtx = $modx->getOption('msCtx', $scriptProperties, $ctx);
$groups = $modx->getOption('groups', $scriptProperties, '');
$accountId = $modx->getOption('accountId', $scriptProperties, $referrals->config['accountMoney']);
$jsParams = $modx->getOption('jsParams', $scriptProperties, '');

$scriptProperties['accountMoney'] = $accountId;
// $modx->log(1, "SNIPPET. CTX $ctx MSCTX $msCtx");

$ms2->initialize($ctx);
$key = md5($modx->toJSON($scriptProperties));
$_SESSION['referrals']['pay'][$key] = $scriptProperties;

$user = $referrals->getUser();
$sameGroup = true;
if ($user && !empty($groups)) {
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
if ($user && $sameGroup) {
    $data['balance'] = $referrals->getBalance($accountId);
    $data['max'] = $referrals->getAvailableForUse($accountId, true, $msCtx);
    if (empty($jsParams) and !$data['max']) {
        $data['max'] = 0;
    }
}
$output = $pdoTools->getChunk($tpl, $data);
$referrals->registerScripts($jsParams);

return $output;