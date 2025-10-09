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

class estudantes_MAO extends \core_external\external_api {
    public static function execute_parameters(): external_function_parameters {
        global $default_al;

        return new external_function_parameters(
            array(
                  'ano_lectivo' => new external_value(PARAM_INT, 'ano lectivo [AAAABB]', VALUE_DEFAULT, $default_al),
                  'lista_stds' => new external_value(PARAM_RAW, 'lista de estudantes [base64(NNNNNNN, NNNNNNN, ...)]', VALUE_OPTIONAL, null)
            )
        );
    }

    public static function execute($ano_lectivo, $lista_stds = null) {
        global $DB;

        $fromDt = substr($ano_lectivo, 0, 4) . '-09-01 00:00:00';
        $toDt = substr($ano_lectivo, 0, 4) + 1 . '-03-01 00:00:00';

        $criteria = "WHERE usr.username REGEXP '^[0-9]+$'
                         AND crs_std.shortname LIKE 'Amb___' ";

        if ($lista_stds) {
            $criteria .= "AND usr.username IN (" . base64_decode($lista_stds) . ") ";
        }

        $query = "SELECT /*+ MAX_EXECUTION_TIME(0) */
                         usr.id AS id,
                         '" . $ano_lectivo . "' AS 'al',
                         usr.username AS stdnum,
                         to_ascii(crs_std.stdName) AS stdname,
                         usr.id AS stdid,
                         crs_std.shortname AS ucsname,
                         to_ascii(crs_std.fullname) AS ucfname,
                         crs_std.crsID AS ucid,
                         COALESCE(T_comp, 0) AS tcomp,
                         COALESCE(T_sub, 0) AS tsub,
                         COALESCE(T_quiz, 0) AS tquiz
                  FROM moodle.mdl_user usr
                      INNER JOIN moodle.vw_CourseStudent crs_std ON usr.id = crs_std.stdID
                      RIGHT JOIN (
                                 SELECT /*+ MAX_EXECUTION_TIME(0) */
                                        crs_asg_sub.userid, crs_asg.course, COUNT(*) AS T_sub
                                 FROM moodle.mdl_assign_submission crs_asg_sub
                                     INNER JOIN moodle.mdl_assign crs_asg ON crs_asg_sub.assignment = crs_asg.id
                                     INNER JOIN moodle.vw_CourseStudent crs_std ON crs_asg.course = crs_std.crsID
                                         AND crs_asg_sub.userid = crs_std.stdID
                                 WHERE crs_asg_sub.status = 'submitted'
                                     AND crs_std.shortname LIKE 'Amb___'
									 AND crs_asg_sub.timemodified BETWEEN UNIX_TIMESTAMP('" . $fromDt . "') AND UNIX_TIMESTAMP('" . $toDt . "')
                                 GROUP BY crs_asg_sub.userid, crs_asg.course
                                ) sub ON (usr.id = sub.userid AND crs_std.crsID = sub.course)
                      LEFT JOIN (
                                 SELECT /*+ MAX_EXECUTION_TIME(0) */
                                        crs_quiz_atp.userid, crs_quiz.course, COUNT(*) AS T_quiz
                                 FROM moodle.mdl_quiz_attempts crs_quiz_atp
                                     INNER JOIN moodle.mdl_quiz crs_quiz ON crs_quiz_atp.quiz = crs_quiz.id
                                     INNER JOIN moodle.vw_CourseStudent crs_std ON crs_quiz.course = crs_std.crsID
                                         AND crs_quiz_atp.userid = crs_std.stdID
                                 WHERE crs_quiz_atp.state = 'finished'
                                     AND crs_std.shortname LIKE 'Amb___'
									 AND crs_quiz_atp.timemodified BETWEEN UNIX_TIMESTAMP('" . $fromDt . "') AND UNIX_TIMESTAMP('" . $toDt . "')
                                 GROUP BY crs_quiz_atp.userid, crs_quiz.course
                                ) quiz ON (usr.id = quiz.userid AND crs_std.crsID = quiz.course)
                      LEFT JOIN (
                                 SELECT /*+ MAX_EXECUTION_TIME(0) */
                                        crs_mod_comp.userid, crs_mod.course, COUNT(*) AS T_comp
                                 FROM moodle.mdl_course_modules_completion crs_mod_comp
                                     INNER JOIN moodle.mdl_course_modules crs_mod ON crs_mod_comp.coursemoduleid = crs_mod.id
                                     INNER JOIN moodle.vw_CourseStudent crs_std ON crs_mod.course = crs_std.crsID
                                         AND crs_mod_comp.userid = crs_std.stdID
                                 WHERE crs_std.shortname LIKE 'Amb___'
									AND crs_mod_comp.timemodified BETWEEN UNIX_TIMESTAMP('" . $fromDt . "') AND UNIX_TIMESTAMP('" . $toDt . "')
                                 GROUP BY crs_mod_comp.userid, crs_mod.course
                                ) comp ON (usr.id = comp.userid AND crs_std.crsID = comp.course) "
                . $criteria .
                 "ORDER BY usr.username , crs_std.shortname;";

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
                'tcomp' => new external_value(PARAM_INT, 'Total de actividades completadas'),
                'tsub' => new external_value(PARAM_INT, 'Total de submissoes'),
                'tquiz' => new external_value(PARAM_INT, 'Total de quizzes terminados'),])
        );
    }
}
