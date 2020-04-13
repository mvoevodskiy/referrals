<?php

switch ($modx->event->name) {
    case 'OnDocFormSave':
        $field = $modx->getOption('referrals_level_rewards_product_field');
        if ($resource->class_key == 'msProduct' && $field) {
            $rewardSelf = $resource->getTVValue('referrals_reward_self');
            $rewardMaster = $resource->getTVValue('referrals_reward_master');

            $rewards = [];
            if ($rewardSelf || $rewardSelf === '0') {
                $rewards['self'] = $rewardSelf;
            }
            if ($rewardMaster || $rewardMaster === '0') {
                $rewards['1'] = $rewardMaster;
            }

            $resource->set($field, $modx->toJSON($rewards));
            $resource->save();
        }

}