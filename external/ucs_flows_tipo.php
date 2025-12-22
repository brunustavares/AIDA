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

class ucs_flows_tipo extends \core_external\external_api {
    public static function execute_parameters(): external_function_parameters {
        global $default_al;

        return new external_function_parameters(
            array(
                  'ano_lectivo' => new external_value(PARAM_INT, 'ano lectivo [AAAABB]', VALUE_DEFAULT, $default_al),
                  'lista_ucs' => new external_value(PARAM_RAW, 'lista de UCs [base64(NNNNN, NNNNN, ...)]', VALUE_OPTIONAL, null)
            )
        );
    }

    public static function execute($ano_lectivo, $lista_ucs=null) {
        global $DB;

        $criteria = "WHERE crs.idnumber LIKE CONCAT('_1___\_', SUBSTRING('" . $ano_lectivo . "', 3, 2), '\_MATRIZ') 
                         AND crsmod.idnumber = 'flow_type' ";

        if ($lista_ucs) {
            $criteria .= "AND SUBSTR(crs.idnumber, 1, 5) IN (" . base64_decode($lista_ucs) . ") ";
        }

        $criteria .= "ORDER BY ucsname ASC, optdt DESC, flow ASC ";

        if ($lista_ucs) {
            $criteria .= "LIMIT 2 ";
        }

        $query = "SELECT /*+ MAX_EXECUTION_TIME(0) */
                         concat(val.id, usr.id) AS id,
                         '" . $ano_lectivo . "' AS 'al',
                         crs.idnumber AS ucsname,
                         crs.fullname AS ucfname,
                         crs.id AS ucid,
                         usr.username AS docusr,
                         to_ascii(concat(usr.firstname, ' ', usr.lastname)) AS docname,
                         usr.id AS docid,
                         IF(LOWER(it.label) = 'exame', 'X', 'E') AS flow,
                         IF(val.value = 1, 'FLOWassign', IF(val.value = 3, 'FLOWmulti', 'FLOWlock')) AS type,
                         from_unixtime(comp.timemodified) AS optdt
                  FROM moodle.mdl_feedback_value val
                      INNER JOIN moodle.mdl_feedback_item it ON it.id = val.item
                      INNER JOIN mdl_feedback fdbk ON fdbk.id = it.feedback
                      INNER JOIN moodle.mdl_feedback_completed comp ON (comp.id = val.completed AND comp.feedback = fdbk.id)
                      INNER JOIN mdl_course crs ON crs.id = fdbk.course
                      INNER JOIN mdl_course_modules crsmod ON (crsmod.course = crs.id AND crsmod.instance = fdbk.id)
                      INNER JOIN mdl_user usr ON usr.id = comp.userid "
                . $criteria . ";";

        $result = $DB->get_records_sql($query, null, 0, 0);
    
        return $result;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'chave primaria'),
                'al' => new external_value(PARAM_INT, 'ano lectivo'),
                'ucsname' => new external_value(PARAM_TEXT, 'nume curto da UC'),
                'ucfname' => new external_value(PARAM_TEXT, 'nome completo da UC'),
                'ucid' => new external_value(PARAM_INT, 'ID da UC'),
                'docusr' => new external_value(PARAM_TEXT, 'username do docente'),
                'docname' => new external_value(PARAM_TEXT, 'nome do docente'),
                'docid' => new external_value(PARAM_INT, 'ID do docente'),
                'flow' => new external_value(PARAM_TEXT, 'prova'),
                'type' => new external_value(PARAM_TEXT, 'tipo de fluxo'),
                'optdt' => new external_value(PARAM_TEXT, 'data da opcao')
            ])
        );
    }
}
