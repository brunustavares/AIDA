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

class estudantes_WF_escolha extends \core_external\external_api {
    public static function execute_parameters(): external_function_parameters {
        global $default_al;

        return new external_function_parameters(
            array(
                  'ano_lectivo' => new external_value(PARAM_INT, 'ano lectivo [AAAABB]', VALUE_DEFAULT, $default_al),
                  'lista_stds' => new external_value(PARAM_RAW, 'lista de estudantes [base64(NNNNNNN, NNNNNNN, ...)]', VALUE_OPTIONAL, null),
                  'flow_inloco' => new external_value(PARAM_INT, 'realização presencial [0 ou 1]', VALUE_OPTIONAL, '2'),
                  'de_ate' => new external_value(PARAM_TEXT, 'intervalo de datas [AAAAMMDD_AAAAMMDD]', VALUE_OPTIONAL, null)
            )
        );
    }

    public static function execute($ano_lectivo, $lista_stds = null, $flow_inloco = null, $de_ate = null) {
        global $DB;

        $criteria = "";

        if ($lista_stds) {
            $criteria .= "AND stdnum IN (" . base64_decode($lista_stds) . ") ";
        }

        if ($flow_inloco !== 2 && $flow_inloco !== null) {
            $criteria .= "AND optionid = " . $flow_inloco . " ";

        }

        if ($de_ate) {
            $from_dt = substr($de_ate, 0, 4) . '-' . substr($de_ate, 4, 2) . '-' . substr($de_ate, 6, 2) . " 00:00:00";
            $to_dt = substr($de_ate, 9, 4) . '-' . substr($de_ate, 13, 2) . '-' . substr($de_ate, 15, 2) . " 23:59:59";

            $criteria_chcDts = "AND FROM_UNIXTIME(chc_ans.timemodified) BETWEEN '" . $from_dt . "' AND '" . $to_dt . "' ";

        } else {
            // $from_dt = substr($ano_lectivo, 0, 4) . '-09-25';
            // $to_dt = substr($ano_lectivo, 0, 4) + 1 . '-09-30';
    
            $criteria_chcDts = "";

        }

        // $from_dt .= " 00:00:00";
        // $to_dt .= " 23:59:59";

        // $criteria_chcDts = "AND FROM_UNIXTIME(chc_ans.timemodified) BETWEEN '" . $from_dt . "' AND '" . $to_dt . "' ";

        $query = "WITH RankedChoices AS (
                                         SELECT /*+ MAX_EXECUTION_TIME(0) */
                                                cs.stdID,
                                                cs.stdNum,
                                                cs.stdName,
                                                cs.stdEmail,
                                                cs.shortname,
                                                cs.fullname,
                                                chc.name AS choice_name,
                                                chc_opt.text AS option_text,
                                                IF(COALESCE(chc_ans.optionid IS NOT NULL, FALSE) AND chc_opt.text = 'Presencialmente', 1, 0) AS optionID,
                                                chc_ans.timemodified,
                                                ROW_NUMBER() OVER (
                                                                   PARTITION BY cs.stdNum
                                                                   ORDER BY chc_ans.timemodified DESC
                                                                  ) AS rn
                                         FROM moodle.vw_CourseStudent cs
                                             INNER JOIN moodle.mdl_choice chc ON chc.course = cs.crsID
                                             INNER JOIN moodle.mdl_choice_options chc_opt ON chc_opt.choiceid = chc.id
                                             INNER JOIN moodle.mdl_choice_answers chc_ans ON (chc_ans.choiceid = chc.id
                                                                                             AND chc_ans.optionid = chc_opt.id
                                                                                             AND chc_ans.userid = cs.stdID)
                                             INNER JOIN (
                                                         SELECT DISTINCT stdNum
                                                         FROM moodle.vw_CourseStudent
                                                         WHERE stdNum REGEXP '^[0-9]+$'
                                                             AND shortname LIKE CONCAT('_1___\\_', SUBSTRING('" . $ano_lectivo . "', 3, 2), '\\___')
                                                        ) valid_std ON cs.stdNum = valid_std.stdNum
                                         WHERE cs.shortname = 'wfinfo'
                                             AND chc.name = 'Realização de Provas Presencialmente' "
                                           . $criteria_chcDts .
                                       ")
                  SELECT /*+ MAX_EXECUTION_TIME(0) */
                         stdID AS id,
                         '" . $ano_lectivo . "' AS 'al',
                         stdNum AS stdnum,
                         to_ascii(stdName) AS stdname,
                         stdID AS stdid,
                         optionID AS flow_inloco,
                         from_unixtime(timemodified) AS optdt
                  FROM RankedChoices
                  WHERE rn = 1 "
                    . $criteria .
                 "ORDER BY stdnum ASC;";

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
                'flow_inloco' => new external_value(PARAM_TEXT, 'opcao de realizacao presencial'),
                'optdt' => new external_value(PARAM_TEXT, 'data da opcao')
            ])
        );
    }
}
