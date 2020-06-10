<?php
/** @var modX $modx */
/** @var array $sources */

$settings = array();

$tmp = array(
    'account_type_id_referrals' => array(
        'xtype' => 'numberfield',
        'value' => 0,
        'area' => 'referrals_accounts',
    ),
    'account_type_id_money' => array(
        'xtype' => 'numberfield',
        'value' => 0,
        'area' => 'referrals_accounts',
    ),

    'use_limit' => array(
        'xtype' => 'textfield',
        'value' => '100%',
        'area' => 'referrals_values',
    ),
    'level_rewards' => array(
        'xtype' => 'textfield',
        'value' => '{}',
        'area' => 'referrals_values',
    ),
    'reward_register_master' => array(
        'xtype' => 'numberfield',
        'value' => 0,
        'area' => 'referrals_values',
    ),
    'reward_register_user' => array(
        'xtype' => 'numberfield',
        'value' => 0,
        'area' => 'referrals_values',
    ),
    'reward_register_user_without_master' => array(
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'referrals_values',
    ),



    'order_status_decrease' => array(
        'xtype' => 'numberfield',
        'value' => 1,
        'area' => 'referrals_statuses',
    ),
    'order_status_increase' => array(
        'xtype' => 'numberfield',
        'value' => 4,
        'area' => 'referrals_statuses',
    ),
    'order_status_reward' => array(
        'xtype' => 'numberfield',
        'value' => 2,
        'area' => 'referrals_statuses',
    ),
    'order_status_revoke' => array(
        'xtype' => 'numberfield',
        'value' => 4,
        'area' => 'referrals_statuses',
    ),


//    'confirm_field' => array(
//        'xtype' => 'textfield',
//        'value' => 'phone',
//        'area' => 'referrals_fields',
//    ),
    'level_rewards_product_field' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'referrals_fields',
    ),
    'use_limit_product_field' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'referrals_fields',
    ),

    'var_url' => array(
        'xtype' => 'textfield',
        'value' => 'ref',
        'area' => 'referrals_main',
    ),
    'var_cookie' => array(
        'xtype' => 'textfield',
        'value' => 'referrals',
        'area' => 'referrals_cookie',
    ),
    'cookie_domain' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'referrals_cookie',
    ),
    'action_script' => array(
        'xtype' => 'textfield',
        'value' => '{assets_path}components/referrals/action.php',
        'area' => 'referrals_main',
    ),
    'frontend_js' => array(
        'xtype' => 'textfield',
        'value' => '[[+jsUrl]]web/default.js',
        'area' => 'referrals_main',
    ),
    'sms_login' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'referrals_main',
    ),
    'sms_password' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'referrals_main',
    ),

    'cookie_ttl' => array(
        'xtype' => 'numberfield',
        'value' => 30,
        'area' => 'referrals_ttl',
    ),
    'reward_ttl' => array(
        'xtype' => 'numberfield',
        'value' => 1095,
        'area' => 'referrals_ttl',
    ),
    'referral_ttl' => array(
        'xtype' => 'numberfield',
        'value' => 1095,
        'area' => 'referrals_ttl',
    ),
);

foreach ($tmp as $k => $v) {
    /** @var modSystemSetting $setting */
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray(array_merge(
        array(
            'key' => 'referrals_' . $k,
            'namespace' => PKG_NAME_LOWER,
        ), $v
    ), '', true, true);

    $settings[] = $setting;
}
unset($tmp);

return $settings;
