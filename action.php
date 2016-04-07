<?php
/**
 * DokuWiki Action Plugin LoadSkin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Klier <chi@chimeric.de>
 * @author     Anika Henke <anika@selfthinker.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if(!defined('DOKU_LF')) define('DOKU_LF', "\n");

require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to interfere with the event system
 * need to inherit from this class
 */
class action_plugin_loadskin extends DokuWiki_Action_Plugin {

    // register hook
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, '_handleConf');
        $controller->register_hook('MEDIAMANAGER_STARTED', 'BEFORE', $this, '_handleConf');
        $controller->register_hook('DETAIL_STARTED', 'BEFORE', $this, '_handleConf');
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, '_handleContent', array());
        // only needed for not yet up-to-date templates:
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, '_defineConstants');
        $controller->register_hook('MEDIAMANAGER_STARTED', 'AFTER', $this, '_defineConstants');
        $controller->register_hook('DETAIL_STARTED', 'AFTER', $this, '_defineConstants');
    }

    /**
     * Define DOKU_TPL and DOKU_TPLINC after $conf['template'] has been overwritten
     *  (this still needs the original constant definition in init.php to be removed)
     * @deprecated DOKU_TPL and DOKU_TPLINC are deprecated since Adora Belle
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    public function _defineConstants(Doku_Event $event, $param) {
        global $conf;

        // define Template baseURL
        if(!defined('DOKU_TPL'))
            define('DOKU_TPL', DOKU_BASE.'lib/tpl/'.$conf['template'].'/');

        // define real Template directory
        if(!defined('DOKU_TPLINC'))
            define('DOKU_TPLINC', DOKU_INC.'lib/tpl/'.$conf['template'].'/');
    }

    /**
     * Overwrites the $conf['template'] setting
     *
     * @author Michael Klier <chi@chimeric.de>
     * @author Anika Henke <anika@selfthinker.org>
     */
    public function _handleConf(Doku_Event $event, $param) {
        global $conf;
        global $ACT;

        // store original template in helper attribute
        $helper = $this->loadHelper('loadskin', true);
        $helper->origTpl = $conf['template'];

        // set template
        $tpl = $this->_getTpl();
        $inAdmin = $ACT == 'admin';
        $allowInAdmin = $this->getConf('allowInAdmin');
        if($tpl && (!$inAdmin || ($inAdmin && $allowInAdmin))) {
            $conf['template'] = $tpl;
        }
    }

    /**
     * Output the template switcher if 'automaticOutput' is on
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    public function _handleContent(Doku_Event $event, $param){
        // @todo: should ideally be in showTemplateSwitcher()
        $isOverwrittenByAdmin = !$this->getConf('preferUserChoice') && $this->_getTplPerNamespace();

        if ($this->getConf('automaticOutput') && !$isOverwrittenByAdmin) {
            $helper = $this->loadHelper('loadskin', true);
            $event->data = $helper->showTemplateSwitcher().$event->data;
        }
    }

    /**
     * Checks if a given page should use a different template then the default
     *
     * @author Michael Klier <chi@chimeric.de>
     * @author Anika Henke <anika@selfthinker.org>
     */
    private function _getTpl() {
        $tplPerUser = $this->_getTplPerUser();
        $tplPerNamespace = $this->_getTplPerNamespace();

        if($this->getConf('preferUserChoice')) {
            if($tplPerUser)
                return $tplPerUser;
            if($tplPerNamespace)
                return $tplPerNamespace;
        } else {
            if($tplPerNamespace)
                return $tplPerNamespace;
            if($tplPerUser)
                return $tplPerUser;
        }

        return false;
    }

    /**
     * Get template from session and/or user config
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    private function _getTplPerUser() {
        global $INPUT;

        // get all available templates
        $helper = $this->loadHelper('loadskin', true);
        $tpls   = $helper->getTemplates();

        $mobileSwitch = $this->getConf('mobileSwitch');
        $user = $_SERVER['REMOTE_USER'];

        $tplRequest = $INPUT->str('tpl');
        $actSelect  = $INPUT->str('act') && ($INPUT->str('act') == 'select');

        // if template switcher was used
        if ($tplRequest && $actSelect && (in_array($tplRequest, $tpls) || ($tplRequest == '*') )) {
            // hidden way of deleting the cookie and config values
            if ($tplRequest == '*')
                $tplRequest = false; // not backwards-compatible, will only work with core PR #1129
            // store in cookie
            set_doku_pref('loadskinTpl', $tplRequest);
            // if registered user, store also in conf file (not for mobile switcher)
            if ($user && !$mobileSwitch)
                $this->_tplUserConfig('set', $user, $tplRequest);
            return $tplRequest;
        }

        $tplUser   = $this->_tplUserConfig('get', $user);// from user conf file
        $tplCookie = get_doku_pref('loadskinTpl', '');
        // if logged in and user is in conf (not for mobile)
        if ($user && $tplUser && in_array($tplUser, $tpls) && !$mobileSwitch){
            if ($tplCookie && ($tplCookie == $tplUser))
                return $tplCookie;
            // store in cookie
            set_doku_pref('loadskinTpl', $tplUser);
            return $tplUser;
        }
        // if template is stored in cookie
        if ($tplCookie && in_array($tplCookie, $tpls))
            return $tplCookie;

        // if viewed on a mobile and mobile switcher is used, set mobile template as default
        global $INFO;
        $mobileTpl = $this->getConf('mobileTemplate');
        if ($mobileTpl && $INFO['ismobile']) {
            set_doku_pref('loadskinTpl', $mobileTpl);
            return $mobileTpl;
        }

        return false;
    }

    /**
     * Get template from namespace/page and config
     *
     * @author Michael Klier <chi@chimeric.de>
     * @author Anika Henke <anika@selfthinker.org>
     */
    private function _getTplPerNamespace() {
        global $ID;
        $config = DOKU_CONF.'loadskin.conf';

        if(@file_exists($config)) {
            $data = unserialize(io_readFile($config, false));
            $id   = $ID;

            // remove language path from $id before you check for a match (it would only be at the start)
            if ($this->getConf('inheritInTranslations') && !plugin_isdisabled('translation')) {
                $transplugin = &plugin_load('helper', 'translation');
                $langPath = $transplugin->getLangPart($id).':';
                $pos = strpos($id, $langPath);
                if (($pos !== false) && ($pos == 0))
                    $id = str_ireplace($langPath, '', $id);
            }

            if($data[$id]) return $data[$id];

            $path  = explode(':', $id);

            while(count($path) > 0) {
                $id = implode(':', $path);
                if($data[$id]) return $data[$id];
                array_pop($path);
            }
        }
        return false;
    }

    /**
     * Get/set template for user in config
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    private function _tplUserConfig($act, $user, $tpl='') {
        $data = array();
        $userConf = DOKU_CONF.'loadskin.users.conf';
        if(@file_exists($userConf)) {
            $data = unserialize(io_readFile($userConf, false));
            if ($act == 'get')
                return $data[$user];
            unset($data[$user]);
        }
        if ($act == 'get')
            return false;
        // keep line deleted if $tpl is empty
        if ($tpl)
            $data[$user] = $tpl;
        io_saveFile($userConf, serialize($data));
    }
}

// vim:ts=4:sw=4:
