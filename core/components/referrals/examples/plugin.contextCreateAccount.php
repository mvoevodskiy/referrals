<?php
/** @var modX $modx */
/** @var array $scriptProperties */
/** @var modContext $context */

switch ($modx->event->name) {
    case 'OnContextSave':
        if ($modx === modSystemEvent::MODE_NEW) {
            if (!$referrals = $modx->getService('referrals', 'referrals', $modx->getOption('referrals_core_path', null,
                    $modx->getOption('core_path') . 'components/referrals/') . 'model/referrals/', $scriptProperties)
            ) {
                return 'Could not load referrals class!';
            }
            $types = [
                'money',
                'referrals',
            ];
            foreach ($types as $type) {
                $sKey = 'referrals_account_type_id_' . $type;
                $aKey = $context->get('key') . '_' . $type;
                /** @var modContextSetting $setting */
                if (!$aType = $modx->getObject('refAccountType', ['name' => $aKey])) {
                    $aType = $modx->newObject('refAccountType');
                    $aType->fromArray([
                        'name' => $aKey,
                        'caption' => ucfirst($type) . ' account for ' . $context->get('key') . ' context',
                    ]);
                    $aType->save();
                }
                $setting = $modx->newObject('modContextSetting');
                $setting->fromArray([
                    'context_key' => $context->get('key'),
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