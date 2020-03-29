<p>Рейтинг пользователей по количеству пригласивших (поле balance)</p>

{$_modx->runSnippet('pdoUsers', [
    'loadModels' => 'referrals',
    'innerJoin' => '{"refAccount":{"class":"refAccount", "on":"refAccount.user = modUser.id"}}',
    'where' => '{"refAccount.type":"' ~ $_modx->config.referrals_account_type_id_referrals ~ '"}',
    'select' => '{"refAccount":"balance"}',
    'sortby' => 'refAccount.balance',
    'groupby' => '',
    'showLog' => true
])}



<p>Рейтинг пользователей по величине основного денежного счета (поле balance)</p>

{$_modx->runSnippet('pdoUsers', [
    'loadModels' => 'referrals',
    'innerJoin' => '{"refAccount":{"class":"refAccount", "on":"refAccount.user = modUser.id"}}',
    'where' => '{"refAccount.type":"' ~ $_modx->config.referrals_account_type_id_money ~ '"}',
    'select' => '{"refAccount":"balance"}',
    'sortby' => 'refAccount.balance',
    'sortdir' => 'desc',
    'groupby' => '',
    'showLog' => true
])}



<p>Список приведенных пользователей</p>

{$_modx->runSnippet('pdoUsers', [
    'loadModels' => 'referrals',
    'innerJoin' => '{"refUser":{"class":"refUser", "on":"refUser.user = modUser.id and refUser.master = \'' ~ $_modx->user.id ~ '\'"}}',
    'select' => '{"refUser":"confirmed"}',
    'groupby' => '',
    'tpl' => '@INLINE {$fullname ?: $username}',
    'showLog' => false,
])}


<p>Список приведенных пользователей, подтвердивших номер телефона</p>

{$_modx->runSnippet('pdoUsers', [
    'loadModels' => 'referrals',
    'innerJoin' => '{"refUser":{"class":"refUser", "on":"refUser.user = modUser.id and refUser.master = \'' ~ $_modx->user.id ~ '\'"}}',
    'select' => '{"refUser":"confirmed"}',
    'where' => '{"refUser.confirmed":1}',
    'groupby' => '',
    'tpl' => '@INLINE {$fullname ?: $username}',
    'showLog' => false,
])}



<p>Список участников</p>

{$_modx->runSnippet('pdoUsers', [
    'loadModels' => 'referrals',
    'innerJoin' => '{"refUser":{"class":"refUser", "on":"refUser.user = modUser.id"}}',
    'where' => '{"refUser.master":0,"refUser.confirmed":1}',
    'groupby' => '',
    'showLog' => true
])}


<p>Вывод баланса пользователя (поле balance)</p>

{$_modx->runSnippet('pdoUsers', [
    'loadModels' => 'referrals',
    'innerJoin' => '{"refAccount":{"class":"refAccount", "on":"refAccount.user = modUser.id and refAccount.user = \'' ~ $_modx->user.id ~ '\'"}}',
    'where' => '{"refAccount.type":"' ~ $_modx->config.referrals_account_type_id_money ~ '"}',
    'select' => '{"refAccount":"balance"}',
    'sortby' => 'refAccount.balance',
    'groupby' => '',
    'showLog' => true
])}


<p>Вывод количества приглашенных пользователей текущим (поле balance)</p>

{$_modx->runSnippet('pdoUsers', [
    'loadModels' => 'referrals',
    'innerJoin' => '{"refAccount":{"class":"refAccount", "on":"refAccount.user = modUser.id and refAccount.user = \'' ~ $_modx->user.id ~ '\'"}}',
    'where' => '{"refAccount.type":"' ~ $_modx->config.referrals_account_type_id_referrals ~ '"}',
    'select' => '{"refAccount":"balance"}',
    'sortby' => 'refAccount.balance',
    'groupby' => '',
    'showLog' => true
])}