<input type="hidden" name="referralKey" value="{$key}">
{if $max > 0 and $balance > 0}
<div id="referrals_pay_outer">
    <p>Ваш баланс <span class="referrals_apply_balance">{$balance}</span> {'ms2_frontend_currency' | lexicon}. Для оплаты заказа Вы можете использовать максимум <span class="referrals_apply_available">{$max}</span> {'ms2_frontend_currency' | lexicon}</p>
    <input type="hidden" name="ctx" value="{$ctx}" id="referrals_ctx_input" >
    <div class="form-group">
        <label for="referrals_apply_account_input">Укажите, какую сумму использовать</label>
        <input type="number"
               name="referralApplyAccount"
               id="referrals_apply_account_input"
               value="{$.session.referrals.applyAccount.sum ?: 0}"
               class="form-control"
               data-referralKey="{$key}"
               data-ctx="{$ctx}"
               data-balance="{$balance}"
               data-max="{$max}"
        >
    </div>
    <div class="alert alert-danger" role="alert" id="referrals_apply_error_message" style="display: none">
        Вы ввели сумму, превышающую доступную для использования. Введите не более {$max} {'ms2_frontend_currency' | lexicon}
    </div>
    <div class="alert alert-success" role="alert" id="referrals_apply_success_message" style="display: none">
        Стоимость заказа уменьшена
    </div>
    <button class="btn btn-primary" id="referralsBtnApplyAccount">Применить</button>
</div>
{/if}