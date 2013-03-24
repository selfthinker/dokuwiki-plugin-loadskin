<?php
/**
 * DokuWiki Plugin Loadskin
 *
 * @author Michael Klier <chi@chimeric.de>
 */
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_loadskin extends DokuWiki_Admin_Plugin {

    /**
     * Constructor
     */
    function admin_plugin_loadskin() {
        $this->setupLocale();
        $this->config = DOKU_CONF.'loadskin.conf';
    }

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 300;
    }

    /**
     * handle user request
     */
    function handle() {
        $data = array();

        if(!empty($_REQUEST['pattern'])) {
            $id = cleanID($_REQUEST['pattern']);

            if($_REQUEST['act'] == 'add') {
                if(@file_exists($this->config)) {
                    $data = unserialize(io_readFile($this->config, false));
                    $data[$id] = $_REQUEST['tpl'];
                    io_saveFile($this->config, serialize($data));
                } else {
                    $data[$id] = $_REQUEST['tpl'];
                    io_saveFile($this->config, serialize($data));
                }
            }

            if($_REQUEST['act'] == 'del') {
                $data = unserialize(io_readFile($this->config, false));
                unset($data[$id]);
                io_saveFile($this->config, serialize($data));
            }
        }
    }

    /**
     * output appropriate html
     */
    function html() {
        global $lang;
        $helper = $this->loadHelper('loadskin', true);

        print '<div id="plugin__loadskin">';
        print $this->locale_xhtml('intro');

        $form = new Doku_Form(array());
        $form->startFieldSet('Add rule');
        $form->addHidden('id',$ID);
        $form->addHidden('do','admin');
        $form->addHidden('page','loadskin');
        $form->addHidden('act','add');
        $form->addElement(form_makeOpenTag('p'));
        $form->addElement(form_makeTextField('pattern','',$this->getLang('pattern')));
        $form->addElement(form_makeCloseTag('p'));
        $form->addElement(form_makeOpenTag('p'));
        $form->addElement(form_makeListboxField('tpl',$helper->getTemplates(),'',$this->getLang('template')));
        $form->addElement(form_makeCloseTag('p'));
        $form->addElement(form_makeButton('submit','',$lang['btn_save']));
        $form->endFieldSet();
        $form->printForm();

        if(@file_exists($this->config)) {
            $data = unserialize(io_readFile($this->config, false));

            if(!empty($data)) {
                echo '<table class="inline">' . DOKU_LF;
                echo '  <tr>' . DOKU_LF;
                echo '    <th>' . $this->getLang('pattern') . '</th>' . DOKU_LF;
                echo '    <th>' . $this->getLang('template') . '</th>' . DOKU_LF;
                echo '    <th>' . $this->getLang('action') . '</th>' . DOKU_LF;
                echo '  </tr>' . DOKU_LF;
                foreach($data as $key => $value) {
                    echo '  <tr>' . DOKU_LF;
                    echo '    <td>' . $key . '</td>' . DOKU_LF;
                    echo '    <td>' . $value . '</td>' . DOKU_LF;
                    echo '    <td>' . DOKU_LF;

                    $form = new Doku_Form(array());
                    $form->addHidden('do','admin');
                    $form->addHidden('page','loadskin');
                    $form->addHidden('act','del');
                    $form->addHidden('id',$ID);
                    $form->addHidden('pattern',$key);
                    $form->addElement(form_makeButton('submit','',$lang['btn_delete']));
                    $form->printForm();

                    echo '    </td>' . DOKU_LF;
                    echo '  </tr>' . DOKU_LF;
                }
                echo '</table>' . DOKU_LF;
            }
        }
        print '</div>';
    }

}
//vim:ts=4:sw=4:et:enc=utf-8:
