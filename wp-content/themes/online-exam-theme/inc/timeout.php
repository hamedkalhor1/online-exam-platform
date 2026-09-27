<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Exam Timeout
|--------------------------------------------------------------------------
|
| رفتار موردنظر:
|
| 1) تا وقتی زمان باقی مانده، Attempt فعال است.
| 2) بعد از expires_at، سوال‌های بدون پاسخ صفر/بدون پاسخ باقی می‌مانند.
| 3) Attempt به صورت خودکار submitted می‌شود.
| 4) نتیجه ساخته می‌شود.
| 5) کاربر مستقیماً به /result/ منتقل می‌شود.
|
*/


/*
|--------------------------------------------------------------------------
| Is Attempt Expired?
|--------------------------------------------------------------------------
*/

function online_exam_is_attempt_expired($attempt)
{
    if (
        empty($attempt) ||
        $attempt['status'] !== 'in_progress'
    ) {
        return false;
    }

    return online_exam_get_remaining_seconds($attempt) <= 0;
}


/*
|--------------------------------------------------------------------------
| Submit Expired Attempt
|--------------------------------------------------------------------------
*/

function online_exam_submit_expired_attempt(
    $attempt_id,
    $user_id
) {
    global $wpdb;

    $attempt_id = absint($attempt_id);
    $user_id    = absint($user_id);

    if (!$attempt_id || !$user_id) {
        return new WP_Error(
            'invalid_timeout_submit',
            'اطلاعات ثبت خودکار آزمون معتبر نیست.'
        );
    }

    $tables = online_exam_get_tables();

    $wpdb->query('START TRANSACTION');


    /*
    |--------------------------------------------------------------------------
    | Lock Attempt
    |--------------------------------------------------------------------------
    */

    $attempt = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT *
            FROM {$tables['attempts']}
            WHERE id = %d
            AND user_id = %d
            LIMIT 1
            FOR UPDATE
            ",
            $attempt_id,
            $user_id
        ),
        ARRAY_A
    );

    if (!$attempt) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'attempt_not_found',
            'آزمون موردنظر پیدا نشد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Already Submitted
    |--------------------------------------------------------------------------
    */

    if ($attempt['status'] === 'submitted') {
        $result = online_exam_get_attempt_result(
            $attempt_id,
            $user_id
        );

        $wpdb->query('COMMIT');

        return array(
            'attempt'           => $attempt,
            'result'            => $result,
            'already_submitted' => true,
        );
    }


    if ($attempt['status'] !== 'in_progress') {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'attempt_not_active',
            'این آزمون دیگر فعال نیست.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Server-side Expiration Check
    |--------------------------------------------------------------------------
    |
    | کاربر نمی‌تواند قبل از پایان واقعی زمان، ثبت ناقص انجام دهد.
    |
    */

    if (!online_exam_is_attempt_expired($attempt)) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'exam_not_expired',
            'زمان آزمون هنوز به پایان نرسیده است.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Questions
    |--------------------------------------------------------------------------
    */

    $question_ids =
        online_exam_get_attempt_question_order(
            $attempt
        );

    if (empty($question_ids)) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'no_questions',
            'سوالات آزمون قابل دریافت نیستند.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Scoring
    |--------------------------------------------------------------------------
    */

    $scoring =
        online_exam_get_exam_scoring(
            absint($attempt['exam_id'])
        );

    if (is_wp_error($scoring)) {
        $wpdb->query('ROLLBACK');

        return $scoring;
    }


    /*
    |--------------------------------------------------------------------------
    | Correct MCQ Answers
    |--------------------------------------------------------------------------
    */

    $correct_count =
        absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$tables['answers']}
                    WHERE attempt_id = %d
                    AND user_id = %d
                    AND is_correct = 1
                    ",
                    $attempt_id,
                    $user_id
                )
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Answered Essay Questions
    |--------------------------------------------------------------------------
    */

    $essay_answered_count =
        absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$tables['answers']}
                    WHERE attempt_id = %d
                    AND user_id = %d
                    AND answer_text IS NOT NULL
                    AND answer_text != ''
                    ",
                    $attempt_id,
                    $user_id
                )
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Objective Score
    |--------------------------------------------------------------------------
    */

    $objective_score =
        round(
            $correct_count *
            $scoring['mcq_score'],
            2
        );


    /*
    |--------------------------------------------------------------------------
    | Result
    |--------------------------------------------------------------------------
    |
    | اگر پاسخ تشریحی ثبت شده باشد:
    |     pending
    |
    | اگر تشریحی وجود ندارد، یا هیچ تشریحی پاسخ داده نشده:
    |     graded
    |
    | سوال‌های بدون پاسخ امتیاز صفر دارند.
    |
    */

    $manual_score = 0;
    $total_score  = $objective_score;
    $max_score    = $scoring['total_score'];

    if (
        $scoring['essay_count'] > 0 &&
        $essay_answered_count > 0
    ) {
        $result_status = 'pending';
        $percentage    = 0;
        $graded_at     = null;

    } else {
        $result_status = 'graded';

        $percentage =
            $max_score > 0
                ? round(
                    ($total_score / $max_score) * 100,
                    2
                )
                : 0;

        $graded_at =
            gmdate('Y-m-d H:i:s');
    }


    $now =
        gmdate('Y-m-d H:i:s');


    /*
    |--------------------------------------------------------------------------
    | Save Result
    |--------------------------------------------------------------------------
    */

    $existing_result =
        online_exam_get_attempt_result(
            $attempt_id,
            $user_id
        );


    $result_data = array(
        'attempt_id'      => $attempt_id,
        'exam_id'         => absint($attempt['exam_id']),
        'user_id'         => $user_id,
        'objective_score' => $objective_score,
        'manual_score'    => $manual_score,
        'total_score'     => $total_score,
        'max_score'       => $max_score,
        'percentage'      => $percentage,
        'passed'          => null,
        'status'          => $result_status,
        'graded_at'       => $graded_at,
        'updated_at'      => $now,
    );


    if ($existing_result) {
        $result_saved =
            $wpdb->update(
                $tables['results'],
                $result_data,
                array(
                    'id' =>
                        absint(
                            $existing_result['id']
                        ),
                )
            );

        if ($result_saved === false) {
            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'result_update_failed',
                'ذخیره نتیجه آزمون با خطا مواجه شد.'
            );
        }

    } else {
        $result_data['created_at'] =
            $now;

        $result_saved =
            $wpdb->insert(
                $tables['results'],
                $result_data
            );

        if ($result_saved === false) {
            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'result_create_failed',
                'ایجاد نتیجه آزمون با خطا مواجه شد: ' .
                $wpdb->last_error
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Submit Attempt
    |--------------------------------------------------------------------------
    */

    $attempt_updated =
        $wpdb->update(
            $tables['attempts'],
            array(
                'status'           => 'submitted',
                'submitted_at'     => $now,
                'last_activity_at' => $now,
                'updated_at'       => $now,
            ),
            array(
                'id'      => $attempt_id,
                'user_id' => $user_id,
                'status'  => 'in_progress',
            )
        );


    if ($attempt_updated !== 1) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'attempt_submit_failed',
            'ثبت خودکار آزمون انجام نشد.'
        );
    }


    $wpdb->query('COMMIT');


    return array(
        'attempt' =>
            online_exam_get_attempt(
                $attempt_id
            ),

        'result' =>
            online_exam_get_attempt_result(
                $attempt_id,
                $user_id
            ),

        'already_submitted' =>
            false,
    );
}


/*
|--------------------------------------------------------------------------
| Result URL
|--------------------------------------------------------------------------
*/

function online_exam_get_attempt_result_url($attempt_id)
{
    return add_query_arg(
        'attempt_id',
        absint($attempt_id),
        home_url('/result/')
    );
}


/*
|--------------------------------------------------------------------------
| Take Exam Guard
|--------------------------------------------------------------------------
|
| این قسمت به JavaScript یا wp_footer وابسته نیست.
|
| وقتی /take-exam/ دوباره Load شود:
|
| submitted -> result
| expired   -> auto submit -> result
|
*/

function online_exam_timeout_template_guard()
{
    if (!is_user_logged_in()) {
        return;
    }

    if (
        !is_page('take-exam') &&
        !is_page_template(
            'page-take-exam.php'
        )
    ) {
        return;
    }


    $exam_id =
        isset($_GET['exam_id'])
            ? absint($_GET['exam_id'])
            : 0;


    if (!$exam_id) {
        return;
    }


    $user_id =
        get_current_user_id();


    $attempt =
        online_exam_get_user_exam_attempt(
            $exam_id,
            $user_id
        );


    if (!$attempt) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Already Submitted
    |--------------------------------------------------------------------------
    */

    if ($attempt['status'] === 'submitted') {
        wp_safe_redirect(
            online_exam_get_attempt_result_url(
                $attempt['id']
            )
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Expired
    |--------------------------------------------------------------------------
    */

    if (
        $attempt['status'] === 'in_progress' &&
        online_exam_is_attempt_expired(
            $attempt
        )
    ) {
        $submit_result =
            online_exam_submit_expired_attempt(
                $attempt['id'],
                $user_id
            );


        if (is_wp_error($submit_result)) {
            wp_die(
                esc_html(
                    $submit_result
                        ->get_error_message()
                ),
                'خطای ثبت خودکار آزمون'
            );
        }


        wp_safe_redirect(
            online_exam_get_attempt_result_url(
                $attempt['id']
            )
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Schedule Server-side Safety Net
    |--------------------------------------------------------------------------
    |
    | حتی اگر صفحه بسته شود، برای پایان Attempt یک رویداد
    | WP-Cron برنامه‌ریزی می‌شود.
    |
    */

    if (
        $attempt['status'] === 'in_progress' &&
        !empty($attempt['expires_at'])
    ) {
        $expires_timestamp =
            strtotime(
                $attempt['expires_at'] .
                ' UTC'
            );


        if ($expires_timestamp) {
            $cron_args =
                array(
                    absint($attempt['id']),
                    $user_id,
                );


            if (
                !wp_next_scheduled(
                    'online_exam_cron_timeout_submit',
                    $cron_args
                )
            ) {
                wp_schedule_single_event(
                    max(
                        time() + 1,
                        $expires_timestamp + 1
                    ),
                    'online_exam_cron_timeout_submit',
                    $cron_args
                );
            }
        }
    }
}

add_action(
    'template_redirect',
    'online_exam_timeout_template_guard',
    20
);


/*
|--------------------------------------------------------------------------
| Cron Safety Net
|--------------------------------------------------------------------------
*/

function online_exam_cron_timeout_submit(
    $attempt_id,
    $user_id
) {
    $attempt_id = absint($attempt_id);
    $user_id    = absint($user_id);

    if (!$attempt_id || !$user_id) {
        return;
    }


    $attempt =
        online_exam_get_attempt(
            $attempt_id
        );


    if (
        !$attempt ||
        absint($attempt['user_id']) !==
        $user_id ||
        $attempt['status'] !==
        'in_progress'
    ) {
        return;
    }


    if (
        !online_exam_is_attempt_expired(
            $attempt
        )
    ) {
        return;
    }


    online_exam_submit_expired_attempt(
        $attempt_id,
        $user_id
    );
}

add_action(
    'online_exam_cron_timeout_submit',
    'online_exam_cron_timeout_submit',
    10,
    2
);
