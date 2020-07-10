<form class="referralsUpdateMasterForm">
    <input type="hidden" name="referralKey" value="{$key}">
    <input type="hidden" name="referrals_action" value="master/update">

    <label>Введите реферальный код: <input type="text" id="referralsRefId" name="refId"></label>
    <div class="alert alert-danger" role="alert" id="referrals_apply_error_message" style="display: none">
        Введен некорректный код
    </div>
    <div class="alert alert-success" role="alert" id="referrals_apply_success_message" style="display: none">
        Привязка изменена успешно
    </div>
    <button type="submit">Отправить</button>
</form>
