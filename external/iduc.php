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
 * @version    2026042811
 * @date       2026-04-28
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

class iduc extends \core_external\external_api {
    public static function execute_parameters(): external_function_parameters {
        global $default_al;

        return new external_function_parameters(
            array(
                  'ano_lectivo' => new external_value(PARAM_INT, 'ano lectivo [AAAABB]', VALUE_DEFAULT, $default_al),
                  'lista_ucs' => new external_value(PARAM_RAW, 'lista de UCs [base64(NNNNN, NNNNN, ...)]', VALUE_OPTIONAL, null),
                  'estrategia_de_avaliacao' => new external_value(PARAM_INT, 'tipologia de avaliação', VALUE_OPTIONAL, null)
            )
        );
    }

    public static function execute($ano_lectivo, $lista_ucs=null, $estrategia_de_avaliacao=null) {
        global $DB;

        $criteria = "WHERE c.idnumber LIKE CONCAT('_1___\_', SUBSTRING('" . $ano_lectivo . "', 3, 2), '\_MATRIZ') ";

        if ($lista_ucs) {
            $criteria .= "AND SUBSTR(c.idnumber, 1, 5) IN (" . base64_decode($lista_ucs) . ") ";
        }

        if ($estrategia_de_avaliacao) {
            $criteria .= "AND estrategia_de_avaliacao LIKE '%" . $estrategia_de_avaliacao . "' ";
        }

        $criteria .= "ORDER BY c.shortname ";

        $query = "SELECT /*+ MAX_EXECUTION_TIME(0) */
                         IdUC.id,
                         IdUC.al,
                         c.idnumber AS ucsname,
                         c.fullname AS ucfname,
                         c.id AS ucid,
                         IdUC.iduc_ate,
                         IdUC.designacao_da_uc,
                         IdUC.codigo_da_uc,
                         IdUC.docente,
                         IdUC.sinopse,
                         IdUC.bibliografia,
                         IdUC.estrategia_de_avaliacao,
                         IdUC.tp01_at01_valor,
                         IdUC.tp01_at02_valor,
                         IdUC.tp01_at03_valor,
                         IdUC.tp01_at04_valor,
                         IdUC.tp01_at04_fluxo,
                         IdUC.tp01_exame,
                         IdUC.tp02_at01_valor,
                         IdUC.tp02_at02_valor,
                         IdUC.tp02_at03_valor,
                         IdUC.tp02_at04_valor,
                         IdUC.tp03_at01_valor,
                         IdUC.tp03_at02_valor,
                         IdUC.tp03_at03_valor,
                         IdUC.tp03_at04_valor,
                         IdUC.tp04_at01_valor,
                         IdUC.tp04_at02_valor,
                         IdUC.tp04_at02_fluxo,
                         IdUC.tp04_exame,
                         IdUC.exame_fluxo,
                         IdUC.dimensao_do_gatu,
                         IdUC.lia,
                         IdUC.iduc_reg,
                         IdUC.docusr,
                         IdUC.docname,
                         IdUC.docid
                  FROM (
                        SELECT /*+ MAX_EXECUTION_TIME(0) */
                               concat(db.id, dbc.fieldid, dbc.recordid) AS id,
                               '" . $ano_lectivo . "' AS 'al',
                               c.idnumber AS ucsname,
                               c.fullname AS ucfname,
                               c.id AS ucid,
                               from_unixtime(db.timeavailableto) AS iduc_ate,
                               MAX(CASE WHEN dbf.name = 'designacao_da_uc' THEN dbc.content END) AS designacao_da_uc,
                               MAX(CASE WHEN dbf.name = 'codigo_da_uc' THEN dbc.content END) AS codigo_da_uc,
                            /* MAX(CASE WHEN dbf.name = 'cursos' THEN dbc.content END) AS cursos,
                               MAX(CASE WHEN dbf.name = 'departamento' THEN dbc.content END) AS departamento,
                               MAX(CASE WHEN dbf.name = 'horas_de_trabalho' THEN dbc.content END) AS horas_de_trabalho,
                               MAX(CASE WHEN dbf.name = 'ects' THEN dbc.content END) AS ects,
                               MAX(CASE WHEN dbf.name = 'duracao' THEN dbc.content END) AS duracao,
                               MAX(CASE WHEN dbf.name = 'area_cientifica' THEN dbc.content END) AS area_cientifica,
                               MAX(CASE WHEN dbf.name = 'total_de_horas_de_trabalho' THEN dbc.content END) AS total_de_horas_de_trabalho,
                               MAX(CASE WHEN dbf.name = 'total_de_horas_de_contacto' THEN dbc.content END) AS total_de_horas_de_contacto, */
                               MAX(CASE WHEN dbf.name = 'docente' THEN dbc.content END) AS docente,
                               MAX(CASE WHEN dbf.name = 'sinopse' THEN dbc.content END) AS sinopse,
                            /* MAX(CASE WHEN dbf.name = 'ods' THEN dbc.content END) AS ods,
                               MAX(CASE WHEN dbf.name = 'competencias' THEN dbc.content END) AS competencias,
                               MAX(CASE WHEN dbf.name = 'resultados' THEN dbc.content END) AS resultados,
                               MAX(CASE WHEN dbf.name = 'conteudos' THEN dbc.content END) AS conteudos,
                               MAX(CASE WHEN dbf.name = 'metodologias' THEN dbc.content END) AS metodologias,
                               MAX(CASE WHEN dbf.name = 'detalhe_da_avaliacao' THEN dbc.content END) AS detalhe_da_avaliacao,
                               MAX(CASE WHEN dbf.name = 'prova_de_coerencia' THEN dbc.content END) AS prova_de_coerencia, */
                               MAX(CASE WHEN dbf.name = 'bibliografia' THEN dbc.content END) AS bibliografia,
                               MAX(CASE WHEN dbf.name = 'estrategia_de_avaliacao' THEN dbc.content END) AS estrategia_de_avaliacao,
                               MAX(CASE WHEN dbf.name = 'tp01_at01_valor' THEN dbc.content END) AS tp01_at01_valor,
                               MAX(CASE WHEN dbf.name = 'tp01_at02_valor' THEN dbc.content END) AS tp01_at02_valor,
                               MAX(CASE WHEN dbf.name = 'tp01_at03_valor' THEN dbc.content END) AS tp01_at03_valor,
                               MAX(CASE WHEN dbf.name = 'tp01_at04_valor' THEN dbc.content END) AS tp01_at04_valor,
                               MAX(CASE WHEN dbf.name = 'tp01_at04_fluxo' THEN dbc.content END) AS tp01_at04_fluxo,
                               MAX(CASE WHEN dbf.name = 'tp01_exame' THEN dbc.content END) AS tp01_exame,
                               MAX(CASE WHEN dbf.name = 'tp02_at01_valor' THEN dbc.content END) AS tp02_at01_valor,
                               MAX(CASE WHEN dbf.name = 'tp02_at02_valor' THEN dbc.content END) AS tp02_at02_valor,
                               MAX(CASE WHEN dbf.name = 'tp02_at03_valor' THEN dbc.content END) AS tp02_at03_valor,
                               MAX(CASE WHEN dbf.name = 'tp02_at04_valor' THEN dbc.content END) AS tp02_at04_valor,
                            /* MAX(CASE WHEN dbf.name = 'tp02_at05_valor' THEN dbc.content END) AS tp02_at05_valor,
                               MAX(CASE WHEN dbf.name = 'tp02_at06_valor' THEN dbc.content END) AS tp02_at06_valor,
                               MAX(CASE WHEN dbf.name = 'tp02_at07_valor' THEN dbc.content END) AS tp02_at07_valor,
                               MAX(CASE WHEN dbf.name = 'tp02_at08_valor' THEN dbc.content END) AS tp02_at08_valor,
                               MAX(CASE WHEN dbf.name = 'tp02_at09_valor' THEN dbc.content END) AS tp02_at09_valor,
                               MAX(CASE WHEN dbf.name = 'tp02_at10_valor' THEN dbc.content END) AS tp02_at10_valor, */
                               MAX(CASE WHEN dbf.name = 'tp03_at01_valor' THEN dbc.content END) AS tp03_at01_valor,
                               MAX(CASE WHEN dbf.name = 'tp03_at02_valor' THEN dbc.content END) AS tp03_at02_valor,
                               MAX(CASE WHEN dbf.name = 'tp03_at03_valor' THEN dbc.content END) AS tp03_at03_valor,
                               MAX(CASE WHEN dbf.name = 'tp03_at04_valor' THEN dbc.content END) AS tp03_at04_valor,
                               MAX(CASE WHEN dbf.name = 'tp04_at01_valor' THEN dbc.content END) AS tp04_at01_valor,
                               MAX(CASE WHEN dbf.name = 'tp04_at02_valor' THEN dbc.content END) AS tp04_at02_valor,
                               MAX(CASE WHEN dbf.name = 'tp04_at02_fluxo' THEN dbc.content END) AS tp04_at02_fluxo,
                               MAX(CASE WHEN dbf.name = 'tp04_exame' THEN dbc.content END) AS tp04_exame,
                               MAX(CASE WHEN dbf.name = 'exame_fluxo' THEN dbc.content END) AS exame_fluxo,
                               MAX(CASE WHEN dbf.name = 'dimensao_do_gatu' THEN dbc.content END) AS dimensao_do_gatu,
                               MAX(CASE WHEN dbf.name = 'lia' THEN dbc.content END) AS lia,
                               from_unixtime(dbr.timemodified) AS iduc_reg,
                               u.username AS docusr,
                               to_ascii(concat(u.firstname, ' ', u.lastname)) AS docname,
                               u.id AS docid
                        FROM moodle.mdl_data db
                            JOIN moodle.mdl_data_fields dbf ON dbf.dataid = db.id
                            JOIN moodle.mdl_data_content dbc ON dbc.fieldid = dbf.id
                            JOIN moodle.mdl_data_records dbr ON dbr.dataid = db.id
                            JOIN moodle.mdl_course c ON c.id = db.course
                            JOIN moodle.mdl_user u ON u.id = dbr.userid
                        WHERE db.name = 'IdUC' 
                        GROUP BY c.id , db.id
                       ) AS IdUC
                      INNER JOIN moodle.mdl_course c ON substr(c.idnumber, 1, 5) = IdUC.codigo_da_uc "
                . $criteria . ";";

        $result = $DB->get_records_sql($query, null, 0, 0);

        if ($result) {
            $data = [];

            foreach ($result as $row) {
                $rowdata = (array) $row;
                $tipologiaRow = null;

                $template = [
                    'id' => $rowdata['id'] ?? null,
                    'al' => $rowdata['al'] ?? null,

                    'ucsname' => $rowdata['ucsname'] ?? null,
                    'ucfname' => $rowdata['ucfname'] ?? null,
                    'ucid' => $rowdata['ucid'] ?? null,

                    'iduc_ate' => $rowdata['iduc_ate'] ?? null,

                    'docente' => $rowdata['docente'] ?? null,
                    'sinopse' => $rowdata['sinopse'] ?? null,

                    'bibliografia' => $rowdata['bibliografia'] ?? null,

                    'estrategia_de_avaliacao' => $rowdata['estrategia_de_avaliacao'] ?? null,
                    'at01_valor' => null,
                    'at02_valor' => null,
                    'at03_valor' => null,
                    'at04_valor' => null,
                    'at04_fluxo' => null,
                    'exame' => null,
                    'exame_fluxo' => $rowdata['exame_fluxo'] ?? null,

                    'dimensao_do_gatu' => $rowdata['dimensao_do_gatu'] ?? null,
                    'lia' => $rowdata['lia'] ?? null,

                    'iduc_reg' => $rowdata['iduc_reg'] ?? null,
                    'docusr' => $rowdata['docusr'] ?? null,
                    'docname' => $rowdata['docname'] ?? null,
                    'docid' => $rowdata['docid'] ?? null
                ];
                
                if (!empty($rowdata['estrategia_de_avaliacao']) &&
                    preg_match('/Tipologia\s*0?(\d+)/i', $rowdata['estrategia_de_avaliacao'], $m)) {
                    $tipologiaRow = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                }

                foreach ($rowdata as $column => $value) {
                    if (preg_match('/^tp(\d{2})_(.+)$/', $column, $m)) {
                        $tipologiaColumn = $m[1];
                        $field = $m[2];

                        if ($tipologiaRow === null || $tipologiaColumn !== $tipologiaRow) { continue; }

                        if ($column === 'tp04_at02_fluxo') {
                            $template['at04_valor'] = $template['at02_valor'];
                            $template['at02_valor'] = null;
                            $template['at04_fluxo'] = $value;

                            continue;
                        }

                        if (array_key_exists($field, $template)) {
                            $template[$field] = $value;
                        }

                        continue;
                    }
                }

                $data[] = $template;
            }

            return $data;
        }

        return [];

    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'chave primaria'),
                'al' => new external_value(PARAM_INT, 'ano lectivo'),
                'ucsname' => new external_value(PARAM_TEXT, 'nume curto da UC'),
                'ucfname' => new external_value(PARAM_TEXT, 'nome completo da UC'),
                'ucid' => new external_value(PARAM_INT, 'ID da UC'),
                'iduc_ate' => new external_value(PARAM_TEXT, 'data limite p/ preenchimento da IdUC'),
                'docente' => new external_value(PARAM_TEXT, 'docente da UC'),
                'sinopse' => new external_value(PARAM_RAW, 'sinopse da UC'),
                'bibliografia' => new external_value(PARAM_TEXT, 'bibliografia da UC'),
                'estrategia_de_avaliacao' => new external_value(PARAM_TEXT, 'tipologia de avaliação da UC'),
                'at01_valor' => new external_value(PARAM_TEXT, 'valor max. do recurso 01'),
                'at02_valor' => new external_value(PARAM_TEXT, 'valor max. do recurso 02'),
                'at03_valor' => new external_value(PARAM_TEXT, 'valor max. do recurso 03'),
                'at04_valor' => new external_value(PARAM_TEXT, 'valor max. do recurso 04'),
                'at04_fluxo' => new external_value(PARAM_TEXT, 'fluxo do recurso 04'),
                'exame' => new external_value(PARAM_TEXT, 'exame na época normal'),
                'exame_fluxo' => new external_value(PARAM_TEXT, 'fluxo do exame (todas as épocas)'),
                'dimensao_do_gatu' => new external_value(PARAM_TEXT, 'dimensao do GATu'),
                'lia' => new external_value(PARAM_TEXT, 'LIA'),
                'iduc_reg' => new external_value(PARAM_TEXT, 'data de registo da IdUC'),
                'docusr' => new external_value(PARAM_TEXT, 'username do docente'),
                'docname' => new external_value(PARAM_TEXT, 'nome do docente'),
                'docid' => new external_value(PARAM_INT, 'ID do docente')
            ])
        );
    }
}