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
 * @version    2025121806
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

class folio_historico_data extends \core_external\external_api {
    public static function execute_parameters(): external_function_parameters {
        global $default_al;

        return new external_function_parameters(
            array(
                  'ano_lectivo' => new external_value(PARAM_INT, 'ano lectivo [AAAABB]', VALUE_DEFAULT, $default_al),
                  'lista_stds' => new external_value(PARAM_RAW, 'lista de estudantes [base64(NNNNNNN, NNNNNNN, ...)]', VALUE_OPTIONAL, null),
                  'lista_ucs' => new external_value(PARAM_RAW, 'lista de UCs [base64(NNNNN, NNNNN, ...)]', VALUE_OPTIONAL, null)
            )
        );
    }

    public static function execute($ano_lectivo, $lista_stds = null, $lista_ucs=null) {
        global $DB;

        if ($lista_stds) {
            $criteria_Stds = "AND u.username IN (" . base64_decode($lista_stds) . ") ";
        }

        if ($lista_ucs) {
            $criteria_UCs = "AND SUBSTR(c.idnumber, 1, 5) IN (" . base64_decode($lista_ucs) . ")";
        }

        $query = "SELECT /*+ MAX_EXECUTION_TIME(0) */
                         view_ID() AS id,
                         '" . $ano_lectivo . "' AS al,
                         u.username AS stdnum,
                         to_ascii(CONCAT(u.firstname, ' ', u.lastname)) AS 'stdname',
                         u.id AS stdid,
                         c.idnumber AS ucsname,
                         to_ascii(c.fullname) AS ucfname,
                         c.id AS ucid,
                         modIDn AS folio,
                         close_date AS closedt
                  FROM moodle.tbl_folio_date_history h
                      INNER JOIN moodle.mdl_course c ON c.idnumber = h.crsIDn
                      INNER JOIN moodle.mdl_user u ON u.username = h.stdNum
                  WHERE SUBSTR(c.idnumber, 7, 2) COLLATE utf8mb4_0900_ai_ci = SUBSTR('" . $ano_lectivo . "', 3, 2) COLLATE utf8mb4_0900_ai_ci "
                    . $criteria_Stds .
                      $criteria_UCs . ";";

        $result = $DB->get_records_sql($query, null, 0, 0);
    
        return $result;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'chave primaria'),
                'al' => new external_value(PARAM_INT, 'ano lectivo'),
                'stdnum' => new external_value(PARAM_TEXT, 'username do estudante'),
                'stdname' => new external_value(PARAM_TEXT, 'nome do estudante'),
                'stdid' => new external_value(PARAM_INT, 'ID do estudante'),
                'ucsname' => new external_value(PARAM_TEXT, 'nume curto da UC'),
                'ucfname' => new external_value(PARAM_TEXT, 'nome completo da UC'),
                'ucid' => new external_value(PARAM_INT, 'ID da UC'),
                'folio' => new external_value(PARAM_TEXT, 'nome do folio'),
                'closedt' => new external_value(PARAM_TEXT, 'data de encerramento do folio'),])
        );
    }
}
