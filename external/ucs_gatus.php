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
 * @version    2026100603
 * @date       2026-10-06
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

class ucs_gatus extends \core_external\external_api {
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

        $criteria = "WHERE ((crs.shortname LIKE CONCAT('_____\_', SUBSTRING('" . $ano_lectivo . "', 3, 2), '\___')
                             AND RIGHT(crs.shortname, 2) REGEXP('^[0-9]+$')
                             AND SUBSTRING(crs.shortname, 10, 2) <> '00')) ";

        if ($lista_ucs) {
            $criteria .= "AND SUBSTR(crs.shortname, 1, 5) IN (" . base64_decode($lista_ucs) . ") ";
        }

        $criteria .= "ORDER BY crs.shortname ASC, g.name ASC, tutusr ASC ";

        $query = "SELECT /*+ MAX_EXECUTION_TIME(0) */
                         CONCAT(crs.id, SUBSTR(g.name, 5, 2)) AS `id`,
                         '" . $ano_lectivo . "' AS 'al',
                         crs.shortname AS ucsname,
                         to_ascii(crs.fullname) AS ucfname,
                         crs.id AS ucid,
                         tutor.username AS tutusr,
                         to_ascii(concat(tutor.firstname, ' ', tutor.lastname)) AS tutname,
                         tutor.email AS tutemail,
                         tutor.id AS tutid,
                         g.name AS `gatu`,
                         CONCAT(SUBSTR(crs.shortname, 1, 9), SUBSTR(g.name, 5, 2)) AS `gen_class`
                  FROM `mdl_course` `crs`
                      JOIN `mdl_context` `ctx` ON (`ctx`.`instanceid` = `crs`.`id` AND `ctx`.`contextlevel` = 50)
                      JOIN `mdl_role_assignments` `rl` ON (`rl`.`contextid` = `ctx`.`id` AND `rl`.`roleid` = 33)
                      JOIN `mdl_user` `tutor` ON `tutor`.`id` = `rl`.`userid`
                      JOIN `mdl_groups` `g` ON (`g`.`courseid` = `crs`.`id` AND `g`.`idnumber` LIKE 'gatu%')
                      JOIN `mdl_groups_members` `gm` ON (`gm`.`groupid` = `g`.`id` AND `gm`.`userid` = `tutor`.`id`) "
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
                'tutusr' => new external_value(PARAM_TEXT, 'username do tutor'),
                'tutname' => new external_value(PARAM_TEXT, 'nome do tutor'),
                'tutemail' => new external_value(PARAM_TEXT, 'email do tutor'),
                'tutid' => new external_value(PARAM_INT, 'ID do tutor'),
                'gatu' => new external_value(PARAM_TEXT, 'GATu'),
                'gen_class' => new external_value(PARAM_TEXT, 'turma gerada a partir da UC e do GATu')
            ])
        );
    }
}
