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

class estudantes_UC_acesso extends \core_external\external_api {
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

        $criteria = "WHERE ((ucsname LIKE CONCAT('_1___\_', SUBSTRING('" . $ano_lectivo . "', 3, 2), '\___')
                         AND RIGHT(ucsname, 2) REGEXP('^[0-9]+$')
                         AND SUBSTRING(ucsname, 10, 2) <> '00' ";

        if ($lista_ucs) {
            $criteria .= "AND SUBSTR(ucsname, 1, 5) IN (" . base64_decode($lista_ucs) . ")";
        }

        $criteria .= ") OR ucsname = '(sys_logon)') ";

        if ($lista_stds) {
            $criteria .= "AND stdnum IN (" . base64_decode($lista_stds) . ") ";
        }

        if ($de_ate) {
            $from_dt = substr($de_ate, 0, 4) . '-' . substr($de_ate, 4, 2) . '-' . substr($de_ate, 6, 2);
            $to_dt = substr($de_ate, 9, 4) . '-' . substr($de_ate, 13, 2) . '-' . substr($de_ate, 15, 2);

        } else {
            $from_dt = substr($ano_lectivo, 0, 4) . '-09-25';
            $to_dt = substr($ano_lectivo, 0, 4) + 1 . '-09-30';
    
        }

        $from_dt .= " 00:00:00";
        $to_dt .= " 23:59:59";

        $criteria .= "AND DATE(FROM_UNIXTIME(lastaccess)) BETWEEN '" . $from_dt . "' AND '" . $to_dt . "' ";

        $query = "SELECT /*+ MAX_EXECUTION_TIME(0) */
                         CONCAT(stdid, ucid) AS id,
                         '" . $ano_lectivo . "' AS 'al',
                         stdnum AS stdnum,
                         to_ascii(stdname) AS stdname,
                         stdid AS stdid,
                         ucsname AS ucsname,
                         to_ascii(ucfname) AS ucfname,
                         ucid AS ucid,
                         FROM_UNIXTIME(lastaccess) AS lastaccess
                  FROM (
                        SELECT /*+ MAX_EXECUTION_TIME(0) */
                               usr.username AS stdnum,
                               CONCAT(usr.firstname, ' ', usr.lastname) AS stdname,
                               usr.id AS stdid,
                               '(sys_logon)' AS ucsname,
                               'PlataformAbERTA' AS ucfname,
                               '1' AS ucid,
                               usr.currentlogin AS lastaccess
                        FROM moodle.mdl_user usr
                        WHERE usr.username REGEXP '^[0-9]+$'
                            AND usr.currentlogin >= UNIX_TIMESTAMP('" . $from_dt . "')
                        UNION ALL
                        SELECT /*+ MAX_EXECUTION_TIME(0) */
                               usr.username AS stdnum,
                               CONCAT(usr.firstname, ' ', usr.lastname) AS stdname,
                               usr.id AS stdid,
                               crs.shortname AS ucsname,
                               crs.fullname AS ucfname,
                               crs.id AS ucid,
                               usr_acs.timeaccess AS lastaccess
                        FROM moodle.mdl_user_lastaccess usr_acs
                            INNER JOIN moodle.mdl_course crs ON crs.id = usr_acs.courseid
                            INNER JOIN moodle.mdl_user usr ON usr.id = usr_acs.userid
                            INNER JOIN mdl_enrol e ON e.courseid = crs.id
                            INNER JOIN mdl_user_enrolments ue ON (ue.userid = usr.id AND ue.enrolid = e.id)
                        WHERE usr.username REGEXP '^[0-9]+$'
                            AND usr_acs.timeaccess >= UNIX_TIMESTAMP('" . $from_dt . "')
                            AND e.enrol IN ('database','manual')
                            AND ue.status = 0
                       ) AS usr_logon "
                . $criteria .
                 "ORDER BY stdnum ASC, ucsname ASC;";

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
                'lastaccess' => new external_value(PARAM_TEXT, 'ultimo acesso')
            ])
        );
    }
}
