<?php
/*
Template Name: آزمون‌های من
*/

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Login Required
|--------------------------------------------------------------------------
*/

if (!is_user_logged_in()) {
    wp_safe_redirect(
        home_url('/login/')
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$current_user =
    wp_get_current_user();

$current_user_id =
    get_current_user_id();

$expert_name =
    $current_user->display_name
        ? $current_user->display_name
        : $current_user->user_login;


/*
|--------------------------------------------------------------------------
| Avatar Letter
|--------------------------------------------------------------------------
*/

if (function_exists('mb_substr')) {
    $avatar_letter =
        mb_substr(
            $expert_name,
            0,
            1,
            'UTF-8'
        );
} else {
    $avatar_letter = 'ک';
}


/*
|--------------------------------------------------------------------------
| ACF Header Data
|--------------------------------------------------------------------------
*/

$site_title =
    function_exists('online_exam_get_option')
        ? online_exam_get_option(
            'site_title',
            'سامانه آزمون آنلاین'
        )
        : 'سامانه آزمون آنلاین';

$site_subtitle =
    function_exists('online_exam_get_option')
        ? online_exam_get_option(
            'site_subtitle',
            'پنل کارشناس'
        )
        : 'پنل کارشناس';

$site_logo_text =
    function_exists('online_exam_get_option')
        ? online_exam_get_option(
            'site_logo_text',
            'آ'
        )
        : 'آ';


/*
|--------------------------------------------------------------------------
| Available Exams
|--------------------------------------------------------------------------
*/

$exams =
    online_exam_get_available_exams();

$exam_count = 0;


get_header();

?>

<div class="student-page">

    <header class="student-header">

        <div class="student-header-inner">

            <a
                href="<?php echo esc_url(home_url('/my-exams/')); ?>"
                class="student-brand"
            >

                <span class="student-brand-logo">
                    <?php echo esc_html($site_logo_text); ?>
                </span>


                <div class="student-brand-text">

                    <strong>
                        <?php echo esc_html($site_title); ?>
                    </strong>

                    <span>
                        <?php echo esc_html($site_subtitle); ?>
                    </span>

                </div>

            </a>


            <div class="student-user">

                <div class="student-user-info">

                    <span class="student-welcome">
                        خوش آمدید
                    </span>

                    <strong>
                        <?php echo esc_html($expert_name); ?>
                    </strong>

                </div>


                <div class="student-avatar">
                    <?php echo esc_html($avatar_letter); ?>
                </div>


                <a
                    href="<?php
                    echo esc_url(
                        wp_logout_url(
                            home_url('/login/')
                        )
                    );
                    ?>"
                    class="logout-button"
                >
                    خروج
                </a>

            </div>

        </div>

    </header>


    <main class="student-main">

        <div class="student-container">

            <section class="page-heading">

                <div>

                    <span class="page-eyebrow">
                        آزمون‌های شما
                    </span>

                    <h1>
                        آزمون‌های من
                    </h1>

                </div>

            </section>


            <section class="exam-section">

                <div class="section-heading">

                    <div>

                        <h2>
                            فهرست آزمون‌ها
                        </h2>

                        <p>
                            وضعیت و زمان برگزاری هر آزمون در این بخش نمایش داده می‌شود.
                        </p>

                    </div>


            
                </div>


                <div class="exam-list">

                    <?php if (!empty($exams)) : ?>

                        <?php foreach ($exams as $exam) : ?>

                            <?php

                            $exam_intro_url =
                                add_query_arg(
                                    'exam_id',
                                    $exam['id'],
                                    home_url('/exam-intro/')
                                );

                            $take_exam_url =
                                add_query_arg(
                                    'exam_id',
                                    $exam['id'],
                                    home_url('/take-exam/')
                                );


                            if (
                                $exam['mcq_count'] > 0 &&
                                $exam['essay_count'] > 0
                            ) {
                                $exam_type =
                                    'تستی و تشریحی';

                            } elseif (
                                $exam['essay_count'] > 0
                            ) {
                                $exam_type =
                                    'تشریحی';

                            } else {
                                $exam_type =
                                    'تستی';
                            }


                            $exam_description =
                                sprintf(
                                    'این آزمون شامل %d سوال است.',
                                    $exam['question_count']
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | Schedule
                            |--------------------------------------------------------------------------
                            */

                            $schedule =
                                online_exam_get_exam_schedule(
                                    $exam['id']
                                );

                            $schedule_status =
                                online_exam_get_exam_schedule_status(
                                    $exam['id']
                                );

                            if (is_wp_error($schedule_status)) {
                                $schedule_status =
                                    'invalid';
                            }


                            $schedule_date_text =
                                '';

                            $schedule_time_text =
                                '';

                            $schedule_start_time =
                                '';


                            if (
                                is_array($schedule) &&
                                !empty($schedule['has_schedule'])
                            ) {

                                $schedule_date_text =
                                    wp_date(
                                        'Y/m/d',
                                        $schedule['start_timestamp'],
                                        wp_timezone()
                                    );

                                $schedule_start_time =
                                    wp_date(
                                        'H:i',
                                        $schedule['start_timestamp'],
                                        wp_timezone()
                                    );

                                $schedule_time_text =
                                    sprintf(
                                        '%s تا %s',
                                        $schedule_start_time,
                                        wp_date(
                                            'H:i',
                                            $schedule['end_timestamp'],
                                            wp_timezone()
                                        )
                                    );
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Attempt
                            |--------------------------------------------------------------------------
                            */

                            $attempt =
                                online_exam_get_user_exam_attempt(
                                    $exam['id'],
                                    $current_user_id
                                );

                            $attempt_status =
                                $attempt
                                    ? $attempt['status']
                                    : '';
/*
|--------------------------------------------------------------------------
| Hide Finished Exams
|--------------------------------------------------------------------------
*/

if (
    $attempt_status === 'submitted'
) {
    continue;
}


if (
    $schedule_status === 'ended'
) {
    continue;
}
$exam_count++;

                            /*
                            |--------------------------------------------------------------------------
                            | Card State
                            |--------------------------------------------------------------------------
                            */

                            $status_label =
                                'آماده شرکت';

                            $status_class =
                                'status-active';

                            $action_label =
                                'شرکت در آزمون';

                            $action_url =
                                $exam_intro_url;

                            $action_disabled =
                                false;


                            if (
                                $attempt &&
                                $attempt_status === 'submitted'
                            ) {

                                $status_label =
                                    'تکمیل شده';

                                $status_class =
                                    'status-completed';

                                $action_label =
                                    'مشاهده نتیجه';

                                $action_url =
                                    add_query_arg(
                                        'attempt_id',
                                        absint($attempt['id']),
                                        home_url('/result/')
                                    );

                            } elseif (
                                $attempt &&
                                $attempt_status === 'in_progress'
                            ) {

                                $remaining_seconds =
                                    online_exam_get_remaining_seconds(
                                        $attempt
                                    );

                                if ($remaining_seconds > 0) {

                                    $status_label =
                                        'در حال انجام';

                                    $status_class =
                                        'status-active';

                                    $action_label =
                                        'ادامه آزمون';

                                    $action_url =
                                        $take_exam_url;

                                } else {

                                    /*
                                     * timeout.php با ورود به صفحه آزمون
                                     * Attempt را نهایی و به نتیجه هدایت می‌کند.
                                     */
                                    $status_label =
                                        'زمان تمام شده';

                                    $status_class =
                                        'status-ended';

                                    $action_label =
                                        'مشاهده نتیجه';

                                    $action_url =
                                        $take_exam_url;
                                }

                            } elseif (
                                $schedule_status === 'upcoming'
                            ) {

                                $status_label =
                                    'هنوز شروع نشده';

                                $status_class =
                                    'status-upcoming';

                                $action_label =
                                    $schedule_start_time !== ''
                                        ? 'شروع در ' . $schedule_start_time
                                        : 'هنوز شروع نشده';

                                $action_url =
                                    '';

                                $action_disabled =
                                    true;

                            } elseif (
                                $schedule_status === 'ended'
                            ) {

                                $status_label =
                                    'پایان یافته';

                                $status_class =
                                    'status-ended';

                                $action_label =
                                    'مهلت شرکت تمام شده';

                                $action_url =
                                    '';

                                $action_disabled =
                                    true;

                            } elseif (
                                $schedule_status === 'invalid'
                            ) {

                                $status_label =
                                    'زمان‌بندی نامعتبر';

                                $status_class =
                                    'status-ended';

                                $action_label =
                                    'امکان ورود وجود ندارد';

                                $action_url =
                                    '';

                                $action_disabled =
                                    true;
                            }

                            ?>


                            <article
                                class="exam-card exam-card-active"
                            >

                                <div class="exam-card-main">

                                    <div class="exam-icon">

                                        <svg
                                            viewBox="0 0 24 24"
                                            width="26"
                                            height="26"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                d="M6 3h12a2 2 0 0 1 2 2v16l-8-4-8 4V5a2 2 0 0 1 2-2Z"
                                            />
                                        </svg>

                                    </div>


                                    <div class="exam-info">

                                        <div class="exam-title-row">

                                            <h3>
                                                <?php echo esc_html($exam['title']); ?>
                                            </h3>


                                            <span
                                                class="exam-status <?php echo esc_attr($status_class); ?>"
                                            >
                                                <?php echo esc_html($status_label); ?>
                                            </span>

                                        </div>


                                        <p class="exam-description">
                                            <?php echo esc_html($exam_description); ?>
                                        </p>


                                        <div class="exam-meta">

                                            <div class="exam-meta-item">

                                                <span>
                                                    تعداد سوالات
                                                </span>

                                                <strong>
                                                    <?php echo esc_html($exam['question_count']); ?>
                                                    سوال
                                                </strong>

                                            </div>


                                            <div class="exam-meta-item">

                                                <span>
                                                    نوع آزمون
                                                </span>

                                                <strong>
                                                    <?php echo esc_html($exam_type); ?>
                                                </strong>

                                            </div>


                                            <div class="exam-meta-item">

                                                <span>
                                                    مدت آزمون
                                                </span>

                                                <strong>
                                                    <?php echo esc_html($exam['duration']); ?>
                                                    دقیقه
                                                </strong>

                                            </div>


                                            <?php if (
                                                $schedule_date_text !== ''
                                            ) : ?>

                                                <div class="exam-meta-item">

                                                    <span>
                                                        زمان برگزاری
                                                    </span>

                                                    <strong>
                                                        <?php
                                                        echo esc_html(
                                                            $schedule_date_text .
                                                            ' | ' .
                                                            $schedule_time_text
                                                        );
                                                        ?>
                                                    </strong>

                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>


                                <div class="exam-card-action">

                                    <?php if ($action_disabled) : ?>

                                        <span
                                            class="button-primary"
                                            aria-disabled="true"
                                            style="
                                                opacity:.55;
                                                cursor:not-allowed;
                                                pointer-events:none;
                                            "
                                        >
                                            <?php echo esc_html($action_label); ?>
                                        </span>

                                    <?php else : ?>

                                        <a
                                            href="<?php echo esc_url($action_url); ?>"
                                            class="button-primary"
                                        >
                                            <?php echo esc_html($action_label); ?>

                                            <span>
                                                ←
                                            </span>
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    <?php else : ?>

                        <div class="exam-empty-state">

                            <div class="exam-icon">

                                <svg
                                    viewBox="0 0 24 24"
                                    width="26"
                                    height="26"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        d="M6 3h12a2 2 0 0 1 2 2v16l-8-4-8 4V5a2 2 0 0 1 2-2Z"
                                    />
                                </svg>

                            </div>

                            <h3>
                                آزمونی وجود ندارد
                            </h3>

                            <p>
                                در حال حاضر آزمونی برای شما منتشر نشده است.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </div>

    </main>

</div>

<?php
get_footer();
?>
