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
 * Thumblinks Action block renderable.
 *
 * @package    block_thumblinks_action
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_featured_courses\output;
global $CFG;

defined('MOODLE_INTERNAL') || die();

use context_course;
use context_helper;
use core_course\external\course_summary_exporter;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

class mini_course_summary_exporter extends course_summary_exporter {

    /**
     * Only a subset of the usual.
     *
     * @return array|array[]
     */
    public static function define_other_properties() {
        return array(
            'fullnamedisplay' => array(
                'type' => PARAM_TEXT,
            ),
            'viewurl' => array(
                'type' => PARAM_URL,
            ),
            'courseimage' => array(
                'type' => PARAM_RAW,
            ),
            'showshortname' => array(
                'type' => PARAM_BOOL
            ),
            'coursecategory' => array(
                'type' => PARAM_TEXT
            )
        );
    }

    /**
     * Constructor - saves the persistent object, and the related objects.
     *
     * @param mixed $data - Either an stdClass or an array of values.
     * @param array $related - An optional list of pre-loaded objects related to this object.
     */
    public function __construct($data, $related = array()) {
        \core\external\exporter::__construct($data, $related);
    }

    protected static function define_related() {
        // We cache the context so it does not need to be retrieved from the course.
        return array('context' => '\\context');
    }

    protected function get_other_values(renderer_base $output) {
        global $CFG;
        $courseimage = self::get_course_image($this->data);
        if (!$courseimage) {
            $courseimage = $output->get_generated_image_for_id($this->data->id);
        }
        $coursecategory = \core_course_category::get($this->data->category, MUST_EXIST, true);
        return array(
            'fullnamedisplay' => get_course_display_name_for_list($this->data),
            'viewurl' => (new moodle_url('/course/view.php', array('id' => $this->data->id)))->out(false),
            'courseimage' => $courseimage,
            'showshortname' => $CFG->courselistshortnames ? true : false,
            'coursecategory' => $coursecategory->name
        );
    }

}

/**
 * Class containing data for featured_courses block.
 *
 * @package    block_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class featured_courses implements renderable, templatable {

    /**
     * @var array course
     */
    public $courses = [];

    /**
     * featured_courses constructor.
     * Retrieve matchin courses
     *
     * @param $coursesid
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct($coursesid) {
        global $DB;
        list($sql, $params) = $DB->get_in_or_equal($coursesid, SQL_PARAMS_NAMED);
        $this->courses = $DB->get_records_select('course', 'id ' . $sql, $params);
    }

    public function export_for_template(renderer_base $renderer) {
        $formattedcourses = array_map(function($course) use ($renderer) {
            context_helper::preload_from_record($course);
            $context = context_course::instance($course->id);
            $exporter = new mini_course_summary_exporter($course, ['context' => $context]);
            $exported = (array) $exporter->export($renderer);
            return $exported;
        }, $this->courses);
        $exportedvalue = [
            'courses' => array_values((array) $formattedcourses),
        ];
        return $exportedvalue;
    }
}