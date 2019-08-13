<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * dhbggame module admin settings and defaults
 *
 * @package contribution
 * @copyright 2013 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('dhbggame/framesize',
        get_string('framesize', 'dhbggame'), get_string('configframesize', 'dhbggame'), 130, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('dhbggame/requiremodintro',
        get_string('requiremodintro', 'admin'), get_string('requiremodintro_desc', 'admin'), 1));
    $settings->add(new admin_setting_configpasswordunmask('dhbggame/secretphrase', get_string('password'),
        get_string('configsecretphrase', 'dhbggame'), ''));
    $settings->add(new admin_setting_configmultiselect('dhbggame/displayoptions',
        get_string('displayoptions', 'dhbggame'), get_string('configdisplayoptions', 'dhbggame'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('dhbggamemodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox_with_advanced('dhbggame/printintro',
        get_string('printintro', 'dhbggame'), get_string('printintroexplain', 'dhbggame'),
        array('value'=>1, 'adv'=>false)));
    $settings->add(new admin_setting_configselect_with_advanced('dhbggame/display',
        get_string('displayselect', 'dhbggame'), get_string('displayselectexplain', 'dhbggame'),
        array('value'=>RESOURCELIB_DISPLAY_AUTO, 'adv'=>false), $displayoptions));
    $settings->add(new admin_setting_configtext_with_advanced('dhbggame/popupwidth',
        get_string('popupwidth', 'dhbggame'), get_string('popupwidthexplain', 'dhbggame'),
        array('value'=>620, 'adv'=>true), PARAM_INT, 7));
    $settings->add(new admin_setting_configtext_with_advanced('dhbggame/popupheight',
        get_string('popupheight', 'dhbggame'), get_string('popupheightexplain', 'dhbggame'),
        array('value'=>450, 'adv'=>true), PARAM_INT, 7));
}
