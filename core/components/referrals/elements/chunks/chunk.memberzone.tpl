{if $needOuter ?}<div id="referralsMemberOuter">{/if}
    {if $confirmed ?}
        <ul>
            <li>Баланс: {$balanceMoney}</li>
            <li>Приглашено: {$balanceReferrals}</li>
            <li>
                Приглашенные пользователи:
                <ul>
                    {$_modx->runSnippet('pdoUsers', [
                    'loadModels' => 'referrals',
                    'innerJoin' => '{"refUser":{"class":"refUser", "on":"refUser.user = modUser.id and refUser.master = \'' ~ $_modx->user.id ~ '\'"}}',
                    'select' => '{"refUser":"confirmed"}',
                    'groupby' => '',
                    'tpl' => '@INLINE {$fullname ?: $username}',
                    'showLog' => false,
                    ])}
                </ul>
            </li>
        </ul>
    {elseif $sent ?}
        <form id="referralsConfirm" method="post">
            {if $msg ?} <p class="referrals_error">{$msg}</p> {/if}
            <div class="form-group">
                <label for="referrals_confirm_code_input">Введите полученный код подтверждения</label>
                <input type="number" name="referralsConfirmCode" id="referrals_confirm_code_input" class="form-control" placeholder="Код подтверждения">
            </div>
            <input type="hidden" name="referralKey" value="{$key}">
            <button type="submit" class="btn btn-primary">Подтвердить</button>
        </form>
    {else}
        <form id="referralsConfirm" method="post">
            {if $msg ?} <p class="referrals_error">{$msg}</p> {/if}
            <div class="form-group">
                <label for="referrals_phone_input">Введите номер телефона</label>
                <input type="text" name="phone" id="referrals_phone_input" value="{$.post.phone ?: $_modx->user.phone}" class="form-control" placeholder="Номер телефона">
                <small id="referrals_phone_help" class="form-text text-muted">На указанный номер телефона будет отправлено SMS с кодом подтверждения</small>
            </div>
            <button type="submit" class="btn btn-primary">Получить код</button>
            <input type="hidden" name="referralsSendCode" value="1">
            <input type="hidden" name="referralKey" value="{$key}">
        </form>
    {/if}
{if $needOuter ?}</div>{/if}
