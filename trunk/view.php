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
 * the first page to view the apply
 *
 * @author  Fumi Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_feedback that by Andreas Grabs)
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/tablelib.php');

apply_init_session();
$SESSION->apply->is_started = false;

//
$id 		= required_param('id', PARAM_INT);
$do_show    = optional_param('do_show', 'view', PARAM_ALPHAEXT);
$courseid   = optional_param('courseid', false, PARAM_INT);
$submit_id  = optional_param('submit_id', 0, PARAM_INT);
$submit_ver = optional_param('submit_ver', -1, PARAM_INT);
$show_all   = optional_param('show_all',  0, PARAM_INT);
$perpage    = optional_param('perpage', APPLY_DEFAULT_PAGE_COUNT, PARAM_INT);
$user_id 	= $USER->id;

$current_tab = 'view';
//
$action_file = 'view.php';
$submit_file = 'submit.php';


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

$req_own_data = true;
$name_pattern = $apply->name_pattern;
$context = context_module::instance($cm->id);


////////////////////////////////////////////////////////
// Check
require_login($course, true, $cm);
//
$apply_submit_cap = false;
if (has_capability('mod/apply:submit', $context)) {
	$apply_submit_cap = true;
}


///////////////////////////////////////////////////////////////////////////
// URL
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$base_url = new moodle_url('/mod/apply/'.$action_file);
$base_url->params(array('id'=>$id, 'courseid'=>$courseid));
//
$this_url = new moodle_url($base_url);
$back_url = new moodle_url($base_url);
$this_url->params(array('do_show'=>$do_show, 'show_all'=>$show_all, 'submit_id'=>$submit_id, 'submit_ver'=>$submit_ver));
$back_url->params(array('do_show'=>'view'));

$log_url = explode('/', $this_url);
add_to_log($course->id, 'apply', 'view', end($log_url), 'apply_id='.$apply->id);


///////////////////////////////////////////////////////////////////////////
// Print the page header
$PAGE->navbar->add(get_string('apply:view', 'apply'));
$PAGE->set_url($this_url);
$PAGE->set_title(format_string($apply->name));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();

require('tabs.php');

//
$cap_view_hidden_activities = has_capability('moodle/course:viewhiddenactivities', $context);
if ((empty($cm->visible) and !$cap_view_hidden_activities)) {
	notice(get_string('activityiscurrentlyhidden'));
}
if ((empty($cm->visible) and !$cap_view_hidden_activities)) {
	notice(get_string('activityiscurrentlyhidden'));
}


///////////////////////////////////////////////////////////////////////////
// Print the main part of the page

echo $OUTPUT->heading(format_text($apply->name));
//
echo $OUTPUT->heading(get_string('description', 'apply'), 4);
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
echo format_module_intro('apply', $apply, $cm->id);
require('period_info.php');
echo $OUTPUT->box_end();


///////////////////////////////////////////////////////////////////////////
// Check
if (!$apply_submit_cap) {
	apply_print_error_messagebox('apply_is_disable', $courseid);
	exit;
}

$apply_can_submit = true;
if (!$apply->multiple_submit) {
	if (apply_get_valid_submits_count($apply->id, $USER->id)>0) {
		$apply_can_submit = false;
		apply_print_messagebox('apply_is_already_submitted', $back_url->out());
	}
}

// Date
if ($apply_can_submit) {
	$checktime = time();
	$apply_is_not_open =  $apply->time_open>$checktime;
	$apply_is_closed   = ($apply->time_close<$checktime and $apply->time_close>0);
	if ($apply_is_not_open or $apply_is_closed) {
		if ($apply_is_not_open) apply_print_messagebox('apply_is_not_open', $back_url->out());
		else					apply_print_messagebox('apply_is_closed',   $back_url->out());
		$apply_can_submit = false;
	}
}


///////////////////////////////////////////////////////////////////////////
// 新規登録
if ($apply_can_submit) {
	$url_params  = array('id'=>$id, 'courseid'=>$courseid, 'go_page'=>0);
	$submit_url  = new moodle_url('/mod/apply/'.$submit_file, $url_params);
	$submit_link = '<div align="center">'.$OUTPUT->single_button($submit_url->out(), get_string('submit_form_button', 'apply')).'</div>';
	apply_print_messagebox('submit_new_apply', $submit_link, 'green');
}


///////////////////////////////////////////////////////////////////////////
// リスト表示
if ($do_show=='view') {
	$submits = apply_get_all_submits($apply->id, $USER->id);
	if ($submits) {
		//
		$table = new flexible_table('apply-view-list-'.$courseid);
		$matchcount = apply_get_valid_submits_count($cm->instance);
		//
		require('entry_header.php');

		echo '<br />';
		echo $OUTPUT->heading(get_string('entries_list_title', 'apply'), 2);

		///////////////////////////////////////////////////////////////////////
		//
		echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

		////////////////////////////////////////////////////////////
		// Submits Data
		$submits = apply_get_submits_select($apply->id, $USER->id, $where, $params, $sort, $start_page, $page_count);

		foreach ($submits as $submit) {
			$student = apply_get_user_info($submit->user_id);
			if ($student) {
				$data = array();
				require('entry_record.php');
				if (!empty($data)) $table->add_data($data);
			}
		}
		$table->print_html();

		$all_url = new moodle_url($base_url);
		if ($show_all) {
			$all_url->param('show_all', 0);
			echo $OUTPUT->container(html_writer::link($all_url, get_string('show_perpage', 'apply', APPLY_DEFAULT_PAGE_COUNT)), array(), 'show_all');
		}
		else if ($matchcount>0 && $perpage<$matchcount) {
			$all_url->param('show_all', 1);
			echo $OUTPUT->container(html_writer::link($all_url, get_string('show_all', 'apply', $matchcount)), array(), 'show_all');
		}

		echo $OUTPUT->box_end();
	}
}


///////////////////////////////////////////////////////////////////////////
// エントリ内容の表示
if ($do_show=='view_one_entry' and $submit_id) {
	$params = array('apply_id'=>$apply->id, 'user_id'=>$USER->id, 'id'=>$submit_id);
	$submit = $DB->get_record('apply_submit', $params);

	echo '<br />';
	if ($submit) {
		$items = $DB->get_records('apply_item', array('apply_id'=>$submit->apply_id), 'position');
		if (is_array($items)) {
            if ($submit_ver==-1 and apply_exist_draft_values($submit->id)) $submit_ver = 0;
			require('entry_view.php');
		}
	}
	else {
		echo $OUTPUT->heading(get_string('no_submit_data', 'apply'), 3);

        $back_label = get_string('back_button', 'apply');
        $back_url   = new moodle_url($url, array('do_show'=>'view'));
        //
        echo '<div align="center">';
        echo $OUTPUT->single_button($back_url, $back_label);
       	echo '</div>';
	}
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();
