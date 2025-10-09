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
 * @copyright  Copyright (C) 2024-2025 Bruno Tavares
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

class posts_UC_semanas_docentes_tutores extends \core_external\external_api {
    public static function execute_parameters(): external_function_parameters {
        global $default_al;

        return new external_function_parameters(
            array('ano_lectivo' => new external_value(PARAM_INT, 'ano lectivo (AAAABB)', VALUE_DEFAULT, $default_al))
        );
    }

    public static function execute($ano_lectivo) {
        global $DB;

        $fromDt = substr($ano_lectivo, 0, 4) . '-09-25 00:00:00';
        $toDt = substr($ano_lectivo, 0, 4) + 1 . '-09-30 23:59:59';

        $query = "SELECT /*+ MAX_EXECUTION_TIME(0) */
                         VIEW_ID() AS 'id',
                         '" . $ano_lectivo . "' AS 'al',
                         LEFT (crs.idnumber, 5) AS 'crs',
                         COUNT(DISTINCT WEEKOFYEAR(FROM_UNIXTIME(forPst.created))) AS 'tweek'
                  FROM mdl_forum_posts forPst
                      INNER JOIN mdl_forum_discussions forDsc ON forDsc.id = forPst.discussion
                      INNER JOIN mdl_course crs ON crs.id = forDsc.course
                      INNER JOIN mdl_context ctx ON (ctx.instanceid = crs.id AND ctx.contextlevel = 50)
                      INNER JOIN mdl_role_assignments rl ON (rl.contextid = ctx.id AND (rl.roleid = 3 OR rl.roleid = 27 OR rl.roleid = 10))
                      INNER JOIN mdl_user usr ON (usr.id = rl.userid AND usr.id = forPst.userid)
                  WHERE (crs.idnumber LIKE CONVERT(CONCAT('_____\_', MID(" . $ano_lectivo . ", 3, 2), '\___') USING utf8)
                          AND RIGHT(crs.idnumber, 2) REGEXP('^[0-9]+$')
                          AND crs.idnumber NOT LIKE '%_00')
                      AND (forPst.created >= UNIX_TIMESTAMP('" . $fromDt . "')
	                      AND forPst.created <= UNIX_TIMESTAMP('" . $toDt . "'))
                  GROUP BY  LEFT (crs.idnumber, 5)
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
                'tweek' => new external_value(PARAM_INT, 'total de semanas'),
            ])
        );
    }
}
