<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Essay Answers For Attempt
|--------------------------------------------------------------------------
*/

function online_exam_get_attempt_essay_answers($attempt_id)
{
    global $wpdb;

    $attempt_id = absint($attempt_id);

    if (!$attempt_id) {
        return array();
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
                ORDER BY id ASC
                ",
                $attempt_id
            ),
            ARRAY_A
        );


    if (empty($answers)) {
        return array();
    }


    $essay_answers =
        array();


    foreach ($answers as $answer) {

        $question_id =
            absint(
                $answer['question_id']
            );


        $type =
            get_post_meta(
                $question_id,
                '_online_exam_question_type',
                true
            );


        if ($type !== 'essay') {
            continue;
        }


        $question =
            get_post(
                $question_id
            );


        if (!$question) {
            continue;
        }


        $max_score =
            floatval(
                get_post_meta(
                    $question_id,
                    '_online_exam_max_score',
                    true
                )
            );


        $essay_answers[] =
            array(

                'answer_id' =>
                    absint(
                        $answer['id']
                    ),

                'question_id' =>
                    $question_id,

                'question_text' =>
                    $question->post_content,

                'answer_text' =>
                    $answer['answer_text'],

                'max_score' =>
                    $max_score,

                'awarded_score' =>
                    $answer['awarded_score'] !== null
                        ? floatval(
                            $answer['awarded_score']
                        )
                        : null,
            );
    }


    return $essay_answers;
}


/*
|--------------------------------------------------------------------------
| Get Pending Grading Attempts
|--------------------------------------------------------------------------
*/

function online_exam_get_pending_grading_attempts()
{
    global $wpdb;

    $tables =
        online_exam_get_tables();


    $rows =
        $wpdb->get_results(
            "
            SELECT
                r.id AS result_id,
                r.attempt_id,
                r.exam_id,
                r.user_id,
                r.objective_score,
                r.status,
                a.submitted_at

            FROM {$tables['results']} r

            INNER JOIN {$tables['attempts']} a
                ON a.id = r.attempt_id

            WHERE r.status = 'pending'

            ORDER BY a.submitted_at ASC
            ",
            ARRAY_A
        );


    return is_array($rows)
        ? $rows
        : array();
}


/*
|--------------------------------------------------------------------------
| Grade Essay Attempt
|--------------------------------------------------------------------------
*/

function online_exam_grade_attempt(
    $attempt_id,
    $scores
) {
    global $wpdb;

    $attempt_id =
        absint(
            $attempt_id
        );


    if (!$attempt_id) {

        return new WP_Error(
            'invalid_attempt',
            'شناسه آزمون معتبر نیست.'
        );
    }


    if (
        !current_user_can(
            'manage_options'
        )
    ) {

        return new WP_Error(
            'permission_denied',
            'شما اجازه تصحیح آزمون را ندارید.'
        );
    }


    $attempt =
        online_exam_get_attempt(
            $attempt_id
        );


    if (!$attempt) {

        return new WP_Error(
            'attempt_not_found',
            'Attempt پیدا نشد.'
        );
    }


    if (
        $attempt['status'] !==
        'submitted'
    ) {

        return new WP_Error(
            'attempt_not_submitted',
            'این آزمون هنوز ثبت نهایی نشده است.'
        );
    }


    $result =
        online_exam_get_attempt_result(
            $attempt_id,
            absint(
                $attempt['user_id']
            )
        );


    if (!$result) {

        return new WP_Error(
            'result_not_found',
            'نتیجه آزمون پیدا نشد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Essay Answers
    |--------------------------------------------------------------------------
    */

    $essay_answers =
        online_exam_get_attempt_essay_answers(
            $attempt_id
        );


    if (empty($essay_answers)) {

        return new WP_Error(
            'no_essay_answers',
            'این آزمون سوال تشریحی ندارد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Scores
    |--------------------------------------------------------------------------
    */

    $normalized_scores =
        array();


    foreach (
        $essay_answers as $essay
    ) {

        $answer_id =
            absint(
                $essay['answer_id']
            );


        if (
            !isset(
                $scores[
                    $answer_id
                ]
            )
        ) {

            return new WP_Error(
                'score_missing',
                'برای همه سوالات تشریحی نمره وارد کنید.'
            );
        }


        $raw_score =
            wp_unslash(
                $scores[
                    $answer_id
                ]
            );


        $score =
            online_exam_parse_score_value(
                $raw_score
            );


        if ($score === false) {

            return new WP_Error(
                'invalid_score',
                'یکی از نمرات واردشده معتبر نیست.'
            );
        }


        if ($score < 0) {

            return new WP_Error(
                'negative_score',
                'نمره نمی‌تواند منفی باشد.'
            );
        }


        $max_score =
            floatval(
                $essay['max_score']
            );


        if (
            $score >
            $max_score + 0.001
        ) {

            return new WP_Error(
                'score_too_high',
                sprintf(
                    'نمره واردشده برای یکی از سوالات از بارم %.2f بیشتر است.',
                    $max_score
                )
            );
        }


        $normalized_scores[
            $answer_id
        ] = round(
            $score,
            2
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Transaction
    |--------------------------------------------------------------------------
    */

    $tables =
        online_exam_get_tables();


    $wpdb->query(
        'START TRANSACTION'
    );


    $now =
        gmdate(
            'Y-m-d H:i:s'
        );


    /*
    |--------------------------------------------------------------------------
    | Save Essay Scores
    |--------------------------------------------------------------------------
    */

    foreach (
        $normalized_scores
        as $answer_id => $score
    ) {

        $updated =
            $wpdb->update(
                $tables['answers'],
                array(

                    'awarded_score' =>
                        $score,

                    'updated_at' =>
                        $now,
                ),
                array(

                    'id' =>
                        $answer_id,

                    'attempt_id' =>
                        $attempt_id,
                )
            );


        if ($updated === false) {

            $wpdb->query(
                'ROLLBACK'
            );


            return new WP_Error(
                'answer_grade_failed',
                'ذخیره نمره تشریحی با خطا مواجه شد.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Manual Score
    |--------------------------------------------------------------------------
    */

    $manual_score =
        array_sum(
            $normalized_scores
        );


    $manual_score =
        round(
            $manual_score,
            2
        );


    /*
    |--------------------------------------------------------------------------
    | Objective Score
    |--------------------------------------------------------------------------
    */

    $objective_score =
        floatval(
            $result[
                'objective_score'
            ]
        );


    /*
    |--------------------------------------------------------------------------
    | Exam Scoring
    |--------------------------------------------------------------------------
    */

    $scoring =
        online_exam_get_exam_scoring(
            absint(
                $attempt['exam_id']
            )
        );


    if (is_wp_error($scoring)) {

        $wpdb->query(
            'ROLLBACK'
        );

        return $scoring;
    }


    $max_score =
        floatval(
            $scoring[
                'total_score'
            ]
        );


    /*
    |--------------------------------------------------------------------------
    | Total
    |--------------------------------------------------------------------------
    */

    $total_score =
        round(
            $objective_score +
            $manual_score,
            2
        );


    /*
    |--------------------------------------------------------------------------
    | Percentage
    |--------------------------------------------------------------------------
    */

    $percentage =
        $max_score > 0
            ? round(
                (
                    $total_score /
                    $max_score
                ) * 100,
                2
            )
            : 0;


    /*
    |--------------------------------------------------------------------------
    | Update Result
    |--------------------------------------------------------------------------
    */

    $result_updated =
        $wpdb->update(
            $tables['results'],
            array(

                'manual_score' =>
                    $manual_score,

                'total_score' =>
                    $total_score,

                'max_score' =>
                    $max_score,

                'percentage' =>
                    $percentage,

                /*
                 * هنوز حدنصاب قبولی تعریف نکردیم.
                 */
                'passed' =>
                    null,

                'status' =>
                    'graded',

                'graded_at' =>
                    $now,

                'updated_at' =>
                    $now,
            ),
            array(

                'id' =>
                    absint(
                        $result['id']
                    ),

                'attempt_id' =>
                    $attempt_id,
            )
        );


    if ($result_updated === false) {

        $wpdb->query(
            'ROLLBACK'
        );


        return new WP_Error(
            'result_grade_failed',
            'نهایی‌سازی نتیجه با خطا مواجه شد.'
        );
    }


    $wpdb->query(
        'COMMIT'
    );


    return online_exam_get_attempt_result(
        $attempt_id,
        absint(
            $attempt['user_id']
        )
    );
}


/*
|--------------------------------------------------------------------------
| Register Grading Admin Page
|--------------------------------------------------------------------------
*/

function online_exam_register_grading_page()
{
    add_submenu_page(
        'edit.php?post_type=exam',
        'تصحیح آزمون‌ها',
        'تصحیح آزمون‌ها',
        'manage_options',
        'online-exam-grading',
        'online_exam_render_grading_page'
    );
}

add_action(
    'admin_menu',
    'online_exam_register_grading_page'
);


/*
|--------------------------------------------------------------------------
| Handle Grade Submit
|--------------------------------------------------------------------------
*/

function online_exam_handle_grading_submit()
{
    if (
        empty($_GET['page']) ||
        $_GET['page'] !==
        'online-exam-grading'
    ) {
        return;
    }


    if (
        ($_SERVER['REQUEST_METHOD'] ?? '') !==
        'POST' ||
        !isset(
            $_POST[
                'online_exam_save_grading'
            ]
        )
    ) {
        return;
    }


    if (
        !current_user_can(
            'manage_options'
        )
    ) {

        wp_die(
            'دسترسی غیرمجاز.'
        );
    }


    $attempt_id =
        isset($_POST['attempt_id'])
            ? absint(
                $_POST['attempt_id']
            )
            : 0;


    check_admin_referer(
        'online_exam_grade_' .
        $attempt_id,
        'online_exam_grade_nonce'
    );


    $scores =
        isset($_POST['scores']) &&
        is_array($_POST['scores'])
            ? $_POST['scores']
            : array();


    $graded =
        online_exam_grade_attempt(
            $attempt_id,
            $scores
        );


    if (is_wp_error($graded)) {

        wp_die(
            esc_html(
                $graded->get_error_message()
            ),
            'خطای تصحیح',
            array(
                'back_link' => true,
            )
        );
    }


    $redirect_url =
        add_query_arg(
            array(
                'post_type' =>
                    'exam',

                'page' =>
                    'online-exam-grading',

                'graded' =>
                    1,
            ),
            admin_url(
                'edit.php'
            )
        );


    wp_safe_redirect(
        $redirect_url
    );

    exit;
}

add_action(
    'admin_init',
    'online_exam_handle_grading_submit'
);


/*
|--------------------------------------------------------------------------
| Render Grading Page
|--------------------------------------------------------------------------
*/

function online_exam_render_grading_page()
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


    $attempt_id =
        isset($_GET['attempt_id'])
            ? absint(
                $_GET['attempt_id']
            )
            : 0;


    ?>

    <div
        class="wrap"
        dir="rtl"
        style="
            max-width: 1100px;
        "
    >

        <h1>
            تصحیح آزمون‌ها
        </h1>


        <?php if (
            !empty($_GET['graded'])
        ) : ?>

            <div
                class="notice notice-success is-dismissible"
            >
                <p>
                    تصحیح آزمون با موفقیت ثبت شد.
                </p>
            </div>

        <?php endif; ?>


        <?php

        /*
        |--------------------------------------------------------------------------
        | Single Attempt Grading
        |--------------------------------------------------------------------------
        */

        if ($attempt_id) :

            $attempt =
                online_exam_get_attempt(
                    $attempt_id
                );


            if (!$attempt) {

                echo '<div class="notice notice-error"><p>Attempt پیدا نشد.</p></div>';

                return;
            }


            $user =
                get_userdata(
                    absint(
                        $attempt['user_id']
                    )
                );


            $exam =
                online_exam_get_exam_data(
                    absint(
                        $attempt['exam_id']
                    )
                );


            $result =
                online_exam_get_attempt_result(
                    $attempt_id,
                    absint(
                        $attempt['user_id']
                    )
                );


            $essay_answers =
                online_exam_get_attempt_essay_answers(
                    $attempt_id
                );


            ?>


            <p>

                <a
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            array(
                                'post_type' =>
                                    'exam',

                                'page' =>
                                    'online-exam-grading',
                            ),
                            admin_url(
                                'edit.php'
                            )
                        )
                    );
                    ?>"
                >
                    ← بازگشت به فهرست تصحیح
                </a>

            </p>


            <div
                class="card"
                style="
                    max-width: none;
                    padding: 22px;
                    margin-top: 20px;
                "
            >

                <h2>

                    <?php
                    echo esc_html(
                        $exam
                            ? $exam['title']
                            : 'آزمون'
                    );
                    ?>

                </h2>


                <p>

                    <strong>
                        دانشجو:
                    </strong>

                    <?php
                    echo esc_html(
                        $user
                            ? $user->display_name
                            : 'کاربر حذف شده'
                    );
                    ?>

                </p>


                <p>

                    <strong>
                        نمره تستی فعلی:
                    </strong>

                    <?php
                    echo esc_html(
                        $result
                            ? $result[
                                'objective_score'
                            ]
                            : 0
                    );
                    ?>

                </p>

            </div>


            <?php if (
                empty($essay_answers)
            ) : ?>

                <div class="notice notice-warning">
                    <p>
                        پاسخ تشریحی برای این Attempt وجود ندارد.
                    </p>
                </div>

            <?php else : ?>


                <form method="post">

                    <?php

                    wp_nonce_field(
                        'online_exam_grade_' .
                        $attempt_id,
                        'online_exam_grade_nonce'
                    );

                    ?>


                    <input
                        type="hidden"
                        name="attempt_id"
                        value="<?php echo esc_attr($attempt_id); ?>"
                    >


                    <?php foreach (
                        $essay_answers
                        as $index => $essay
                    ) : ?>


                        <div
                            class="card"
                            style="
                                max-width: none;
                                padding: 22px;
                                margin-top: 20px;
                            "
                        >

                            <h3>

                                سوال تشریحی

                                <?php
                                echo esc_html(
                                    $index + 1
                                );
                                ?>

                            </h3>


                            <div
                                style="
                                    background: #f6f7f7;
                                    padding: 15px;
                                    border-radius: 7px;
                                    margin: 15px 0;
                                "
                            >

                                <strong>
                                    متن سوال:
                                </strong>

                                <p>
                                    <?php
                                    echo esc_html(
                                        $essay[
                                            'question_text'
                                        ]
                                    );
                                    ?>
                                </p>

                            </div>


                            <div
                                style="
                                    background: #fff;
                                    border: 1px solid #dcdcde;
                                    padding: 15px;
                                    border-radius: 7px;
                                    margin: 15px 0;
                                "
                            >

                                <strong>
                                    پاسخ دانشجو:
                                </strong>

                                <p
                                    style="
                                        line-height: 2;
                                        white-space: pre-wrap;
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        $essay[
                                            'answer_text'
                                        ]
                                    );
                                    ?>
                                </p>

                            </div>


                            <p>

                                <label>

                                    <strong>
                                        نمره:
                                    </strong>


                                    <input
                                        type="number"
                                        name="scores[<?php echo esc_attr(
                                            $essay[
                                                'answer_id'
                                            ]
                                        ); ?>]"
                                        min="0"
                                        max="<?php echo esc_attr(
                                            $essay[
                                                'max_score'
                                            ]
                                        ); ?>"
                                        step="0.01"
                                        required
                                        value="<?php
                                        echo esc_attr(
                                            $essay[
                                                'awarded_score'
                                            ] !== null
                                                ? $essay[
                                                    'awarded_score'
                                                ]
                                                : ''
                                        );
                                        ?>"
                                        style="
                                            width: 100px;
                                            margin: 0 8px;
                                        "
                                    >

                                    از

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $essay[
                                                'max_score'
                                            ]
                                        );
                                        ?>
                                    </strong>

                                </label>

                            </p>

                        </div>


                    <?php endforeach; ?>


                    <?php

                    submit_button(
                        'ثبت نهایی تصحیح',
                        'primary',
                        'online_exam_save_grading'
                    );

                    ?>

                </form>


            <?php endif; ?>


        <?php else : ?>


            <?php

            /*
            |--------------------------------------------------------------------------
            | Pending List
            |--------------------------------------------------------------------------
            */

            $pending_attempts =
                online_exam_get_pending_grading_attempts();

            ?>


            <?php if (
                empty($pending_attempts)
            ) : ?>

                <div
                    class="notice notice-info"
                    style="
                        margin-top: 20px;
                    "
                >
                    <p>
                        در حال حاضر آزمونی در انتظار تصحیح نیست.
                    </p>
                </div>


            <?php else : ?>


                <table
                    class="widefat striped"
                    style="
                        margin-top: 20px;
                    "
                >

                    <thead>

                        <tr>

                            <th>
                                آزمون
                            </th>

                            <th>
                                دانشجو
                            </th>

                            <th>
                                نمره تستی
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
                            $pending_attempts
                            as $row
                        ) : ?>


                            <?php

                            $exam =
                                online_exam_get_exam_data(
                                    absint(
                                        $row[
                                            'exam_id'
                                        ]
                                    )
                                );


                            $user =
                                get_userdata(
                                    absint(
                                        $row[
                                            'user_id'
                                        ]
                                    )
                                );


                            $grade_url =
                                add_query_arg(
                                    array(

                                        'post_type' =>
                                            'exam',

                                        'page' =>
                                            'online-exam-grading',

                                        'attempt_id' =>
                                            absint(
                                                $row[
                                                    'attempt_id'
                                                ]
                                            ),
                                    ),
                                    admin_url(
                                        'edit.php'
                                    )
                                );

                            ?>


                            <tr>

                                <td>

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $exam
                                                ? $exam[
                                                    'title'
                                                ]
                                                : 'آزمون حذف شده'
                                        );
                                        ?>
                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo esc_html(
                                        $user
                                            ? $user->display_name
                                            : 'کاربر حذف شده'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo esc_html(
                                        $row[
                                            'objective_score'
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo esc_html(
                                        $row[
                                            'submitted_at'
                                        ] ?: '—'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <a
                                        href="<?php echo esc_url(
                                            $grade_url
                                        ); ?>"
                                        class="button button-primary"
                                    >
                                        تصحیح
                                    </a>

                                </td>

                            </tr>


                        <?php endforeach; ?>


                    </tbody>

                </table>


            <?php endif; ?>


        <?php endif; ?>

    </div>

    <?php
}