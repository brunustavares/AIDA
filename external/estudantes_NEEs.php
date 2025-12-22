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

class estudantes_NEEs extends \core_external\external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                  'lista_stds' => new external_value(PARAM_RAW, 'lista de estudantes [base64(NNNNNNN, NNNNNNN, ...)]', VALUE_OPTIONAL, null),
                  'status' => new external_value(PARAM_INT, 'estado [0 ou 1]', VALUE_OPTIONAL, null)
            )
        );
    }

    public static function execute($lista_stds = null, $status = null) {
        global $DB;

        $criteria = "";

        if ($lista_stds) {
            $criteria .= "WHERE stdnum IN (" . base64_decode($lista_stds) . ") ";
        }

        if ($status !== null) {
            $criteria .= ($lista_stds ? "AND " : "WHERE ") . "status = " . $status . " ";

        }

        $query = "SELECT *
                  FROM (
                        SELECT /*+ MAX_EXECUTION_TIME(0) */
                               usr.id AS id,
                               tmp3.stdNum AS stdnum,
                               to_ascii(tmp3.stdName) AS stdname,
                               usr.id AS stdid,
                               IF((tmp3.status = 'Activo'), 1, 0) AS status,
                               tmp3.xtrT AS xtrt
                        FROM (
                              SELECT /*+ MAX_EXECUTION_TIME(0) */
                                     MAX(CASE WHEN (tmp2.name = 'stdNum') THEN tmp2.content END) AS stdNum,
                                     MAX(CASE WHEN (tmp2.name = 'stdName') THEN tmp2.content END) AS stdName,
                                     MAX(CASE WHEN (tmp2.name = 'status') THEN tmp2.content END) AS status,
                                     MAX(CASE WHEN (tmp2.name = 'xtrT') THEN tmp2.content END) AS xtrT
                              FROM (
                                    SELECT /*+ MAX_EXECUTION_TIME(0) */
                                           tmp1.recordid AS recordid,
                                           mdl_data_fields.name AS name,
                                           tmp1.content AS content
                                    FROM (
                                          SELECT /*+ MAX_EXECUTION_TIME(0) */
                                                 mdl_data_content.id AS id,
                                                 mdl_data_content.fieldid AS fieldid,
                                                 mdl_data_content.recordid AS recordid,
                                                 mdl_data_content.content AS content,
                                                 mdl_data_content.content1 AS content1,
                                                 mdl_data_content.content2 AS content2,
                                                 mdl_data_content.content3 AS content3
                                          FROM mdl_data_content
                                          WHERE mdl_data_content.fieldid IN (
                                                                             SELECT /*+ MAX_EXECUTION_TIME(0) */
                                                                                    mdl_data_fields.id
                                                                             FROM mdl_data_fields
                                                                             WHERE mdl_data_fields.dataid IN (
                                                                                                              SELECT /*+ MAX_EXECUTION_TIME(0) */
                                                                                                                     mdl_data.id
                                                                                                              FROM mdl_data
                                                                                                                  JOIN mdl_course ON mdl_course.id = mdl_data.course
                                                                                                                  JOIN mdl_course_modules ON (mdl_course_modules.course = mdl_course.id AND mdl_course_modules.instance = mdl_data.id)
                                                                                                              WHERE mdl_course_modules.idnumber = 'NEEs_DB'
                                                                                                             )
                                                                            )
                                         ) tmp1
                                        JOIN mdl_data_fields ON mdl_data_fields.id = tmp1.fieldid
                                   ) tmp2
                              GROUP BY tmp2.recordid
                              ORDER BY tmp2.recordid
                             ) tmp3
                            JOIN mdl_user usr ON usr.username = tmp3.stdNum
                        ORDER BY tmp3.stdNum
                       ) neestd "
                . $criteria .
                 "ORDER BY stdNum;";

        $result = $DB->get_records_sql($query, null, 0, 0);
    
        return $result;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'chave primaria'),
                'stdnum' => new external_value(PARAM_TEXT, 'username do estudante'),
                'stdname' => new external_value(PARAM_TEXT, 'nome do estudante'),
                'stdid' => new external_value(PARAM_INT, 'ID do estudante'),
                'status' => new external_value(PARAM_INT, 'activo'),
                'xtrt' => new external_value(PARAM_INT, 'tempo adicional'),
            ])
        );
    }
}
