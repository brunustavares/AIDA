<p align="center">
  <img src="pix/logo.jpg" alt="AIDA Logo" width="300">
</p>

# AIDA

AIDA is a Moodle local plugin (`local_aida`) that exposes read-only external web services for academic data loading and reporting. It is intended to feed business intelligence platforms, dashboards, and other institutional systems that need structured Moodle activity, assessment, and course data.

This plugin was originally developed for [Universidade Aberta (UAb)](https://portal.uab.pt/).

## 1. Core Features

*   **Registry-based Moodle Web Services**: All endpoints are Moodle external functions registered under the `local_aida_*` namespace.
*   **Read-only Reporting**: Every registered service is declared as `type => read`; the plugin is designed for extraction, not Moodle data mutation.
*   **AJAX-ready Services**: All registered services set `ajax => true`.
*   **Capability-protected Access**: Every registered service requires the `moodle/site:viewreports` capability.
*   **Academic-year Filtering**: Most endpoints accept `ano_lectivo` in `AAAABB` format and default to the current academic year.
*   **Targeted Optional Filters**: Several endpoints accept base64-encoded student lists, UC lists, date ranges, NEEs status, workflow presence choice, or IdUC assessment strategy.
*   **Reporting-oriented SQL**: Endpoints query Moodle tables, custom views, and materialized views directly through `$DB->get_records_sql()`, often using `MAX_EXECUTION_TIME(0)` for large reporting queries.

## 2. Architecture

`AIDA` follows the standard Moodle local plugin structure:

The service registry is defined in [`db/services.php`](db/services.php), and each endpoint is implemented in its own file under [`external/`](external/).

1.  **Service registry (`db/services.php`)**: Defines the exposed function name, class, method, class path, description, capability, access type, and AJAX availability.
2.  **External API implementations (`external/*.php`)**: Each service extends `\core_external\external_api` and defines:
    *   `execute_parameters()`: accepted request parameters.
    *   `execute()`: query construction and execution.
    *   `execute_returns()`: returned data structure.

### Data Flow

1.  An authenticated client calls Moodle's web service endpoint, for example `/webservice/rest/server.php`.
2.  Moodle resolves the requested `wsfunction` to a registered `local_aida_*` function.
3.  Moodle loads the corresponding class from `external/*.php`.
4.  The endpoint validates parameters, executes the reporting SQL query, and returns the structured result.

## 3. Endpoint Registry

The plugin currently registers **31** read-only endpoints. The table below is based on the endpoints defined in `db/services.php` and the parameter/return structures implemented in `external/*.php`.

### Parameter Reference

*   `ano_lectivo`: Academic year in `AAAABB` format, for example `202425`. Defaults to the current academic year in most endpoints.
*   `lista_stds`: Optional base64-encoded student list, formatted as `NNNNNNN, NNNNNNN, ...`.
*   `lista_ucs`: Optional base64-encoded UC list, formatted as `NNNNN, NNNNN, ...`.
*   `de_ate`: Optional date interval in `AAAAMMDD_AAAAMMDD` format.
*   `status`: Optional NEEs status filter, `0` or `1`.
*   `flow_inloco`: Optional workflow in-person choice filter, `0` or `1`; the endpoint default leaves the result unfiltered.
*   `estrategia_de_avaliacao`: Optional IdUC assessment strategy/tipologia filter.

### Teacher and Course Distribution

| Function | Purpose | Parameters | Returns |
| --- | --- | --- | --- |
| `local_aida_dsd` | Distribuição do serviço docente por UC. | `ano_lectivo` | `id`, `al`, `crs`, `usr`, `usrnm` |

### Student Data and Assessments

| Function | Purpose | Parameters | Returns |
| --- | --- | --- | --- |
| `local_aida_estudantes_1C_AC_opcao` | Estudantes em avaliação contínua por opção. | `ano_lectivo` | `id`, `al`, `crs`, `tstd` |
| `local_aida_estudantes_1C_AC` | Total de estudantes em avaliação contínua. | `ano_lectivo` | `id`, `al`, `crs`, `tstd` |
| `local_aida_estudantes_1C_efolios_nota` | E-fólios com nota por estudante e UC. | `ano_lectivo` | `id`, `al`, `std`, `crs`, `grd` |
| `local_aida_estudantes_1C_efolios` | Total de e-fólios submetidos por UC. | `ano_lectivo` | `id`, `al`, `crs`, `tsubs` |
| `local_aida_estudantes_ausentes` | Estudantes ausentes do sistema. | `ano_lectivo` | `id`, `al`, `usr`, `usrnm`, `email` |
| `local_aida_estudantes_avaliacao` | Avaliação dos estudantes para inscrição nas provas. | `ano_lectivo`, `lista_stds`, `lista_ucs`, `de_ate` | `id`, `al`, `stdnum`, `stdname`, `stdemail`, `stdid`, `ucsname`, `ucfname`, `ucid`, `efolios`, `pfolio`, `exame`, `final`, `aval`, `updated` |
| `local_aida_estudantes_folios` | Notas parcelares em avaliação contínua. | `ano_lectivo`, `lista_stds`, `lista_ucs`, `de_ate` | `id`, `al`, `stdnum`, `stdname`, `stdid`, `ucsname`, `ucfname`, `ucid`, `folio`, `subdt`, `grd`, `grddt`, `acttype` |
| `local_aida_estudantes_inscritos_UC` | Total de estudantes inscritos nas UCs. | `ano_lectivo` | `id`, `al`, `crs`, `tstd` |
| `local_aida_estudantes_NEEs` | Estudantes com Necessidades Educativas Especiais. | `lista_stds`, `status` | `id`, `stdnum`, `stdname`, `stdid`, `status`, `xtrt` |
| `local_aida_estudantes_MAO` | Frequência do Módulo de Ambientação Online. | `ano_lectivo`, `lista_stds` | `id`, `al`, `stdnum`, `stdname`, `stdid`, `ucsname`, `ucfname`, `ucid`, `tcomp`, `tsub`, `tquiz` |
| `local_aida_estudantes_UC_acesso` | Acessos dos estudantes às UCs e ao sistema. | `ano_lectivo`, `lista_stds`, `lista_ucs`, `de_ate` | `id`, `al`, `stdnum`, `stdname`, `stdid`, `ucsname`, `ucfname`, `ucid`, `lastaccess` |
| `local_aida_estudantes_UC_actividade` | Atividades dos estudantes nas UCs. | `ano_lectivo`, `lista_stds`, `lista_ucs`, `de_ate` | `id`, `al`, `stdnum`, `stdname`, `stdid`, `ucsname`, `ucfname`, `ucid`, `actdt`, `tpost`, `tsub`, `tquiz` |
| `local_aida_estudantes_UC_escolha` | Escolhas do método de avaliação por estudante e UC. | `ano_lectivo`, `lista_stds`, `lista_ucs`, `de_ate` | `id`, `al`, `stdnum`, `stdname`, `stdid`, `ucsname`, `ucfname`, `ucid`, `opt`, `grpadddt` |
| `local_aida_estudantes_WF_escolha` | Escolhas da realização presencial dos fluxos por estudante. | `ano_lectivo`, `lista_stds`, `flow_inloco`, `de_ate` | `id`, `al`, `stdnum`, `stdname`, `stdid`, `flow_inloco`, `optdt` |
| `local_aida_folio_historico_data` | Histórico de datas dos e-fólios. | `ano_lectivo`, `lista_stds`, `lista_ucs` | `id`, `al`, `stdnum`, `stdname`, `stdid`, `ucsname`, `ucfname`, `ucid`, `folio`, `closedt` |

### IdUC Data

| Function | Purpose | Parameters | Returns |
| --- | --- | --- | --- |
| `local_aida_iduc` | Dados da IdUC nas UCs. | `ano_lectivo`, `lista_ucs`, `estrategia_de_avaliacao` | `id`, `al`, `ucsname`, `ucfname`, `ucid`, `iduc_ate`, `docente`, `sinopse`, `bibliografia`, `estrategia_de_avaliacao`, `at01_valor`, `at02_valor`, `at03_valor`, `at04_valor`, `at04_fluxo`, `exame`, `exame_fluxo`, `dimensao_do_gatu`, `lia`, `iduc_reg`, `docusr`, `docname`, `docid` |

`local_aida_iduc` reads the Moodle `IdUC` database activity, pivots configured IdUC fields into one row per UC record, joins the extracted UC code back to Moodle course data, and orders results by course short name. It detects `Tipologia NN` values in `estrategia_de_avaliacao` and normalizes the matching `tpNN_*` fields into `at01_valor`, `at02_valor`, `at03_valor`, `at04_valor`, `at04_fluxo`, and `exame`.

### Forum Posts and Interaction

| Function | Purpose | Parameters | Returns |
| --- | --- | --- | --- |
| `local_aida_feedback_efolios_palavras` | Total de palavras no feedback dos e-fólios. | `ano_lectivo` | `id`, `al`, `crs`, `tword` |
| `local_aida_posts_UC_dias_docentes_tutores` | Total de dias com posts por docentes/tutores. | `ano_lectivo` | `id`, `al`, `crs`, `tday` |
| `local_aida_posts_UC_dias` | Total de dias com posts por UC. | `ano_lectivo` | `id`, `al`, `crs`, `tday` |
| `local_aida_posts_UC_docentes_tutores` | Total de posts por docentes/tutores. | `ano_lectivo` | `id`, `al`, `crs`, `tpost` |
| `local_aida_posts_UC_estudantes` | Total de posts por estudante e UC. | `ano_lectivo` | `id`, `al`, `std`, `crs`, `tpost` |
| `local_aida_posts_UC_palavras_docentes_tutores` | Total de palavras nos posts por docentes/tutores. | `ano_lectivo` | `id`, `al`, `crs`, `tword` |
| `local_aida_posts_UC_palavras` | Total de palavras nos posts por UC. | `ano_lectivo` | `id`, `al`, `crs`, `tword` |
| `local_aida_posts_UC_semanas_docentes_tutores` | Total de semanas com posts por docentes/tutores. | `ano_lectivo` | `id`, `al`, `crs`, `tweek` |
| `local_aida_posts_UC_semanas` | Total de semanas com posts por UC. | `ano_lectivo` | `id`, `al`, `crs`, `tweek` |
| `local_aida_posts_UC_utilizadores` | Total de utilizadores participantes por UC. | `ano_lectivo` | `id`, `al`, `crs`, `tpart` |
| `local_aida_posts_UC` | Total de posts por UC. | `ano_lectivo` | `id`, `al`, `crs`, `tpost` |

### Aggregated Summaries

| Function | Purpose | Parameters | Returns |
| --- | --- | --- | --- |
| `local_aida_stds_full` | Totais agregados por estudante. | `ano_lectivo` | `id`, `al`, `std`, `crs`, `grd`, `posts` |
| `local_aida_ucs_full` | Totais agregados por UC. | `ano_lectivo` | `id`, `al`, `crs`, `stds`, `stdsac`, `stdsacop`, `efolios`, `fdbkwords`, `posts`, `parts`, `postsdt`, `days`, `daysdt`, `weeks`, `weeksdt`, `words`, `wordsdt` |
| `local_aida_ucs_flows_tipo` | Tipos de fluxo por UC. | `ano_lectivo`, `lista_ucs` | `id`, `al`, `ucsname`, `ucfname`, `ucid`, `docusr`, `docname`, `docid`, `flow`, `type`, `optdt` |

## 4. Technical Details

*   **Component**: `local_aida`
*   **Backend**: PHP, Moodle External Services API
*   **Plugin version**: `2026042811`
*   **Release**: `v1`
*   **Minimum Moodle build configured in `version.php`**: `2024042209`
*   **Database access**: Moodle `$DB` API with direct SQL reporting queries.

### Default Academic Year

Most external API files compute a default `$default_al`:

*   If the current month is September or later, the academic year is `YYYY(YY+1)`.
*   If the current month is before September, the academic year is `(YYYY-1)YY`.

Examples:

*   October 2024 defaults to `202425`.
*   February 2025 defaults to `202425`.

### SQL Sources

The plugin queries Moodle core tables and UAb-specific reporting structures, including:

*   Moodle tables such as `mdl_course`, `mdl_user`, `mdl_context`, `mdl_role_assignments`, `mdl_enrol`, `mdl_user_enrolments`, `mdl_forum_posts`, `mdl_forum_discussions`, `mdl_grade_items`, `mdl_grade_grades`, `mdl_choice*`, `mdl_feedback*`, `mdl_data*`, and activity completion/submission tables.
*   UAb custom views/materialized views such as `moodle.vw_CourseStudent`, `moodle.vw_StudentAC_EfolioAvg`, `moodle.mv_CoursePostAll`, `moodle.mv_AIDA_{ano_lectivo}_STDs`, and `moodle.mv_AIDA_{ano_lectivo}_UCs`.
*   UAb custom tables/schemas such as `moodle.tbl_folio_date_history` and `inscricoes.alunos_inscricoes`.

Many filters rely on the UAb Moodle `course.idnumber` / `course.shortname` format, especially the academic-year segment and UC code prefix.

## 5. Setup and Usage

1.  Install the plugin in the `local/aida` directory of the Moodle instance.
2.  Visit Moodle's admin notifications page (`/admin/index.php`) so Moodle registers the plugin.
3.  Enable Moodle web services.
4.  Create or select a service user with the `moodle/site:viewreports` capability.
5.  Generate a web service token for the service user.
6.  Call Moodle's web service endpoint with a registered function name, for example:

```text
https://your.moodle.site/webservice/rest/server.php?wstoken=TOKEN&wsfunction=local_aida_ucs_full&moodlewsrestformat=json&ano_lectivo=202425
```

## 6. License

**Author**: Bruno Tavares  
**Contact**: [brunustavares@gmail.com](mailto:brunustavares@gmail.com)  
**LinkedIn**: [https://www.linkedin.com/in/brunomastavares/](https://www.linkedin.com/in/brunomastavares/)  
**Copyright**: 2024-present Bruno Tavares  
**License**: GNU GPL v3 or later  

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or, at your option, any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.

### Assets

*   **Source code**: GNU GPL v3 or later, copyright Bruno Tavares.
*   **Image**: Created using [Image Creator from Microsoft Designer](https://www.bing.com/images/create?FORM=IRPGEN).
