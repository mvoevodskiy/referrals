referrals.panel.Home = function (config) {
    config = config || {};
    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        layout: 'anchor',
        /*
         stateful: true,
         stateId: 'referrals-panel-home',
         stateEvents: ['tabchange'],
         getState:function() {return {activeTab:this.items.indexOf(this.getActiveTab())};},
         */
        hideMode: 'offsets',
        items: [{
            html: '<h2>' + _('referrals') + '</h2>',
            cls: '',
            style: {margin: '15px 0'}
        }, {
            xtype: 'modx-tabs',
            defaults: {border: false, autoHeight: true},
            border: true,
            hideMode: 'offsets',
            items: [{
                title: _('referrals_items'),
                layout: 'anchor',
                items: [{
                    html: _('referrals_intro_msg'),
                    cls: 'panel-desc',
                }, {
                    xtype: 'referrals-grid-items',
                    cls: 'main-wrapper',
                }]
            }]
        }]
    });
    referrals.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(referrals.panel.Home, MODx.Panel);
Ext.reg('referrals-panel-home', referrals.panel.Home);
