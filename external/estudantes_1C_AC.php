<?php
/**
 * AIDA
 * Moodle webservices for data loading
 * (developed for UAb - Universidade Aberta)
 *
 * @category   moodle_plugin
 * @package    local_aida
 * @author     Bruno Tavares <brunustavares@gmail.com>
 * @link       https://www.linkedin.com/in/brunomastavares/
 * @copyright  Copyright (C) 2024-present Bruno Tavares
 * @license    GNU General Public License v3 or later
 *             https://www.gnu.org/licenses/gpl-3.0.html
 * @version    2025071604
 * @date       2024-10-09
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

 require_once($CFG->libdir . '/externallib.php');
 
 defined('MOODLE_INTERNAL') || die();

 global $default_al;
 
 if ((int)date("m") >= 9) {
	$default_al = date("Y") . ((int)(substr(date("Y"), 2, 2)) + 1);

} else {
	$default_al = (int)(date("Y") - 1) . substr(date("Y"), 2, 2);

}

class estudantes_1C_AC extends \core_external\external_api {
    public static function execute_parameters(): external_function_parameters {
        global $default_al;

        return new external_function_parameters(
            array('ano_lectivo' => new external_value(PARAM_INT, 'ano lectivo (AAAABB)', VALUE_DEFAULT, $default_al))
        );
    }

    public static function execute($ano_lectivo) {
        global $DB;

        $query = "SELECT /*+ MAX_EXECUTION_TIME(0) */
                         VIEW_ID() AS 'id',
                         '" . $ano_lectivo . "' AS 'al',
                         LEFT (crs.idnumber, 5) AS 'crs',
                         COUNT(DISTINCT std.id) AS 'tstd'
                  FROM mdl_course crs
                      INNER JOIN mdl_context ctx ON (ctx.instanceid = crs.id AND ctx.contextlevel = 50)
                      INNER JOIN mdl_role_assignments rl ON (rl.contextid = ctx.id AND (rl.roleid = 5 OR rl.roleid = 28 OR rl.roleid = 11 OR rl.roleid = 17))
                      INNER JOIN mdl_user std ON std.id = rl.userid
                      INNER JOIN mdl_enrol e ON e.courseid = crs.id
                      INNER JOIN mdl_user_enrolments ue ON (ue.userid = std.id AND ue.enrolid = e.id)
                      INNER JOIN mdl_groups grp ON crs.id = grp.courseid
	                  INNER JOIN mdl_groups_members grpMb ON (grpMb.groupid = grp.id AND grpMb.userid = std.id)
                  WHERE (crs.idnumber LIKE CONVERT(CONCAT('_____\_', MID('" . $ano_lectivo . "', 3, 2), '\___') USING utf8)
                          AND RIGHT(crs.idnumber, 2) REGEXP('^[0-9]+$')
                          AND SUBSTRING(crs.idnumber, 10, 2) <> '00')
                      AND e.enrol IN ('database','manual')
                      AND ue.status = 0
                      AND grp.name = 'Avaliação Contínua'
                  GROUP BY LEFT (crs.idnumber, 5)
                  ORDER BY crs.idnumber;";

        $result = $DB->get_records_sql($query, null, 0, 0);
    
        return $result;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'chave primaria'),
                'al' => new external_value(PARAM_INT, 'ano lectivo'),
                'crs' => new external_value(PARAM_INT, 'codigo da UC'),
                'tstd' => new external_value(PARAM_INT, 'total de estudantes'),
            ])
        );
    }
}
