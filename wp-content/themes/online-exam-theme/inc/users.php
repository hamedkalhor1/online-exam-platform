<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Expert User Helper
|--------------------------------------------------------------------------
|
| در این پروژه نقش اختصاصی جدید تعریف نمی‌کنیم.
| کارشناسان همان کاربران با نقش پیش‌فرض WordPress یعنی subscriber / مشترک هستند.
|
*/

function online_exam_admin_is_exam_user($user_id)
{
    $user_id = absint($user_id);

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return false;
    }

    return in_array(
        'subscriber',
        (array) $user->roles,
        true
    );
}


/*
|--------------------------------------------------------------------------
| Score Formatting
|--------------------------------------------------------------------------
*/

function online_exam_admin_format_score($value)
{
    $number = (float) $value;

    if (
        abs(
            $number - round($number)
        ) < 0.000001
    ) {
        return (string) (int) round($number);
    }

    return rtrim(
        rtrim(
            number_format(
                $number,
                2,
                '.',
                ''
            ),
            '0'
        ),
        '.'
    );
}


/*
|--------------------------------------------------------------------------
| Get Expert Exam Overview
|--------------------------------------------------------------------------
*/

function online_exam_admin_get_user_exam_overview($user_id)
{
    global $wpdb;

    $user_id = absint($user_id);

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return false;
    }

    $tables = online_exam_get_tables();


    /*
    |--------------------------------------------------------------------------
    | All Published Exams
    |--------------------------------------------------------------------------
    */

    $exam_ids = get_posts(
        array(
            'post_type'      => 'exam',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );

    $exam_ids =
        array_map(
            'absint',
            (array) $exam_ids
        );


    /*
    |--------------------------------------------------------------------------
    | User Attempts
    |--------------------------------------------------------------------------
    */

    $attempts =
        $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tables['attempts']}
                WHERE user_id = %d
                ORDER BY id DESC
                ",
                $user_id
            ),
            ARRAY_A
        );

    $attempts =
        is_array($attempts)
            ? $attempts
            : array();


    /*
     * Latest attempt per exam.
     */

    $attempt_by_exam = array();

    foreach ($attempts as $attempt) {

        $exam_id =
            absint(
                $attempt['exam_id']
            );

        if (
            $exam_id &&
            !isset(
                $attempt_by_exam[$exam_id]
            )
        ) {
            $attempt_by_exam[$exam_id] =
                $attempt;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | User Results
    |--------------------------------------------------------------------------
    */

    $results =
        $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tables['results']}
                WHERE user_id = %d
                ORDER BY id DESC
                ",
                $user_id
            ),
            ARRAY_A
        );

    $results =
        is_array($results)
            ? $results
            : array();


    $result_by_attempt = array();

    foreach ($results as $result) {

        $attempt_id =
            absint(
                $result['attempt_id']
            );

        if (
            $attempt_id &&
            !isset(
                $result_by_attempt[$attempt_id]
            )
        ) {
            $result_by_attempt[$attempt_id] =
                $result;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Build Rows
    |--------------------------------------------------------------------------
    */

    $rows = array();

    $counts = array(
        'total'       => count($exam_ids),
        'submitted'   => 0,
        'graded'      => 0,
        'pending'     => 0,
        'in_progress' => 0,
        'not_started' => 0,
    );


    foreach ($exam_ids as $exam_id) {

        $exam =
            online_exam_get_exam_data(
                $exam_id
            );

        if (!$exam) {
            continue;
        }


        $attempt =
            isset(
                $attempt_by_exam[$exam_id]
            )
                ? $attempt_by_exam[$exam_id]
                : null;


        $result = null;
        $status = 'not_started';


        if (!$attempt) {

            $counts['not_started']++;

        } else {

            $attempt_id =
                absint(
                    $attempt['id']
                );


            $result =
                isset(
                    $result_by_attempt[$attempt_id]
                )
                    ? $result_by_attempt[$attempt_id]
                    : null;


            if (
                $attempt['status'] ===
                'in_progress'
            ) {

                $status = 'in_progress';
                $counts['in_progress']++;

            } elseif (
                $attempt['status'] ===
                'submitted'
            ) {

                $counts['submitted']++;


                if (
                    $result &&
                    $result['status'] ===
                    'pending'
                ) {

                    $status = 'pending';
                    $counts['pending']++;

                } elseif (
                    $result &&
                    $result['status'] ===
                    'graded'
                ) {

                    $status = 'graded';
                    $counts['graded']++;

                } else {

                    $status = 'submitted';
                }

            } else {

                $status =
                    sanitize_key(
                        (string)
                        $attempt['status']
                    );
            }
        }


        $rows[] = array(
            'exam'    => $exam,
            'attempt' => $attempt,
            'result'  => $result,
            'status'  => $status,
        );
    }


    return array(
        'user'   => $user,
        'counts' => $counts,
        'rows'   => $rows,
    );
}


/*
|--------------------------------------------------------------------------
| Status Labels
|--------------------------------------------------------------------------
*/

function online_exam_admin_get_exam_status_label($status)
{
    $labels = array(
        'not_started' => 'شرکت نکرده',
        'in_progress' => 'در حال انجام',
        'pending'     => 'در انتظار تصحیح',
        'graded'      => 'تصحیح شده',
        'submitted'   => 'ثبت شده',
        'expired'     => 'پایان زمان',
    );

    return isset($labels[$status])
        ? $labels[$status]
        : 'نامشخص';
}


function online_exam_admin_get_exam_status_class($status)
{
    $allowed = array(
        'not_started',
        'in_progress',
        'pending',
        'graded',
        'submitted',
        'expired',
    );

    return in_array(
        $status,
        $allowed,
        true
    )
        ? $status
        : 'unknown';
}


/*
|--------------------------------------------------------------------------
| Users List Columns
|--------------------------------------------------------------------------
*/

function online_exam_add_users_exam_columns($columns)
{
    $new_columns = array();

    foreach ($columns as $key => $label) {

        $new_columns[$key] =
            $label;

        if ($key === 'email') {

            $new_columns[
                'online_exam_count'
            ] =
                'آزمون‌های داده‌شده';


            $new_columns[
                'online_exam_last_result'
            ] =
                'آخرین نتیجه';


            $new_columns[
                'online_exam_results'
            ] =
                'نتایج آزمون‌ها';
        }
    }


    if (
        !isset(
            $new_columns[
                'online_exam_count'
            ]
        )
    ) {

        $new_columns[
            'online_exam_count'
        ] =
            'آزمون‌های داده‌شده';


        $new_columns[
            'online_exam_last_result'
        ] =
            'آخرین نتیجه';


        $new_columns[
            'online_exam_results'
        ] =
            'نتایج آزمون‌ها';
    }


    return $new_columns;
}

add_filter(
    'manage_users_columns',
    'online_exam_add_users_exam_columns'
);


/*
|--------------------------------------------------------------------------
| Users List Column Content
|--------------------------------------------------------------------------
*/

function online_exam_users_exam_column_content(
    $value,
    $column_name,
    $user_id
) {

    if (
        !in_array(
            $column_name,
            array(
                'online_exam_count',
                'online_exam_last_result',
                'online_exam_results',
            ),
            true
        )
    ) {
        return $value;
    }


    /*
     * فقط کاربران مشترک، کارشناس سامانه محسوب می‌شوند.
     */

    if (
        !online_exam_admin_is_exam_user(
            $user_id
        )
    ) {
        return '—';
    }


    static $overview_cache = array();


    if (
        !isset(
            $overview_cache[$user_id]
        )
    ) {
        $overview_cache[$user_id] =
            online_exam_admin_get_user_exam_overview(
                $user_id
            );
    }


    $overview =
        $overview_cache[$user_id];


    if (!$overview) {
        return '—';
    }


    if (
        $column_name ===
        'online_exam_count'
    ) {

        return sprintf(
            '%d از %d',
            absint(
                $overview[
                    'counts'
                ][
                    'submitted'
                ]
            ),
            absint(
                $overview[
                    'counts'
                ][
                    'total'
                ]
            )
        );
    }


    if (
        $column_name ===
        'online_exam_last_result'
    ) {

        foreach (
            $overview['rows']
            as $row
        ) {

            if (
                $row['status'] ===
                'graded' &&
                $row['result']
            ) {

                return sprintf(
                    '%s از %s',
                    esc_html(
                        online_exam_admin_format_score(
                            $row[
                                'result'
                            ][
                                'total_score'
                            ]
                        )
                    ),
                    esc_html(
                        online_exam_admin_format_score(
                            $row[
                                'result'
                            ][
                                'max_score'
                            ]
                        )
                    )
                );
            }


            if (
                $row['status'] ===
                'pending'
            ) {
                return 'در انتظار تصحیح';
            }
        }


        return '—';
    }


    if (
        $column_name ===
        'online_exam_results'
    ) {

        $url =
            add_query_arg(
                array(
                    'page' =>
                        'online-exam-user-results',

                    'user_id' =>
                        absint(
                            $user_id
                        ),
                ),
                admin_url(
                    'users.php'
                )
            );


        return sprintf(
            '<a class="button button-small" href="%s">مشاهده نتایج</a>',
            esc_url($url)
        );
    }


    return $value;
}

add_filter(
    'manage_users_custom_column',
    'online_exam_users_exam_column_content',
    10,
    3
);



/*
|--------------------------------------------------------------------------
| Results XLSX Export
|--------------------------------------------------------------------------
*/

function online_exam_admin_xlsx_xml_escape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_XML1 | ENT_COMPAT,
        'UTF-8'
    );
}


function online_exam_admin_xlsx_column_name($index)
{
    $index = absint($index);

    if ($index < 1) {
        return 'A';
    }

    $name = '';

    while ($index > 0) {

        $remainder =
            ($index - 1) % 26;

        $name =
            chr(
                65 + $remainder
            ) .
            $name;

        $index =
            intdiv(
                $index - 1,
                26
            );
    }

    return $name;
}


function online_exam_admin_xlsx_inline_cell(
    $column,
    $row,
    $value,
    $style = 0
) {
    $reference =
        $column .
        absint($row);

    return sprintf(
        '<c r="%s" t="inlineStr" s="%d"><is><t xml:space="preserve">%s</t></is></c>',
        esc_attr($reference),
        absint($style),
        online_exam_admin_xlsx_xml_escape(
            $value
        )
    );
}


function online_exam_admin_get_export_date_time($utc_value)
{
    $utc_value =
        trim(
            (string) $utc_value
        );

    if ($utc_value === '') {
        return '—';
    }

    $timestamp =
        strtotime(
            $utc_value .
            ' UTC'
        );

    if (!$timestamp) {
        return '—';
    }

    return wp_date(
        'Y/m/d H:i',
        $timestamp,
        wp_timezone()
    );
}


function online_exam_admin_get_exam_export_rows($exam_id)
{
    global $wpdb;

    $exam_id =
        absint(
            $exam_id
        );

    if (!$exam_id) {
        return new WP_Error(
            'invalid_exam',
            'آزمون معتبر نیست.'
        );
    }


    $exam =
        online_exam_get_exam_data(
            $exam_id
        );


    if (!$exam) {
        return new WP_Error(
            'exam_not_found',
            'آزمون پیدا نشد.'
        );
    }


    $experts =
        get_users(
            array(
                'role'    => 'subscriber',
                'orderby' => 'display_name',
                'order'   => 'ASC',
            )
        );


    $tables =
        online_exam_get_tables();


    /*
    |--------------------------------------------------------------------------
    | Attempts
    |--------------------------------------------------------------------------
    */

    $attempts =
        $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tables['attempts']}
                WHERE exam_id = %d
                ORDER BY id DESC
                ",
                $exam_id
            ),
            ARRAY_A
        );


    $attempts =
        is_array($attempts)
            ? $attempts
            : array();


    $attempt_by_user =
        array();


    foreach ($attempts as $attempt) {

        $user_id =
            absint(
                $attempt['user_id']
            );

        if (
            $user_id &&
            !isset(
                $attempt_by_user[
                    $user_id
                ]
            )
        ) {
            $attempt_by_user[
                $user_id
            ] = $attempt;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Results
    |--------------------------------------------------------------------------
    */

    $attempt_ids =
        array();


    foreach (
        $attempt_by_user as $attempt
    ) {

        $attempt_id =
            absint(
                $attempt['id']
            );

        if ($attempt_id) {
            $attempt_ids[] =
                $attempt_id;
        }
    }


    $result_by_attempt =
        array();

    $answered_by_attempt =
        array();


    if (!empty($attempt_ids)) {

        $placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count($attempt_ids),
                    '%d'
                )
            );


        $results_sql =
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tables['results']}
                WHERE attempt_id IN ({$placeholders})
                ORDER BY id DESC
                ",
                $attempt_ids
            );


        $results =
            $wpdb->get_results(
                $results_sql,
                ARRAY_A
            );


        if (is_array($results)) {

            foreach (
                $results as $result
            ) {

                $attempt_id =
                    absint(
                        $result[
                            'attempt_id'
                        ]
                    );


                if (
                    $attempt_id &&
                    !isset(
                        $result_by_attempt[
                            $attempt_id
                        ]
                    )
                ) {
                    $result_by_attempt[
                        $attempt_id
                    ] = $result;
                }
            }
        }


        $answers_sql =
            $wpdb->prepare(
                "
                SELECT
                    attempt_id,
                    COUNT(*) AS answered_count
                FROM {$tables['answers']}
                WHERE attempt_id IN ({$placeholders})
                GROUP BY attempt_id
                ",
                $attempt_ids
            );


        $answer_counts =
            $wpdb->get_results(
                $answers_sql,
                ARRAY_A
            );


        if (is_array($answer_counts)) {

            foreach (
                $answer_counts as $answer_count
            ) {

                $answered_by_attempt[
                    absint(
                        $answer_count[
                            'attempt_id'
                        ]
                    )
                ] =
                    absint(
                        $answer_count[
                            'answered_count'
                        ]
                    );
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Build Rows
    |--------------------------------------------------------------------------
    */

    $rows =
        array();


    foreach ($experts as $expert) {

        $attempt =
            isset(
                $attempt_by_user[
                    $expert->ID
                ]
            )
                ? $attempt_by_user[
                    $expert->ID
                ]
                : null;


        $result =
            null;

        $attempt_status =
            'not_started';

        $result_status_text =
            '—';

        $answered_count =
            0;

        $objective_score =
            '—';

        $manual_score =
            '—';

        $final_score =
            '—';

        $max_score =
            online_exam_admin_format_score(
                $exam['total_score']
            );

        $percentage =
            '—';

        $started_at =
            '—';

        $submitted_at =
            '—';


        if ($attempt) {

            $attempt_id =
                absint(
                    $attempt['id']
                );


            $answered_count =
                isset(
                    $answered_by_attempt[
                        $attempt_id
                    ]
                )
                    ? absint(
                        $answered_by_attempt[
                            $attempt_id
                        ]
                    )
                    : 0;


            $result =
                isset(
                    $result_by_attempt[
                        $attempt_id
                    ]
                )
                    ? $result_by_attempt[
                        $attempt_id
                    ]
                    : null;


            $started_at =
                online_exam_admin_get_export_date_time(
                    $attempt[
                        'started_at'
                    ] ?? ''
                );


            $submitted_at =
                online_exam_admin_get_export_date_time(
                    $attempt[
                        'submitted_at'
                    ] ?? ''
                );


            if (
                $attempt['status'] ===
                'in_progress'
            ) {

                $attempt_status =
                    'in_progress';

            } elseif (
                $attempt['status'] ===
                'submitted'
            ) {

                if (
                    $result &&
                    $result['status'] ===
                    'pending'
                ) {

                    $attempt_status =
                        'pending';

                    $result_status_text =
                        'در انتظار تصحیح';

                } elseif (
                    $result &&
                    $result['status'] ===
                    'graded'
                ) {

                    $attempt_status =
                        'graded';

                    $result_status_text =
                        'تصحیح شده';

                } else {

                    $attempt_status =
                        'submitted';

                    $result_status_text =
                        'ثبت شده';
                }

            } else {

                $attempt_status =
                    sanitize_key(
                        (string)
                        $attempt['status']
                    );
            }


            if ($result) {

                $objective_score =
                    online_exam_admin_format_score(
                        $result[
                            'objective_score'
                        ]
                    );


                $manual_score =
                    online_exam_admin_format_score(
                        $result[
                            'manual_score'
                        ]
                    );


                if (
                    $result['status'] ===
                    'graded'
                ) {

                    $final_score =
                        online_exam_admin_format_score(
                            $result[
                                'total_score'
                            ]
                        );


                    $max_score =
                        online_exam_admin_format_score(
                            $result[
                                'max_score'
                            ]
                        );


                    $percentage =
                        online_exam_admin_format_score(
                            $result[
                                'percentage'
                            ]
                        );
                }
            }
        }


        $rows[] =
            array(

                $expert->user_login,

                get_user_meta(
                    $expert->ID,
                    'first_name',
                    true
                ),

                get_user_meta(
                    $expert->ID,
                    'last_name',
                    true
                ),

                $expert->user_email,

                $exam['title'],

                online_exam_admin_get_exam_status_label(
                    $attempt_status
                ),

                absint(
                    $exam[
                        'question_count'
                    ]
                ),

                $answered_count,

                $objective_score,

                $manual_score,

                $final_score,

                $max_score,

                $percentage,

                $result_status_text,

                $started_at,

                $submitted_at,
            );
    }


    return array(
        'exam' =>
            $exam,

        'rows' =>
            $rows,
    );
}


function online_exam_admin_create_results_xlsx(
    $exam_id
) {
    if (!class_exists('ZipArchive')) {

        return new WP_Error(
            'zip_missing',
            'برای ساخت فایل Excel، افزونه PHP ZipArchive باید فعال باشد.'
        );
    }


    $export =
        online_exam_admin_get_exam_export_rows(
            $exam_id
        );


    if (is_wp_error($export)) {
        return $export;
    }


    $headers =
        array(
            'نام کاربری',
            'نام',
            'نام خانوادگی',
            'ایمیل',
            'عنوان آزمون',
            'وضعیت شرکت',
            'تعداد سوال',
            'پاسخ داده‌شده',
            'نمره تستی',
            'نمره تشریحی',
            'نمره نهایی',
            'نمره کل آزمون',
            'درصد',
            'وضعیت تصحیح',
            'زمان شروع',
            'زمان ارسال',
        );


    $sheet_rows =
        array(
            $headers,
        );


    foreach (
        $export['rows']
        as $row
    ) {
        $sheet_rows[] =
            $row;
    }


    /*
    |--------------------------------------------------------------------------
    | Worksheet XML
    |--------------------------------------------------------------------------
    */

    $sheet_data =
        '';


    foreach (
        $sheet_rows
        as $row_index => $row
    ) {

        $excel_row =
            $row_index + 1;


        $cells =
            '';


        foreach (
            $row as $column_index => $value
        ) {

            $column_name =
                online_exam_admin_xlsx_column_name(
                    $column_index + 1
                );


            /*
             * Style 1 = Header
             * Style 2 = Normal RTL
             */
            $style =
                $excel_row === 1
                    ? 1
                    : 2;


            $cells .=
                online_exam_admin_xlsx_inline_cell(
                    $column_name,
                    $excel_row,
                    $value,
                    $style
                );
        }


        $sheet_data .=
            sprintf(
                '<row r="%d">%s</row>',
                $excel_row,
                $cells
            );
    }


    $last_column =
        online_exam_admin_xlsx_column_name(
            count(
                $headers
            )
        );


    $last_row =
        count(
            $sheet_rows
        );


    $worksheet_xml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetViews>' .
                '<sheetView workbookViewId="0" rightToLeft="1">' .
                    '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>' .
                '</sheetView>' .
            '</sheetViews>' .
            '<cols>' .
                '<col min="1" max="1" width="18" customWidth="1"/>' .
                '<col min="2" max="3" width="16" customWidth="1"/>' .
                '<col min="4" max="4" width="28" customWidth="1"/>' .
                '<col min="5" max="5" width="28" customWidth="1"/>' .
                '<col min="6" max="6" width="18" customWidth="1"/>' .
                '<col min="7" max="14" width="15" customWidth="1"/>' .
                '<col min="15" max="16" width="20" customWidth="1"/>' .
            '</cols>' .
            '<sheetData>' .
                $sheet_data .
            '</sheetData>' .
            '<autoFilter ref="A1:' .
                $last_column .
                $last_row .
            '"/>' .
        '</worksheet>';


    /*
    |--------------------------------------------------------------------------
    | Workbook XML
    |--------------------------------------------------------------------------
    */

    $workbook_xml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' .
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets>' .
                '<sheet name="نتایج آزمون" sheetId="1" r:id="rId1"/>' .
            '</sheets>' .
        '</workbook>';


    $workbook_rels_xml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" ' .
                'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" ' .
                'Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" ' .
                'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" ' .
                'Target="styles.xml"/>' .
        '</Relationships>';


    $root_rels_xml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" ' .
                'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" ' .
                'Target="xl/workbook.xml"/>' .
        '</Relationships>';


    $styles_xml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="2">' .
                '<font><sz val="11"/><name val="Calibri"/></font>' .
                '<font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font>' .
            '</fonts>' .
            '<fills count="3">' .
                '<fill><patternFill patternType="none"/></fill>' .
                '<fill><patternFill patternType="gray125"/></fill>' .
                '<fill><patternFill patternType="solid"><fgColor rgb="FF2271B1"/><bgColor indexed="64"/></patternFill></fill>' .
            '</fills>' .
            '<borders count="2">' .
                '<border><left/><right/><top/><bottom/><diagonal/></border>' .
                '<border>' .
                    '<left style="thin"><color rgb="FFDCDCDE"/></left>' .
                    '<right style="thin"><color rgb="FFDCDCDE"/></right>' .
                    '<top style="thin"><color rgb="FFDCDCDE"/></top>' .
                    '<bottom style="thin"><color rgb="FFDCDCDE"/></bottom>' .
                    '<diagonal/>' .
                '</border>' .
            '</borders>' .
            '<cellStyleXfs count="1">' .
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' .
            '</cellStyleXfs>' .
            '<cellXfs count="3">' .
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' .
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1">' .
                    '<alignment horizontal="center" vertical="center" wrapText="1" readingOrder="2"/>' .
                '</xf>' .
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1">' .
                    '<alignment horizontal="right" vertical="center" wrapText="1" readingOrder="2"/>' .
                '</xf>' .
            '</cellXfs>' .
            '<cellStyles count="1">' .
                '<cellStyle name="Normal" xfId="0" builtinId="0"/>' .
            '</cellStyles>' .
        '</styleSheet>';


    $content_types_xml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ' .
                'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ' .
                'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ' .
                'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
        '</Types>';


    /*
    |--------------------------------------------------------------------------
    | ZIP
    |--------------------------------------------------------------------------
    */

    $temp_file =
        wp_tempnam(
            'online-exam-results.xlsx'
        );


    if (!$temp_file) {

        return new WP_Error(
            'temp_file_failed',
            'ساخت فایل موقت Excel امکان‌پذیر نیست.'
        );
    }


    $zip =
        new ZipArchive();


    $opened =
        $zip->open(
            $temp_file,
            ZipArchive::CREATE |
            ZipArchive::OVERWRITE
        );


    if ($opened !== true) {

        @unlink(
            $temp_file
        );

        return new WP_Error(
            'xlsx_create_failed',
            'ساخت فایل Excel با خطا مواجه شد.'
        );
    }


    $zip->addFromString(
        '[Content_Types].xml',
        $content_types_xml
    );


    $zip->addFromString(
        '_rels/.rels',
        $root_rels_xml
    );


    $zip->addFromString(
        'xl/workbook.xml',
        $workbook_xml
    );


    $zip->addFromString(
        'xl/_rels/workbook.xml.rels',
        $workbook_rels_xml
    );


    $zip->addFromString(
        'xl/worksheets/sheet1.xml',
        $worksheet_xml
    );


    $zip->addFromString(
        'xl/styles.xml',
        $styles_xml
    );


    $zip->close();


    return array(
        'file' =>
            $temp_file,

        'exam' =>
            $export['exam'],
    );
}


/*
|--------------------------------------------------------------------------
| Download XLSX
|--------------------------------------------------------------------------
*/

function online_exam_admin_handle_results_xlsx_export()
{
    if (
        !is_admin() ||
        empty($_GET['action']) ||
        $_GET['action'] !==
        'online_exam_export_results_xlsx'
    ) {
        return;
    }


    if (
        !current_user_can(
            'manage_options'
        )
    ) {
        wp_die(
            'شما اجازه دریافت این فایل را ندارید.'
        );
    }


    $exam_id =
        isset($_GET['exam_id'])
            ? absint(
                $_GET['exam_id']
            )
            : 0;


    if (!$exam_id) {

        wp_die(
            'آزمون معتبر نیست.'
        );
    }


    check_admin_referer(
        'online_exam_export_results_' .
        $exam_id
    );


    $xlsx =
        online_exam_admin_create_results_xlsx(
            $exam_id
        );


    if (is_wp_error($xlsx)) {

        wp_die(
            esc_html(
                $xlsx->get_error_message()
            )
        );
    }


    $file =
        $xlsx['file'];


    if (
        !$file ||
        !file_exists($file)
    ) {

        wp_die(
            'فایل Excel ساخته نشد.'
        );
    }


    $filename =
        sprintf(
            'exam-results-%d-%s.xlsx',
            $exam_id,
            gmdate(
                'Ymd-His'
            )
        );


    while (
        ob_get_level() > 0
    ) {
        ob_end_clean();
    }


    nocache_headers();


    header(
        'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );


    header(
        'Content-Disposition: attachment; filename="' .
        $filename .
        '"'
    );


    header(
        'Content-Length: ' .
        filesize($file)
    );


    header(
        'X-Content-Type-Options: nosniff'
    );


    readfile(
        $file
    );


    @unlink(
        $file
    );


    exit;
}

add_action(
    'admin_init',
    'online_exam_admin_handle_results_xlsx_export'
);


function online_exam_admin_get_results_export_url(
    $exam_id
) {
    $exam_id =
        absint(
            $exam_id
        );


    $url =
        add_query_arg(
            array(
                'action' =>
                    'online_exam_export_results_xlsx',

                'exam_id' =>
                    $exam_id,
            ),
            admin_url(
                'users.php'
            )
        );


    return wp_nonce_url(
        $url,
        'online_exam_export_results_' .
        $exam_id
    );
}




/*
|--------------------------------------------------------------------------
| Reset Expert Attempt
|--------------------------------------------------------------------------
|
| بازنشانی کامل یک Attempt:
|
| - پاسخ‌ها حذف می‌شوند.
| - نتیجه حذف می‌شود.
| - خود Attempt حذف می‌شود.
|
| بعد از آن کارشناس از دید سیستم مثل کسی است که هنوز در آن آزمون
| شرکت نکرده است و در صورت معتبر بودن بازه زمانی می‌تواند دوباره شروع کند.
|
*/

function online_exam_admin_get_reset_attempt_url(
    $user_id,
    $attempt_id
) {
    $user_id =
        absint(
            $user_id
        );

    $attempt_id =
        absint(
            $attempt_id
        );


    $url =
        add_query_arg(
            array(
                'page' =>
                    'online-exam-user-results',

                'action' =>
                    'online_exam_reset_attempt',

                'user_id' =>
                    $user_id,

                'attempt_id' =>
                    $attempt_id,
            ),
            admin_url(
                'users.php'
            )
        );


    return wp_nonce_url(
        $url,
        'online_exam_reset_attempt_' .
        $attempt_id .
        '_' .
        $user_id
    );
}


function online_exam_admin_reset_attempt(
    $user_id,
    $attempt_id
) {
    global $wpdb;


    $user_id =
        absint(
            $user_id
        );

    $attempt_id =
        absint(
            $attempt_id
        );


    if (
        !$user_id ||
        !$attempt_id
    ) {
        return new WP_Error(
            'invalid_reset_data',
            'اطلاعات بازنشانی آزمون معتبر نیست.'
        );
    }


    if (
        !online_exam_admin_is_exam_user(
            $user_id
        )
    ) {
        return new WP_Error(
            'invalid_exam_user',
            'کاربر موردنظر جزو کارشناسان سامانه نیست.'
        );
    }


    $attempt =
        online_exam_get_attempt(
            $attempt_id
        );


    if (
        !$attempt ||
        absint(
            $attempt['user_id']
        ) !==
        $user_id
    ) {
        return new WP_Error(
            'attempt_not_found',
            'Attempt موردنظر پیدا نشد یا متعلق به این کارشناس نیست.'
        );
    }


    $exam_id =
        absint(
            $attempt['exam_id']
        );


    $tables =
        online_exam_get_tables();


    $wpdb->query(
        'START TRANSACTION'
    );


    /*
    |--------------------------------------------------------------------------
    | Delete Answers
    |--------------------------------------------------------------------------
    */

    $answers_deleted =
        $wpdb->delete(
            $tables['answers'],
            array(
                'attempt_id' =>
                    $attempt_id,

                'user_id' =>
                    $user_id,
            ),
            array(
                '%d',
                '%d',
            )
        );


    if (
        $answers_deleted ===
        false
    ) {

        $wpdb->query(
            'ROLLBACK'
        );


        return new WP_Error(
            'answers_delete_failed',
            'حذف پاسخ‌های آزمون با خطا مواجه شد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Result
    |--------------------------------------------------------------------------
    */

    $result_deleted =
        $wpdb->delete(
            $tables['results'],
            array(
                'attempt_id' =>
                    $attempt_id,

                'user_id' =>
                    $user_id,
            ),
            array(
                '%d',
                '%d',
            )
        );


    if (
        $result_deleted ===
        false
    ) {

        $wpdb->query(
            'ROLLBACK'
        );


        return new WP_Error(
            'result_delete_failed',
            'حذف نتیجه آزمون با خطا مواجه شد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Attempt
    |--------------------------------------------------------------------------
    */

    $attempt_deleted =
        $wpdb->delete(
            $tables['attempts'],
            array(
                'id' =>
                    $attempt_id,

                'user_id' =>
                    $user_id,
            ),
            array(
                '%d',
                '%d',
            )
        );


    if (
        $attempt_deleted !==
        1
    ) {

        $wpdb->query(
            'ROLLBACK'
        );


        return new WP_Error(
            'attempt_delete_failed',
            'حذف Attempt آزمون انجام نشد.'
        );
    }


    $wpdb->query(
        'COMMIT'
    );


    /*
    |--------------------------------------------------------------------------
    | Remove Timeout Cron Safety Net
    |--------------------------------------------------------------------------
    |
    | اگر Attempt در حال انجام بوده، Cron قبلی دیگر نباید بعداً اجرا شود.
    |
    */

    if (
        function_exists(
            'wp_clear_scheduled_hook'
        )
    ) {

        wp_clear_scheduled_hook(
            'online_exam_cron_timeout_submit',
            array(
                $attempt_id,
                $user_id,
            )
        );
    }


    return array(
        'exam_id' =>
            $exam_id,

        'attempt_id' =>
            $attempt_id,

        'user_id' =>
            $user_id,
    );
}


function online_exam_admin_handle_reset_attempt()
{
    if (
        !is_admin() ||
        empty($_GET['page']) ||
        $_GET['page'] !==
        'online-exam-user-results' ||
        empty($_GET['action']) ||
        $_GET['action'] !==
        'online_exam_reset_attempt'
    ) {
        return;
    }


    if (
        !current_user_can(
            'manage_options'
        )
    ) {
        wp_die(
            'شما اجازه انجام این عملیات را ندارید.'
        );
    }


    $user_id =
        isset($_GET['user_id'])
            ? absint(
                $_GET['user_id']
            )
            : 0;


    $attempt_id =
        isset($_GET['attempt_id'])
            ? absint(
                $_GET['attempt_id']
            )
            : 0;


    if (
        !$user_id ||
        !$attempt_id
    ) {
        wp_die(
            'اطلاعات بازنشانی آزمون معتبر نیست.'
        );
    }


    check_admin_referer(
        'online_exam_reset_attempt_' .
        $attempt_id .
        '_' .
        $user_id
    );


    $reset =
        online_exam_admin_reset_attempt(
            $user_id,
            $attempt_id
        );


    if (
        is_wp_error(
            $reset
        )
    ) {
        wp_die(
            esc_html(
                $reset
                    ->get_error_message()
            ),
            'خطای بازنشانی آزمون',
            array(
                'back_link' =>
                    true,
            )
        );
    }


    $redirect_url =
        add_query_arg(
            array(
                'page' =>
                    'online-exam-user-results',

                'user_id' =>
                    $user_id,

                'online_exam_reset' =>
                    1,
            ),
            admin_url(
                'users.php'
            )
        );


    wp_safe_redirect(
        $redirect_url
    );

    exit;
}

add_action(
    'admin_init',
    'online_exam_admin_handle_reset_attempt'
);



/*
|--------------------------------------------------------------------------
| Results Page Under Users
|--------------------------------------------------------------------------
*/

function online_exam_register_user_results_page()
{
    add_users_page(
        'نتایج کارشناسان',
        'نتایج کارشناسان',
        'manage_options',
        'online-exam-user-results',
        'online_exam_render_user_results_page'
    );
}

add_action(
    'admin_menu',
    'online_exam_register_user_results_page'
);


/*
|--------------------------------------------------------------------------
| Results Page
|--------------------------------------------------------------------------
*/

function online_exam_render_user_results_page()
{
    if (
        !current_user_can(
            'manage_options'
        )
    ) {
        wp_die(
            'شما اجازه دسترسی به این صفحه را ندارید.'
        );
    }


    $user_id =
        isset($_GET['user_id'])
            ? absint(
                $_GET['user_id']
            )
            : 0;


    $attempt_id =
        isset($_GET['attempt_id'])
            ? absint(
                $_GET['attempt_id']
            )
            : 0;


    ?>

    <div
        class="wrap online-exam-user-results-wrap"
        dir="rtl"
    >

        <h1>
            نتایج کارشناسان
        </h1>


        <?php if (
            !empty(
                $_GET[
                    'online_exam_reset'
                ]
            )
        ) : ?>

            <div
                class="notice notice-success is-dismissible"
            >

                <p>
                    Attempt، پاسخ‌ها و نتیجه آزمون این کارشناس با موفقیت بازنشانی شدند.
                </p>

            </div>

        <?php endif; ?>


        <?php

        if (!$user_id) {

            online_exam_render_experts_results_index();

        } elseif ($attempt_id) {

            online_exam_render_attempt_result_details(
                $user_id,
                $attempt_id
            );

        } else {

            online_exam_render_single_user_exam_results(
                $user_id
            );
        }

        ?>

    </div>

    <?php
}


/*
|--------------------------------------------------------------------------
| Experts Results Index
|--------------------------------------------------------------------------
*/

function online_exam_render_experts_results_index()
{
    /*
     * نقش مشترک WordPress = subscriber
     */

    $experts =
        get_users(
            array(
                'role'    => 'subscriber',
                'orderby' => 'display_name',
                'order'   => 'ASC',
            )
        );


    if (empty($experts)) {

        ?>

        <div class="notice notice-info">

            <p>
                هنوز کارشناسی در سامانه ایجاد نشده است.
            </p>

        </div>

        <?php

        return;
    }


    ?>

    <p>
        از این صفحه می‌توانید سوابق آزمون هر کارشناس را مشاهده کنید.
    </p>


    <?php

    $export_exam_ids =
        get_posts(
            array(
                'post_type'      => 'exam',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

    ?>


    <?php if (!empty($export_exam_ids)) : ?>

        <div
            style="
                background:#fff;
                border:1px solid #dcdcde;
                border-radius:8px;
                padding:18px 20px;
                margin:18px 0 24px;
            "
        >

            <h2
                style="
                    margin-top:0;
                    margin-bottom:8px;
                "
            >
                خروجی Excel نتایج
            </h2>


            <p
                style="
                    margin-top:0;
                    color:#646970;
                "
            >
                آزمون موردنظر را انتخاب کنید و فایل Excel نتایج همه کارشناسان را دریافت کنید.
                کارشناسانی که در آزمون شرکت نکرده‌اند نیز داخل فایل خواهند بود.
            </p>


            <form
                method="get"
                action="<?php echo esc_url(admin_url('users.php')); ?>"
                style="
                    display:flex;
                    align-items:center;
                    gap:10px;
                    flex-wrap:wrap;
                "
            >

                <input
                    type="hidden"
                    name="page"
                    value="online-exam-user-results"
                >


                <label
                    for="online_exam_export_exam_id"
                    style="font-weight:600;"
                >
                    آزمون:
                </label>


                <select
                    id="online_exam_export_exam_id"
                    style="min-width:280px;"
                >

                    <option value="">
                        — انتخاب آزمون —
                    </option>


                    <?php foreach (
                        $export_exam_ids as $export_exam_id
                    ) : ?>

                        <option
                            value="<?php echo esc_attr($export_exam_id); ?>"
                            data-export-url="<?php
                            echo esc_url(
                                online_exam_admin_get_results_export_url(
                                    $export_exam_id
                                )
                            );
                            ?>"
                        >
                            <?php
                            echo esc_html(
                                get_the_title(
                                    $export_exam_id
                                )
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <a
                    id="online_exam_export_results_button"
                    class="button button-primary"
                    href="#"
                    aria-disabled="true"
                    style="
                        opacity:.55;
                        pointer-events:none;
                    "
                >
                    دانلود Excel
                </a>

            </form>

        </div>


        <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const select =
                    document.getElementById(
                        'online_exam_export_exam_id'
                    );

                const button =
                    document.getElementById(
                        'online_exam_export_results_button'
                    );


                if (
                    !select ||
                    !button
                ) {
                    return;
                }


                function updateExportButton()
                {
                    const option =
                        select.options[
                            select.selectedIndex
                        ];


                    const url =
                        option
                            ? option.dataset.exportUrl
                            : '';


                    if (!url) {

                        button.href =
                            '#';

                        button.setAttribute(
                            'aria-disabled',
                            'true'
                        );

                        button.style.opacity =
                            '.55';

                        button.style.pointerEvents =
                            'none';

                        return;
                    }


                    button.href =
                        url;

                    button.setAttribute(
                        'aria-disabled',
                        'false'
                    );

                    button.style.opacity =
                        '1';

                    button.style.pointerEvents =
                        'auto';
                }


                select.addEventListener(
                    'change',
                    updateExportButton
                );


                updateExportButton();

            }
        );
        </script>

    <?php endif; ?>


    <table class="widefat striped">

        <thead>

            <tr>

                <th>
                    کارشناس
                </th>

                <th>
                    نام کاربری
                </th>

                <th>
                    ایمیل
                </th>

                <th>
                    آزمون‌های داده‌شده
                </th>

                <th>
                    شرکت نکرده
                </th>

                <th>
                    در انتظار تصحیح
                </th>

                <th>
                    عملیات
                </th>

            </tr>

        </thead>


        <tbody>


        <?php foreach (
            $experts as $expert
        ) : ?>


            <?php

            $overview =
                online_exam_admin_get_user_exam_overview(
                    $expert->ID
                );


            if (!$overview) {
                continue;
            }


            $results_url =
                add_query_arg(
                    array(
                        'page' =>
                            'online-exam-user-results',

                        'user_id' =>
                            absint(
                                $expert->ID
                            ),
                    ),
                    admin_url(
                        'users.php'
                    )
                );

            ?>


            <tr>

                <td>

                    <strong>
                        <?php
                        echo esc_html(
                            $expert
                                ->display_name
                        );
                        ?>
                    </strong>

                </td>


                <td>

                    <?php
                    echo esc_html(
                        $expert
                            ->user_login
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo esc_html(
                        $expert
                            ->user_email
                            ?: '—'
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo esc_html(
                        $overview[
                            'counts'
                        ][
                            'submitted'
                        ]
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo esc_html(
                        $overview[
                            'counts'
                        ][
                            'not_started'
                        ]
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo esc_html(
                        $overview[
                            'counts'
                        ][
                            'pending'
                        ]
                    );
                    ?>

                </td>


                <td>

                    <a
                        class="button button-primary"
                        href="<?php
                        echo esc_url(
                            $results_url
                        );
                        ?>"
                    >
                        مشاهده نتایج
                    </a>

                </td>

            </tr>


        <?php endforeach; ?>


        </tbody>

    </table>

    <?php
}


/*
|--------------------------------------------------------------------------
| Single Expert Results
|--------------------------------------------------------------------------
*/

function online_exam_render_single_user_exam_results($user_id)
{
    $overview =
        online_exam_admin_get_user_exam_overview(
            $user_id
        );


    if (!$overview) {

        ?>

        <div class="notice notice-error">

            <p>
                کاربر موردنظر پیدا نشد.
            </p>

        </div>

        <?php

        return;
    }


    $user =
        $overview['user'];


    if (
        !online_exam_admin_is_exam_user(
            $user->ID
        )
    ) {

        ?>

        <div class="notice notice-error">

            <p>
                این کاربر جزو کارشناسان سامانه نیست.
            </p>

        </div>

        <?php

        return;
    }


    $counts =
        $overview['counts'];


    ?>

    <p>

        <a
            href="<?php
            echo esc_url(
                admin_url(
                    'users.php'
                )
            );
            ?>"
        >
            ← بازگشت به کاربران
        </a>

    </p>


    <div class="online-exam-user-header">

        <h2>
            <?php
            echo esc_html(
                $user->display_name
            );
            ?>
        </h2>


        <p>

            نام کاربری:

            <strong>
                <?php
                echo esc_html(
                    $user->user_login
                );
                ?>
            </strong>


            <?php if (
                $user->user_email
            ) : ?>

                &nbsp; | &nbsp;

                ایمیل:

                <strong>
                    <?php
                    echo esc_html(
                        $user->user_email
                    );
                    ?>
                </strong>

            <?php endif; ?>

        </p>

    </div>


    <div class="online-exam-summary-grid">

        <?php

        online_exam_render_summary_box(
            'کل آزمون‌ها',
            $counts['total']
        );


        online_exam_render_summary_box(
            'آزمون‌های داده‌شده',
            $counts['submitted']
        );


        online_exam_render_summary_box(
            'شرکت نکرده',
            $counts['not_started']
        );


        online_exam_render_summary_box(
            'در انتظار تصحیح',
            $counts['pending']
        );


        online_exam_render_summary_box(
            'در حال انجام',
            $counts['in_progress']
        );

        ?>

    </div>


    <?php if (
        empty(
            $overview['rows']
        )
    ) : ?>

        <div class="notice notice-info">

            <p>
                هنوز آزمون منتشرشده‌ای در سامانه وجود ندارد.
            </p>

        </div>

        <?php

        return;

    endif;
    ?>


    <table
        class="widefat striped online-exam-results-table"
    >

        <thead>

            <tr>

                <th>
                    آزمون
                </th>

                <th>
                    وضعیت
                </th>

                <th>
                    نمره تستی
                </th>

                <th>
                    نمره نهایی
                </th>

                <th>
                    درصد
                </th>

                <th>
                    زمان ثبت
                </th>

                <th>
                    عملیات
                </th>

            </tr>

        </thead>


        <tbody>


        <?php foreach (
            $overview['rows']
            as $row
        ) : ?>


            <?php

            $exam =
                $row['exam'];


            $attempt =
                $row['attempt'];


            $result =
                $row['result'];


            $status =
                $row['status'];


            $status_label =
                online_exam_admin_get_exam_status_label(
                    $status
                );


            $status_class =
                online_exam_admin_get_exam_status_class(
                    $status
                );


            $objective_score =
                '—';


            $final_score =
                '—';


            $percentage =
                '—';


            $submitted_at =
                '—';


            $action_html =
                '—';


            if ($result) {

                $objective_score =
                    online_exam_admin_format_score(
                        $result[
                            'objective_score'
                        ]
                    );


                if (
                    $status ===
                    'graded'
                ) {

                    $final_score =
                        sprintf(
                            '%s از %s',

                            online_exam_admin_format_score(
                                $result[
                                    'total_score'
                                ]
                            ),

                            online_exam_admin_format_score(
                                $result[
                                    'max_score'
                                ]
                            )
                        );


                    $percentage =
                        sprintf(
                            '%s%%',
                            online_exam_admin_format_score(
                                $result[
                                    'percentage'
                                ]
                            )
                        );
                }


                if (
                    $status ===
                    'pending'
                ) {

                    $final_score =
                        'در انتظار تصحیح';
                }
            }


            if (
                $attempt &&
                !empty(
                    $attempt[
                        'submitted_at'
                    ]
                )
            ) {

                $timestamp =
                    strtotime(
                        $attempt[
                            'submitted_at'
                        ] .
                        ' UTC'
                    );


                if ($timestamp) {

                    $submitted_at =
                        wp_date(
                            'Y/m/d H:i',
                            $timestamp,
                            wp_timezone()
                        );
                }
            }


            if (
                $attempt &&
                in_array(
                    $status,
                    array(
                        'pending',
                        'graded',
                        'submitted',
                    ),
                    true
                )
            ) {

                $details_url =
                    add_query_arg(
                        array(
                            'page' =>
                                'online-exam-user-results',

                            'user_id' =>
                                absint(
                                    $user->ID
                                ),

                            'attempt_id' =>
                                absint(
                                    $attempt['id']
                                ),
                        ),
                        admin_url(
                            'users.php'
                        )
                    );


                $action_html =
                    sprintf(
                        '<a class="button button-small" href="%s">جزئیات</a>',
                        esc_url(
                            $details_url
                        )
                    );


                if ($status === 'pending') {

                    $grading_url =
                        add_query_arg(
                            array(
                                'post_type' =>
                                    'exam',

                                'page' =>
                                    'online-exam-grading',

                                'attempt_id' =>
                                    absint(
                                        $attempt['id']
                                    ),
                            ),
                            admin_url(
                                'edit.php'
                            )
                        );


                    $action_html .=
                        sprintf(
                            ' <a class="button button-small button-primary" href="%s">تصحیح</a>',
                            esc_url(
                                $grading_url
                            )
                        );
                }
            }


            /*
             * هر Attempt موجود را ادمین می‌تواند بازنشانی کند؛
             * حتی اگر در حال انجام باشد.
             */
            if ($attempt) {

                $reset_url =
                    online_exam_admin_get_reset_attempt_url(
                        $user->ID,
                        $attempt['id']
                    );


                $reset_button =
                    sprintf(
                        '<a class="button button-small online-exam-reset-attempt" href="%s" onclick="return confirm(\'با بازنشانی، Attempt، تمام پاسخ‌ها و نتیجه این آزمون حذف می‌شوند و این عملیات قابل بازگشت نیست. ادامه می‌دهید؟\');">بازنشانی آزمون</a>',
                        esc_url(
                            $reset_url
                        )
                    );


                if (
                    $action_html ===
                    '—'
                ) {

                    $action_html =
                        $reset_button;

                } else {

                    $action_html .=
                        ' ' .
                        $reset_button;
                }
            }

            ?>


            <tr>

                <td>

                    <strong>
                        <?php
                        echo esc_html(
                            $exam['title']
                        );
                        ?>
                    </strong>


                    <div class="online-exam-small-meta">

                        <?php
                        echo esc_html(
                            $exam[
                                'question_count'
                            ]
                        );
                        ?>

                        سوال

                        &nbsp; | &nbsp;

                        نمره کل:

                        <?php
                        echo esc_html(
                            online_exam_admin_format_score(
                                $exam[
                                    'total_score'
                                ]
                            )
                        );
                        ?>

                    </div>

                </td>


                <td>

                    <span
                        class="online-exam-status online-exam-status-<?php
                        echo esc_attr(
                            $status_class
                        );
                        ?>"
                    >
                        <?php
                        echo esc_html(
                            $status_label
                        );
                        ?>
                    </span>

                </td>


                <td>

                    <?php
                    echo esc_html(
                        $objective_score
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo esc_html(
                        $final_score
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo esc_html(
                        $percentage
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo esc_html(
                        $submitted_at
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo wp_kses_post(
                        $action_html
                    );
                    ?>

                </td>

            </tr>


        <?php endforeach; ?>


        </tbody>

    </table>


    <?php

    online_exam_render_users_admin_styles();
}



/*
|--------------------------------------------------------------------------
| Attempt Details Helpers
|--------------------------------------------------------------------------
*/

function online_exam_admin_get_option_text(
    $options,
    $option_id
) {
    $option_id =
        sanitize_key(
            (string) $option_id
        );


    if (
        $option_id === '' ||
        !is_array($options)
    ) {
        return '';
    }


    foreach ($options as $option) {

        if (
            empty($option['id'])
        ) {
            continue;
        }


        $current_id =
            sanitize_key(
                $option['id']
            );


        if (
            $current_id !==
            $option_id
        ) {
            continue;
        }


        return isset(
            $option['label']
        )
            ? (string)
                $option['label']
            : '';
    }


    return '';
}


function online_exam_admin_get_option_display(
    $options,
    $option_id
) {
    $option_id =
        sanitize_key(
            (string) $option_id
        );


    if ($option_id === '') {
        return '—';
    }


    $labels = array(
        'a' => 'الف',
        'b' => 'ب',
        'c' => 'ج',
        'd' => 'د',
    );


    $text =
        online_exam_admin_get_option_text(
            $options,
            $option_id
        );


    $prefix =
        isset($labels[$option_id])
            ? $labels[$option_id]
            : $option_id;


    if ($text === '') {
        return $prefix;
    }


    return $prefix . ') ' . $text;
}


function online_exam_admin_get_attempt_details(
    $user_id,
    $attempt_id
) {
    global $wpdb;

    $user_id =
        absint(
            $user_id
        );

    $attempt_id =
        absint(
            $attempt_id
        );


    if (
        !$user_id ||
        !$attempt_id
    ) {
        return false;
    }


    $user =
        get_userdata(
            $user_id
        );


    if (
        !$user ||
        !online_exam_admin_is_exam_user(
            $user_id
        )
    ) {
        return false;
    }


    $attempt =
        online_exam_get_attempt(
            $attempt_id
        );


    if (
        !$attempt ||
        absint(
            $attempt['user_id']
        ) !== $user_id
    ) {
        return false;
    }


    $exam =
        online_exam_get_exam_data(
            absint(
                $attempt['exam_id']
            )
        );


    if (!$exam) {
        return false;
    }


    $result =
        online_exam_get_attempt_result(
            $attempt_id,
            $user_id
        );


    $scoring =
        online_exam_get_exam_scoring(
            $exam['id']
        );


    if (is_wp_error($scoring)) {
        return $scoring;
    }


    $tables =
        online_exam_get_tables();


    $answers =
        $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tables['answers']}
                WHERE attempt_id = %d
                AND user_id = %d
                ORDER BY id ASC
                ",
                $attempt_id,
                $user_id
            ),
            ARRAY_A
        );


    $answers =
        is_array($answers)
            ? $answers
            : array();


    $answer_by_question =
        array();


    foreach ($answers as $answer) {

        $question_id =
            absint(
                $answer['question_id']
            );


        if ($question_id) {
            $answer_by_question[
                $question_id
            ] = $answer;
        }
    }


    $question_ids =
        online_exam_get_attempt_question_order(
            $attempt
        );


    $question_rows =
        array();


    $answered_count =
        0;


    foreach (
        $question_ids as $index => $question_id
    ) {

        $question =
            online_exam_get_question_data(
                $question_id
            );


        if (!$question) {
            continue;
        }


        $answer =
            isset(
                $answer_by_question[
                    $question_id
                ]
            )
                ? $answer_by_question[
                    $question_id
                ]
                : null;


        if ($answer) {
            $answered_count++;
        }


        $row =
            array(
                'number' =>
                    $index + 1,

                'question_id' =>
                    $question_id,

                'question' =>
                    $question,

                'answer' =>
                    $answer,

                'user_answer' =>
                    'بدون پاسخ',

                'correct_answer' =>
                    '—',

                'answer_status' =>
                    'unanswered',

                'earned_score' =>
                    0,

                'max_score' =>
                    0,
            );


        /*
        |--------------------------------------------------------------------------
        | MCQ
        |--------------------------------------------------------------------------
        */

        if (
            $question['type'] ===
            'mcq'
        ) {

            $row['max_score'] =
                $scoring['mcq_score'];


            $options =
                isset(
                    $question['options']
                ) &&
                is_array(
                    $question['options']
                )
                    ? $question['options']
                    : array();


            $correct_option_id =
                sanitize_key(
                    get_post_meta(
                        $question_id,
                        '_online_exam_correct_option_id',
                        true
                    )
                );


            $row['correct_answer'] =
                online_exam_admin_get_option_display(
                    $options,
                    $correct_option_id
                );


            if ($answer) {

                $selected_option_id =
                    isset(
                        $answer[
                            'selected_option_id'
                        ]
                    )
                        ? sanitize_key(
                            $answer[
                                'selected_option_id'
                            ]
                        )
                        : '';


                $row['user_answer'] =
                    online_exam_admin_get_option_display(
                        $options,
                        $selected_option_id
                    );


                if (
                    isset(
                        $answer['is_correct']
                    ) &&
                    (string)
                    $answer['is_correct'] ===
                    '1'
                ) {

                    $row['answer_status'] =
                        'correct';

                    $row['earned_score'] =
                        $scoring[
                            'mcq_score'
                        ];

                } else {

                    $row['answer_status'] =
                        'incorrect';
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Essay
        |--------------------------------------------------------------------------
        */

        elseif (
            $question['type'] ===
            'essay'
        ) {

            $row['max_score'] =
                isset(
                    $scoring[
                        'essay_scores'
                    ][
                        $question_id
                    ]
                )
                    ? floatval(
                        $scoring[
                            'essay_scores'
                        ][
                            $question_id
                        ]
                    )
                    : 0;


            if (
                $answer &&
                isset(
                    $answer[
                        'answer_text'
                    ]
                ) &&
                trim(
                    (string)
                    $answer[
                        'answer_text'
                    ]
                ) !== ''
            ) {

                $row['user_answer'] =
                    (string)
                    $answer[
                        'answer_text'
                    ];


                if (
                    $result &&
                    $result['status'] ===
                    'pending'
                ) {

                    $row['answer_status'] =
                        'pending';

                } elseif (
                    isset(
                        $answer[
                            'awarded_score'
                        ]
                    ) &&
                    $answer[
                        'awarded_score'
                    ] !== null &&
                    $answer[
                        'awarded_score'
                    ] !== ''
                ) {

                    $row['answer_status'] =
                        'graded';

                    $row['earned_score'] =
                        floatval(
                            $answer[
                                'awarded_score'
                            ]
                        );

                } else {

                    $row['answer_status'] =
                        'answered';
                }
            }
        }


        $question_rows[] =
            $row;
    }


    return array(
        'user' =>
            $user,

        'exam' =>
            $exam,

        'attempt' =>
            $attempt,

        'result' =>
            $result,

        'questions' =>
            $question_rows,

        'answered_count' =>
            $answered_count,

        'unanswered_count' =>
            max(
                0,
                count($question_ids) -
                $answered_count
            ),
    );
}


/*
|--------------------------------------------------------------------------
| Attempt Details Page
|--------------------------------------------------------------------------
*/

function online_exam_render_attempt_result_details(
    $user_id,
    $attempt_id
) {
    $details =
        online_exam_admin_get_attempt_details(
            $user_id,
            $attempt_id
        );


    if (is_wp_error($details)) {

        ?>

        <div class="notice notice-error">

            <p>
                <?php
                echo esc_html(
                    $details
                        ->get_error_message()
                );
                ?>
            </p>

        </div>

        <?php

        return;
    }


    if (!$details) {

        ?>

        <div class="notice notice-error">

            <p>
                نتیجه موردنظر پیدا نشد یا متعلق به این کارشناس نیست.
            </p>

        </div>

        <?php

        return;
    }


    $user =
        $details['user'];

    $exam =
        $details['exam'];

    $attempt =
        $details['attempt'];

    $result =
        $details['result'];


    $back_url =
        add_query_arg(
            array(
                'page' =>
                    'online-exam-user-results',

                'user_id' =>
                    absint(
                        $user->ID
                    ),
            ),
            admin_url(
                'users.php'
            )
        );


    ?>

    <p>

        <a
            href="<?php
            echo esc_url(
                $back_url
            );
            ?>"
        >
            ← بازگشت به نتایج کارشناس
        </a>

    </p>


    <div class="online-exam-user-header">

        <h2>
            <?php
            echo esc_html(
                $exam['title']
            );
            ?>
        </h2>


        <p>

            کارشناس:

            <strong>
                <?php
                echo esc_html(
                    $user
                        ->display_name
                );
                ?>
            </strong>

            &nbsp; | &nbsp;

            نام کاربری:

            <strong>
                <?php
                echo esc_html(
                    $user
                        ->user_login
                );
                ?>
            </strong>

        </p>

    </div>


    <div class="online-exam-summary-grid">

        <?php

        online_exam_render_summary_box(
            'کل سوالات',
            count(
                $details['questions']
            )
        );


        online_exam_render_summary_box(
            'پاسخ داده‌شده',
            $details[
                'answered_count'
            ]
        );


        online_exam_render_summary_box(
            'بدون پاسخ',
            $details[
                'unanswered_count'
            ]
        );


        if (
            $result &&
            $result['status'] ===
            'graded'
        ) {

            online_exam_render_summary_box(
                'نمره نهایی',
                online_exam_admin_format_score(
                    $result[
                        'total_score'
                    ]
                ) .
                ' از ' .
                online_exam_admin_format_score(
                    $result[
                        'max_score'
                    ]
                )
            );


            online_exam_render_summary_box(
                'درصد',
                online_exam_admin_format_score(
                    $result[
                        'percentage'
                    ]
                ) .
                '٪'
            );

        } elseif (
            $result &&
            $result['status'] ===
            'pending'
        ) {

            online_exam_render_summary_box(
                'نمره تستی',
                online_exam_admin_format_score(
                    $result[
                        'objective_score'
                    ]
                )
            );


            online_exam_render_summary_box(
                'وضعیت',
                'در انتظار تصحیح'
            );

        } else {

            online_exam_render_summary_box(
                'وضعیت',
                'ثبت شده'
            );
        }

        ?>

    </div>


    <?php if (
        $result &&
        $result['status'] ===
        'pending'
    ) : ?>


        <?php

        $grading_url =
            add_query_arg(
                array(
                    'post_type' =>
                        'exam',

                    'page' =>
                        'online-exam-grading',

                    'attempt_id' =>
                        absint(
                            $attempt['id']
                        ),
                ),
                admin_url(
                    'edit.php'
                )
            );

        ?>


        <p>

            <a
                class="button button-primary"
                href="<?php
                echo esc_url(
                    $grading_url
                );
                ?>"
            >
                تصحیح پاسخ‌های تشریحی
            </a>

        </p>


    <?php endif; ?>


    <?php

    $reset_attempt_url =
        online_exam_admin_get_reset_attempt_url(
            $user->ID,
            $attempt['id']
        );

    ?>


    <p>

        <a
            class="button online-exam-reset-attempt"
            href="<?php
            echo esc_url(
                $reset_attempt_url
            );
            ?>"
            onclick="return confirm('با بازنشانی، Attempt، تمام پاسخ‌ها و نتیجه این آزمون حذف می‌شوند و این عملیات قابل بازگشت نیست. ادامه می‌دهید؟');"
        >
            بازنشانی آزمون برای این کارشناس
        </a>

    </p>


    <table
        class="widefat striped online-exam-answer-details"
    >

        <thead>

            <tr>

                <th style="width:60px;">
                    #
                </th>

                <th style="width:80px;">
                    نوع
                </th>

                <th>
                    سوال
                </th>

                <th>
                    پاسخ کارشناس
                </th>

                <th>
                    پاسخ صحیح
                </th>

                <th style="width:120px;">
                    وضعیت
                </th>

                <th style="width:110px;">
                    نمره
                </th>

            </tr>

        </thead>


        <tbody>


        <?php foreach (
            $details['questions']
            as $row
        ) : ?>


            <?php

            $type_label =
                $row['question']['type'] ===
                'essay'
                    ? 'تشریحی'
                    : 'تستی';


            $status_label =
                'بدون پاسخ';


            $status_class =
                'not_started';


            switch (
                $row['answer_status']
            ) {

                case 'correct':

                    $status_label =
                        'صحیح';

                    $status_class =
                        'graded';

                    break;


                case 'incorrect':

                    $status_label =
                        'غلط';

                    $status_class =
                        'expired';

                    break;


                case 'pending':

                    $status_label =
                        'در انتظار تصحیح';

                    $status_class =
                        'pending';

                    break;


                case 'graded':

                    $status_label =
                        'تصحیح شده';

                    $status_class =
                        'graded';

                    break;


                case 'answered':

                    $status_label =
                        'پاسخ داده شده';

                    $status_class =
                        'submitted';

                    break;
            }


            $score_text =
                '0 از ' .
                online_exam_admin_format_score(
                    $row['max_score']
                );


            if (
                $row['answer_status'] ===
                'pending'
            ) {

                $score_text =
                    'در انتظار تصحیح';

            } else {

                $score_text =
                    online_exam_admin_format_score(
                        $row[
                            'earned_score'
                        ]
                    ) .
                    ' از ' .
                    online_exam_admin_format_score(
                        $row[
                            'max_score'
                        ]
                    );
            }

            ?>


            <tr>

                <td>
                    <?php
                    echo esc_html(
                        $row['number']
                    );
                    ?>
                </td>


                <td>
                    <?php
                    echo esc_html(
                        $type_label
                    );
                    ?>
                </td>


                <td>

                    <strong
                        dir="auto"
                    >
                        <?php
                        echo esc_html(
                            $row[
                                'question'
                            ][
                                'text'
                            ]
                        );
                        ?>
                    </strong>

                </td>


                <td>

                    <div
                        class="online-exam-answer-text"
                        dir="auto"
                    >
                        <?php
                        echo nl2br(
                            esc_html(
                                $row[
                                    'user_answer'
                                ]
                            )
                        );
                        ?>
                    </div>

                </td>


                <td>

                    <?php if (
                        $row[
                            'question'
                        ][
                            'type'
                        ] ===
                        'mcq'
                    ) : ?>

                        <div dir="auto">

                            <?php
                            echo esc_html(
                                $row[
                                    'correct_answer'
                                ]
                            );
                            ?>

                        </div>

                    <?php else : ?>

                        —

                    <?php endif; ?>

                </td>


                <td>

                    <span
                        class="online-exam-status online-exam-status-<?php
                        echo esc_attr(
                            $status_class
                        );
                        ?>"
                    >
                        <?php
                        echo esc_html(
                            $status_label
                        );
                        ?>
                    </span>

                </td>


                <td>
                    <?php
                    echo esc_html(
                        $score_text
                    );
                    ?>
                </td>

            </tr>


        <?php endforeach; ?>


        </tbody>

    </table>


    <?php

    online_exam_render_users_admin_styles();
}


/*
|--------------------------------------------------------------------------
| Summary Box
|--------------------------------------------------------------------------
*/

function online_exam_render_summary_box(
    $label,
    $value
) {

    ?>

    <div class="online-exam-summary-box">

        <span>
            <?php
            echo esc_html(
                $label
            );
            ?>
        </span>


        <strong>
            <?php
            echo esc_html(
                $value
            );
            ?>
        </strong>

    </div>

    <?php
}


/*
|--------------------------------------------------------------------------
| User Edit/Profile Exam History
|--------------------------------------------------------------------------
*/

function online_exam_render_user_profile_exam_history(
    $profile_user
) {

    if (
        !current_user_can(
            'manage_options'
        ) ||
        !$profile_user
            instanceof WP_User ||
        !online_exam_admin_is_exam_user(
            $profile_user->ID
        )
    ) {
        return;
    }


    $overview =
        online_exam_admin_get_user_exam_overview(
            $profile_user->ID
        );


    if (!$overview) {
        return;
    }


    $results_url =
        add_query_arg(
            array(
                'page' =>
                    'online-exam-user-results',

                'user_id' =>
                    absint(
                        $profile_user->ID
                    ),
            ),
            admin_url(
                'users.php'
            )
        );


    ?>

    <h2>
        سوابق آزمون کارشناس
    </h2>


    <table
        class="form-table"
        role="presentation"
    >

        <tr>

            <th>
                خلاصه آزمون‌ها
            </th>


            <td>

                <p>

                    کل آزمون‌ها:

                    <strong>
                        <?php
                        echo esc_html(
                            $overview[
                                'counts'
                            ][
                                'total'
                            ]
                        );
                        ?>
                    </strong>


                    &nbsp; | &nbsp;

                    شرکت کرده:

                    <strong>
                        <?php
                        echo esc_html(
                            $overview[
                                'counts'
                            ][
                                'submitted'
                            ]
                        );
                        ?>
                    </strong>


                    &nbsp; | &nbsp;

                    شرکت نکرده:

                    <strong>
                        <?php
                        echo esc_html(
                            $overview[
                                'counts'
                            ][
                                'not_started'
                            ]
                        );
                        ?>
                    </strong>


                    &nbsp; | &nbsp;

                    در انتظار تصحیح:

                    <strong>
                        <?php
                        echo esc_html(
                            $overview[
                                'counts'
                            ][
                                'pending'
                            ]
                        );
                        ?>
                    </strong>

                </p>


                <p>

                    <a
                        href="<?php
                        echo esc_url(
                            $results_url
                        );
                        ?>"
                        class="button button-primary"
                    >
                        مشاهده همه نتایج آزمون‌ها
                    </a>

                </p>

            </td>

        </tr>

    </table>

    <?php
}

add_action(
    'show_user_profile',
    'online_exam_render_user_profile_exam_history'
);

add_action(
    'edit_user_profile',
    'online_exam_render_user_profile_exam_history'
);


/*
|--------------------------------------------------------------------------
| Admin Styles
|--------------------------------------------------------------------------
*/

function online_exam_render_users_admin_styles()
{
    static $printed =
        false;


    if ($printed) {
        return;
    }


    $printed =
        true;


    ?>

    <style>

        .online-exam-user-header {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 8px;
            padding: 18px 20px;
            margin: 20px 0;
        }


        .online-exam-user-header h2 {
            margin-top: 0;
            margin-bottom: 8px;
        }


        .online-exam-user-header p {
            margin-bottom: 0;
        }


        .online-exam-summary-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    5,
                    minmax(0, 1fr)
                );
            gap: 12px;
            margin: 18px 0 24px;
        }


        .online-exam-summary-box {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 8px;
            padding: 16px;
        }


        .online-exam-summary-box span {
            display: block;
            color: #646970;
            margin-bottom: 8px;
        }


        .online-exam-summary-box strong {
            display: block;
            font-size: 22px;
        }


        .online-exam-small-meta {
            color: #646970;
            font-size: 12px;
            margin-top: 5px;
        }


        .online-exam-answer-text {
            white-space: pre-wrap;
            line-height: 1.8;
            max-width: 420px;
        }


        .online-exam-answer-details td {
            vertical-align: top;
        }


        .online-exam-status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }


        .online-exam-status-graded {
            background: #edfaef;
            color: #116329;
        }


        .online-exam-status-pending {
            background: #fff8e5;
            color: #8a5a00;
        }


        .online-exam-status-in_progress {
            background: #eaf3ff;
            color: #135e96;
        }


        .online-exam-status-not_started {
            background: #f0f0f1;
            color: #50575e;
        }


        .online-exam-status-submitted {
            background: #eef4ff;
            color: #1d4ed8;
        }


        .online-exam-status-expired,
        .online-exam-status-unknown {
            background: #fbeaea;
            color: #b32d2e;
        }


        .online-exam-reset-attempt {
            color: #b32d2e !important;
            border-color: #b32d2e !important;
        }


        .online-exam-reset-attempt:hover,
        .online-exam-reset-attempt:focus {
            color: #8a2424 !important;
            border-color: #8a2424 !important;
        }


        @media (max-width: 1100px) {

            .online-exam-summary-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }
        }


        @media (max-width: 782px) {

            .online-exam-summary-grid {
                grid-template-columns:
                    1fr;
            }
        }

    </style>

    <?php
}
