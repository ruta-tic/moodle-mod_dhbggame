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
 * Private dhbggame module utility functions
 *
 * @package   mod_dhbggame
 * @copyright 2013 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/dhbggame/lib.php");

/**
 * This methods does weak dhbggame validation, we are looking for major problems only,
 * no strict RFE validation.
 *
 * @param $url
 * @return bool true is seems valid, false if definitely not valid URL
 */
function dhbggame_appears_valid_url($url) {
    if (preg_match('/^(\/|https?:|ftp:)/i', $url)) {
        // note: this is not exact validation, we look for severely malformed URLs only
        return (bool)preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[a-z0-9_\.\-]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $url);
    } else {
        return (bool)preg_match('/^[a-z]+:\/\/...*$/i', $url);
    }
}

/**
 * Fix common URL problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $url
 * @return string
 */
function dhbggame_fix_submitted_url($url) {
    // note: empty urls are prevented in form validation
    $url = trim($url);

    // remove encoded entities - we want the raw URI here
    $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

    if (!preg_match('|^[a-z]+:|i', $url) and !preg_match('|^/|', $url)) {
        // invalid URI, try to fix it by making it normal URL,
        // please note relative urls are not allowed, /xx/yy links are ok
        $url = 'http://'.$url;
    }

    return $url;
}

/**
 * Return full url with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param object $dhbggame
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string url with & encoded as &amp;
 */
function dhbggame_get_full_url($dhbggame, $cm, $course, $config=null) {
    global $USER, $DB;
    // make sure there are no encoded entities, it is ok to do this twice
    $fullurl = html_entity_decode($dhbggame->externalurl, ENT_QUOTES, 'UTF-8');

    if (isloggedin()) {
        $DB->delete_records('dhbggame_auth', array('username' => $USER->username));

        $context = context_module::instance($cm->id);
        $manager_cap = has_capability('mod/dhbggame:manager', $context);

        $data = new stdClass();
        $data->username = $USER->username;
        $data->hashkey  = md5($USER->username . time());
        $data->time     = time();
        $data->type     = $manager_cap ? 'manager' : 'gamer';

        $parts = explode('/', $fullurl);

        if (count($parts) > 0) {
            $data->game = $parts[count($parts) - 1];
        }

        if($DB->insert_record('dhbggame_auth', $data)) {
            $fullurl .= '.html?user=' . $USER->username . '&h=' . $data->hashkey;
        }
    }

    if (preg_match('/^(\/|https?:|ftp:)/i', $fullurl) or preg_match('|^/|', $fullurl)) {
        // encode extra chars in URLs - this does not make it always valid, but it helps with some UTF-8 problems
        $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
        $fullurl = preg_replace_callback("/[^$allowed]/", 'dhbggame_filter_callback', $fullurl);
    } else {
        // encode special chars only
        $fullurl = str_replace('"', '%22', $fullurl);
        $fullurl = str_replace('\'', '%27', $fullurl);
        $fullurl = str_replace(' ', '%20', $fullurl);
        $fullurl = str_replace('<', '%3C', $fullurl);
        $fullurl = str_replace('>', '%3E', $fullurl);
    }

    // encode all & to &amp; entity
    $fullurl = str_replace('&', '&amp;', $fullurl);

    return $fullurl;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function dhbggame_filter_callback($matches) {
    return rawurlencode($matches[0]);
}

/**
 * Print dhbggame header.
 * @param object $dhbggame
 * @param object $cm
 * @param object $course
 * @return void
 */
function dhbggame_print_header($dhbggame, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$dhbggame->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($dhbggame);
    echo $OUTPUT->header();
}

/**
 * Print url heading.
 * @param object $dhbggame
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function dhbggame_print_heading($dhbggame, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($dhbggame->name), 2);
}

/**
 * Print url introduction.
 * @param object $dhbggame
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function dhbggame_print_intro($dhbggame, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($dhbggame->displayoptions) ? array() : unserialize($dhbggame->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($dhbggame->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'dhbggameintro');
            echo format_module_intro('dhbggame', $dhbggame, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Display dhbggame frames.
 * @param object $dhbggame
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function dhbggame_display_frame($dhbggame, $cm, $course) {
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        dhbggame_print_header($dhbggame, $cm, $course);
        dhbggame_print_heading($dhbggame, $cm, $course);
        dhbggame_print_intro($dhbggame, $cm, $course);
        echo $OUTPUT->footer();
        die;

    } else {
        $config = get_config('url');
        $context = context_module::instance($cm->id);
        $exteurl = dhbggame_get_full_url($dhbggame, $cm, $course, $config);
        $navurl = "$CFG->wwwroot/mod/url/view.php?id=$cm->id&amp;frameset=top";
        $coursecontext = context_course::instance($course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $title = strip_tags($courseshortname.': '.format_string($dhbggame->name));
        $framesize = $config->framesize;
        $modulename = s(get_string('modulename','dhbggame'));
        $contentframetitle = format_string($dhbggame->name);
        $dir = get_string('thisdirection', 'langconfig');

        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navurl" title="$modulename"/>
    <frame src="$exteurl" title="$contentframetitle"/>
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
        die;
    }
}

/**
 * Print dhbggame info and link.
 * @param object $dhbggame
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function dhbggame_print_workaround($dhbggame, $cm, $course) {
    global $OUTPUT;

    dhbggame_print_header($dhbggame, $cm, $course);
    dhbggame_print_heading($dhbggame, $cm, $course, true);
    dhbggame_print_intro($dhbggame, $cm, $course, true);

    $fullurl = dhbggame_get_full_url($dhbggame, $cm, $course);

    $display = dhbggame_get_final_display_type($dhbggame);
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $jsfullurl = addslashes_js($fullurl);
        $options = empty($dhbggame->displayoptions) ? array() : unserialize($dhbggame->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$jsfullurl', '', '$wh'); return false;\"";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $extra = "onclick=\"this.target='_blank';\"";

    } else {
        $extra = '';
    }

    echo '<div class="dhbggameworkaround">';
    print_string('clicktoopen', 'dhbggame', "<a href=\"$fullurl\" $extra>$fullurl</a>");
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Display embedded dhbggame file.
 * @param object $dhbggame
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function dhbggame_display_embed($dhbggame, $cm, $course) {
    global $CFG, $PAGE, $OUTPUT;

    $mimetype = resourcelib_guess_url_mimetype($dhbggame->externalurl);
    $fullurl  = dhbggame_get_full_url($dhbggame, $cm, $course);
    $title    = $dhbggame->name;

    $link = html_writer::tag('a', $fullurl, array('href'=>str_replace('&amp;', '&', $fullurl)));
    $clicktoopen = get_string('clicktoopen', 'dhbggame', $link);
    $moodleurl = new moodle_url($fullurl);

    $extension = resourcelib_get_extension($dhbggame->externalurl);

    $mediamanager = core_media_manager::instance($PAGE);
    $embedoptions = array(
        core_media_manager::OPTION_TRUSTED => true,
        core_media_manager::OPTION_BLOCK => true
    );

    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($fullurl, $title);

    } else if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediamanager->embed_url($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
    }

    dhbggame_print_header($dhbggame, $cm, $course);
    dhbggame_print_heading($dhbggame, $cm, $course);

    echo $code;

    dhbggame_print_intro($dhbggame, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

/**
 * Decide the best display format.
 * @param object $dhbggame
 * @return int display type constant
 */
function dhbggame_get_final_display_type($dhbggame) {
    global $CFG;

    if ($dhbggame->display != RESOURCELIB_DISPLAY_AUTO) {
        return $dhbggame->display;
    }

    // detect links to local moodle pages
    if (strpos($dhbggame->externalurl, $CFG->wwwroot) === 0) {
        if (strpos($dhbggame->externalurl, 'file.php') === false and strpos($dhbggame->externalurl, '.php') !== false ) {
            // most probably our moodle page with navigation
            return RESOURCELIB_DISPLAY_OPEN;
        }
    }

    static $download = array('application/zip', 'application/x-tar', 'application/g-zip',     // binary formats
                             'application/pdf', 'text/html');  // these are known to cause trouble for external links, sorry
    static $embed    = array('image/gif', 'image/jpeg', 'image/png', 'image/svg+xml',         // images
                             'application/x-shockwave-flash', 'video/x-flv', 'video/x-ms-wm', // video formats
                             'video/quicktime', 'video/mpeg', 'video/mp4',
                             'audio/mp3', 'audio/x-realaudio-plugin', 'x-realaudio-plugin',   // audio formats,
                            );

    $mimetype = resourcelib_guess_url_mimetype($dhbggame->externalurl);

    if (in_array($mimetype, $download)) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    }
    if (in_array($mimetype, $embed)) {
        return RESOURCELIB_DISPLAY_EMBED;
    }

    // let the browser deal with it somehow
    return RESOURCELIB_DISPLAY_OPEN;
}

/**
 * Get the parameters that may be appended to URL
 * @param object $config url module config options
 * @return array array describing opt groups
 */
function dhbggame_get_variable_options($config) {
    global $CFG;

    $options = array();
    $options[''] = array('' => get_string('chooseavariable', 'url'));

    $options[get_string('course')] = array(
        'courseid'        => 'id',
        'coursefullname'  => get_string('fullnamecourse'),
        'courseshortname' => get_string('shortnamecourse'),
        'courseidnumber'  => get_string('idnumbercourse'),
        'coursesummary'   => get_string('summary'),
        'courseformat'    => get_string('format'),
    );

    $options[get_string('modulename', 'url')] = array(
        'urlinstance'     => 'id',
        'urlcmid'         => 'cmid',
        'urlname'         => get_string('name'),
        'urlidnumber'     => get_string('idnumbermod'),
    );

    $options[get_string('miscellaneous')] = array(
        'sitename'        => get_string('fullsitename'),
        'serverurl'       => get_string('serverurl', 'url'),
        'currenttime'     => get_string('time'),
        'lang'            => get_string('language'),
    );
    if (!empty($config->secretphrase)) {
        $options[get_string('miscellaneous')]['encryptedcode'] = get_string('encryptedcode');
    }

    $options[get_string('user')] = array(
        'userid'          => 'id',
        'userusername'    => get_string('username'),
        'useridnumber'    => get_string('idnumber'),
        'userfirstname'   => get_string('firstname'),
        'userlastname'    => get_string('lastname'),
        'userfullname'    => get_string('fullnameuser'),
        'useremail'       => get_string('email'),
        'usericq'         => get_string('icqnumber'),
        'userphone1'      => get_string('phone1'),
        'userphone2'      => get_string('phone2'),
        'userinstitution' => get_string('institution'),
        'userdepartment'  => get_string('department'),
        'useraddress'     => get_string('address'),
        'usercity'        => get_string('city'),
        'usertimezone'    => get_string('timezone'),
        'userurl'         => get_string('webpage'),
    );

    if ($config->rolesinparams) {
        $roles = role_fix_names(get_all_roles());
        $roleoptions = array();
        foreach ($roles as $role) {
            $roleoptions['course'.$role->shortname] = get_string('yourwordforx', '', $role->localname);
        }
        $options[get_string('roles')] = $roleoptions;
    }

    return $options;
}

/**
 * Get the parameter values that may be appended to URL
 * @param object $dhbggame module instance
 * @param object $cm
 * @param object $course
 * @param object $config module config options
 * @return array of parameter values
 */
function dhbggame_get_variable_values($dhbggame, $cm, $course, $config) {
    global $USER, $CFG;

    $site = get_site();

    $coursecontext = context_course::instance($course->id);

    $values = array (
        'courseid'        => $course->id,
        'coursefullname'  => format_string($course->fullname),
        'courseshortname' => format_string($course->shortname, true, array('context' => $coursecontext)),
        'courseidnumber'  => $course->idnumber,
        'coursesummary'   => $course->summary,
        'courseformat'    => $course->format,
        'lang'            => current_language(),
        'sitename'        => format_string($site->fullname),
        'serverurl'       => $CFG->wwwroot,
        'currenttime'     => time(),
        'urlinstance'     => $dhbggame->id,
        'urlcmid'         => $cm->id,
        'urlname'         => format_string($dhbggame->name),
        'urlidnumber'     => $cm->idnumber,
    );

    if (isloggedin()) {
        $values['userid']          = $USER->id;
        $values['userusername']    = $USER->username;
        $values['useridnumber']    = $USER->idnumber;
        $values['userfirstname']   = $USER->firstname;
        $values['userlastname']    = $USER->lastname;
        $values['userfullname']    = fullname($USER);
        $values['useremail']       = $USER->email;
        $values['usericq']         = $USER->icq;
        $values['userphone1']      = $USER->phone1;
        $values['userphone2']      = $USER->phone2;
        $values['userinstitution'] = $USER->institution;
        $values['userdepartment']  = $USER->department;
        $values['useraddress']     = $USER->address;
        $values['usercity']        = $USER->city;
        $now = new DateTime('now', core_date::get_user_timezone_object());
        $values['usertimezone']    = $now->getOffset() / 3600.0; // Value in hours for BC.
        $values['userurl']         = $USER->url;
    }

    // weak imitation of Single-Sign-On, for backwards compatibility only
    // NOTE: login hack is not included in 2.0 any more, new contrib auth plugin
    //       needs to be createed if somebody needs the old functionality!
    if (!empty($config->secretphrase)) {
        $values['encryptedcode'] = dhbggame_get_encrypted_parameter($dhbggame, $config);
    }

    //hmm, this is pretty fragile and slow, why do we need it here??
    if ($config->rolesinparams) {
        $coursecontext = context_course::instance($course->id);
        $roles = role_fix_names(get_all_roles($coursecontext), $coursecontext, ROLENAME_ALIAS);
        foreach ($roles as $role) {
            $values['course'.$role->shortname] = $role->localname;
        }
    }

    return $values;
}

/**
 * BC internal function
 * @param object $dhbggame
 * @param object $config
 * @return string
 */
function dhbggame_get_encrypted_parameter($dhbggame, $config) {
    global $CFG;

    if (file_exists("$CFG->dirroot/local/externserverfile.php")) {
        require_once("$CFG->dirroot/local/externserverfile.php");
        if (function_exists('extern_server_file')) {
            return extern_server_file($dhbggame, $config);
        }
    }
    return md5(getremoteaddr().$config->secretphrase);
}

/**
 * Optimised mimetype detection from general URL
 * @param $fullurl
 * @param int $size of the icon.
 * @return string|null mimetype or null when the filetype is not relevant.
 */
function dhbggame_guess_icon($fullurl, $size = null) {
    global $CFG;
    require_once("$CFG->libdir/filelib.php");

    if (substr_count($fullurl, '/') < 3 or substr($fullurl, -1) === '/') {
        // Most probably default directory - index.php, index.html, etc. Return null because
        // we want to use the default module icon instead of the HTML file icon.
        return null;
    }

    $icon = file_extension_icon($fullurl, $size);
    $htmlicon = file_extension_icon('.htm', $size);
    $unknownicon = file_extension_icon('', $size);

    // We do not want to return those icon types, the module icon is more appropriate.
    if ($icon === $unknownicon || $icon === $htmlicon) {
        return null;
    }

    return $icon;
}
