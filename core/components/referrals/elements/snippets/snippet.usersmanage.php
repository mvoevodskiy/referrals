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

$tpl = $modx->getOption('tpl', $scriptProperties, 'referrals.usersManage');
$ctx = $modx->getOption('ctx', $scriptProperties, $modx->context->key);
$page = $modx->getOption('page', $scriptProperties, 1);
$limit = $modx->getOption('limit', $scriptProperties, 10);
$groups = $modx->getOption('groups', $scriptProperties, '');
$totalVar = $modx->getOption('totalVar', $scriptProperties, 'page.total');
$accountId = $modx->getOption('accountId', $scriptProperties, $referrals->config['accountReferrals']);
$jsParams = $modx->getOption('jsParams', $scriptProperties, '');
$sortDir = $modx->getOption('sortdir', $scriptProperties, $modx->getOption('sortdir', $_REQUEST, 'DESC'));

$ms2->initialize($ctx);
$key = md5($modx->toJSON($scriptProperties));
$_SESSION['referrals']['usersManage'][$key] = $scriptProperties;

if (!$user = $referrals->getUser()) {
    return;
}

$users = [];
$userIds = [];

$q = $modx->newQuery('refUser');
$q->innerJoin('modUserProfile', 'Profile', 'Profile.internalKey = refUser.master');
$q->select(['Profile.*', 'refUser.*', 'COUNT(refUser.user) AS `count`']);
$q->groupby('refUser.master');
$q->sortby('count', $sortDir);
$q->where(['refUser.master:!=' => 0]);

$total = $modx->getCount('refUser', $q);
$modx->setPlaceholder($totalVar, $total);

$q->limit($limit, ($page - 1) * $limit);
$q->prepare();
$q->stmt->execute();

$usersTmp = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($usersTmp as $userTmp) {
    $users[$userTmp['user']] = $userTmp;
    $userIds[] = $userTmp['user'];
}

if (!empty($userIds)) {
    $q = $modx->newQuery('refLog');
    $q->where([
        'refLog.user:IN' => $userIds,
        'refLog.status:IN' => [refLog::STATUS_ACTIVE, refLog::STATUS_REVOKED, '']
    ]);
    $q->select('user, SUM(delta) as delta');
    $q->groupby('user');
    $q->prepare();
    $q->stmt->execute();

    $deltas = $q->stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($deltas as $delta) {
        if (isset($users[$delta['user']])) {
            $users[$delta['user']]['delta'] = $delta['delta'];
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
    'newSortDir' => $sortDir === 'ASC' ? 'DESC' : 'ASC',
];

$output = $pdoTools->getChunk($tpl, $data);
$referrals->registerScripts($jsParams);

return $output;