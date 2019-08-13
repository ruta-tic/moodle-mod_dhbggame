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
 * List of urls of dhbggame in course
 *
 * @package   mod_dhbggame
 * @copyright 2019 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_dhbggame\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strdhbggame       = get_string('modulename', 'dhbggame');
$strdhbggames      = get_string('modulenameplural', 'dhbggame');
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/dhbggame/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strdhbggames);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strdhbggames);
echo $OUTPUT->header();
echo $OUTPUT->heading($strdhbggames);

if (!$dhbggames = get_all_instances_in_course('dhbggame', $course)) {
    notice(get_string('thereareno', 'moodle', $strdhbggames), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($dhbggames as $dhbggame) {
    $cm = $modinfo->cms[$dhbggame->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($dhbggame->section !== $currentsection) {
            if ($dhbggame->section) {
                $printsection = get_section_name($course, $dhbggame->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $dhbggame->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($dhbggame->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        $icon = $OUTPUT->pix_icon($cm->icon, get_string('modulename', $cm->modname)) . ' ';
    }

    $class = $dhbggame->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed
    $table->data[] = array (
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($dhbggame->name)."</a>",
        format_module_intro('dhbggame', $dhbggame, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
