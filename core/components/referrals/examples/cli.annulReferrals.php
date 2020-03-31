<?php

$ctxKeys = [

];
$types = [
    'money',
    'referrals',
];

$startTime = microtime(true);
define('MODX_API_MODE', true);

$dir = dirname(__FILE__);
$subdirs = array('', 'www');
$subdir = '';

for ($i = 0; $i <= 10; $i++) {
    foreach ($subdirs as $subdir) {
        $path = $dir . '/' . $subdir;
        if (file_exists($path) and file_exists($path . 'index.php')) {
            require_once $path . 'index.php';
            break 2;
        }
    }
    $dir = dirname($dir . '/');
}

// Включаем обработку ошибок
$modx->getService('error', 'error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');
//$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');
$modx->error->message = null; // Обнуляем переменную
/** @var miniShop2 $ms2 */
$ms2 = $modx->getService('minishop2');
/** @var referrals $referrals */
if (!$referrals = $modx->getService('referrals', 'referrals', $modx->getOption('referrals_core_path', null,
        $modx->getOption('core_path') . 'components/referrals/') . 'model/referrals/', $scriptProperties)
) {
    return 'Could not load referrals class!';
}

error_reporting(E_ALL);
$err = "";

function echoLog($msg, $data = [])
{
    if ($data) {
        $msg .= ' [DATA] ' . print_r($data, 1);
    }
    echo '[' . date('H:i:s') . '] ' . $msg;
    ob_flush();
}

$date = date('Y-m-d', time() - $referrals->config['ttl']['referral'] * 3600 * 24);
$q = $modx->newQuery('refUser');
$q->innerJoin('refLog', 'refLog', 'refUser.id = refLog.user');
$q->where([
    'refLog.action' => refLog::ACTION_REGISTER,
    'refUser.master:>' => 0,
    'refLog.occurred:<' => $date,
]);
$users = $modx->getIterator('refUser', $q);
foreach ($users as $user) {
    $user->set('master', 0);
    $user->save();
    refLog::write($modx, ['user' => $user->get('user'), 'action' => refLog::ACTION_ANNUL_REFERRAL]);
}

echoLog(PHP_EOL . PHP_EOL);
echoLog('[MAX MEMORY] ' . round(memory_get_peak_usage() / 1024 / 1024, 3) . 'M' . PHP_EOL);
echoLog('[TIME] ' . round(microtime(true) - $startTime, 3) . ' s' . PHP_EOL);
echoLog(PHP_EOL . PHP_EOL);