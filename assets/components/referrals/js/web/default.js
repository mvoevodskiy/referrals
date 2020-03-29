window.Referrals = window.Referrals || {};

Referrals.config = Referrals.config || {};

Referrals.success = function (msg) {
    Referrals.notice(true, msg);
};

Referrals.failure = function (msg) {
    Referrals.notice(false, msg);
};

Referrals.notice = function (success, msg) {
    if (miniShop2 !== undefined) {
        var type;
        if (success) {
            type = 'success';
        } else {
            type = 'error';
        }
        miniShop2.Message[type](msg);
    } else {
        var el;
        if (success) {
            el = $('#referrals_apply_error_message');
        } else {
            el = $('#referrals_apply_success_message');
        }
        el.html(msg);
        el.show();
        setTimeout(function () {
            el.hide();
        }, 5000);
    }

};

Referrals.showPayForm = function(show) {
    outer = $('#referrals_pay_outer');
    if (show) {
        outer.show();
    } else {
        outer.hide();
    }
};

Referrals.sendConfirmForm = function (e) {
    e.preventDefault();
    $.post(window.location.href, $(e.target).serializeArray(), function (data) {
        // $.post(window.location.href, {referralApplyAccount: applyAccount, referralKey: key}, function (data) {
        response = JSON.parse(data);
        if (response.success) {
            $('#referralsMemberOuter').html(response.html);
            $('#referralsConfirm').on('submit', Referrals.sendConfirmForm);
        } else {
            Referrals.failure(response.msg);
        }
    });
};


jQuery(document).ready(function($) {
    $('#referralsFormApplyAccount').on('submit', function (e) {
        e.preventDefault();
        var applyAccountInput = $('#referrals_apply_account_input');
        var applyAccount = parseInt(applyAccountInput.val());
        var ctxInput = $('#referrals_ctx_input');
        var ctx = parseInt(ctxInput.val());
        var max = parseInt(applyAccountInput.parent().parent().data('max'));
        if (applyAccount <= max || Referrals.config.costCheckByServer) {
            var key =
                $.post(window.location.href, $(e.target).serializeArray(), function (data) {
                // $.post(window.location.href, {referralApplyAccount: applyAccount, referralKey: key}, function (data) {
                    response = JSON.parse(data);
                    if (response.success) {
                        miniShop2.Order.getcost();
                        Referrals.success('Сумма заказа уменьшена');
                    } else {
                        Referrals.failure(response.msg);
                    }
                });
        } else {
            Referrals.failure('Вы ввели сумму, превышающую доступную для использования');
        }
    });

    $('#referralsConfirm').on('submit', Referrals.sendConfirmForm);

});