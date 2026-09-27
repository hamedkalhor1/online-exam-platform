<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Permanent Exam Cleanup
|--------------------------------------------------------------------------
|
| فقط وقتی یک آزمون "برای همیشه" حذف شود اجرا می‌شود.
|
| انتقال به زباله‌دان:
|     هیچ دیتایی حذف نمی‌شود.
|
| حذف برای همیشه:
|     - سوالات آزمون
|     - پاسخ‌ها
|     - نتایج
|     - Attemptها
|     - Assignmentهای احتمالی
|     - Cronهای Timeout
|
| پاک می‌شوند.
|
*/


/*
|--------------------------------------------------------------------------
| Clear Attempt Timeout Cron
|--------------------------------------------------------------------------
*/

function online_exam_cleanup_clear_attempt_cron(
    $attempt_id,
    $user_id
) {
    $attempt_id =
        absint(
            $attempt_id
        );

    $user_id =
        absint(
            $user_id
        );


    if (
        !$attempt_id ||
        !$user_id
    ) {
        return;
    }


    /*
     * Cron مربوط به Timeout آزمون.
     *
     * همان Hook استفاده‌شده در timeout.php
     */
    wp_clear_scheduled_hook(
        'online_exam_cron_timeout_submit',
        array(
            $attempt_id,
            $user_id,
        )
    );
}


/*
|--------------------------------------------------------------------------
| Delete Exam Custom Database Data
|--------------------------------------------------------------------------
*/

function online_exam_cleanup_exam_database(
    $exam_id
) {
    global $wpdb;


    $exam_id =
        absint(
            $exam_id
        );


    if (!$exam_id) {
        return;
    }


    if (
        !function_exists(
            'online_exam_get_tables'
        )
    ) {
        return;
    }


    $tables =
        online_exam_get_tables();


    /*
    |--------------------------------------------------------------------------
    | Get Attempts Before Delete
    |--------------------------------------------------------------------------
    |
    | برای پاک‌کردن Cronها باید attempt_id و user_id را قبل از حذف داشته باشیم.
    |
    */

    $attempts =
        $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT id, user_id
                FROM {$tables['attempts']}
                WHERE exam_id = %d
                ",
                $exam_id
            ),
            ARRAY_A
        );


    if (!is_array($attempts)) {
        $attempts = array();
    }


    /*
    |--------------------------------------------------------------------------
    | Clear Timeout Crons
    |--------------------------------------------------------------------------
    */

    foreach ($attempts as $attempt) {

        online_exam_cleanup_clear_attempt_cron(
            absint(
                $attempt['id']
            ),
            absint(
                $attempt['user_id']
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Transaction
    |--------------------------------------------------------------------------
    */

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
                'exam_id' =>
                    $exam_id,
            ),
            array(
                '%d',
            )
        );


    if ($answers_deleted === false) {

        $wpdb->query(
            'ROLLBACK'
        );

        error_log(
            sprintf(
                '[ONLINE_EXAM_CLEANUP] Failed deleting answers for exam %d: %s',
                $exam_id,
                $wpdb->last_error
            )
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Results
    |--------------------------------------------------------------------------
    */

    $results_deleted =
        $wpdb->delete(
            $tables['results'],
            array(
                'exam_id' =>
                    $exam_id,
            ),
            array(
                '%d',
            )
        );


    if ($results_deleted === false) {

        $wpdb->query(
            'ROLLBACK'
        );

        error_log(
            sprintf(
                '[ONLINE_EXAM_CLEANUP] Failed deleting results for exam %d: %s',
                $exam_id,
                $wpdb->last_error
            )
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Attempts
    |--------------------------------------------------------------------------
    */

    $attempts_deleted =
        $wpdb->delete(
            $tables['attempts'],
            array(
                'exam_id' =>
                    $exam_id,
            ),
            array(
                '%d',
            )
        );


    if ($attempts_deleted === false) {

        $wpdb->query(
            'ROLLBACK'
        );

        error_log(
            sprintf(
                '[ONLINE_EXAM_CLEANUP] Failed deleting attempts for exam %d: %s',
                $exam_id,
                $wpdb->last_error
            )
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Assignments
    |--------------------------------------------------------------------------
    |
    | Assignment فعلاً در پروژه استفاده نمی‌شود،
    | ولی جدول آن وجود دارد؛ پس برای جلوگیری از دیتای یتیم پاکش می‌کنیم.
    |
    */

    if (
        !empty(
            $tables['assignments']
        )
    ) {

        $assignments_deleted =
            $wpdb->delete(
                $tables['assignments'],
                array(
                    'exam_id' =>
                        $exam_id,
                ),
                array(
                    '%d',
                )
            );


        if ($assignments_deleted === false) {

            $wpdb->query(
                'ROLLBACK'
            );

            error_log(
                sprintf(
                    '[ONLINE_EXAM_CLEANUP] Failed deleting assignments for exam %d: %s',
                    $exam_id,
                    $wpdb->last_error
                )
            );

            return;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $wpdb->query(
        'COMMIT'
    );
}


/*
|--------------------------------------------------------------------------
| Delete Exam Questions
|--------------------------------------------------------------------------
*/

function online_exam_cleanup_exam_questions(
    $exam_id
) {
    $exam_id =
        absint(
            $exam_id
        );


    if (!$exam_id) {
        return;
    }


    /*
     * همه سوالات مربوط به آزمون،
     * حتی Draft یا Trash.
     */

    $question_ids =
        get_posts(
            array(

                'post_type' =>
                    'question',

                'post_status' =>
                    'any',

                'posts_per_page' =>
                    -1,

                'fields' =>
                    'ids',

                'no_found_rows' =>
                    true,

                'meta_query' =>
                    array(

                        array(

                            'key' =>
                                '_online_exam_exam_id',

                            'value' =>
                                $exam_id,

                            'compare' =>
                                '=',

                            'type' =>
                                'NUMERIC',
                        ),
                    ),
            )
        );


    if (empty($question_ids)) {
        return;
    }


    foreach ($question_ids as $question_id) {

        $question_id =
            absint(
                $question_id
            );


        if (!$question_id) {
            continue;
        }


        /*
         * true =
         * حذف برای همیشه،
         * نه انتقال به Trash.
         *
         * Post Metaهای سوال نیز توسط WordPress پاک می‌شوند.
         */

        wp_delete_post(
            $question_id,
            true
        );
    }
}


/*
|--------------------------------------------------------------------------
| Handle Permanent Exam Deletion
|--------------------------------------------------------------------------
|
| deleted_post بعد از حذف واقعی Post اجرا می‌شود.
|
| در نتیجه با "Trash" شدن آزمون اجرا نمی‌شود.
|
*/

function online_exam_cleanup_after_exam_deleted(
    $post_id,
    $post
) {
    $post_id =
        absint(
            $post_id
        );


    if (
        !$post_id ||
        !$post instanceof WP_Post
    ) {
        return;
    }


    /*
     * فقط Exam.
     *
     * وقتی Question را داخل همین Cleanup حذف می‌کنیم،
     * این Hook دوباره اجرا می‌شود ولی چون post_type آن question است،
     * هیچ اتفاق دیگری نمی‌افتد.
     */

    if (
        $post->post_type !==
        'exam'
    ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | 1. Custom Database
    |--------------------------------------------------------------------------
    */

    online_exam_cleanup_exam_database(
        $post_id
    );


    /*
    |--------------------------------------------------------------------------
    | 2. Questions
    |--------------------------------------------------------------------------
    */

    online_exam_cleanup_exam_questions(
        $post_id
    );


    error_log(
        sprintf(
            '[ONLINE_EXAM_CLEANUP] Exam %d permanently deleted and related data cleaned.',
            $post_id
        )
    );
}


add_action(
    'deleted_post',
    'online_exam_cleanup_after_exam_deleted',
    10,
    2
);