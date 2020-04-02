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

Referrals.manageShowDetails = function (e) {
    id = 0;
    if (typeof e === 'number') {
        id = e;
    } else {
        e.preventDefault();
        id = $(e.target).data('id');
    }
    $.post(window.location.href, {referrals_action: 'manage/master/details', id: id}, function (data) {
        response = JSON.parse(data);
        if (response.success) {
            $('.referralsManageDetails').hide();
            $('.referralsManageDetails' + id).show();
            console.log(response);
            data = response.object;
            $('.referralsManageDetails' + data.master + '-invited').html(parseInt(data.confirmed || 0) + parseInt(data.notConfirmed || 0));
            $('.referralsManageDetails' + data.master + '-confirmed').html(data.confirmed || 0);
            $('.referralsManageDetails' + data.master + '-paid').html(data.paid || 0);
            referrals = '';
            template = $('#referralsManageDetailsUserTemplate').html();
            for (referral of data.referrals) {
                string = template;
                for (let key in referral) {
                    if (referral.hasOwnProperty(key)) {
                        string = string.split('((' + key + '))').join(referral[key]);
                    }
                }
                referrals = referrals + string;
            }
            $('.referralsManageDetails' + data.master + '-referrals').html(referrals);
        } else {
            Referrals.failure(response.msg);
        }
    })
};

Referrals.manageDetachReferral = function (e) {
    e.preventDefault();
    id = $(e.target).data('id');
    $.post(window.location.href, {referrals_action: 'manage/referral/detach', id: id}, function (data) {
        response = JSON.parse(data);
        if (response.success) {
            parent = document.getElementById('referralsManageDetailsUserRemove' + id);
            if (parent) {
                parent.parentNode.removeChild(parent);
            }
        } else {
            Referrals.failure(response.msg);
        }
    })
};

Referrals.manageDetachReferral = function (e) {
    e.preventDefault();
    id = $(e.target).data('id');
    $.post(window.location.href, {referrals_action: 'manage/referral/attach', id: id}, function (data) {
        response = JSON.parse(data);
        if (response.success) {
            parent = document.getElementById('referralsManageDetailsUserRemove' + id);
            if (parent) {
                parent.parentNode.removeChild(parent);
            }
        } else {
            Referrals.failure(response.msg);
        }
    })
};

Referrals.manageAttachReferral = function (e) {
    e.preventDefault();
    $.post(window.location.href, $(e.target).serializeArray(), function (data) {
        // $.post(window.location.href, {referralApplyAccount: applyAccount, referralKey: key}, function (data) {
        response = JSON.parse(data);
        if (response.success) {
            Referrals.manageShowDetails($(e.target).data('master'));
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
    $('.referralsManageShowMore').on('click', Referrals.manageShowDetails);
    $(document).on('click', '.referralsManageDetailsUserRemove', Referrals.manageDetachReferral);
    $(document).on('submit', '.referralsManageDetailsUserAddForm', Referrals.manageAttachReferral);

});