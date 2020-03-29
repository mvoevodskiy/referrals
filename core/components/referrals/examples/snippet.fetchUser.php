<?php

/** @var modX $modx */
/** @var array $scriptProperties */
/** @var referrals $referrals */
if (!$referrals = $modx->getService('referrals', 'referrals', $modx->getOption('referrals_core_path', null,
        $modx->getOption('core_path') . 'components/referrals/') . 'model/referrals/', $scriptProperties)
) {
    return 'Could not load referrals class!';
}
/** @var pdoFetch $pdo */
$pdo = $modx->getService('pdoFetch');

$tpl = $modx->getOption('tpl', $scriptProperties, '@INLINE {$fullname}: {if $confirmed}Телефон подтвержден{/if}');

$q = $modx->newQuery('modUserProfile');
$q->innerJoin('refUser', 'refUser', 'refUser.user = modUserProfile.internalKey');
$q->where(['modUserProfile.internalKey' => $modx->user->id]);
$q->prepare();
$q->stmt->execute();
$rows = $q->stmt->fetchAll(PDO::FETCH_ASSOC);

return $pdo->getChunk($tpl, $rows);