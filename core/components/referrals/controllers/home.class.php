<?php

/**
 * The home manager controller for referrals.
 *
 */
class referralsHomeManagerController extends modExtraManagerController
{
    /** @var referrals $referrals */
    public $referrals;


    /**
     *
     */
    public function initialize()
    {
        $path = $this->modx->getOption('referrals_core_path', null,
                $this->modx->getOption('core_path') . 'components/referrals/') . 'model/referrals/';
        $this->referrals = $this->modx->getService('referrals', 'referrals', $path);
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return array('referrals:default');
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('referrals');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        $this->addCss($this->referrals->config['cssUrl'] . 'mgr/main.css');
        $this->addCss($this->referrals->config['cssUrl'] . 'mgr/bootstrap.buttons.css');
        $this->addJavascript($this->referrals->config['jsUrl'] . 'mgr/referrals.js');
        $this->addJavascript($this->referrals->config['jsUrl'] . 'mgr/misc/utils.js');
        $this->addJavascript($this->referrals->config['jsUrl'] . 'mgr/misc/combo.js');
        $this->addJavascript($this->referrals->config['jsUrl'] . 'mgr/widgets/items.grid.js');
        $this->addJavascript($this->referrals->config['jsUrl'] . 'mgr/widgets/items.windows.js');
        $this->addJavascript($this->referrals->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->referrals->config['jsUrl'] . 'mgr/sections/home.js');

        $this->addHtml('<script type="text/javascript">
        referrals.config = ' . json_encode($this->referrals->config) . ';
        referrals.config.connector_url = "' . $this->referrals->config['connectorUrl'] . '";
        Ext.onReady(function() {
            MODx.load({ xtype: "referrals-page-home"});
        });
        </script>
        ');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->referrals->config['templatesPath'] . 'home.tpl';
    }
}