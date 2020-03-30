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

$ctxs = $modx->getCollection('modContext', $ctxKeys ? ['key:IN' => $ctxKeys] : ['key:!=' => 'mgr']);
foreach ($ctxs as $ctx) {
    foreach ($types as $type) {
        $sKey = 'referrals_account_type_id_' . $type;
        $aKey = $ctx->get('key') . '_' . $type;
        /** @var modContextSetting $setting */
        if (!$setting = $modx->getObject('modContextSetting', ['key' => $sKey, 'context_key' => $ctx->get('key')])) {
            if (!$aType = $modx->getObject('refAccountType', ['name' => $aKey])) {
                $aType = $modx->newObject('refAccountType');
                $aType->fromArray([
                    'name' => $aKey,
                    'caption' => ucfirst($type) . ' account for ' . $ctx->get('key') . ' context',
                ]);
                $aType->save();
            }
            $setting = $modx->newObject('modContextSetting');
            $setting->fromArray([
                'context_key' => $ctx->get('key'),
                'key' => $sKey,
                'value' => $aType->get('id'),
                'xtype' => 'numberfield',
                'namespace' => 'referrals',
                'area' => 'referrals_accounts',
            ], '', true);
            $setting->save();
        }
    }
}



echoLog(PHP_EOL . PHP_EOL);
echoLog('[MAX MEMORY] ' . round(memory_get_peak_usage() / 1024 / 1024, 3) . 'M' . PHP_EOL);
echoLog('[TIME] ' . round(microtime(true) - $startTime, 3) . ' s' . PHP_EOL);
echoLog(PHP_EOL . PHP_EOL);