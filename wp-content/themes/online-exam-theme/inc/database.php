<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Database Version
|--------------------------------------------------------------------------
|
| هر وقت ساختار جدول‌ها را تغییر دادیم این نسخه را افزایش می‌دهیم.
|
*/

define('ONLINE_EXAM_DB_VERSION', '1.0.1');

/*
|--------------------------------------------------------------------------
| Table Names
|--------------------------------------------------------------------------
*/

function online_exam_get_tables()
{
    global $wpdb;

    return array(
        'assignments' => $wpdb->prefix . 'exam_assignments',
        'attempts'    => $wpdb->prefix . 'exam_attempts',
        'answers'     => $wpdb->prefix . 'exam_answers',
        'results'     => $wpdb->prefix . 'exam_results',
    );
}


/*
|--------------------------------------------------------------------------
| Install / Update Database
|--------------------------------------------------------------------------
*/

function online_exam_install_database()
{
    $installed_version = get_option(
        'online_exam_db_version'
    );

    if ($installed_version === ONLINE_EXAM_DB_VERSION) {
        return;
    }


    global $wpdb;

    $tables = online_exam_get_tables();

    $charset_collate = $wpdb->get_charset_collate();


    /*
    |--------------------------------------------------------------------------
    | Assignments
    |--------------------------------------------------------------------------
    |
    | مشخص می‌کند چه آزمونی به چه کاربری اختصاص داده شده است.
    |
    */

    $sql_assignments = "
        CREATE TABLE {$tables['assignments']} (

            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            exam_id bigint(20) unsigned NOT NULL,

            user_id bigint(20) unsigned NOT NULL,

            status varchar(20) NOT NULL DEFAULT 'active',

            max_attempts smallint(5) unsigned NOT NULL DEFAULT 1,

            available_from datetime DEFAULT NULL,

            available_until datetime DEFAULT NULL,

            assigned_at datetime DEFAULT NULL,

            created_at datetime DEFAULT NULL,

            updated_at datetime DEFAULT NULL,

            PRIMARY KEY  (id),

            UNIQUE KEY exam_user (exam_id, user_id),

            KEY user_id (user_id),

            KEY exam_id (exam_id),

            KEY status (status)

        ) $charset_collate;
    ";


    /*
    |--------------------------------------------------------------------------
    | Attempts
    |--------------------------------------------------------------------------
    |
    | هر بار که کاربر یک آزمون را شروع می‌کند یک Attempt ساخته می‌شود.
    |
    | question_order:
    | ترتیب نهایی سوالات این Attempt
    |
    | option_order:
    | ترتیب گزینه‌های هر سوال
    |
    | current_position:
    | برای جلوگیری Backend از برگشت به سوال قبلی
    |
    */

    $sql_attempts = "
        CREATE TABLE {$tables['attempts']} (

            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            exam_id bigint(20) unsigned NOT NULL,

            user_id bigint(20) unsigned NOT NULL,

            assignment_id bigint(20) unsigned DEFAULT NULL,

            attempt_no smallint(5) unsigned NOT NULL DEFAULT 1,

            status varchar(20) NOT NULL DEFAULT 'in_progress',

            current_position int(10) unsigned NOT NULL DEFAULT 0,

            question_order longtext DEFAULT NULL,

            option_order longtext DEFAULT NULL,

            started_at datetime DEFAULT NULL,

            expires_at datetime DEFAULT NULL,

            submitted_at datetime DEFAULT NULL,

            last_activity_at datetime DEFAULT NULL,

            created_at datetime DEFAULT NULL,

            updated_at datetime DEFAULT NULL,

            PRIMARY KEY  (id),

            UNIQUE KEY exam_user_attempt (
                exam_id,
                user_id,
                attempt_no
            ),

            KEY user_exam (user_id, exam_id),

            KEY assignment_id (assignment_id),

            KEY status (status),

            KEY expires_at (expires_at)

        ) $charset_collate;
    ";


    /*
    |--------------------------------------------------------------------------
    | Answers
    |--------------------------------------------------------------------------
    |
    | پاسخ هر سوال داخل این جدول ذخیره می‌شود.
    |
    | selected_option_id:
    | برای سوال تستی
    |
    | answer_text:
    | برای سوال تشریحی
    |
    */

    $sql_answers = "
        CREATE TABLE {$tables['answers']} (

            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            attempt_id bigint(20) unsigned NOT NULL,

            exam_id bigint(20) unsigned NOT NULL,

            user_id bigint(20) unsigned NOT NULL,

            question_id bigint(20) unsigned NOT NULL,

selected_option_id varchar(20) DEFAULT NULL,
            answer_text longtext DEFAULT NULL,

            is_correct tinyint(1) DEFAULT NULL,

            awarded_score decimal(10,2) DEFAULT NULL,

            answered_at datetime DEFAULT NULL,

            created_at datetime DEFAULT NULL,

            updated_at datetime DEFAULT NULL,

            PRIMARY KEY  (id),

            UNIQUE KEY attempt_question (
                attempt_id,
                question_id
            ),

            KEY attempt_id (attempt_id),

            KEY user_id (user_id),

            KEY exam_id (exam_id),

            KEY question_id (question_id)

        ) $charset_collate;
    ";


    /*
    |--------------------------------------------------------------------------
    | Results
    |--------------------------------------------------------------------------
    |
    | نتیجه نهایی هر Attempt
    |
    | objective_score:
    | نمره سوالات خودکار
    |
    | manual_score:
    | نمره سوالات تشریحی
    |
    */

    $sql_results = "
        CREATE TABLE {$tables['results']} (

            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            attempt_id bigint(20) unsigned NOT NULL,

            exam_id bigint(20) unsigned NOT NULL,

            user_id bigint(20) unsigned NOT NULL,

            objective_score decimal(10,2) NOT NULL DEFAULT 0,

            manual_score decimal(10,2) NOT NULL DEFAULT 0,

            total_score decimal(10,2) NOT NULL DEFAULT 0,

            max_score decimal(10,2) NOT NULL DEFAULT 0,

            percentage decimal(6,2) NOT NULL DEFAULT 0,

            passed tinyint(1) DEFAULT NULL,

            status varchar(20) NOT NULL DEFAULT 'pending',

            graded_at datetime DEFAULT NULL,

            created_at datetime DEFAULT NULL,

            updated_at datetime DEFAULT NULL,

            PRIMARY KEY  (id),

            UNIQUE KEY attempt_id (attempt_id),

            KEY user_id (user_id),

            KEY exam_id (exam_id),

            KEY status (status)

        ) $charset_collate;
    ";


    /*
    |--------------------------------------------------------------------------
    | dbDelta
    |--------------------------------------------------------------------------
    */

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';


    dbDelta($sql_assignments);

    dbDelta($sql_attempts);

    dbDelta($sql_answers);

    dbDelta($sql_results);


    /*
    |--------------------------------------------------------------------------
    | Save Version
    |--------------------------------------------------------------------------
    */

    update_option(
        'online_exam_db_version',
        ONLINE_EXAM_DB_VERSION
    );
}


/*
|--------------------------------------------------------------------------
| Run Database Installer
|--------------------------------------------------------------------------
|
| چون Theme در حال حاضر فعال است، فقط after_switch_theme کافی نیست.
|
| این تابع در هر Request اجرا می‌شود اما اگر Version تغییر نکرده باشد
| هیچ dbDelta اجرا نمی‌شود.
|
*/

add_action(
    'after_setup_theme',
    'online_exam_install_database'
);