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

class estudantes_folios extends \core_external\external_api {
    public static function execute_parameters(): external_function_parameters {
        global $default_al;

        return new external_function_parameters(
            array(
                'ano_lectivo' => new external_value(PARAM_INT, 'ano lectivo [AAAABB]', VALUE_DEFAULT, $default_al),
                'lista_stds' => new external_value(PARAM_RAW, 'lista de estudantes [base64(NNNNNNN, NNNNNNN, ...)]', VALUE_OPTIONAL, null),
                'lista_ucs' => new external_value(PARAM_RAW, 'lista de UCs [base64(NNNNN, NNNNN, ...)]', VALUE_OPTIONAL, null),
                'de_ate' => new external_value(PARAM_TEXT, 'intervalo de datas [AAAAMMDD_AAAAMMDD]', VALUE_OPTIONAL, null)
          )
        );
    }

    public static function execute($ano_lectivo, $lista_stds = null, $lista_ucs=null, $de_ate = null) {
        global $DB;

        $criteria = "WHERE cm.idnumber IN ('efolioA', 'efolioB', 'efolioC', 'pfolio')
                         AND gi.itemtype = 'mod'
                         AND gi.itemmodule IN ('assign', 'quiz')
                         AND u.username REGEXP '^[0-9]+$'
                         AND (c.idnumber LIKE CONCAT('_1___\_', SUBSTR('" . $ano_lectivo . "', 3, 2), '\___')
                             AND RIGHT(c.idnumber, 2) REGEXP('^[0-9]+$')
                             AND SUBSTRING(c.idnumber, 10, 2) <> '00')
                         AND e.enrol IN ('database','manual')
                         AND ue.status = 0 ";

        if ($lista_stds) {
            $criteria .= "AND u.username IN (" . base64_decode($lista_stds) . ") ";
        }

        if ($lista_ucs) {
            $criteria .= "AND SUBSTR(c.idnumber, 1, 5) IN (" . base64_decode($lista_ucs) . ") ";
        }

        if ($de_ate) {
            $from_dt = substr($de_ate, 0, 4) . '-' . substr($de_ate, 4, 2) . '-' . substr($de_ate, 6, 2);
            $to_dt = substr($de_ate, 9, 4) . '-' . substr($de_ate, 13, 2) . '-' . substr($de_ate, 15, 2);
    
            $criteria .= "AND (DATE(FROM_UNIXTIME(s.timemodified)) BETWEEN '" . $from_dt . "' AND '" . $to_dt . "'
                              OR DATE(FROM_UNIXTIME(q.timemodified)) BETWEEN '" . $from_dt . "' AND '" . $to_dt . "') ";
        }

        $query = "SELECT /*+ MAX_EXECUTION_TIME(0) */
                         gg.id AS id,
                         '" . $ano_lectivo . "' AS al,
                         u.username AS stdnum,
                         TO_ASCII(CONCAT(u.firstname, ' ', u.lastname)) AS stdname,
                         u.id AS stdid,
                         c.idnumber AS ucsname,
                         TO_ASCII(c.fullname) AS ucfname,
                         c.id AS ucid,
                         cm.idnumber AS folio,
                         CASE
                             WHEN s.timemodified IS NOT NULL THEN FROM_UNIXTIME(s.timemodified)
                             WHEN q.timemodified IS NOT NULL THEN FROM_UNIXTIME(q.timemodified)
                             ELSE NULL
                         END AS subdt,
                         CASE
                             WHEN gg.finalgrade >= 0 THEN gg.finalgrade
                             ELSE NULL
                         END AS grd,
                         FROM_UNIXTIME(gg.timemodified) AS grddt,
                         gi.itemmodule  AS acttype
                         FROM moodle.mdl_grade_items gi
                             JOIN moodle.mdl_grade_grades gg ON gg.itemid = gi.id
                             JOIN moodle.mdl_user u ON u.id = gg.userid
                             JOIN moodle.mdl_course_modules cm ON cm.instance = gi.iteminstance
                             JOIN moodle.mdl_course c ON (c.id = cm.course AND c.id = gi.courseid)
                             JOIN mdl_enrol e ON e.courseid = c.id
                             JOIN mdl_user_enrolments ue ON (ue.userid = u.id AND ue.enrolid = e.id)
                             LEFT JOIN moodle.mdl_assign_submission s ON (s.assignment = gi.iteminstance AND s.userid = gg.userid AND s.status = 'submitted')
                             LEFT JOIN moodle.mdl_quiz_attempts q ON (q.quiz = gi.iteminstance AND q.userid = gg.userid AND q.state = 'finished') "
                       . $criteria .
                        "ORDER BY c.idnumber ASC, cm.idnumber ASC;";

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
                'folio' => new external_value(PARAM_TEXT, 'folio'),
                'subdt' => new external_value(PARAM_TEXT, 'data da submissao'),
                'grd' => new external_value(PARAM_TEXT, 'nota'),
                'grddt' => new external_value(PARAM_TEXT, 'data da nota'),
                'acttype' => new external_value(PARAM_TEXT, 'tipo de actividade'),
            ])
        );
    }
}
