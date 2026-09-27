<?php
/*
Template Name: نتیجه آزمون
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

$user_id =
    get_current_user_id();


$current_user =
    wp_get_current_user();


$student_name =
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
            $student_name,
            0,
            1,
            'UTF-8'
        );

} else {

    $avatar_letter = 'ک';
}


/*
|--------------------------------------------------------------------------
| Attempt ID
|--------------------------------------------------------------------------
*/

$attempt_id =
    isset($_GET['attempt_id'])
        ? absint(
            $_GET['attempt_id']
        )
        : 0;


if (!$attempt_id) {

    wp_safe_redirect(
        home_url('/my-exams/')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Attempt
|--------------------------------------------------------------------------
*/

$attempt =
    online_exam_get_attempt(
        $attempt_id
    );


if (
    !$attempt ||
    absint($attempt['user_id']) !== $user_id
) {

    wp_safe_redirect(
        home_url('/my-exams/')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Submitted Only
|--------------------------------------------------------------------------
*/

if (
    $attempt['status'] !==
    'submitted'
) {

    wp_safe_redirect(
        home_url('/my-exams/')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Exam
|--------------------------------------------------------------------------
*/

$exam_id =
    absint(
        $attempt['exam_id']
    );


$exam =
    online_exam_get_exam_data(
        $exam_id
    );


if (!$exam) {

    wp_safe_redirect(
        home_url('/my-exams/')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Result
|--------------------------------------------------------------------------
*/

$result =
    online_exam_get_attempt_result(
        $attempt_id,
        $user_id
    );


if (!$result) {

    wp_safe_redirect(
        home_url('/submit-success/')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$statistics =
    online_exam_get_attempt_statistics(
        $attempt_id,
        $user_id
    );


if (!$statistics) {

    wp_die(
        'اطلاعات نتیجه آزمون قابل دریافت نیست.',
        'خطای نتیجه آزمون'
    );
}


/*
|--------------------------------------------------------------------------
| Result State
|--------------------------------------------------------------------------
*/

$is_pending =
    $result['status'] ===
    'pending';


$is_graded =
    $result['status'] ===
    'graded';


/*
|--------------------------------------------------------------------------
| Scores
|--------------------------------------------------------------------------
*/

$percentage =
    $is_graded
        ? floatval(
            $result['percentage']
        )
        : 0;


$total_score =
    $is_graded
        ? floatval(
            $result['total_score']
        )
        : 0;


$max_score =
    $is_graded
        ? floatval(
            $result['max_score']
        )
        : 0;


$objective_score =
    floatval(
        $result['objective_score']
    );


/*
|--------------------------------------------------------------------------
| Performance Text
|--------------------------------------------------------------------------
*/

if ($is_pending) {

    $performance_title =
        'در انتظار تصحیح';

    $performance_description =
        'این آزمون دارای سوال تشریحی است و نتیجه نهایی پس از تصحیح توسط مدرس نمایش داده می‌شود.';

} elseif ($percentage >= 90) {

    $performance_title =
        'عملکرد عالی';

    $performance_description =
        'درصد بسیار خوبی در این آزمون کسب کرده‌اید.';

} elseif ($percentage >= 75) {

    $performance_title =
        'عملکرد بسیار خوب';

    $performance_description =
        'عملکرد شما در این آزمون بسیار خوب بوده است.';

} elseif ($percentage >= 50) {

    $performance_title =
        'عملکرد متوسط';

    $performance_description =
        'نتیجه آزمون ثبت شده است و می‌توانید جزئیات عملکرد خود را مشاهده کنید.';

} else {

    $performance_title =
        'نتیجه ثبت شد';

    $performance_description =
        'نتیجه آزمون شما ثبت شده است.';
}


/*
|--------------------------------------------------------------------------
| Dates
|--------------------------------------------------------------------------
*/

$exam_date =
    '—';

$start_time =
    '—';

$submitted_time =
    '—';


if (!empty($attempt['started_at'])) {

    $started_timestamp =
        strtotime(
            $attempt['started_at'] .
            ' UTC'
        );


    if ($started_timestamp) {

        $exam_date =
            wp_date(
                'Y/m/d',
                $started_timestamp,
                wp_timezone()
            );


        $start_time =
            wp_date(
                'H:i',
                $started_timestamp,
                wp_timezone()
            );
    }
}


if (!empty($attempt['submitted_at'])) {

    $submitted_timestamp =
        strtotime(
            $attempt['submitted_at'] .
            ' UTC'
        );


    if ($submitted_timestamp) {

        $submitted_time =
            wp_date(
                'H:i',
                $submitted_timestamp,
                wp_timezone()
            );
    }
}


/*
|--------------------------------------------------------------------------
| ACF Header
|--------------------------------------------------------------------------
*/

$site_title =
    function_exists(
        'online_exam_get_option'
    )
        ? online_exam_get_option(
            'site_title',
            'سامانه آزمون آنلاین'
        )
        : 'سامانه آزمون آنلاین';


$site_subtitle =
    function_exists(
        'online_exam_get_option'
    )
        ? online_exam_get_option(
            'site_subtitle',
            'پنل دانشجو'
        )
        : 'پنل دانشجو';


$site_logo_text =
    function_exists(
        'online_exam_get_option'
    )
        ? online_exam_get_option(
            'site_logo_text',
            'آ'
        )
        : 'آ';


get_header();

?>

<div class="student-page">

    <!-- =========================================================
         Header
    ========================================================== -->

    <header class="student-header">

        <div class="student-header-inner">

            <a
                href="<?php echo esc_url(home_url('/my-exams/')); ?>"
                class="student-brand"
            >

                <span class="student-brand-logo">

                    <?php
                    echo esc_html(
                        $site_logo_text
                    );
                    ?>

                </span>


                <div class="student-brand-text">

                    <strong>

                        <?php
                        echo esc_html(
                            $site_title
                        );
                        ?>

                    </strong>


                    <span>

                        <?php
                        echo esc_html(
                            $site_subtitle
                        );
                        ?>

                    </span>

                </div>

            </a>


            <div class="student-user">

                <div class="student-user-info">

                    <span class="student-welcome">
                        خوش آمدید
                    </span>

                    <strong>

                        <?php
                        echo esc_html(
                            $student_name
                        );
                        ?>

                    </strong>

                </div>


                <div class="student-avatar">

                    <?php
                    echo esc_html(
                        $avatar_letter
                    );
                    ?>

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


    <!-- =========================================================
         Result
    ========================================================== -->

    <main class="result-page">

        <div class="result-container">

            <a
                href="<?php echo esc_url(home_url('/my-exams/')); ?>"
                class="back-link"
            >
                <span>→</span>
                بازگشت به آزمون‌های من
            </a>


            <section class="result-main-card">


                <!-- =================================================
                     Result Header
                ================================================== -->

                <div class="result-header">

                    <div>

                        <span class="result-eyebrow">
                            نتیجه آزمون
                        </span>


                        <h1>

                            <?php
                            echo esc_html(
                                $exam['title']
                            );
                            ?>

                        </h1>


                        <p>

                            <?php if ($is_pending) : ?>

                                نتیجه نهایی این آزمون هنوز آماده نشده است.

                            <?php else : ?>

                                نتیجه نهایی آزمون شما ثبت شده است.

                            <?php endif; ?>

                        </p>

                    </div>


                    <?php if ($is_pending) : ?>

                        <span class="result-status">
                            در انتظار تصحیح
                        </span>

                    <?php else : ?>

                        <span class="result-status result-status-pass">
                            نتیجه آماده
                        </span>

                    <?php endif; ?>

                </div>


                <!-- =================================================
                     Score
                ================================================== -->

                <div class="score-section">


                    <div class="score-circle">

                        <?php if ($is_pending) : ?>

                            <span>
                                وضعیت
                            </span>

                            <strong>
                                —
                            </strong>

                            <small>
                                در انتظار تصحیح
                            </small>

                        <?php else : ?>

                            <span>
                                امتیاز شما
                            </span>

                            <strong>

                                <?php
                                echo esc_html(
                                    $total_score
                                );
                                ?>

                            </strong>

                            <small>

                                از

                                <?php
                                echo esc_html(
                                    $max_score
                                );
                                ?>

                            </small>

                        <?php endif; ?>

                    </div>


                    <div class="score-description">

                        <h2>

                            <?php
                            echo esc_html(
                                $performance_title
                            );
                            ?>

                        </h2>


                        <p>

                            <?php
                            echo esc_html(
                                $performance_description
                            );
                            ?>

                        </p>


                        <?php if ($is_graded) : ?>

                            <div class="score-progress">

                                <div class="score-progress-header">

                                    <span>
                                        درصد پاسخ صحیح
                                    </span>


                                    <strong>

                                        <?php
                                        echo esc_html(
                                            number_format(
                                                $percentage,
                                                2
                                            )
                                        );
                                        ?>

                                        ٪

                                    </strong>

                                </div>


                                <div class="score-progress-track">

                                    <div
                                        class="score-progress-bar"
                                        style="width: <?php echo esc_attr(
                                            min(
                                                100,
                                                max(
                                                    0,
                                                    $percentage
                                                )
                                            )
                                        ); ?>%;"
                                    ></div>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="result-divider"></div>


                <!-- =================================================
                     Statistics
                ================================================== -->

                <div class="result-stat-grid">


                    <div class="result-stat">

                        <span>
                            پاسخ صحیح
                        </span>

                        <strong>

                            <?php
                            echo esc_html(
                                $statistics[
                                    'correct_count'
                                ]
                            );
                            ?>

                        </strong>

                        <small>
                            سوال تستی
                        </small>

                    </div>


                    <div class="result-stat">

                        <span>
                            پاسخ غلط
                        </span>

                        <strong>

                            <?php
                            echo esc_html(
                                $statistics[
                                    'incorrect_count'
                                ]
                            );
                            ?>

                        </strong>

                        <small>
                            سوال تستی
                        </small>

                    </div>


                    <div class="result-stat">

                        <span>
                            سوالات تشریحی
                        </span>

                        <strong>

                            <?php
                            echo esc_html(
                                $statistics[
                                    'essay_count'
                                ]
                            );
                            ?>

                        </strong>

                        <small>

                            <?php if (
                                $statistics['essay_count'] > 0
                            ) : ?>

                                در انتظار تصحیح

                            <?php else : ?>

                                بدون سوال تشریحی

                            <?php endif; ?>

                        </small>

                    </div>


                    <div class="result-stat">

                        <span>
                            بدون پاسخ
                        </span>

                        <strong>

                            <?php
                            echo esc_html(
                                $statistics[
                                    'unanswered_count'
                                ]
                            );
                            ?>

                        </strong>

                        <small>
                            سوال
                        </small>

                    </div>

                </div>


                <div class="result-divider"></div>


                <!-- =================================================
                     Exam Details
                ================================================== -->

                <div class="result-details">

                    <h2>
                        اطلاعات آزمون
                    </h2>


                    <div class="result-detail-grid">


                        <div>

                            <span>
                                تاریخ برگزاری
                            </span>

                            <strong>

                                <?php
                                echo esc_html(
                                    $exam_date
                                );
                                ?>

                            </strong>

                        </div>


                        <div>

                            <span>
                                ساعت شروع
                            </span>

                            <strong>

                                <?php
                                echo esc_html(
                                    $start_time
                                );
                                ?>

                            </strong>

                        </div>


                        <div>

                            <span>
                                زمان ثبت
                            </span>

                            <strong>

                                <?php
                                echo esc_html(
                                    $submitted_time
                                );
                                ?>

                            </strong>

                        </div>


                        <div>

                            <span>
                                مدت آزمون
                            </span>

                            <strong>

                                <?php
                                echo esc_html(
                                    $exam['duration']
                                );
                                ?>

                                دقیقه

                            </strong>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     Actions
                ================================================== -->

                <div class="result-actions">

                    <a
                        href="<?php echo esc_url(home_url('/my-exams/')); ?>"
                        class="result-primary-button"
                    >
                        بازگشت به آزمون‌های من
                    </a>

                </div>

            </section>

        </div>

    </main>

</div>

<?php
get_footer();
?>