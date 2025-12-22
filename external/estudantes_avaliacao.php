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

class estudantes_avaliacao extends \core_external\external_api {
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

        $criteria = "";

        if ($lista_stds) {
            $criteria .= "WHERE eval.username IN (" . base64_decode($lista_stds) . ") ";
        }

        if ($lista_ucs) {
            $criteria .= ($lista_stds ? "AND " : "WHERE ") . "SUBSTR(eval.idnumber, 1, 5) IN (" . base64_decode($lista_ucs) . ") ";
        }

        if ($de_ate) {
            $from_dt = substr($de_ate, 0, 4) . '-' . substr($de_ate, 4, 2) . '-' . substr($de_ate, 6, 2);
            $to_dt = substr($de_ate, 9, 4) . '-' . substr($de_ate, 13, 2) . '-' . substr($de_ate, 15, 2);
    
            $criteria .= ($lista_stds || $lista_ucs ? "AND " : "WHERE ") . "DATE(eval.updated) BETWEEN '" . $from_dt . "' AND '" . $to_dt . "' ";
        }

        $query = "WITH eval AS (
                                SELECT /*+ MAX_EXECUTION_TIME(0) */
                                       usr.username AS username,
                                       usr.firstname AS firstname,
                                       usr.lastname AS lastname,
                                       usr.email AS email,
                                       usr.id AS stdID,
                                       crs.idnumber AS idnumber,
                                       crs.fullname AS fullname,
                                       crs.id AS UCID,
                                       MAX(CASE WHEN grd_it.itemname = 'E-fólios' THEN grd_grds.finalgrade END) AS efolios,
                                       MAX(CASE WHEN grd_it.itemname = 'P-fólio' THEN grd_grds.finalgrade END) AS pfolio,
                                       MAX(CASE WHEN grd_it.itemname = 'Exame' THEN grd_grds.finalgrade END) AS exame,
                                       MAX(CASE WHEN grd_it.itemname = 'Nota final' THEN grd_grds.finalgrade END) AS final,
                                       CASE
                                           WHEN MAX(CASE WHEN grd_it.itemname = 'E-fólios' THEN grd_grds.finalgrade END) >= 3.5 THEN 'C'
                                           ELSE 'F'
                                       END AS aval,
                                       from_unixtime(grd_grds.timemodified) as updated
                                FROM mdl_user usr
                                    JOIN mdl_groups_members grp_mb ON grp_mb.userid = usr.id
                                    JOIN mdl_groups grp ON grp.id = grp_mb.groupid
                                    JOIN mdl_course crs ON crs.id = grp.courseid
                                    JOIN mdl_enrol e ON e.courseid = crs.id
                                    JOIN mdl_user_enrolments ue ON (ue.userid = usr.id AND ue.enrolid = e.id)
                                    JOIN mdl_grade_items grd_it ON grd_it.courseid = crs.id
                                    LEFT JOIN mdl_grade_grades grd_grds ON grd_grds.itemid = grd_it.id AND grd_grds.userid = usr.id
                                WHERE grd_it.itemname IN ('E-fólios', 'P-fólio', 'Exame', 'Nota final')
                                    AND (crs.idnumber LIKE CONCAT('_1___\_', SUBSTR('" . $ano_lectivo . "', 3, 2), '\___')
                                        AND SUBSTRING(crs.idnumber, 10, 2) <> '00')
                                    AND usr.username REGEXP '^[0-9]+$'
                                    AND e.enrol IN ('database','manual')
                                    AND ue.status = 0
                                GROUP BY usr.id, crs.id
                               )
                  SELECT /*+ MAX_EXECUTION_TIME(0) */
                         CONCAT(eval.stdID, eval.UCID) AS id,
                         '" . $ano_lectivo . "' AS al,
                         eval.username AS stdnum,
                         to_ascii(CONCAT(eval.firstname, ' ', eval.lastname)) AS stdname,
                         eval.email AS stdemail,
                         eval.stdID AS stdid,
                         eval.idnumber AS ucsname,
                         to_ascii(eval.fullname) AS ucfname,
                         eval.UCID AS ucid,
                         eval.efolios AS efolios,
                         eval.pfolio AS pfolio,
                         eval.exame AS exame,
                         eval.final AS final,
                         eval.aval AS aval,
                         eval.updated AS updated
                  FROM eval "
                . $criteria .
                 "ORDER BY eval.username, eval.idnumber;";

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
                'stdemail' => new external_value(PARAM_TEXT, 'email do estudante'),
                'stdid' => new external_value(PARAM_INT, 'ID do estudante'),
                'ucsname' => new external_value(PARAM_TEXT, 'nume curto da UC'),
                'ucfname' => new external_value(PARAM_TEXT, 'nome completo da UC'),
                'ucid' => new external_value(PARAM_INT, 'ID da UC'),
                'efolios' => new external_value(PARAM_TEXT, 'soma dos e-folios'),
                'pfolio' => new external_value(PARAM_TEXT, 'nota do e-folio Global'),
                'exame' => new external_value(PARAM_TEXT, 'media do exame'),
                'final' => new external_value(PARAM_TEXT, 'nota final'),
                'aval' => new external_value(PARAM_TEXT, 'tipo de avaliacao'),
                'updated' => new external_value(PARAM_TEXT, 'data da nota')
            ])
        );
    }
}
