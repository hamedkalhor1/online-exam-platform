<?php
/*
Template Name: شرکت در آزمون
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
| User
|--------------------------------------------------------------------------
*/

$user_id =
    get_current_user_id();

/*
|--------------------------------------------------------------------------
| Already Submitted?
|--------------------------------------------------------------------------
*/

$existing_attempt =
    online_exam_get_user_exam_attempt(
        $exam_id,
        $user_id
    );


if (
    $existing_attempt &&
    $existing_attempt['status'] ===
    'submitted'
) {

    wp_safe_redirect(
        add_query_arg(
            'attempt_id',
            absint(
                $existing_attempt['id']
            ),
            home_url(
                '/submit-success/'
            )
        )
    );

    exit;
}
/*
|--------------------------------------------------------------------------
| Start / Resume Attempt
|--------------------------------------------------------------------------
*/

$attempt =
    online_exam_start_attempt(
        $exam_id,
        $user_id
    );


if (is_wp_error($attempt)) {

    wp_die(
        esc_html(
            $attempt->get_error_message()
        ),
        'خطای ورود به آزمون',
        array(
            'back_link' => true,
        )
    );
}


/*
|--------------------------------------------------------------------------
| Attempt ID
|--------------------------------------------------------------------------
|
| خیلی مهم:
| باید قبل از Process Answer ساخته شود،
| چون Nonce به attempt_id نیاز دارد.
|
*/

$attempt_id =
    absint(
        $attempt['id']
    );
/*
|--------------------------------------------------------------------------
| Final Submit
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['online_exam_submit'])
) {

    $submit_nonce =
        isset(
            $_POST[
                'online_exam_submit_nonce'
            ]
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST[
                        'online_exam_submit_nonce'
                    ]
                )
            )
            : '';


    if (
        !wp_verify_nonce(
            $submit_nonce,
            'online_exam_submit_' .
            $attempt_id
        )
    ) {

        wp_die(
            'درخواست ثبت نهایی معتبر نیست.',
            'خطای امنیتی'
        );
    }


    $submit_result =
        online_exam_submit_attempt(
            $attempt_id,
            $user_id
        );


    if (
        is_wp_error(
            $submit_result
        )
    ) {

        wp_die(
            esc_html(
                $submit_result
                    ->get_error_message()
            ),
            'خطای ثبت نهایی',
            array(
                'back_link' => true,
            )
        );
    }


    wp_safe_redirect(
        add_query_arg(
            'attempt_id',
            $attempt_id,
            home_url(
                '/submit-success/'
            )
        )
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Process Answer
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['online_exam_next'])
) {

    /*
    |--------------------------------------------------------------------------
    | Nonce
    |--------------------------------------------------------------------------
    */

    $nonce =
        isset($_POST['online_exam_nonce'])
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['online_exam_nonce']
                )
            )
            : '';


    if (
        !wp_verify_nonce(
            $nonce,
            'online_exam_answer_' .
            $attempt_id
        )
    ) {

        wp_die(
            'درخواست معتبر نیست.',
            'خطای امنیتی'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Question ID
    |--------------------------------------------------------------------------
    */

    $posted_question_id =
        isset($_POST['question_id'])
            ? absint(
                $_POST['question_id']
            )
            : 0;


    /*
    |--------------------------------------------------------------------------
    | MCQ Answer
    |--------------------------------------------------------------------------
    */

    $selected_option_id =
        isset($_POST['selected_option_id'])
            ? sanitize_key(
                wp_unslash(
                    $_POST['selected_option_id']
                )
            )
            : '';


    /*
    |--------------------------------------------------------------------------
    | Essay Answer
    |--------------------------------------------------------------------------
    */

    $answer_text =
        isset($_POST['answer_text'])
            ? sanitize_textarea_field(
                wp_unslash(
                    $_POST['answer_text']
                )
            )
            : '';


    /*
    |--------------------------------------------------------------------------
    | Save Answer
    |--------------------------------------------------------------------------
    */

    $save_result =
        online_exam_save_current_answer(
            $attempt_id,
            $user_id,
            $posted_question_id,
            $selected_option_id,
            $answer_text
        );


    if (is_wp_error($save_result)) {

        wp_die(
            esc_html(
                $save_result->get_error_message()
            ),
            'خطای ثبت پاسخ',
            array(
                'back_link' => true,
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    |
    | PRG:
    |
    | POST
    | ↓
    | ذخیره
    | ↓
    | Redirect
    | ↓
    | GET
    |
    | بنابراین Refresh باعث ثبت دوباره پاسخ نمی‌شود.
    |
    */

    $redirect_args =
        array(
            'exam_id'      => $exam_id,
            'answer_saved' => 1,
        );


    if (
        !empty(
            $save_result['all_answered']
        )
    ) {

        $redirect_args[
            'questions_complete'
        ] = 1;
    }


    wp_safe_redirect(
        add_query_arg(
            $redirect_args,
            home_url('/take-exam/')
        )
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Attempt Data
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


$current_position =
    isset($attempt['current_position'])
        ? absint(
            $attempt['current_position']
        )
        : 0;


/*
|--------------------------------------------------------------------------
| Questions Complete?
|--------------------------------------------------------------------------
*/

$questions_complete =
    $total_questions > 0 &&
    $current_position >= $total_questions;


/*
|--------------------------------------------------------------------------
| Current Number
|--------------------------------------------------------------------------
*/

if ($questions_complete) {

    $current_number =
        $total_questions;

} else {

    $current_number =
        $current_position + 1;
}


/*
|--------------------------------------------------------------------------
| Current Question
|--------------------------------------------------------------------------
*/

$current_question = false;


if (!$questions_complete) {

    $current_question =
        online_exam_get_current_attempt_question(
            $attempt
        );


    if (!$current_question) {

        wp_die(
            'سوال فعلی آزمون قابل دریافت نیست.',
            'خطای آزمون'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Current Auto-saved Answer
|--------------------------------------------------------------------------
*/

$current_saved_answer = false;
$current_saved_option_id = '';
$current_saved_answer_text = '';
$current_has_saved_answer = false;


if ($current_question) {

    $current_saved_answer =
        online_exam_get_attempt_question_answer(
            $attempt_id,
            $user_id,
            $current_question['id']
        );


    if ($current_saved_answer) {

        if (
            $current_question['type'] ===
            'mcq'
        ) {
            $current_saved_option_id =
                isset(
                    $current_saved_answer[
                        'selected_option_id'
                    ]
                )
                    ? sanitize_key(
                        $current_saved_answer[
                            'selected_option_id'
                        ]
                    )
                    : '';


            $current_has_saved_answer =
                $current_saved_option_id !== '';

        } elseif (
            $current_question['type'] ===
            'essay'
        ) {
            $current_saved_answer_text =
                isset(
                    $current_saved_answer[
                        'answer_text'
                    ]
                )
                    ? (string)
                        $current_saved_answer[
                            'answer_text'
                        ]
                    : '';


            $current_has_saved_answer =
                trim(
                    $current_saved_answer_text
                ) !== '';
        }
    }
}


$autosave_url =
    admin_url(
        'admin-ajax.php'
    );


$autosave_nonce =
    wp_create_nonce(
        'online_exam_autosave_' .
        $attempt_id
    );


/*
|--------------------------------------------------------------------------
| Timer
|--------------------------------------------------------------------------
*/

$remaining_seconds =
    online_exam_get_remaining_seconds(
        $attempt
    );


/*
|--------------------------------------------------------------------------
| Progress
|--------------------------------------------------------------------------
*/

if ($questions_complete) {

    $progress = 100;

} else {

    $progress =
        $total_questions > 0
            ? round(
                (
                    $current_number /
                    $total_questions
                ) * 100
            )
            : 0;
}


/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$answered_count =
    min(
        $current_position,
        $total_questions
    );


$unanswered_count =
    max(
        0,
        $total_questions -
        $answered_count
    );


/*
|--------------------------------------------------------------------------
| ACF
|--------------------------------------------------------------------------
*/

$site_logo_text =
    function_exists(
        'online_exam_get_option'
    )
        ? online_exam_get_option(
            'site_logo_text',
            'آ'
        )
        : 'آ';


/*
|--------------------------------------------------------------------------
| Form URL
|--------------------------------------------------------------------------
*/

$form_url =
    add_query_arg(
        'exam_id',
        $exam_id,
        home_url('/take-exam/')
    );


get_header();

?>

<div
    class="exam-page"
    data-attempt-id="<?php echo esc_attr($attempt_id); ?>"
    data-exam-id="<?php echo esc_attr($exam_id); ?>"
    data-remaining-seconds="<?php echo esc_attr($remaining_seconds); ?>"
    data-autosave-url="<?php echo esc_url($autosave_url); ?>"
    data-autosave-nonce="<?php echo esc_attr($autosave_nonce); ?>"
>

    <!-- =========================================================
         Header
    ========================================================== -->

    <header class="exam-header">

        <div class="exam-header-inner">

            <div class="exam-header-title">

                <div class="exam-header-logo">

                    <?php
                    echo esc_html(
                        $site_logo_text
                    );
                    ?>

                </div>


                <div>

                    <span>
                        آزمون در حال برگزاری
                    </span>

                    <h1>

                        <?php
                        echo esc_html(
                            $exam['title']
                        );
                        ?>

                    </h1>

                </div>

            </div>


            <div class="exam-timer">

                <div class="timer-icon">

                    <svg
                        viewBox="0 0 24 24"
                        width="21"
                        height="21"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >

                        <circle
                            cx="12"
                            cy="13"
                            r="8"
                        ></circle>

                        <path
                            d="M12 9v4l2.5 2"
                        ></path>

                        <path
                            d="M9 2h6"
                        ></path>

                    </svg>

                </div>


                <div>

                    <span>
                        زمان باقی‌مانده
                    </span>

                    <strong id="examTimer">
                        --:--
                    </strong>

                </div>

            </div>

        </div>

    </header>


    <!-- =========================================================
         Main
    ========================================================== -->

    <main class="exam-main">

        <div class="exam-layout">


            <!-- =================================================
                 Question Area
            ================================================== -->

            <section class="question-area">


                <!-- =============================================
                     Progress
                ============================================== -->

                <div class="exam-progress">

                    <div class="exam-progress-info">

                        <span>
                            پیشرفت آزمون
                        </span>


                        <strong id="questionCounter">

                            <?php if ($questions_complete) : ?>

                                همه سوالات پاسخ داده شدند

                            <?php else : ?>

                                سوال
                                <?php echo esc_html($current_number); ?>

                                از

                                <?php echo esc_html($total_questions); ?>

                            <?php endif; ?>

                        </strong>

                    </div>


                    <div class="progress-track">

                        <div
                            class="progress-bar"
                            id="examProgressBar"
                            style="width: <?php echo esc_attr($progress); ?>%;"
                        ></div>

                    </div>

                </div>


                <?php if (!$questions_complete) : ?>


                    <!-- =========================================
                         Answer Form
                    ========================================== -->

                    <form
                        method="post"
                        action="<?php echo esc_url($form_url); ?>"
                        id="examAnswerForm"
                    >

                        <?php

                        wp_nonce_field(
                            'online_exam_answer_' .
                            $attempt_id,
                            'online_exam_nonce'
                        );

                        ?>


                        <input
                            type="hidden"
                            name="question_id"
                            value="<?php echo esc_attr($current_question['id']); ?>"
                        >


                        <!-- =====================================
                             Current Question
                        ====================================== -->

                        <article
                            class="question-card active"
                            data-question-id="<?php echo esc_attr($current_question['id']); ?>"
                            data-question-number="<?php echo esc_attr($current_number); ?>"
                            data-type="<?php echo esc_attr($current_question['type']); ?>"
                        >

                            <div class="question-card-header">

                                <span class="question-number">

                                    سوال

                                    <?php
                                    echo esc_html(
                                        $current_number
                                    );
                                    ?>

                                </span>

                            </div>


                            <h2
                                class="question-text"
                                dir="auto"
                            >

                                <?php
                                echo esc_html(
                                    $current_question['text']
                                );
                                ?>

                            </h2>


                            <!-- =============================
                                 MCQ
                            ============================== -->

                            <?php if (
                                $current_question['type'] ===
                                'mcq'
                            ) : ?>


                                <div class="answer-options">

                                    <?php

                                    /*
                                     * حروفی که کارشناس می‌بیند بر اساس
                                     * جایگاه فعلی گزینه است.
                                     *
                                     * value رادیو همچنان ID اصلی گزینه است
                                     * تا پاسخ صحیح Backend تغییر نکند.
                                     */

                                    $display_option_labels =
                                        array(
                                            'الف',
                                            'ب',
                                            'ج',
                                            'د',
                                        );

                                    ?>


                                    <?php foreach (
                                        $current_question['options']
                                        as $option_index => $option
                                    ) : ?>


                                        <?php

                                        $option_id =
                                            isset(
                                                $option['id']
                                            )
                                                ? sanitize_key(
                                                    $option['id']
                                                )
                                                : '';


                                        $option_text =
                                            isset(
                                                $option['label']
                                            )
                                                ? $option['label']
                                                : '';


                                        if (
                                            $option_id === '' ||
                                            $option_text === ''
                                        ) {

                                            continue;
                                        }

                                        ?>


                                        <label class="answer-option">

                                            <input
                                                type="radio"
                                                name="selected_option_id"
                                                value="<?php echo esc_attr($option_id); ?>"
                                                <?php
                                                checked(
                                                    $current_saved_option_id,
                                                    $option_id
                                                );
                                                ?>
                                            >


                                            <span class="option-radio"></span>


                                            <span class="option-label">

                                                <b>

                                                    <?php
                                                    echo esc_html(
                                                        $display_option_labels[
                                                            $option_index
                                                        ] ?? (
                                                            $option_index + 1
                                                        )
                                                    );
                                                    ?>

                                                </b>


                                                <span dir="auto">

                                                    <?php
                                                    echo esc_html(
                                                        $option_text
                                                    );
                                                    ?>

                                                </span>

                                            </span>

                                        </label>


                                    <?php endforeach; ?>

                                </div>


                            <!-- =============================
                                 Essay
                            ============================== -->

                            <?php elseif (
                                $current_question['type'] ===
                                'essay'
                            ) : ?>


                                <div class="essay-answer">

                                    <textarea
                                        name="answer_text"
                                        id="essayAnswer"
                                        placeholder="پاسخ خود را اینجا بنویسید..."
                                        rows="7"
                                    ><?php echo esc_textarea($current_saved_answer_text); ?></textarea>


                                    <div class="essay-footer">

                                        <span class="save-status">

                                            پاسخ به‌صورت خودکار ذخیره می‌شود

                                        </span>


                                        <span class="character-count">

                                            <span id="essayCharacterCount">
                                                ۰
                                            </span>

                                            کاراکتر

                                        </span>

                                    </div>

                                </div>


                            <?php endif; ?>

                        </article>


                        <!-- =====================================
                             Actions
                        ====================================== -->

                        <div class="question-actions">

                            <div class="auto-save-message">

                                <span class="save-dot"></span>

                                <span id="saveMessage">
                                    <?php
                                    echo $current_has_saved_answer
                                        ? 'پاسخ ذخیره شده'
                                        : 'آماده پاسخ‌گویی';
                                    ?>
                                </span>

                            </div>


                            <button
                                type="submit"
                                name="online_exam_next"
                                value="1"
                                class="question-nav-button next-question"
                                id="nextQuestion"
                                <?php
                                disabled(
                                    !$current_has_saved_answer
                                );
                                ?>
                            >

                                <?php if (
                                    $current_number >=
                                    $total_questions
                                ) : ?>

                                    ثبت پاسخ آخر

                                <?php else : ?>

                                    سوال بعدی

                                    <span>
                                        ←
                                    </span>

                                <?php endif; ?>

                            </button>

                        </div>

                    </form>


                <?php else : ?>


                    <!-- =========================================
                         All Questions Answered
                    ========================================== -->

                    <article class="question-card active">

                        <div class="question-card-header">

                            <span class="question-number">
                                پایان سوالات
                            </span>

                        </div>


                        <h2 class="question-text">
                            پاسخ تمام سوالات آزمون ثبت شد.
                        </h2>


                        <p>
                            برای پایان آزمون از گزینه
                            «ثبت نهایی آزمون» استفاده کنید.
                        </p>

                    </article>


                <?php endif; ?>

            </section>


            <!-- =================================================
                 Sidebar
            ================================================== -->

            <aside class="question-sidebar">

                <div class="question-sidebar-card">


                    <div class="question-sidebar-header">

                        <div>

                            <h3>
                                سوالات آزمون
                            </h3>

                            <p>
                                ترتیب سوالات آزمون شما
                            </p>

                        </div>

                    </div>


                    <!-- =========================================
                         Question Grid
                    ========================================== -->

                    <div
                        class="question-grid"
                        id="questionGrid"
                    >

                        <?php for (
                            $i = 1;
                            $i <= $total_questions;
                            $i++
                        ) : ?>


                            <?php

                            $class =
                                'question-grid-item';


                            if ($questions_complete) {

                                $class .=
                                    ' answered';

                            } elseif (
                                $i ===
                                $current_number
                            ) {

                                $class .=
                                    ' current';

                            } elseif (
                                $i <
                                $current_number
                            ) {

                                $class .=
                                    ' answered';
                            }

                            ?>


                            <span
                                class="<?php echo esc_attr($class); ?>"
                            >

                                <?php
                                echo esc_html(
                                    $i
                                );
                                ?>

                            </span>


                        <?php endfor; ?>

                    </div>


                    <!-- =========================================
                         Legend
                    ========================================== -->

                    <div class="question-legend">

                        <div>

                            <span
                                class="legend-box current"
                            ></span>

                            سوال فعلی

                        </div>


                        <div>

                            <span
                                class="legend-box answered"
                            ></span>

                            پاسخ داده شده

                        </div>


                        <div>

                            <span
                                class="legend-box unanswered"
                            ></span>

                            سوالات بعدی

                        </div>

                    </div>


                    <div class="sidebar-divider"></div>


                    <!-- =========================================
                         Summary
                    ========================================== -->

                    <div class="exam-summary">

                        <div>

                            <span>
                                پاسخ داده شده
                            </span>

                            <strong id="answeredCount">

                                <?php
                                echo esc_html(
                                    $answered_count
                                );
                                ?>

                            </strong>

                        </div>


                        <div>

                            <span>
                                بدون پاسخ
                            </span>

                            <strong id="unansweredCount">

                                <?php
                                echo esc_html(
                                    $unanswered_count
                                );
                                ?>

                            </strong>

                        </div>

                    </div>


                    <!-- =========================================
                         Final Submit
                    ========================================== -->

<?php if ($questions_complete) : ?>

    <form
        method="post"
        action="<?php echo esc_url($form_url); ?>"
        id="finalSubmitForm"
    >

        <?php

        wp_nonce_field(
            'online_exam_submit_' .
            $attempt_id,
            'online_exam_submit_nonce'
        );

        ?>


        <button
            type="submit"
            name="online_exam_submit"
            value="1"
            class="submit-exam-button"
        >
            ثبت نهایی آزمون
        </button>

    </form>


<?php else : ?>


    <button
        type="button"
        class="submit-exam-button"
        disabled
    >
        ثبت نهایی آزمون
    </button>


<?php endif; ?>


                    <p class="submit-warning-text">

                        <?php if (
                            $questions_complete
                        ) : ?>

                            تمام پاسخ‌ها ثبت شده‌اند.
                            اکنون می‌توانید آزمون را نهایی کنید.

                        <?php else : ?>

                            ثبت نهایی پس از پاسخ دادن
                            به تمام سوالات فعال می‌شود.

                        <?php endif; ?>

                    </p>

                </div>

            </aside>

        </div>

    </main>

</div>


<!-- =========================================================
     Current Page JS
========================================================== -->

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

        const examPage =
            document.querySelector(
                '.exam-page'
            );

        const answerForm =
            document.getElementById(
                'examAnswerForm'
            );

        const nextButton =
            document.getElementById(
                'nextQuestion'
            );

        const saveMessage =
            document.getElementById(
                'saveMessage'
            );


        /*
        |--------------------------------------------------------------------------
        | Timer
        |--------------------------------------------------------------------------
        */

        const timerElement =
            document.getElementById(
                'examTimer'
            );


        if (
            examPage &&
            timerElement
        ) {
            let remaining =
                parseInt(
                    examPage.dataset.remainingSeconds,
                    10
                ) || 0;


            function renderTimer()
            {
                const minutes =
                    Math.floor(
                        remaining / 60
                    );

                const seconds =
                    remaining % 60;


                timerElement.textContent =
                    String(minutes)
                        .padStart(2, '0') +
                    ':' +
                    String(seconds)
                        .padStart(2, '0');
            }


            function disableExam()
            {
                document
                    .querySelectorAll(
                        '#examAnswerForm input, #examAnswerForm textarea, #examAnswerForm button'
                    )
                    .forEach(
                        function (element) {
                            element.disabled =
                                true;
                        }
                    );
            }


            renderTimer();


            const timerInterval =
                setInterval(
                    function () {

                        if (remaining <= 0) {
                            clearInterval(
                                timerInterval
                            );

                            remaining = 0;
                            renderTimer();
                            disableExam();


                            if (saveMessage) {
                                saveMessage.textContent =
                                    'زمان آزمون به پایان رسیده است';
                            }


                            /*
                             * timeout.php بعد از Reload، Attempt را
                             * سمت سرور نهایی و به نتیجه هدایت می‌کند.
                             */
                            window.setTimeout(
                                function () {
                                    window.location.reload();
                                },
                                1200
                            );

                            return;
                        }


                        remaining--;
                        renderTimer();

                    },
                    1000
                );
        }


        if (
            !examPage ||
            !answerForm
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Auto-save Configuration
        |--------------------------------------------------------------------------
        */

        const autosaveUrl =
            examPage.dataset.autosaveUrl ||
            '';

        const autosaveNonce =
            examPage.dataset.autosaveNonce ||
            '';

        const attemptId =
            parseInt(
                examPage.dataset.attemptId,
                10
            ) || 0;

        const questionIdInput =
            answerForm.querySelector(
                'input[name="question_id"]'
            );

        const questionId =
            questionIdInput
                ? parseInt(
                    questionIdInput.value,
                    10
                ) || 0
                : 0;

        const radioInputs =
            answerForm.querySelectorAll(
                'input[name="selected_option_id"]'
            );

        const essay =
            document.getElementById(
                'essayAnswer'
            );

        const characterCount =
            document.getElementById(
                'essayCharacterCount'
            );

        let essayTimer = null;
        let autosaveQueue =
            Promise.resolve();

        let saveVersion = 0;
        let isNavigating = false;


        function setSaveMessage(text)
        {
            if (saveMessage) {
                saveMessage.textContent =
                    text;
            }
        }


        function getAnswerSnapshot()
        {
            let selectedOptionId =
                '';


            radioInputs.forEach(
                function (input) {
                    if (input.checked) {
                        selectedOptionId =
                            input.value;
                    }
                }
            );


            return {
                selectedOptionId:
                    selectedOptionId,
                answerText:
                    essay
                        ? essay.value
                        : '',
            };
        }


        function hasCurrentAnswer()
        {
            const snapshot =
                getAnswerSnapshot();


            if (radioInputs.length > 0) {
                return snapshot.selectedOptionId !== '';
            }


            if (essay) {
                return snapshot.answerText.trim() !== '';
            }


            return false;
        }


        function updateNextButton()
        {
            if (nextButton) {
                nextButton.disabled =
                    !hasCurrentAnswer();
            }
        }


        function buildAutosaveBody(snapshot)
        {
            const body =
                new FormData();


            body.append(
                'action',
                'online_exam_autosave_answer'
            );

            body.append(
                'nonce',
                autosaveNonce
            );

            body.append(
                'attempt_id',
                String(attemptId)
            );

            body.append(
                'question_id',
                String(questionId)
            );

            body.append(
                'selected_option_id',
                snapshot.selectedOptionId
            );

            body.append(
                'answer_text',
                snapshot.answerText
            );


            return body;
        }


        async function performAutosave(
            snapshot,
            keepalive
        ) {
            if (
                !autosaveUrl ||
                !autosaveNonce ||
                !attemptId ||
                !questionId
            ) {
                throw new Error(
                    'autosave_config_missing'
                );
            }


            const response =
                await fetch(
                    autosaveUrl,
                    {
                        method: 'POST',
                        body:
                            buildAutosaveBody(
                                snapshot
                            ),
                        credentials:
                            'same-origin',
                        cache:
                            'no-store',
                        keepalive:
                            Boolean(keepalive),
                    }
                );


            const payload =
                await response.json();


            if (
                !response.ok ||
                !payload ||
                !payload.success
            ) {
                const error =
                    new Error(
                        payload &&
                        payload.data &&
                        payload.data.message
                            ? payload.data.message
                            : 'ذخیره خودکار انجام نشد.'
                    );


                error.code =
                    payload &&
                    payload.data &&
                    payload.data.code
                        ? payload.data.code
                        : 'autosave_failed';


                throw error;
            }


            return payload.data || {};
        }


        function queueAutosave()
        {
            const snapshot =
                getAnswerSnapshot();

            const version =
                ++saveVersion;


            setSaveMessage(
                'در حال ذخیره...'
            );


            autosaveQueue =
                autosaveQueue
                    .catch(
                        function () {
                            /*
                             * خطای درخواست قبلی نباید Queue را متوقف کند.
                             */
                        }
                    )
                    .then(
                        function () {
                            return performAutosave(
                                snapshot,
                                false
                            );
                        }
                    )
                    .then(
                        function (data) {

                            if (
                                version ===
                                saveVersion
                            ) {
                                if (data.cleared) {
                                    setSaveMessage(
                                        'پاسخ خالی است'
                                    );
                                } else {
                                    setSaveMessage(
                                        'پاسخ ذخیره شد'
                                    );
                                }
                            }


                            return data;
                        }
                    )
                    .catch(
                        function (error) {

                            if (
                                error &&
                                (
                                    error.code === 'exam_expired' ||
                                    error.code === 'attempt_not_active'
                                )
                            ) {
                                setSaveMessage(
                                    'زمان آزمون به پایان رسیده است'
                                );

                                window.setTimeout(
                                    function () {
                                        window.location.reload();
                                    },
                                    500
                                );

                                throw error;
                            }


                            if (
                                version ===
                                saveVersion
                            ) {
                                setSaveMessage(
                                    'ذخیره خودکار ناموفق؛ با سوال بعدی ثبت می‌شود'
                                );
                            }


                            throw error;
                        }
                    );


            return autosaveQueue;
        }


        /*
        |--------------------------------------------------------------------------
        | MCQ: Save Immediately
        |--------------------------------------------------------------------------
        */

        radioInputs.forEach(
            function (input) {

                input.addEventListener(
                    'change',
                    function () {
                        updateNextButton();
                        queueAutosave();
                    }
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Essay: Debounce 2 Seconds
        |--------------------------------------------------------------------------
        */

        if (essay) {

            if (characterCount) {
                characterCount.textContent =
                    essay.value.length;
            }


            essay.addEventListener(
                'input',
                function () {

                    if (characterCount) {
                        characterCount.textContent =
                            essay.value.length;
                    }


                    updateNextButton();
                    setSaveMessage(
                        'در حال تایپ...'
                    );


                    if (essayTimer) {
                        clearTimeout(
                            essayTimer
                        );
                    }


                    essayTimer =
                        window.setTimeout(
                            function () {
                                essayTimer = null;
                                queueAutosave();
                            },
                            2000
                        );
                }
            );
        }


        updateNextButton();


        /*
        |--------------------------------------------------------------------------
        | Next: Wait For In-flight Auto-save
        |--------------------------------------------------------------------------
        |
        | POST معمولی همچنان مرجع نهایی پاسخ است.
        | Auto-save فقط Draft فعلی را محافظت می‌کند.
        |
        */

        answerForm.addEventListener(
            'submit',
            function (event) {

                if (isNavigating) {
                    return;
                }


                event.preventDefault();


                if (essayTimer) {
                    clearTimeout(
                        essayTimer
                    );

                    essayTimer = null;
                }


                if (nextButton) {
                    nextButton.disabled =
                        true;
                }


                setSaveMessage(
                    'در حال ثبت پاسخ...'
                );


                isNavigating = true;

if (
    typeof answerForm.requestSubmit === 'function'
) {

    answerForm.requestSubmit(
        nextButton
    );

} else {

    answerForm.submit();

}


        /*
        |--------------------------------------------------------------------------
        | Browser Close / Tab Hide Safety Net
        |--------------------------------------------------------------------------
        */

        function keepaliveAutosave()
        {
            if (
                isNavigating ||
                !autosaveUrl ||
                !autosaveNonce ||
                !attemptId ||
                !questionId
            ) {
                return;
            }


            const snapshot =
                getAnswerSnapshot();


            /*
             * برای MCQ بدون انتخاب چیزی ارسال نمی‌کنیم.
             * برای Essay حتی متن خالی ارسال می‌شود تا Draft پاک‌شده
             * روی سرور هم پاک بماند.
             */
            if (
                radioInputs.length > 0 &&
                snapshot.selectedOptionId === ''
            ) {
                return;
            }


            performAutosave(
                snapshot,
                true
            ).catch(
                function () {
                    /* best effort */
                }
            );
        }


        document.addEventListener(
            'visibilitychange',
            function () {
                if (
                    document.visibilityState ===
                    'hidden'
                ) {
                    keepaliveAutosave();
                }
            }
        );


        window.addEventListener(
            'pagehide',
            keepaliveAutosave
        );

    }
);
</script>


<?php
get_footer();
?>