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
 * dhbggame module main user interface
 *
 * @package mod_dhbggame
 * @copyright 2013 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/dhbggame/lib.php");
require_once("$CFG->dirroot/mod/dhbggame/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // dhbggame instance id
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module
    $dhbggame = $DB->get_record('dhbggame', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('dhbggame', $dhbggame->id, $dhbggame->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('dhbggame', $id, 0, false, MUST_EXIST);
    $dhbggame = $DB->get_record('dhbggame', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/dhbggame:view', $context);

// Completion and trigger events.
dhbggame_view($dhbggame, $course, $cm, $context);

$PAGE->set_url('/mod/dhbggame/view.php', array('id' => $cm->id));

// Make sure dhbggame exists before generating output - some older sites may contain empty urls
// Do not use PARAM_URL here, it is too strict and does not support general URIs!
$exturl = trim($dhbggame->externalurl);
if (empty($exturl) or $exturl === 'http://') {
    dhbggame_print_header($dhbggame, $cm, $course);
    dhbggame_print_heading($dhbggame, $cm, $course);
    dhbggame_print_intro($dhbggame, $cm, $course);
    notice(get_string('invalidstoredurl', 'dhbggame'), new moodle_url('/course/view.php', array('id'=>$cm->course)));
    die;
}
unset($exturl);

$displaytype = dhbggame_get_final_display_type($dhbggame);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN) {
    // For 'open' links, we always redirect to the content - except if the user
    // just chose 'save and display' from the form then that would be confusing
    if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'modedit.php') === false) {
        $redirect = true;
    }
}

if ($redirect) {
    // coming from course page or url index page,
    // the redirection is needed for completion tracking and logging
    $fullurl = str_replace('&amp;', '&', dhbggame_get_full_url($dhbggame, $cm, $course));

    if (!course_get_format($course)->has_view_page()) {
        // If course format does not have a view page, add redirection delay with a link to the edit page.
        // Otherwise teacher is redirected to the external URL without any possibility to edit activity or course settings.
        $editurl = null;
        if (has_capability('moodle/course:manageactivities', $context)) {
            $editurl = new moodle_url('/course/modedit.php', array('update' => $cm->id));
            $edittext = get_string('editthisactivity');
        } else if (has_capability('moodle/course:update', $context->get_course_context())) {
            $editurl = new moodle_url('/course/edit.php', array('id' => $course->id));
            $edittext = get_string('editcoursesettings');
        }
        if ($editurl) {
            redirect($fullurl, html_writer::link($editurl, $edittext)."<br/>".
                    get_string('pageshouldredirect'), 10);
        }
    }
    redirect($fullurl);
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        dhbggame_display_embed($dhbggame, $cm, $course);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        dhbggame_display_frame($dhbggame, $cm, $course);
        break;
    default:
        dhbggame_print_workaround($dhbggame, $cm, $course);
        break;
}