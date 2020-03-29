referrals.page.Home = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [{
            xtype: 'referrals-panel-home',
            renderTo: 'referrals-panel-home-div'
        }]
    });
    referrals.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(referrals.page.Home, MODx.Component);
Ext.reg('referrals-page-home', referrals.page.Home);