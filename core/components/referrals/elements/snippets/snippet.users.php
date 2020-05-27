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

$tpl = $modx->getOption('tpl', $scriptProperties, 'referrals.users');
$ctx = $modx->getOption('ctx', $scriptProperties, $modx->context->key);
$groups = $modx->getOption('groups', $scriptProperties, '');
$accountId = $modx->getOption('accountId', $scriptProperties, $referrals->config['accountReferrals']);
$jsParams = $modx->getOption('jsParams', $scriptProperties, '');

$ms2->initialize($ctx);
$key = md5($modx->toJSON($scriptProperties));
$_SESSION['referrals']['users'][$key] = $scriptProperties;

if (!$user = $referrals->getUser()) {
    return;
}

$users = [];
$userIds = [];

$q = $modx->newQuery('refUser');
$q->where(['refUser.master' => $user->get('id')]);
$q->innerJoin('modUserProfile', 'Profile', 'Profile.internalKey = refUser.user');
$q->select(['Profile.*', 'refUser.*']);
$q->prepare();
$q->stmt->execute();

$usersTmp = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($usersTmp as $userTmp) {
    $users[$userTmp['user']] = $userTmp;
    $userIds[] = $userTmp['user'];
}

if (!empty($userIds)) {
    $q = $modx->newQuery('refLog');
    $q->innerJoin('refAccount', 'refAccount', 'refAccount.id = refLog.account');
    $q->where([
        'refLog.user:IN' => $userIds,
        'refLog.status:IN' => [refLog::STATUS_ACTIVE, refLog::STATUS_REVOKED, ''],
        'refAccount.type:!=' => $referrals->config['accountReferrals'],
    ]);
    $q->select('refLog.user as user, SUM(refLog.delta) as delta');
    $q->groupby('refLog.user');
    $q->prepare();
    $q->stmt->execute();

    $deltas = $q->stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($deltas as $delta) {
        if (isset($users[$delta['user']])) {
            $users[$delta['user']]['delta'] = $delta['delta'];
        }
    }

    $q = $modx->newQuery('refLog');
    $q->innerJoin('refAccount', 'refAccount', 'refAccount.id = refLog.account');
    $q->where([
        'refLog.user:IN' => $userIds,
        'refLog.status:IN' => [refLog::STATUS_ACTIVE, ''],
        'refLog.action:IN' => [refLog::ACTION_INCREASE, refLog::ACTION_ORDER_INCREASE, refLog::ACTION_REGISTER, refLog::ACTION_REGISTER_REFERRAL, refLog::ACTION_REWARD, refLog::ACTION_REWARD_REGISTER],
        'refAccount.type:!=' => $referrals->config['accountReferrals'],
    ]);
    $q->select('refLog.user as user, SUM(refLog.delta) as charge');
    $q->groupby('refLog.referral');
    $q->prepare();
    $modx->log(1, $q->toSQL());
    $q->stmt->execute();

    $deltas = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
    $modx->log(1, 'REFERRALS CHARGES: ' . print_r($deltas, 1));

    foreach ($deltas as $delta) {
        if (isset($users[$delta['referral']])) {
            $users[$delta['user']]['charge'] = $delta['charge'];
        }
    }

    $q = $modx->newQuery('refLog');
    $q->innerJoin('refAccount', 'refAccount', 'refAccount.id = refLog.account');
    $q->where([
        'refLog.user' => $user->get('id'),
        'refLog.referral:IN' => $userIds,
        'refLog.status:IN' => [refLog::STATUS_ACTIVE, ''],
        'refLog.action:IN' => [refLog::ACTION_INCREASE, refLog::ACTION_ORDER_INCREASE, refLog::ACTION_REGISTER, refLog::ACTION_REGISTER_REFERRAL, refLog::ACTION_REWARD, refLog::ACTION_REWARD_REGISTER],
        'refAccount.type:!=' => $referrals->config['accountReferrals'],
    ]);
    $q->select('refLog.referral as referral, SUM(refLog.delta) as income');
    $q->groupby('refLog.referral');
    $q->prepare();
    $modx->log(1, $q->toSQL());
    $q->stmt->execute();

    $deltas = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
    $modx->log(1, 'REFERRALS INCOMES: ' . print_r($deltas, 1));

    foreach ($deltas as $delta) {
        if (isset($users[$delta['referral']])) {
            $users[$delta['referral']]['income'] = $delta['income'];
        }
    }

    $q = $modx->newQuery('msOrder');
    $q->where(['msOrder.user_id:IN' => $userIds]);
    $q->select('user_id as user, SUM(cost) as cost');
    $q->groupby('user');
    $q->prepare();
    $q->stmt->execute();

    $orders = $q->stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        if (isset($users[$order['user']])) {
            $users[$order['user']]['cost'] = $order['cost'];
        }
    }

}

$output = '';
$data = [
    'key' => $key,
    'users' => $users,
];

$output = $pdoTools->getChunk($tpl, $data);
$referrals->registerScripts($jsParams);

return $output;