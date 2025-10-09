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

class estudantes_UC_actividade extends \core_external\external_api {
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

        if ($lista_stds) {
            $criteria_Stds = "AND u.username IN (" . base64_decode($lista_stds) . ") ";
        }

        if ($lista_ucs) {
            $criteria_UCs = "AND SUBSTR(shortname, 1, 5) IN (" . base64_decode($lista_ucs) . ")";
        }

        if ($de_ate) {
            $from_dt = substr($de_ate, 0, 4) . '-' . substr($de_ate, 4, 2) . '-' . substr($de_ate, 6, 2);
            $to_dt = substr($de_ate, 9, 4) . '-' . substr($de_ate, 13, 2) . '-' . substr($de_ate, 15, 2);
    
            $criteria_postDts = "AND DATE(FROM_UNIXTIME(post.modified)) BETWEEN '" . $from_dt . "' AND '" . $to_dt . "' ";
            $criteria_subDts = "AND DATE(FROM_UNIXTIME(sub.timemodified)) BETWEEN '" . $from_dt . "' AND '" . $to_dt . "' ";
            $criteria_quizDts = "AND DATE(FROM_UNIXTIME(qa.timemodified)) BETWEEN '" . $from_dt . "' AND '" . $to_dt . "' ";
        }

        $query = "WITH ActiveStudents AS (
                                          SELECT /*+ MAX_EXECUTION_TIME(0) */
                                                 crsID,
                                                 shortname,
                                                 fullname,
                                                 visible,
                                                 stdID,
                                                 stdNum,
                                                 stdName,
                                                 stdEmail,
                                                 role
                                          FROM moodle.vw_CourseStudent vw
                                              INNER JOIN mdl_enrol e ON e.courseid = vw.crsID
                                              INNER JOIN mdl_user_enrolments ue ON (ue.userid = vw.stdID AND ue.enrolid = e.id)
                                          WHERE (shortname LIKE CONCAT('_1___\_', SUBSTRING('" . $ano_lectivo . "', 3, 2), '\___')
                                                  AND RIGHT(shortname, 2) REGEXP('^[0-9]+$')
                                                  AND SUBSTRING(shortname, 10, 2) <> '00')
                                              AND e.enrol IN ('database','manual')
                                              AND ue.status = 0 "
                                            . $criteria_UCs .
                                        "),
                       ForumPosts AS (
                                      SELECT /*+ MAX_EXECUTION_TIME(0) */
                                             post.userid,
                                             disc.course,
                                             DATE(FROM_UNIXTIME(post.modified)) AS activity_date,
                                             COUNT(*) AS tpost
                                      FROM moodle.mdl_forum_posts post
                                          JOIN moodle.mdl_forum_discussions disc ON disc.id = post.discussion
                                          JOIN ActiveStudents cs ON cs.crsID = disc.course AND cs.stdID = post.userid
                                      WHERE TRUE "
                                        . $criteria_postDts .
                                     "GROUP BY post.userid, disc.course, activity_date
                                     ),
                       AssignmentSubs AS (
                                          SELECT /*+ MAX_EXECUTION_TIME(0) */
                                                 sub.userid,
                                                 asg.course,
                                                 DATE(FROM_UNIXTIME(sub.timemodified)) AS activity_date,
                                                 COUNT(*) AS tsub
                                          FROM moodle.mdl_assign_submission sub
                                              JOIN moodle.mdl_assign asg ON asg.id = sub.assignment
                                              JOIN ActiveStudents cs ON cs.crsID = asg.course AND cs.stdID = sub.userid
                                          WHERE sub.status = 'submitted' "
                                            . $criteria_subDts .
                                         "GROUP BY sub.userid, asg.course, activity_date
                                         ),
                       QuizAttempts AS (
                                        SELECT /*+ MAX_EXECUTION_TIME(0) */
                                               qa.userid,
                                               q.course,
                                               DATE(FROM_UNIXTIME(qa.timemodified)) AS activity_date,
                                               COUNT(*) AS tquiz
                                        FROM moodle.mdl_quiz_attempts qa
                                            JOIN moodle.mdl_quiz q ON q.id = qa.quiz
                                            JOIN ActiveStudents cs ON cs.crsID = q.course AND cs.stdID = qa.userid
                                        WHERE qa.state = 'finished' "
                                          . $criteria_quizDts .
                                       "GROUP BY qa.userid, q.course, activity_date
                                       )
                  SELECT /*+ MAX_EXECUTION_TIME(0) */
                         CONCAT(u.id, COALESCE(UNIX_TIMESTAMP(fp.activity_date), UNIX_TIMESTAMP(asub.activity_date), UNIX_TIMESTAMP(qat.activity_date))) AS id,
                         '" . $ano_lectivo . "' AS al,
                         u.username AS stdnum,
                         TO_ASCII(cs.stdName) AS stdname,
                         u.id AS stdid,
                         cs.shortname AS ucsname,
                         TO_ASCII(cs.fullname) AS ucfname,
                         cs.crsID AS ucid,
                         COALESCE(fp.activity_date, asub.activity_date, qat.activity_date) AS actdt,
                         COALESCE(fp.tpost, 0) AS tpost,
                         COALESCE(asub.tsub, 0) AS tsub,
                         COALESCE(qat.tquiz, 0) AS tquiz
                  FROM moodle.mdl_user u
                      JOIN ActiveStudents cs ON cs.stdID = u.id
                      LEFT JOIN ForumPosts fp ON fp.userid = u.id AND fp.course = cs.crsID
                      LEFT JOIN AssignmentSubs asub ON asub.userid = u.id AND asub.course = cs.crsID
                      LEFT JOIN QuizAttempts qat ON qat.userid = u.id AND qat.course = cs.crsID
                  WHERE u.username REGEXP '^[0-9]+$'
                      AND (fp.activity_date IS NOT NULL OR asub.activity_date IS NOT NULL OR qat.activity_date IS NOT NULL) "
                    . $criteria_Stds .
                 "GROUP BY u.id, cs.crsID, actdt
                  ORDER BY u.username, cs.shortname, actdt;";

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
                'actdt' => new external_value(PARAM_TEXT, 'data das actividades'),
                'tpost' => new external_value(PARAM_INT, 'Total de posts em foruns'),
                'tsub' => new external_value(PARAM_INT, 'Total de submissoes'),
                'tquiz' => new external_value(PARAM_INT, 'Total de quizzes terminados'),])
        );
    }
}
