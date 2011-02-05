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
    function register(&$controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, '_handleConf');
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, '_defineConstants');
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_handleMeta');
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, '_handleContent', array());
    }

    /**
     * Define DOKU_TPL and DOKU_TPLINC after $conf['template'] has been overwritten
     *  (this still needs the original constant definition in init.php to be removed)
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    function _defineConstants(&$event, $param) {
        global $conf;

        // define Template baseURL
        define('DOKU_TPL', DOKU_BASE.'lib/tpl/'.$conf['template'].'/');

        // define real Template directory
        define('DOKU_TPLINC', DOKU_INC.'lib/tpl/'.$conf['template'].'/');
    }

    /**
     * Overwrites the $conf['template'] setting
     *
     * @author Michael Klier <chi@chimeric.de>
     * @author Anika Henke <anika@selfthinker.org>
     */
    function _handleConf(&$event, $param) {
        global $conf;

        $tpl = $this->getTpl();

        if($tpl && $_REQUEST['do'] != 'admin') {
            $conf['template'] = $tpl;
        }
    }

    /**
     * Replaces the style headers with a different skin if specified in the
     * configuration
     *
     * @author Michael Klier <chi@chimeric.de>
     * @author Anika Henke <anika@selfthinker.org>
     */
    function _handleMeta(&$event, $param) {
        $tpl = $this->getTpl();

        if($tpl && $_REQUEST['do'] != 'admin') {
            $head =& $event->data;
            for($i=0; $i<=count($head['link']); $i++) {
                if($head['link'][$i]['rel'] == 'stylesheet') {
                    $head['link'][$i]['href'] = preg_replace('/t=([\w]+$)/', "t=$tpl", $head['link'][$i]['href']);
                }
            }
        }
    }

    /**
     * Output the template switcher if 'automaticOutput' is on
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    function _handleContent(&$event, $param){
        if ($this->getConf('automaticOutput')) {
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
    function getTpl() {
        $tplPerUser = $this->_getTplPerUser();
        if($tplPerUser)
            return $tplPerUser;

        $tplPerNamespace = $this->_getTplPerNamespace();
        if($tplPerNamespace)
            return $tplPerNamespace;

        return false;
    }

    /**
     * Get template from session and/or user config
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    function _getTplPerUser() {
        // get all available templates
        $helper = $this->loadHelper('loadskin', true);
        $tpls   = $helper->getTemplates();

        $user = $_SERVER['REMOTE_USER'];

        $tplRequest = $_REQUEST['tpl'];
        if ($tplRequest && in_array($tplRequest, $tpls)) {
            // store in cookie
            $_SESSION[DOKU_COOKIE]['loadskinTpl'] = $tplRequest;
            // if registered user, store also in conf file
            if ($user)
                $this->_setTplConfig($user, $tplRequest);
            return $tplRequest;
        }

        $tplUser   = $this->_getTplConfig($user);// from user conf file
        $tplCookie = $_SESSION[DOKU_COOKIE]['loadskinTpl'];
        // if logged in and user is in conf
        if ($user && $tplUser && in_array($tplUser, $tpls)){
            if ($tplCookie && ($tplCookie == $tplUser))
                return $tplCookie;
            // store in cookie
            $_SESSION[DOKU_COOKIE]['loadskinTpl'] = $tplUser;
            return $tplUser;
        }
        if ($tplCookie && in_array($tplCookie, $tpls))
            return $tplCookie;

        return false;
    }

    /**
     * Get template from namespace/page and config
     *
     * @author Michael Klier <chi@chimeric.de>
     * @author Anika Henke <anika@selfthinker.org>
     */
    function _getTplPerNamespace() {
        global $ID;
        $config = DOKU_CONF.'loadskin.conf';

        if(@file_exists($config)) {
            $data = unserialize(io_readFile($config, false));
            $id   = $ID;

            if($data[$id]) return $data[$id];

            $path  = explode(':', $id);
            $found = false;

            while(count($path) > 0) {
                $id = implode(':', $path);
                if($data[$id]) return $data[$id];
                array_pop($path);
            }
        }
        return false;
    }

    /**
     * Save template for user in config
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    function _setTplConfig($user, $tpl) {
        $data = array();
        $userConf = DOKU_CONF.'loadskin.users.conf';
        if(@file_exists($userConf)) {
            $data = unserialize(io_readFile($userConf, false));
            unset($data[$user]);
        }
        $data[$user] = $tpl;
        io_saveFile($userConf, serialize($data));
    }

    /**
     * Get template for user from config
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    function _getTplConfig($user) {
        $data = array();
        $userConf = DOKU_CONF.'loadskin.users.conf';
        if(@file_exists($userConf)) {
            $data = unserialize(io_readFile($userConf, false));
            return $data[$user];
        }
        return false;
    }

}

// vim:ts=4:sw=4:
