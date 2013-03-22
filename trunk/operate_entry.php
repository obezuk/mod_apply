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
 * operate the submitted entry
 *
 * @author  Fumi.Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_feedback that by Andreas Grabs)
 */

require_once('../../config.php');
require_once('lib.php');

apply_init_session();


////////////////////////////////////////////////////////
//get the params
$id		    = required_param('id', PARAM_INT);
$user_id    = optional_param('user_id',   0, PARAM_INT);
$submit_id  = optional_param('submit_id', 0, PARAM_INT);
$submit_ver = optional_param('submit_ver', -1, PARAM_INT);
$courseid   = optional_param('courseid',  0, PARAM_INT);
$operate    = optional_param('operate',  'show_page', PARAM_ALPHAEXT);

$current_tab = '';


///////////////////////////////////////////////////////////////////////////
// Form Data
if (($formdata = data_submitted()) and !confirm_sesskey()) {
    print_error('invalidsesskey');
}


////////////////////////////////////////////////////////
//get the objects
if (! $cm = get_coursemodule_from_id('apply', $id)) {
	print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
	print_error('coursemisconf');
}
if (! $apply = $DB->get_record('apply', array('id'=>$cm->instance))) {
	print_error('invalidcoursemodule');
}
if (!$courseid) $courseid = $course->id;

//
$context = context_module::instance($cm->id);

$name_pattern = $apply->name_pattern;
$req_own_data = false;


////////////////////////////////////////////////////////
// Check
require_login($course, true, $cm);
//
if (!has_capability('mod/apply:operatesubmit', $context)) {
    apply_print_error_messagebox('operate_is_disable', $id);
    exit;
}


////////////////////////////////////////////////////////
/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$PAGE->navbar->add(get_string('apply:operate_submit', 'apply'));
$url_params = array('id'=>$cm->id, 'courseid'=>$courseid);
$url = new moodle_url('/mod/apply/oerate_entry.php', $url_params);
$PAGE->set_url($url);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();

require('tabs.php');


///////////////////////////////////////////////////////////////////////////
// Operate

if ($operate=='operate') {
}



///////////////////////////////////////////////////////////////////////////
// Print Entry

if ($operate=='show_page' and $submit_id) {
	$params = array('id'=>$submit_id);
	$submit = $DB->get_record('apply_submit', $params); 

	echo $OUTPUT->heading(format_text($apply->name));

	$items = $DB->get_records('apply_item', array('apply_id'=>$submit->apply_id), 'position');
	if (is_array($items)) require('entry_transact.php');
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();

