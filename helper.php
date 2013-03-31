<?php
/**
 * DokuWiki Helper Plugin LoadSkin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 * @author     Michael Klier <chi@chimeric.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_loadskin extends DokuWiki_Plugin {

    /**
     * Returns an array of available templates to choose from
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function getTemplates() {
        $tpl_dir = DOKU_INC.'lib/tpl/';
        if ($dh = @opendir($tpl_dir)) {
            while (false !== ($entry = readdir($dh))) {
                if ($entry == '.' || $entry == '..') continue;
                if (!preg_match('/^[\w-]+$/', $entry)) continue;

                $file = (is_link($tpl_dir.$entry)) ? readlink($tpl_dir.$entry) : $entry;
                if (is_dir($tpl_dir.$file)) $list[] = $entry;
            }
            closedir($dh);
            sort($list);
        }
        return $list;
    }


    /**
     * Builds a select box with all available templates
     *  (unless excluded in 'excludeTemplates')
     *  or show only two templates for mobile switcher: standard plus mobile template
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    function showTemplateSwitcher() {
        global $conf;
        global $ID;
        global $ACT;

        if ($ACT != 'show') return;

        $mobileSwitch = $this->getConf('mobileSwitch');
        $mobileTpl = $this->getConf('mobileTemplate');
        if ($mobileSwitch && $mobileTpl) {
            // templates for mobile switcher
            $templates = array(
                $mobileTpl                             => $this->getLang('switchMobile'),
                $_SESSION[DOKU_COOKIE]['loadskinOrig'] => $this->getLang('switchFull')
            );
        } else {
            // all templates (minus excluded templates)
            $excludeTemplates = array_map('trim', explode(",", $this->getConf('excludeTemplates')));
            $templates        = array_diff($this->getTemplates(),$excludeTemplates);
        }

        $form = new Doku_Form(array(
            'id' => 'tpl__switcher',
            'title' => $this->getLang('switchTpl'),
            'action' => wl($ID)
        ));
        $form->addHidden('act','select');
        $form->addElement(form_makeListboxField(
            'tpl',
            $templates,
            $conf['template'],
            $this->getLang('template'),
            '',
            '',
            array('class' => 'quickselect')
        ));
        $form->addElement(form_makeButton(
            'submit',
            '',
            $this->getLang('switch'),
            array('name' => 'switch')
        ));

        $out  = '<div class="plugin_loadskin">';
        $out .= $form->getForm();
        $out .= '</div>';

        return $out;
    }


}
