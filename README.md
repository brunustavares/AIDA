<p align="center">
  <img src="pix/logo.jpg" alt="AIDA Logo" width="300">
</p>

# AIDA
Moodle local plugin developed to provide a comprehensive suite of web services for data loading and reporting. It exposes numerous endpoints that allow external systems to query and retrieve aggregated academic data, performance indicators, and statistics directly from the Moodle database.

The plugin is designed to serve as a data source for business intelligence platforms, dashboards, or other institutional systems that require detailed information about student and teacher activities within Moodle.

This plugin was originally developed for [Universidade Aberta (UAb)](https://portal.uab.pt/).

## 1. Core Features

*   **Extensive Data Endpoints**: Offers a wide range of web services to extract data related to courses, students, forum posts, assessments, and user activity.
*   **Performance-Oriented**: Queries are designed for data extraction, often using pre-aggregated data from materialized views (`mv_*`) and including hints like `MAX_EXECUTION_TIME(0)` to handle large datasets.
*   **Parameter-Driven Queries**: Most endpoints are filterable by academic year (`ano_lectivo`), with some offering more granular filtering by student lists, course lists, or date ranges.
*   **Read-Only Access**: All web services are defined as `read` operations, ensuring they do not modify Moodle data.
*   **AJAX Enabled**: All services are configured for AJAX calls, making them suitable for integration with modern web applications.
*   **Secure**: Access to all endpoints is protected by Moodle's capabilities system, requiring the `moodle/site:viewreports` capability.

## 2. Architecture

`AIDA` is implemented as a standard Moodle local plugin (`local_aida`). Its architecture is straightforward and consists of two main parts:

1.  **Service Definitions (`db/services.php`)**: This file acts as a registry, defining all the external functions (web services) that the plugin provides. For each function, it specifies:
    *   The function name (e.g., `local_aida_dsd`).
    *   The implementing class and method.
    *   A description of the service.
    *   The required capability to access it.

2.  **External API Implementations (`external/*.php`)**: Each web service is implemented in its own PHP file within the `external/` directory. These files contain a class that extends Moodle's `\core_external\external_api` and typically includes three static methods:
    *   `execute_parameters()`: Defines the input parameters the web service accepts (e.g., `ano_lectivo`).
    *   `execute()`: Contains the core business logic. It builds and executes a SQL query against the Moodle database using the global `$DB` object and returns the results.
    *   `execute_returns()`: Defines the structure of the data that the web service returns, ensuring a consistent and predictable output format.

### Data Flow

The typical data flow is as follows:
1.  An external client (e.g., a BI tool, a web application) makes an authenticated request to a Moodle web service endpoint provided by AIDA.
2.  Moodle's external services layer routes the request to the appropriate class and method in the `local_aida` plugin.
3.  The `execute()` method in the corresponding `external/*.php` file is called.
4.  The method constructs and runs a SQL query to fetch the requested data from the Moodle database.
5.  The data is returned to the client in the format defined by the `execute_returns()` method.

## 3. Web Service Endpoints

The following is a detailed list of all web services provided by the AIDA plugin. All services require the `moodle/site:viewreports` capability.

### 3.1. Teacher and Course Distribution

*   **`local_aida_dsd`**: Distribuição do serviço do docente.

### 3.2. Student Data & Assessments

*   **`local_aida_estudantes_1C_AC_opcao`**: Estudantes em avaliação contínua p/ opção.
*   **`local_aida_estudantes_1C_AC`**: Total de estudantes em avaliação contínua.
*   **`local_aida_estudantes_1C_efolios_nota`**: E-fólios com nota.
*   **`local_aida_estudantes_1C_efolios`**: Total de e-fólios submetidos.
*   **`local_aida_estudantes_ausentes`**: Estudantes ausentes do sistema.
*   **`local_aida_estudantes_avaliacao`**: Avaliação dos estudantes p/ inscrição nas provas.
*   **`local_aida_estudantes_folios`**: Notas parcelares em avaliação contínua.
*   **`local_aida_estudantes_inscritos_UC`**: Total de estudantes inscritos nas UCs.
*   **`local_aida_estudantes_NEEs`**: Estudantes com Necessidades Educativas Especiais.
*   **`local_aida_estudantes_MAO`**: Frequência do Módulo de Ambientação Online.
*   **`local_aida_estudantes_UC_acesso`**: Acessos dos estudantes às UCs e ao sistema.
*   **`local_aida_estudantes_UC_actividade`**: Actividades dos estudantes nas UCs.
*   **`local_aida_estudantes_UC_escolha`**: Escolhas do método de avaliação por estudante e UC.
*   **`local_aida_estudantes_WF_escolha`**: Escolhas da realização presencial dos fluxos por estudante.
*   **`local_aida_folio_historico_data`**: Histórico de datas dos e-fólios.

### 3.3. Forum Posts & Interaction

*   **`local_aida_feedback_efolios_palavras`**: Total de palavras no feedback dos e-fólios.
*   **`local_aida_posts_UC_dias_docentes_tutores`**: Total de posts por dias e docentes/tutores.
*   **`local_aida_posts_UC_dias`**: Total de posts por dias.
*   **`local_aida_posts_UC_docentes_tutores`**: Total de posts por docentes/tutores.
*   **`local_aida_posts_UC_estudantes`**: Total de posts por estudantes.
*   **`local_aida_posts_UC_palavras_docentes_tutores`**: Total de palavras nos posts por docentes/tutores.
*   **`local_aida_posts_UC_palavras`**: Total de palavras nos posts.
*   **`local_aida_posts_UC_semanas_docentes_tutores`**: Total de posts por semanas e docentes/tutores.
*   **`local_aida_posts_UC_semanas`**: Total de posts por semanas.
*   **`local_aida_posts_UC_utilizadores`**: Total de posts por utilizadores.
*   **`local_aida_posts_UC`**: Total de posts.

### 3.4. Aggregated Summaries

*   **`local_aida_stds_full`**: Totais por estudante.
    *   **Description**: Retrieves aggregated data for each student for a given academic year from the `moodle.mv_AIDA_{ano_lectivo}_STDs` materialized view.
    *   **Returns**: `id`, `al` (ano lectivo), `std` (student number), `crs` (course code), `grd` (e-folio grade), `posts` (total posts).

*   **`local_aida_ucs_full`**: Totais por UC (Unidade Curricular).
    *   **Description**: Retrieves a comprehensive set of aggregated metrics for each course for a given academic year from the `moodle.mv_AIDA_{ano_lectivo}_UCs` materialized view.
    *   **Returns**: `id`, `al`, `crs`, `stds` (total students), `stdsac` (students in continuous assessment), `stdsacop` (students in continuous assessment by choice), `efolios` (students with submitted e-folios), `fdbkwords` (feedback word count), `posts`, `parts` (participants), `postsdt` (teacher/tutor posts), `days` (days with posts), `daysdt` (teacher/tutor days with posts), `weeks` (weeks with posts), `weeksdt` (teacher/tutor weeks with posts), `words` (word count), `wordsdt` (teacher/tutor word count).

*   **`local_aida_ucs_flows_tipo`**: Tipos de fluxo por UC.

## 4. Technical Details

*   **Backend**: PHP, Moodle External Services API
*   **Database**: Moodle DBAL (primarily tested with MySQL/MariaDB)
*   **Dependencies**: Relies on the core Moodle API.

### Default Academic Year

A global variable `$default_al` is used across the external API files to determine the default academic year if one is not provided in the request. The logic is as follows:
*   If the current month is September (9) or later, the academic year is `YYYY(YYYY+1)`.
*   If the current month is before September, the academic year is `(YYYY-1)YYYY`.

For example:
*   In October 2024, `$default_al` would be `202425`.
*   In February 2025, `$default_al` would be `202425`.

### SQL Queries

The plugin makes extensive use of direct SQL queries via `$DB->get_records_sql()`. The queries are highly specific to the UAb Moodle database schema, including custom materialized views (e.g., `mv_CoursePostAll`, `mv_AIDA_*_UCs`). They often contain complex `WHERE` clauses to filter by academic year based on Moodle's `course.idnumber` format.

## 5. Setup and Usage

1.  Install the plugin in the `local/aida` directory of your Moodle instance.
2.  Visit the Moodle notifications page (`/admin/index.php`) to allow the plugin to install its database tables and other components.
3.  Enable Moodle's web services and create a user with a token that has the `moodle/site:viewreports` capability.
4.  Configure your external application to make requests to the Moodle web service URL (e.g., `https://your.moodle.site/webservice/rest/server.php`) using the created token and the appropriate function name (e.g., `local_aida_ucs_full`).

## 6. License

**Author**: Bruno Tavares  
**Contact**: [brunustavares@gmail.com](mailto:brunustavares@gmail.com)  
**LinkedIn**: [https://www.linkedin.com/in/brunomastavares/](https://www.linkedin.com/in/brunomastavares/)  
**Copyright**: 2024-present Bruno Tavares  
**License**: GNU GPL v3 or later  

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.

### Assets

- **Source code**: GNU GPL v3 or later (© Bruno Tavares)  
- **Image**: created using [Image Creator from ©Microsoft Designer](https://www.bing.com/images/create?FORM=IRPGEN)
