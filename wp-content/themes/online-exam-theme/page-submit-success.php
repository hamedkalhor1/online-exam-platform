<?php
/*
Template Name: ثبت موفق آزمون
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
    absint(
        $attempt['user_id']
    ) !== get_current_user_id()
) {

    wp_safe_redirect(
        home_url('/my-exams/')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Must Be Submitted
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

$exam =
    online_exam_get_exam_data(
        absint(
            $attempt['exam_id']
        )
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
        get_current_user_id()
    );


/*
|--------------------------------------------------------------------------
| Question Count
|--------------------------------------------------------------------------
*/

$question_ids =
    online_exam_get_attempt_question_order(
        $attempt
    );


$total_questions =
    count(
        $question_ids
    );


/*
|--------------------------------------------------------------------------
| Submit Time
|--------------------------------------------------------------------------
*/

$submitted_time =
    '—';


if (
    !empty(
        $attempt['submitted_at']
    )
) {

    $submitted_timestamp =
        strtotime(
            $attempt[
                'submitted_at'
            ] .
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
| Result Status
|--------------------------------------------------------------------------
*/

$result_pending =
    $result &&
    $result['status'] ===
    'pending';


/*
|--------------------------------------------------------------------------
| Result URL
|--------------------------------------------------------------------------
*/

$result_url =
    add_query_arg(
        'attempt_id',
        $attempt_id,
        home_url('/result/')
    );


get_header();

?>

<div class="result-state-page">

    <div class="result-state-container">

        <div class="result-state-card">


            <div class="success-icon">

                <svg
                    viewBox="0 0 24 24"
                    width="42"
                    height="42"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <path
                        d="m7 12 3 3 7-7"
                    ></path>

                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                    ></circle>

                </svg>

            </div>


            <span class="success-badge">
                ثبت نهایی انجام شد
            </span>


            <h1>
                آزمون با موفقیت ثبت شد
            </h1>


            <p class="success-description">

                پاسخ‌های شما با موفقیت ثبت شدند.

                پس از ثبت نهایی، امکان تغییر
                پاسخ‌ها وجود ندارد.

            </p>


            <div class="success-exam-info">


                <div>

                    <span>
                        نام آزمون
                    </span>

                    <strong>

                        <?php
                        echo esc_html(
                            $exam['title']
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
                        تعداد سوالات
                    </span>

                    <strong>

                        <?php
                        echo esc_html(
                            $total_questions
                        );
                        ?>

                        سوال

                    </strong>

                </div>

            </div>


            <div class="success-note">

                <div class="success-note-icon">
                    i
                </div>


                <p>

                    <?php if ($result_pending) : ?>

                        این آزمون شامل سوال تشریحی است.
                        نتیجه نهایی پس از تصحیح توسط
                        مدرس نمایش داده خواهد شد.

                    <?php else : ?>

                        آزمون شما تصحیح شده و نتیجه
                        قابل مشاهده است.

                    <?php endif; ?>

                </p>

            </div>


            <div class="success-actions">

                <a
                    href="<?php
                    echo esc_url(
                        home_url(
                            '/my-exams/'
                        )
                    );
                    ?>"
                    class="success-primary-button"
                >
                    بازگشت به آزمون‌های من
                </a>


                <a
                    href="<?php
                    echo esc_url(
                        $result_url
                    );
                    ?>"
                    class="success-secondary-button"
                >

                    <?php if ($result_pending) : ?>

                        وضعیت نتیجه

                    <?php else : ?>

                        مشاهده نتیجه

                    <?php endif; ?>

                </a>

            </div>

        </div>

    </div>

</div>

<?php
get_footer();
?>