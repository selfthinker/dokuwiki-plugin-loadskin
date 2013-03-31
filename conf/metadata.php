<?php
/*
 * Loadskin plugin, configuration metadata
 *
 */

$meta['automaticOutput']  = array('onoff');
$meta['excludeTemplates'] = array('string');
$meta['mobileSwitch']     = array('onoff');
$meta['mobileTemplate']   = array('dirchoice', '_dir' => DOKU_INC.'lib/tpl/', '_pattern' => '/^[\w-]+$/');
$meta['preferUserChoice'] = array('onoff');
