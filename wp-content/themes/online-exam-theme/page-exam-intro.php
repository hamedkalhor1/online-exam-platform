<?php
/*
Template Name: معرفی آزمون
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
| Exam ID
|--------------------------------------------------------------------------
*/

$exam_id =
    isset($_GET['exam_id'])
        ? absint($_GET['exam_id'])
        : 0;


if (!$exam_id) {
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
| URLs
|--------------------------------------------------------------------------
*/

$take_exam_url =
    add_query_arg(
        'exam_id',
        $exam_id,
        home_url('/take-exam/')
    );

$back_url =
    home_url('/my-exams/');


/*
|--------------------------------------------------------------------------
| Existing Attempt
|--------------------------------------------------------------------------
*/

$attempt =
    online_exam_get_user_exam_attempt(
        $exam_id,
        $current_user_id
    );


if (
    $attempt &&
    $attempt['status'] === 'submitted'
) {

    wp_safe_redirect(
        add_query_arg(
            'attempt_id',
            absint($attempt['id']),
            home_url('/result/')
        )
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Schedule
|--------------------------------------------------------------------------
*/

$schedule =
    online_exam_get_exam_schedule(
        $exam_id
    );

$schedule_status =
    online_exam_get_exam_schedule_status(
        $exam_id
    );


if (is_wp_error($schedule_status)) {
    $schedule_status =
        'invalid';
}


$schedule_date_text =
    '';

$schedule_start_text =
    '';

$schedule_end_text =
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

    $schedule_start_text =
        wp_date(
            'H:i',
            $schedule['start_timestamp'],
            wp_timezone()
        );

    $schedule_end_text =
        wp_date(
            'H:i',
            $schedule['end_timestamp'],
            wp_timezone()
        );
}


/*
|--------------------------------------------------------------------------
| Intro State
|--------------------------------------------------------------------------
*/

$can_enter_exam =
    false;

$action_label =
    'شروع آزمون';

$intro_status_text =
    'آماده شروع آزمون';

$availability_message =
    '';

$availability_style =
    '';


if (
    $attempt &&
    $attempt['status'] === 'in_progress'
) {

    $remaining_seconds =
        online_exam_get_remaining_seconds(
            $attempt
        );

    if ($remaining_seconds > 0) {

        $can_enter_exam =
            true;

        $action_label =
            'ادامه آزمون';

        $intro_status_text =
            'آزمون شما در حال انجام است';

    } else {

        /*
         * timeout.php در صفحه take-exam
         * Attempt را نهایی و به نتیجه هدایت می‌کند.
         */
        wp_safe_redirect(
            $take_exam_url
        );

        exit;
    }

} elseif (
    $schedule_status === 'active'
) {

    $can_enter_exam =
        true;

    $action_label =
        'شروع آزمون';

    $intro_status_text =
        'آماده شروع آزمون';

} elseif (
    $schedule_status === 'upcoming'
) {

    $can_enter_exam =
        false;

    $intro_status_text =
        'آزمون هنوز شروع نشده است';

    if (
        $schedule_date_text !== '' &&
        $schedule_start_text !== ''
    ) {

        $availability_message =
            sprintf(
                'این آزمون در تاریخ %s از ساعت %s فعال می‌شود.',
                $schedule_date_text,
                $schedule_start_text
            );

    } else {

        $availability_message =
            'زمان شروع این آزمون هنوز نرسیده است.';
    }

    $availability_style =
        'background:#fff8e5;border:1px solid #dba617;color:#6c4a00;';

} elseif (
    $schedule_status === 'ended'
) {

    $can_enter_exam =
        false;

    $intro_status_text =
        'مهلت آزمون به پایان رسیده است';

    $availability_message =
        'مهلت شرکت در این آزمون به پایان رسیده و امکان شروع آزمون وجود ندارد.';

    $availability_style =
        'background:#fcf0f1;border:1px solid #d63638;color:#8a2424;';

} else {

    $can_enter_exam =
        false;

    $intro_status_text =
        'زمان‌بندی آزمون معتبر نیست';

    $availability_message =
        'به دلیل نامعتبر بودن زمان‌بندی، امکان شروع آزمون وجود ندارد.';

    $availability_style =
        'background:#fcf0f1;border:1px solid #d63638;color:#8a2424;';
}


/*
|--------------------------------------------------------------------------
| Exam Type
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Exam Rules
|--------------------------------------------------------------------------
*/

if (
    function_exists(
        'online_exam_get_intro_rules'
    )
) {
    $exam_rules =
        online_exam_get_intro_rules(
            $exam_id
        );
} else {
    $exam_rules =
        array();
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


get_header();

?>

<div class="exam-intro-page">

    <header class="exam-intro-header">

        <div class="exam-intro-header-inner">

            <a
                href="<?php echo esc_url($back_url); ?>"
                class="exam-intro-brand"
            >

                <span class="exam-intro-brand-logo">
                    <?php echo esc_html($site_logo_text); ?>
                </span>


                <div>

                    <strong>
                        <?php echo esc_html($site_title); ?>
                    </strong>

                    <span>
                        <?php echo esc_html($site_subtitle); ?>
                    </span>

                </div>

            </a>


            <div class="exam-intro-user">

                <div>

                    <span>
                        شرکت‌کننده
                    </span>

                    <strong>
                        <?php echo esc_html($expert_name); ?>
                    </strong>

                </div>

            </div>

        </div>

    </header>


    <main class="exam-intro-main">

        <div class="exam-intro-container">

            <div class="exam-intro-top">

                <a
                    href="<?php echo esc_url($back_url); ?>"
                    class="exam-intro-back"
                >
                    <span>
                        →
                    </span>

                    بازگشت به آزمون‌های من
                </a>

            </div>


            <section class="exam-intro-card">

                <div class="exam-intro-title">

                    <div class="exam-intro-title-icon">

                        <svg
                            viewBox="0 0 24 24"
                            width="30"
                            height="30"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M6 3h12a2 2 0 0 1 2 2v16l-8-4-8 4V5a2 2 0 0 1 2-2Z"
                            />
                        </svg>

                    </div>


                    <div>

                        <span class="exam-intro-eyebrow">
                            <?php echo esc_html($intro_status_text); ?>
                        </span>

                        <h1>
                            <?php echo esc_html($exam['title']); ?>
                        </h1>

                        <p>
                            پیش از شروع آزمون اطلاعات و قوانین زیر را با دقت مطالعه کنید.
                        </p>

                    </div>

                </div>


                <?php if ($availability_message !== '') : ?>

                    <div
                        style="
                            <?php echo esc_attr($availability_style); ?>
                            border-radius:10px;
                            padding:14px 16px;
                            margin:0 0 20px;
                            font-weight:600;
                            line-height:1.9;
                        "
                    >
                        <?php echo esc_html($availability_message); ?>
                    </div>

                <?php endif; ?>


                <div class="exam-intro-meta">

                    <div class="exam-intro-meta-item">

                        <span class="exam-intro-meta-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M6 4h12v16H6z"/>
                                <path d="M9 8h6"/>
                                <path d="M9 12h6"/>
                                <path d="M9 16h4"/>
                            </svg>

                        </span>


                        <div>

                            <span>
                                تعداد سوالات
                            </span>

                            <strong>
                                <?php echo esc_html($exam['question_count']); ?>
                                سوال
                            </strong>

                        </div>

                    </div>


                    <div class="exam-intro-meta-item">

                        <span class="exam-intro-meta-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 7v5l3 2"/>
                            </svg>

                        </span>


                        <div>

                            <span>
                                مدت آزمون
                            </span>

                            <strong>
                                <?php echo esc_html($exam['duration']); ?>
                                دقیقه
                            </strong>

                        </div>

                    </div>


                    <div class="exam-intro-meta-item">

                        <span class="exam-intro-meta-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <circle cx="12" cy="12" r="9"/>
                                <path d="m8.5 12 2.2 2.2 4.8-5"/>
                            </svg>

                        </span>


                        <div>

                            <span>
                                نوع آزمون
                            </span>

                            <strong>
                                <?php echo esc_html($exam_type); ?>
                            </strong>

                        </div>

                    </div>


                    <div class="exam-intro-meta-item">

                        <span class="exam-intro-meta-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M4 6h16"/>
                                <path d="M4 12h16"/>
                                <path d="M4 18h16"/>
                            </svg>

                        </span>


                        <div>

                            <span>
                                ترکیب سوالات
                            </span>

                            <strong>

                                <?php if (
                                    $exam['mcq_count'] > 0
                                ) : ?>

                                    <?php echo esc_html($exam['mcq_count']); ?>
                                    تستی

                                <?php endif; ?>


                                <?php if (
                                    $exam['mcq_count'] > 0 &&
                                    $exam['essay_count'] > 0
                                ) : ?>
                                    /
                                <?php endif; ?>


                                <?php if (
                                    $exam['essay_count'] > 0
                                ) : ?>

                                    <?php echo esc_html($exam['essay_count']); ?>
                                    تشریحی

                                <?php endif; ?>

                            </strong>

                        </div>

                    </div>


                    <?php if ($schedule_date_text !== '') : ?>

                        <div class="exam-intro-meta-item">

                            <span class="exam-intro-meta-icon">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <rect x="4" y="5" width="16" height="15" rx="2"/>
                                    <path d="M8 3v4"/>
                                    <path d="M16 3v4"/>
                                    <path d="M4 10h16"/>
                                </svg>

                            </span>


                            <div>

                                <span>
                                    زمان برگزاری
                                </span>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $schedule_date_text .
                                        ' | ' .
                                        $schedule_start_text .
                                        ' تا ' .
                                        $schedule_end_text
                                    );
                                    ?>
                                </strong>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <div class="exam-intro-rules">

                    <div class="exam-intro-rules-heading">

                        <span class="exam-intro-warning-icon">
                            !
                        </span>


                        <div>

                            <h2>
                                نکات مهم قبل از شروع
                            </h2>

                            <p>
                                شروع آزمون به معنی پذیرفتن قوانین زیر است.
                            </p>

                        </div>

                    </div>


                    <div class="exam-intro-rules-list">

                        <?php if (!empty($exam_rules)) : ?>

                            <?php

                            $display_rule_number = 1;

                            foreach (
                                $exam_rules as $rule
                            ) :

                                $rule_text =
                                    isset($rule['rule_text'])
                                        ? trim($rule['rule_text'])
                                        : '';

                                if ($rule_text === '') {
                                    continue;
                                }

                            ?>

                                <div class="exam-intro-rule">

                                    <span>
                                        <?php echo esc_html($display_rule_number); ?>
                                    </span>

                                    <p>
                                        <?php echo esc_html($rule_text); ?>
                                    </p>

                                </div>

                            <?php

                                $display_rule_number++;

                            endforeach;

                            ?>

                        <?php else : ?>

                            <div class="exam-intro-rule">

                                <span>
                                    1
                                </span>

                                <p>
                                    پس از شروع آزمون، زمان آزمون شما آغاز خواهد شد.
                                </p>

                            </div>


                            <div class="exam-intro-rule">

                                <span>
                                    2
                                </span>

                                <p>
                                    پس از رفتن به سوال بعدی امکان بازگشت وجود ندارد.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="exam-intro-actions">

                    <a
                        href="<?php echo esc_url($back_url); ?>"
                        class="exam-intro-cancel-button"
                    >
                        بازگشت
                    </a>


                    <?php if ($can_enter_exam) : ?>

                        <a
                            href="<?php echo esc_url($take_exam_url); ?>"
                            class="exam-intro-start-button"
                        >
                            <?php echo esc_html($action_label); ?>

                            <span>
                                ←
                            </span>
                        </a>

                    <?php else : ?>

                        <span
                            class="exam-intro-start-button"
                            aria-disabled="true"
                            style="
                                opacity:.5;
                                cursor:not-allowed;
                                pointer-events:none;
                            "
                        >
                            <?php
                            echo $schedule_status === 'upcoming'
                                ? 'هنوز شروع نشده'
                                : 'امکان شروع وجود ندارد';
                            ?>
                        </span>

                    <?php endif; ?>

                </div>

            </section>

        </div>

    </main>

</div>

<?php
get_footer();
?>
