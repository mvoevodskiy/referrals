<?php
/**
 * Handles adding Component to Extension Packages
 *
 * @var xPDOObject $object
 * @var array $options
 */
if ($object->xpdo) {
	/** @var modX $modx */
	$modx =& $object->xpdo;

	switch ($options[xPDOTransport::PACKAGE_ACTION]) {
		case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
		    foreach (['referrals' => 'Пользователи', 'money' => 'Внутренний счет'] as $name => $caption) {
                if (!$modx->getCount('refAccountType', ['name' => $name])) {
                    $accountType = $modx->newObject('refAccountType', [
                        'name' => $name,
                        'caption' => $caption,
                        'system' => true,1344
                    ]);
                    if ($accountType->save()) {
                        $key = 'referrals_account_type_id_' . $name;
                        $setting = $modx->getObject('modSystemSetting', ['key' => $key]);
                        $setting->set('value', $accountType->get('id'));
                        $setting->save();
                    }
                }
            }
		    break;

		case xPDOTransport::ACTION_UNINSTALL:
			break;
	}
}
return true;