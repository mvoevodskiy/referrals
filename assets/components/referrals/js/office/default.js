Ext.onReady(function () {
    referrals.config.connector_url = OfficeConfig.actionUrl;

    var grid = new referrals.panel.Home();
    grid.render('office-referrals-wrapper');

    var preloader = document.getElementById('office-preloader');
    if (preloader) {
        preloader.parentNode.removeChild(preloader);
    }
});