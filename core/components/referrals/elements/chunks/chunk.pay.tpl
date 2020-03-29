<div id="referrals_pay_outer">
    <p>Ваш баланс {$balance} {'ms2_frontend_currency' | lexicon}. Для оплаты заказа Вы можете использовать максимум {$max} {'ms2_frontend_currency' | lexicon}</p>
    <form id="referralsFormApplyAccount" data-max="{$max}">
        <input type="hidden" name="referralKey" value="{$key}">
        <input type="hidden" name="ctx" value="{$ctx}" id="referrals_ctx_input" >
        <div class="form-group">
            <label for="referrals_apply_account_input">Укажите, какую сумму использовать</label>
            <input type="number" name="referralApplyAccount" id="referrals_apply_account_input" value="{$.session.referrals.applyAccount.sum ?: 0}" class="form-control">
        </div>
        <div class="alert alert-danger" role="alert" id="referrals_apply_error_message" style="display: none">
            Вы ввели сумму, превышающую доступную для использования. Введите не более {$max} {'ms2_frontend_currency' | lexicon}
        </div>
        <div class="alert alert-success" role="alert" id="referrals_apply_success_message" style="display: none">
            Стоимость заказа уменьшена
        </div>
        <button type="submit" class="btn btn-primary">Применить</button>
    </form>
</div>