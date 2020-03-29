var referrals = function (config) {
    config = config || {};
    referrals.superclass.constructor.call(this, config);
};
Ext.extend(referrals, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('referrals', referrals);

referrals = new referrals();