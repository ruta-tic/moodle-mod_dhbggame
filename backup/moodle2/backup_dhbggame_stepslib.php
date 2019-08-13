<?php
//
// You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Define all the backup steps that will be used by the backup_dhbggame_activity_task
 *
 * @package contribution
 * @copyright 2013 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

 /**
 * Define the complete dhbggame structure for backup, with file and id annotations
 */
class backup_dhbggame_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        //the dhbggame module stores no user info

        // Define each element separated
        $dhbggame = new backup_nested_element('dhbggame', array('id'), array(
            'name', 'intro', 'introformat', 'externalurl',
            'display', 'displayoptions', 'parameters', 'timemodified'));


        // Build the tree
        //nothing here for URLs

        // Define sources
        $dhbggame->set_source_table('dhbggame', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        //module has no id annotations

        // Define file annotations
        $dhbggame->annotate_files('mod_dhbggame', 'intro', null); // This file area hasn't itemid

        // Return the root element (dhbggame), wrapped into standard activity structure
        return $this->prepare_activity_structure($dhbggame);

    }
}
