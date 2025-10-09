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

class feedback_efolios_palavras extends \core_external\external_api {
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
                         LEFT(courseCD,5) AS 'crs',
                         SUM(LENGTH(fdbkTxt) - LENGTH(REPLACE(CAST(fdbkTxt AS CHAR CHARACTER SET utf8), ' ', '')) + 2) AS 'tword'
                  FROM moodle.mv_CourseFdbk
                  WHERE (courseCD LIKE CONVERT(CONCAT('_____\_', MID(" . $ano_lectivo . ", 3, 2), '\___') USING utf8)
                          AND RIGHT(courseCD, 2) REGEXP('^[0-9]+$')
                          AND courseCD NOT LIKE '%_00')
                      AND (fdbkDate >= UNIX_TIMESTAMP('" . $fromDt . "')
                      AND fdbkDate <= UNIX_TIMESTAMP('" . $toDt . "'))
                  GROUP BY LEFT(courseCD, 5)
                  ORDER BY courseCD;";

        $result = $DB->get_records_sql($query, null, 0, 0);
    
        return $result;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'chave primaria'),
                'al' => new external_value(PARAM_INT, 'ano lectivo'),
                'crs' => new external_value(PARAM_INT, 'codigo da UC'),
                'tword' => new external_value(PARAM_INT, 'total de estudantes'),
            ])
        );
    }
}
