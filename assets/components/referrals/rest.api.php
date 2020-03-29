<?php
// Boot up MODX
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
} else {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
}
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('web');
$modx->getService('error','error.modError', '', '');
header("Access-Control-Allow-Origin: *");
// Boot up any service classes or packages (models) you will need
$path = $modx->getOption('referrals.core_path', null,
        $modx->getOption('core_path').'components/referrals/') . 'model/referrals/';
/** @var referrals $referrals */
$referrals = $modx->getService('referrals', 'referrals', $path);
// Load the modRestService class and pass it some basic configuration
/** @var modRestService $rest */
$rest = $modx->getService('rest', 'referralsRestService', $referrals->config['corePath'] . 'model/referrals/', array(
    'basePath' => $referrals->config['corePath'] . 'rest/controllers/',
    'controllerClassSeparator' => '',
    'controllerClassPrefix' => 'referrals',
    'xmlRootNode' => 'response',
));
require_once $referrals->config['corePath'] . 'rest/basecontroller.php';
// Prepare the request
$rest->prepare();
// Make sure the user has the proper permissions, send the user a 401 error if not
if (!$rest->checkPermissions()) {
    $rest->sendUnauthorized(true);
}
// Run the request
$rest->process();