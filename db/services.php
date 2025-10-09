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

defined('MOODLE_INTERNAL') || die();

$capabilities = 'moodle/site:viewreports';

$functions = [
    'local_aida_dsd' => [
        'classname' => 'dsd',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/dsd.php',
        'description' => 'distribuição do serviço do docente',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_1C_AC_opcao' => [
        'classname' => 'estudantes_1C_AC_opcao',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_1C_AC_opcao.php',
        'description' => 'estudantes em avaliação contínua p/ opção',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_1C_AC' => [
        'classname' => 'estudantes_1C_AC',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_1C_AC.php',
        'description' => 'total de estudantes em avaliação contínua',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_1C_efolios_nota' => [
        'classname' => 'estudantes_1C_efolios_nota',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_1C_efolios_nota.php',
        'description' => 'e-fólios c/ nota',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_1C_efolios' => [
        'classname' => 'estudantes_1C_efolios',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_1C_efolios.php',
        'description' => 'total de e-fólios submetidos',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_ausentes' => [
        'classname' => 'estudantes_ausentes',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_ausentes.php',
        'description' => 'estudantes ausentes do sistema',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_avaliacao' => [
        'classname' => 'estudantes_avaliacao',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_avaliacao.php',
        'description' => 'avaliação dos estudantes p/ inscrição nas provas',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_folios' => [
        'classname' => 'estudantes_folios',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_folios.php',
        'description' => 'notas parcelares em avaliação contínua',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_inscritos_UC' => [
        'classname' => 'estudantes_inscritos_UC',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_inscritos_UC.php',
        'description' => 'total de estudantes inscritos nas UCs',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_NEEs' => [
        'classname' => 'estudantes_NEEs',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_NEEs.php',
        'description' => 'estudantes c/ Necessidades Educativas Especiais',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_MAO' => [
        'classname' => 'estudantes_MAO',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_MAO.php',
        'description' => 'frequência do Módulo de Ambientação Online',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_UC_acesso' => [
        'classname' => 'estudantes_UC_acesso',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_UC_acesso.php',
        'description' => 'acessos dos estudantes às UCs e ao sistema',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_UC_actividade' => [
        'classname' => 'estudantes_UC_actividade',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_UC_actividade.php',
        'description' => 'actividades dos estudantes nas UCs',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_UC_escolha' => [
        'classname' => 'estudantes_UC_escolha',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_UC_escolha.php',
        'description' => 'escolhas do método de avaliação p/ estudante e UC',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_estudantes_WF_escolha' => [
        'classname' => 'estudantes_WF_escolha',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/estudantes_WF_escolha.php',
        'description' => 'escolhas da realização presencial dos fluxos p/ estudante',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_feedback_efolios_palavras' => [
        'classname' => 'feedback_efolios_palavras',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/feedback_efolios_palavras.php',
        'description' => 'total de palavras no feedback dos e-fólios',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_posts_UC_dias_docentes_tutores' => [
        'classname' => 'posts_UC_dias_docentes_tutores',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/posts_UC_dias_docentes_tutores.php',
        'description' => 'total de posts p/ dias e docentes/tutores',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_posts_UC_dias' => [
        'classname' => 'posts_UC_dias',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/posts_UC_dias.php',
        'description' => 'total de posts p/ dias',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_posts_UC_docentes_tutores' => [
        'classname' => 'posts_UC_docentes_tutores',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/posts_UC_docentes_tutores.php',
        'description' => 'total de posts p/ docentes/tutores',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_posts_UC_estudantes' => [
        'classname' => 'posts_UC_estudantes',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/posts_UC_estudantes.php',
        'description' => 'total de posts p/ estudantes',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_posts_UC_palavras_docentes_tutores' => [
        'classname' => 'posts_UC_palavras_docentes_tutores',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/posts_UC_palavras_docentes_tutores.php',
        'description' => 'total de palavras nos posts p/ docentes/tutores',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_posts_UC_palavras' => [
        'classname' => 'posts_UC_palavras',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/posts_UC_palavras.php',
        'description' => 'total de palavras nos posts',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_posts_UC_semanas_docentes_tutores' => [
        'classname' => 'posts_UC_semanas_docentes_tutores',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/posts_UC_semanas_docentes_tutores.php',
        'description' => 'total de posts p/ semanas e docentes/tutores',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_posts_UC_semanas' => [
        'classname' => 'posts_UC_semanas',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/posts_UC_semanas.php',
        'description' => 'total de posts p/ semanas',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_posts_UC_utilizadores' => [
        'classname' => 'posts_UC_utilizadores',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/posts_UC_utilizadores.php',
        'description' => 'total de posts p/ utilizadores',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_posts_UC' => [
        'classname' => 'posts_UC',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/posts_UC.php',
        'description' => 'total de posts',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_stds_full' => [
        'classname' => 'stds_full',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/stds_full.php',
        'description' => 'totais por estudante',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_ucs_flows_tipo' => [
        'classname' => 'ucs_flows_tipo',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/ucs_flows_tipo.php',
        'description' => 'tipos de fluxo por UC',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
    'local_aida_ucs_full' => [
        'classname' => 'ucs_full',
        'methodname' => 'execute',
        'classpath' => 'local/aida/external/ucs_full.php',
        'description' => 'totais por UC',
        'capabilities' => $capabilities,
        'type' => 'read',
        'ajax' => true,
    ],
];
