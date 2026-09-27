<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Exam Data
|--------------------------------------------------------------------------
*/

function online_exam_get_exam_data($exam_id)
{
    $exam_id = absint($exam_id);

    if (!$exam_id) {
        return false;
    }


    $exam = get_post($exam_id);


    if (
        !$exam ||
        $exam->post_type !== 'exam' ||
        $exam->post_status !== 'publish'
    ) {
        return false;
    }


    return array(

        'id' =>
            $exam_id,


        'title' =>
            get_the_title(
                $exam_id
            ),


        'duration' =>
            absint(
                get_post_meta(
                    $exam_id,
                    '_online_exam_duration',
                    true
                )
            ),


        'question_count' =>
            absint(
                get_post_meta(
                    $exam_id,
                    '_online_exam_question_count',
                    true
                )
            ),


        'mcq_count' =>
            absint(
                get_post_meta(
                    $exam_id,
                    '_online_exam_mcq_count',
                    true
                )
            ),


        'essay_count' =>
            absint(
                get_post_meta(
                    $exam_id,
                    '_online_exam_essay_count',
                    true
                )
            ),


        'total_score' =>
            floatval(
                get_post_meta(
                    $exam_id,
                    '_online_exam_total_score',
                    true
                )
            ),


        'created_at' =>
            get_the_date(
                'Y-m-d H:i:s',
                $exam_id
            ),
    );
}

/*
|--------------------------------------------------------------------------
| Get Available Exams
|--------------------------------------------------------------------------
|
| فعلاً Assignment نداریم.
|
| بنابراین هر آزمونی که:
|
| post_type = exam
| post_status = publish
|
| باشد برای همه کاربران واردشده قابل مشاهده است.
|
*/

function online_exam_get_available_exams()
{
    if (!is_user_logged_in()) {
        return array();
    }


    $query = new WP_Query(
        array(

            'post_type' => 'exam',

            'post_status' => 'publish',

            'posts_per_page' => -1,

            'orderby' => array(
                'date' => 'DESC',
                'ID'   => 'DESC',
            ),

            'no_found_rows' => true,

            /*
             * فقط ID لازم داریم.
             */
            'fields' => 'ids',
        )
    );


    if (empty($query->posts)) {
        return array();
    }


    $exams = array();


    foreach ($query->posts as $exam_id) {

        $exam =
            online_exam_get_exam_data(
                $exam_id
            );


        if ($exam !== false) {

            $exams[] = $exam;
        }
    }


    return $exams;
}


/*
|--------------------------------------------------------------------------
| Get Exam Questions
|--------------------------------------------------------------------------
*/

function online_exam_get_exam_questions($exam_id)
{
    $exam_id = absint($exam_id);

    if (!$exam_id) {
        return array();
    }


    $query = new WP_Query(
        array(

            'post_type' => 'question',

            'post_status' => 'publish',

            'posts_per_page' => -1,

            'meta_query' => array(

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

            'meta_key' =>
                '_online_exam_question_order',

            'orderby' =>
                'meta_value_num',

            'order' =>
                'ASC',
        )
    );


    if (!$query->have_posts()) {

        return array();
    }


    $questions = array();


    foreach ($query->posts as $question) {

        $question_id =
            $question->ID;


        $type =
            get_post_meta(
                $question_id,
                '_online_exam_question_type',
                true
            );


        $question_data = array(

            'id' =>
                $question_id,

            'text' =>
                $question->post_content,

            'type' =>
                $type,

            'order' =>
                absint(
                    get_post_meta(
                        $question_id,
                        '_online_exam_question_order',
                        true
                    )
                ),
        );


        /*
         * برای سوال تستی گزینه‌ها را اضافه می‌کنیم.
         *
         * دقت کن:
         * پاسخ صحیح عمداً اینجا برگردانده نمی‌شود.
         */
        if ($type === 'mcq') {

            $question_data['options'] =
                get_post_meta(
                    $question_id,
                    '_online_exam_options',
                    true
                );
        }


        $questions[] =
            $question_data;
    }


    wp_reset_postdata();


    return $questions;
}
/*
|--------------------------------------------------------------------------
| Get User Exam Attempt
|--------------------------------------------------------------------------
*/

function online_exam_get_user_exam_attempt(
    $exam_id,
    $user_id
) {
    global $wpdb;

    $exam_id = absint($exam_id);
    $user_id = absint($user_id);

    if (!$exam_id || !$user_id) {
        return false;
    }


    $table =
        $wpdb->prefix .
        'exam_attempts';


    $attempt =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$table}
                WHERE exam_id = %d
                AND user_id = %d
                ORDER BY id DESC
                LIMIT 1
                ",
                $exam_id,
                $user_id
            ),
            ARRAY_A
        );


    return $attempt ?: false;
}


/*
|--------------------------------------------------------------------------
| Get Attempt By ID
|--------------------------------------------------------------------------
*/

function online_exam_get_attempt($attempt_id)
{
    global $wpdb;

    $attempt_id =
        absint($attempt_id);

    if (!$attempt_id) {
        return false;
    }


    $table =
        $wpdb->prefix .
        'exam_attempts';


    $attempt =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$table}
                WHERE id = %d
                LIMIT 1
                ",
                $attempt_id
            ),
            ARRAY_A
        );


    return $attempt ?: false;
}


/*
|--------------------------------------------------------------------------
| Exam Schedule
|--------------------------------------------------------------------------
*/

function online_exam_get_exam_schedule($exam_id)
{
    $exam_id = absint($exam_id);

    if (!$exam_id) {
        return false;
    }


    $start_at =
        get_post_meta(
            $exam_id,
            '_online_exam_start_at',
            true
        );


    $end_at =
        get_post_meta(
            $exam_id,
            '_online_exam_end_at',
            true
        );


    /*
     * سازگاری با آزمون‌های قدیمی بدون زمان‌بندی.
     */
    if (
        empty($start_at) ||
        empty($end_at)
    ) {
        return array(
            'has_schedule'    => false,
            'start_at'        => '',
            'end_at'          => '',
            'start_timestamp' => 0,
            'end_timestamp'   => 0,
        );
    }


    $start_timestamp =
        strtotime(
            $start_at . ' UTC'
        );


    $end_timestamp =
        strtotime(
            $end_at . ' UTC'
        );


    if (
        !$start_timestamp ||
        !$end_timestamp
    ) {
        return false;
    }


    return array(
        'has_schedule'    => true,
        'start_at'        => $start_at,
        'end_at'          => $end_at,
        'start_timestamp' => $start_timestamp,
        'end_timestamp'   => $end_timestamp,
    );
}


function online_exam_get_exam_schedule_status($exam_id)
{
    $schedule =
        online_exam_get_exam_schedule(
            $exam_id
        );


    if ($schedule === false) {
        return new WP_Error(
            'invalid_exam_schedule',
            'زمان‌بندی آزمون معتبر نیست.'
        );
    }


    if (
        empty(
            $schedule['has_schedule']
        )
    ) {
        return 'active';
    }


    $now = time();


    if (
        $now <
        $schedule['start_timestamp']
    ) {
        return 'upcoming';
    }


    if (
        $now >=
        $schedule['end_timestamp']
    ) {
        return 'ended';
    }


    return 'active';
}


/*
|--------------------------------------------------------------------------
| Start Exam Attempt
|--------------------------------------------------------------------------
*/

function online_exam_start_attempt(
    $exam_id,
    $user_id
) {
    global $wpdb;


    $exam_id =
        absint($exam_id);

    $user_id =
        absint($user_id);


    if (!$exam_id || !$user_id) {

        return new WP_Error(
            'invalid_attempt_data',
            'اطلاعات آزمون معتبر نیست.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Exam
    |--------------------------------------------------------------------------
    */

    $exam =
        online_exam_get_exam_data(
            $exam_id
        );


    if (!$exam) {

        return new WP_Error(
            'invalid_exam',
            'آزمون موردنظر پیدا نشد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Exam Schedule Validation
    |--------------------------------------------------------------------------
    */

    $schedule =
        online_exam_get_exam_schedule(
            $exam_id
        );


    if ($schedule === false) {
        return new WP_Error(
            'invalid_exam_schedule',
            'زمان‌بندی آزمون معتبر نیست.'
        );
    }


    $schedule_status =
        online_exam_get_exam_schedule_status(
            $exam_id
        );


    if (is_wp_error($schedule_status)) {
        return $schedule_status;
    }


    if ($schedule_status === 'upcoming') {

        $start_text =
            wp_date(
                'Y/m/d H:i',
                $schedule['start_timestamp'],
                wp_timezone()
            );


        return new WP_Error(
            'exam_not_started',
            sprintf(
                'این آزمون هنوز شروع نشده است. زمان شروع: %s',
                $start_text
            )
        );
    }


    if ($schedule_status === 'ended') {
        return new WP_Error(
            'exam_ended',
            'مهلت شرکت در این آزمون به پایان رسیده است.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Existing Attempt
    |--------------------------------------------------------------------------
    |
    | اگر کاربر قبلاً آزمون را شروع کرده:
    |
    | in_progress → همان Attempt قبلی
    | هر وضعیت دیگر → اجازه Attempt جدید نمی‌دهیم
    |
    */

    $existing_attempt =
        online_exam_get_user_exam_attempt(
            $exam_id,
            $user_id
        );


    if ($existing_attempt) {

        if (
            $existing_attempt['status'] ===
            'in_progress'
        ) {

            return $existing_attempt;
        }


        return new WP_Error(
            'attempt_already_exists',
            'شما قبلاً در این آزمون شرکت کرده‌اید.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Questions
    |--------------------------------------------------------------------------
    */

    $questions =
        online_exam_get_exam_questions(
            $exam_id
        );


    if (empty($questions)) {

        return new WP_Error(
            'exam_has_no_questions',
            'برای این آزمون سوالی تعریف نشده است.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Question IDs
    |--------------------------------------------------------------------------
    */

    $question_ids =
        array();


    foreach ($questions as $question) {

        if (!empty($question['id'])) {

            $question_ids[] =
                absint(
                    $question['id']
                );
        }
    }


    if (empty($question_ids)) {

        return new WP_Error(
            'exam_has_no_questions',
            'سوالات آزمون معتبر نیستند.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Random Question Order
    |--------------------------------------------------------------------------
    |
    | فقط یک بار هنگام ساخت Attempt تصادفی می‌شود.
    |
    | بعد از Refresh ترتیب تغییر نمی‌کند.
    |
    */

    shuffle(
        $question_ids
    );


    $question_order =
        wp_json_encode(
            array_values(
                $question_ids
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Random Option Order
    |--------------------------------------------------------------------------
    |
    | برای هر سوال تستی، ترتیب ID گزینه‌ها فقط یک بار
    | هنگام ساخت Attempt تصادفی می‌شود.
    |
    | خود ID گزینه‌ها تغییر نمی‌کند؛ فقط ترتیب نمایش تغییر می‌کند.
    | بنابراین پاسخ صحیح و نمره‌دهی فعلی معتبر می‌ماند.
    |
    */

    $option_order_map =
        online_exam_build_attempt_option_order(
            $questions
        );


    $option_order =
        wp_json_encode(
            $option_order_map
        );


    /*
    |--------------------------------------------------------------------------
    | Time
    |--------------------------------------------------------------------------
    */

    $duration =
        absint(
            $exam['duration']
        );


    if ($duration <= 0) {

        return new WP_Error(
            'invalid_duration',
            'مدت زمان آزمون معتبر نیست.'
        );
    }


    /*
     * همه زمان‌های Attempt را فعلاً UTC
     * در دیتابیس نگه می‌داریم.
     */

    $started_timestamp =
        time();


    $expires_timestamp =
        $started_timestamp +
        ($duration * 60);


    /*
     * اگر پایان کلی آزمون زودتر از زمان شخصی Attempt باشد،
     * پایان کلی آزمون ملاک است.
     */
    if (
        !empty($schedule['has_schedule']) &&
        !empty($schedule['end_timestamp'])
    ) {
        $expires_timestamp =
            min(
                $expires_timestamp,
                $schedule['end_timestamp']
            );
    }


    if (
        $expires_timestamp <=
        $started_timestamp
    ) {
        return new WP_Error(
            'exam_ended',
            'مهلت شرکت در این آزمون به پایان رسیده است.'
        );
    }


    $started_at =
        gmdate(
            'Y-m-d H:i:s',
            $started_timestamp
        );


    $expires_at =
        gmdate(
            'Y-m-d H:i:s',
            $expires_timestamp
        );


    /*
    |--------------------------------------------------------------------------
    | Insert Attempt
    |--------------------------------------------------------------------------
    */

    $table =
        $wpdb->prefix .
        'exam_attempts';


    $inserted =
        $wpdb->insert(
            $table,
            array(

                'exam_id' =>
                    $exam_id,

                'user_id' =>
                    $user_id,

                /*
                 * Assignment را حذف کردیم.
                 */
                'assignment_id' =>
                    null,

                /*
                 * فعلاً هر دانشجو فقط یک بار
                 * در هر آزمون شرکت می‌کند.
                 */
                'attempt_no' =>
                    1,

                'status' =>
                    'in_progress',

                /*
                 * موقعیت از صفر شروع می‌شود:
                 *
                 * 0 = سوال اول
                 * 1 = سوال دوم
                 * ...
                 */
                'current_position' =>
                    0,

                'question_order' =>
                    $question_order,

                /*
                 * ترتیب تصادفی گزینه‌های تستی
                 * برای همین Attempt.
                 */
                'option_order' =>
                    $option_order,

                'started_at' =>
                    $started_at,

                'expires_at' =>
                    $expires_at,

                'last_activity_at' =>
                    $started_at,
            )
        );


    if ($inserted === false) {

        return new WP_Error(
            'attempt_create_failed',
            'شروع آزمون با خطا مواجه شد: ' .
            $wpdb->last_error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Read Created Attempt
    |--------------------------------------------------------------------------
    */

    $attempt_id =
        absint(
            $wpdb->insert_id
        );


    $attempt =
        online_exam_get_attempt(
            $attempt_id
        );


    if (!$attempt) {

        return new WP_Error(
            'attempt_read_failed',
            'Attempt ساخته شد اما اطلاعات آن قابل خواندن نیست.'
        );
    }


    return $attempt;
}
/*
|--------------------------------------------------------------------------
| Get Question Data
|--------------------------------------------------------------------------
*/

function online_exam_get_question_data($question_id)
{
    $question_id = absint($question_id);

    if (!$question_id) {
        return false;
    }

    $question = get_post($question_id);

    if (
        !$question ||
        $question->post_type !== 'question' ||
        $question->post_status !== 'publish'
    ) {
        return false;
    }

    $type =
        get_post_meta(
            $question_id,
            '_online_exam_question_type',
            true
        );

    $data = array(
        'id'   => $question_id,
        'text' => $question->post_content,
        'type' => $type,
    );

    /*
     * فقط گزینه‌ها را می‌فرستیم.
     * پاسخ صحیح عمداً خوانده نمی‌شود.
     */
    if ($type === 'mcq') {

        $options =
            get_post_meta(
                $question_id,
                '_online_exam_options',
                true
            );

        $data['options'] =
            is_array($options)
                ? $options
                : array();
    }

    return $data;
}


/*
|--------------------------------------------------------------------------
| Get Attempt Question Order
|--------------------------------------------------------------------------
*/

function online_exam_get_attempt_question_order($attempt)
{
    if (
        empty($attempt) ||
        empty($attempt['question_order'])
    ) {
        return array();
    }

    $question_ids =
        json_decode(
            $attempt['question_order'],
            true
        );

    if (!is_array($question_ids)) {
        return array();
    }

    return array_values(
        array_filter(
            array_map(
                'absint',
                $question_ids
            )
        )
    );
}



/*
|--------------------------------------------------------------------------
| Build Attempt Option Order
|--------------------------------------------------------------------------
|
| خروجی:
|
| [
|     question_id => ['c', 'a', 'd', 'b'],
|     ...
| ]
|
| فقط سوالات تستی داخل Map قرار می‌گیرند.
|
*/

function online_exam_build_attempt_option_order($questions)
{
    if (!is_array($questions)) {
        return array();
    }


    $option_order =
        array();


    foreach ($questions as $question) {

        if (
            empty($question['id']) ||
            empty($question['type']) ||
            $question['type'] !== 'mcq' ||
            empty($question['options']) ||
            !is_array($question['options'])
        ) {
            continue;
        }


        $question_id =
            absint(
                $question['id']
            );


        if (!$question_id) {
            continue;
        }


        $option_ids =
            array();


        foreach (
            $question['options']
            as $option
        ) {

            if (
                empty($option['id'])
            ) {
                continue;
            }


            $option_id =
                sanitize_key(
                    $option['id']
                );


            if (
                $option_id !== '' &&
                !in_array(
                    $option_id,
                    $option_ids,
                    true
                )
            ) {
                $option_ids[] =
                    $option_id;
            }
        }


        if (count($option_ids) > 1) {
            shuffle(
                $option_ids
            );
        }


        if (!empty($option_ids)) {

            $option_order[
                (string) $question_id
            ] =
                array_values(
                    $option_ids
                );
        }
    }


    return $option_order;
}


/*
|--------------------------------------------------------------------------
| Get Attempt Option Order
|--------------------------------------------------------------------------
*/

function online_exam_get_attempt_option_order($attempt)
{
    if (
        empty($attempt) ||
        empty($attempt['option_order'])
    ) {
        return array();
    }


    $decoded =
        json_decode(
            $attempt['option_order'],
            true
        );


    if (!is_array($decoded)) {
        return array();
    }


    $normalized =
        array();


    foreach (
        $decoded
        as $question_id => $option_ids
    ) {

        $question_id =
            absint(
                $question_id
            );


        if (
            !$question_id ||
            !is_array($option_ids)
        ) {
            continue;
        }


        $clean_ids =
            array();


        foreach ($option_ids as $option_id) {

            $option_id =
                sanitize_key(
                    $option_id
                );


            if (
                $option_id !== '' &&
                !in_array(
                    $option_id,
                    $clean_ids,
                    true
                )
            ) {
                $clean_ids[] =
                    $option_id;
            }
        }


        if (!empty($clean_ids)) {

            $normalized[
                (string) $question_id
            ] =
                $clean_ids;
        }
    }


    return $normalized;
}


/*
|--------------------------------------------------------------------------
| Apply Attempt Option Order
|--------------------------------------------------------------------------
|
| Attemptهای قدیمی که option_order ندارند،
| با ترتیب اصلی گزینه‌ها ادامه پیدا می‌کنند.
|
*/

function online_exam_apply_attempt_option_order(
    $attempt,
    $question
) {
    if (
        !$question ||
        empty($question['id']) ||
        empty($question['type']) ||
        $question['type'] !== 'mcq' ||
        empty($question['options']) ||
        !is_array($question['options'])
    ) {
        return $question;
    }


    $all_option_order =
        online_exam_get_attempt_option_order(
            $attempt
        );


    $question_id =
        absint(
            $question['id']
        );


    $question_key =
        (string) $question_id;


    if (
        !$question_id ||
        empty(
            $all_option_order[
                $question_key
            ]
        )
    ) {
        return $question;
    }


    $options_by_id =
        array();


    foreach (
        $question['options']
        as $option
    ) {

        if (
            empty($option['id'])
        ) {
            continue;
        }


        $option_id =
            sanitize_key(
                $option['id']
            );


        if ($option_id !== '') {

            $options_by_id[
                $option_id
            ] =
                $option;
        }
    }


    if (empty($options_by_id)) {
        return $question;
    }


    $ordered_options =
        array();


    foreach (
        $all_option_order[
            $question_key
        ]
        as $option_id
    ) {

        if (
            isset(
                $options_by_id[
                    $option_id
                ]
            )
        ) {

            $ordered_options[] =
                $options_by_id[
                    $option_id
                ];


            unset(
                $options_by_id[
                    $option_id
                ]
            );
        }
    }


    /*
     * Safety:
     * اگر داده قدیمی یا ناقص بود، هیچ گزینه‌ای حذف نشود.
     */
    foreach (
        $options_by_id
        as $option
    ) {
        $ordered_options[] =
            $option;
    }


    if (!empty($ordered_options)) {

        $question['options'] =
            array_values(
                $ordered_options
            );
    }


    return $question;
}



/*
|--------------------------------------------------------------------------
| Get Current Attempt Question
|--------------------------------------------------------------------------
*/

function online_exam_get_current_attempt_question($attempt)
{
    $question_ids =
        online_exam_get_attempt_question_order(
            $attempt
        );

    if (empty($question_ids)) {
        return false;
    }

    $position =
        isset($attempt['current_position'])
            ? absint($attempt['current_position'])
            : 0;

    if (!isset($question_ids[$position])) {
        return false;
    }

    $question =
        online_exam_get_question_data(
            $question_ids[$position]
        );


    if (!$question) {
        return false;
    }


    return online_exam_apply_attempt_option_order(
        $attempt,
        $question
    );
}


/*
|--------------------------------------------------------------------------
| Remaining Exam Time
|--------------------------------------------------------------------------
*/

function online_exam_get_remaining_seconds($attempt)
{
    if (
        empty($attempt) ||
        empty($attempt['expires_at'])
    ) {
        return 0;
    }


    $expires_timestamp =
        strtotime(
            $attempt['expires_at'] .
            ' UTC'
        );


    if (!$expires_timestamp) {
        return 0;
    }


    if (
        !empty(
            $attempt['exam_id']
        )
    ) {
        $schedule =
            online_exam_get_exam_schedule(
                absint(
                    $attempt['exam_id']
                )
            );


        if (
            is_array($schedule) &&
            !empty($schedule['has_schedule']) &&
            !empty($schedule['end_timestamp'])
        ) {
            $expires_timestamp =
                min(
                    $expires_timestamp,
                    $schedule['end_timestamp']
                );
        }
    }


    return max(
        0,
        $expires_timestamp - time()
    );
}


/*
|--------------------------------------------------------------------------
| Get Saved Answer For Current Question
|--------------------------------------------------------------------------
*/

function online_exam_get_attempt_question_answer(
    $attempt_id,
    $user_id,
    $question_id
) {
    global $wpdb;


    $attempt_id = absint($attempt_id);
    $user_id = absint($user_id);
    $question_id = absint($question_id);


    if (
        !$attempt_id ||
        !$user_id ||
        !$question_id
    ) {
        return false;
    }


    $tables =
        online_exam_get_tables();


    $answer =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tables['answers']}
                WHERE attempt_id = %d
                AND user_id = %d
                AND question_id = %d
                LIMIT 1
                ",
                $attempt_id,
                $user_id,
                $question_id
            ),
            ARRAY_A
        );


    return $answer ?: false;
}


/*
|--------------------------------------------------------------------------
| Auto-save Current Answer Without Moving Forward
|--------------------------------------------------------------------------
*/

function online_exam_autosave_current_answer(
    $attempt_id,
    $user_id,
    $question_id,
    $selected_option_id = '',
    $answer_text = ''
) {
    global $wpdb;


    $attempt_id = absint($attempt_id);
    $user_id = absint($user_id);
    $question_id = absint($question_id);


    if (
        !$attempt_id ||
        !$user_id ||
        !$question_id
    ) {
        return new WP_Error(
            'invalid_answer_data',
            'اطلاعات پاسخ معتبر نیست.'
        );
    }


    $tables =
        online_exam_get_tables();


    $wpdb->query(
        'START TRANSACTION'
    );


    $attempt =
        $wpdb->get_row(
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
            'Attempt آزمون پیدا نشد.'
        );
    }


    if (
        $attempt['status'] !==
        'in_progress'
    ) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'attempt_not_active',
            'این آزمون دیگر فعال نیست.'
        );
    }


    if (
        online_exam_get_remaining_seconds(
            $attempt
        ) <= 0
    ) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'exam_expired',
            'زمان آزمون به پایان رسیده است.'
        );
    }


    $question_order =
        online_exam_get_attempt_question_order(
            $attempt
        );


    $current_position =
        absint(
            $attempt['current_position']
        );


    if (
        !isset(
            $question_order[
                $current_position
            ]
        ) ||
        absint(
            $question_order[
                $current_position
            ]
        ) !== $question_id
    ) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'invalid_question',
            'امکان ذخیره پاسخ این سوال وجود ندارد.'
        );
    }


    $question =
        online_exam_get_question_data(
            $question_id
        );


    if (!$question) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'question_not_found',
            'سوال موردنظر پیدا نشد.'
        );
    }


    $existing_answer =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tables['answers']}
                WHERE attempt_id = %d
                AND user_id = %d
                AND question_id = %d
                LIMIT 1
                ",
                $attempt_id,
                $user_id,
                $question_id
            ),
            ARRAY_A
        );


    $db_selected_option = null;
    $db_answer_text = null;
    $is_correct = null;
    $has_answer = true;


    if (
        $question['type'] ===
        'mcq'
    ) {
        $selected_option_id =
            sanitize_key(
                $selected_option_id
            );


        if ($selected_option_id === '') {
            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'answer_required',
                'لطفاً یکی از گزینه‌ها را انتخاب کنید.'
            );
        }


        $valid_option_ids = array();


        foreach (
            $question['options']
            as $option
        ) {
            if (!empty($option['id'])) {
                $valid_option_ids[] =
                    sanitize_key(
                        $option['id']
                    );
            }
        }


        if (
            !in_array(
                $selected_option_id,
                $valid_option_ids,
                true
            )
        ) {
            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'invalid_option',
                'گزینه انتخاب‌شده معتبر نیست.'
            );
        }


        $correct_option_id =
            sanitize_key(
                get_post_meta(
                    $question_id,
                    '_online_exam_correct_option_id',
                    true
                )
            );


        $is_correct =
            $selected_option_id ===
            $correct_option_id
                ? 1
                : 0;


        $db_selected_option =
            $selected_option_id;

    } elseif (
        $question['type'] ===
        'essay'
    ) {
        $answer_text =
            trim(
                sanitize_textarea_field(
                    $answer_text
                )
            );


        /*
         * اگر کاربر متن ذخیره‌شده را کامل پاک کرد،
         * Draft قبلی هم حذف می‌شود تا Timeout آن را پاسخ حساب نکند.
         */
        if ($answer_text === '') {

            if ($existing_answer) {
                $deleted =
                    $wpdb->delete(
                        $tables['answers'],
                        array(
                            'id' =>
                                absint(
                                    $existing_answer['id']
                                ),
                            'user_id' =>
                                $user_id,
                        ),
                        array(
                            '%d',
                            '%d',
                        )
                    );


                if ($deleted === false) {
                    $wpdb->query('ROLLBACK');

                    return new WP_Error(
                        'answer_clear_failed',
                        'پاک‌کردن پاسخ ذخیره‌شده با خطا مواجه شد.'
                    );
                }
            }


            $now =
                gmdate(
                    'Y-m-d H:i:s'
                );


            $wpdb->update(
                $tables['attempts'],
                array(
                    'last_activity_at' =>
                        $now,
                    'updated_at' =>
                        $now,
                ),
                array(
                    'id' =>
                        $attempt_id,
                    'user_id' =>
                        $user_id,
                )
            );


            $wpdb->query('COMMIT');


            return array(
                'saved' => false,
                'cleared' => true,
                'has_answer' => false,
            );
        }


        $db_answer_text =
            $answer_text;

    } else {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'invalid_question_type',
            'نوع سوال معتبر نیست.'
        );
    }


    $now =
        gmdate(
            'Y-m-d H:i:s'
        );


    $answer_data =
        array(
            'selected_option_id' =>
                $db_selected_option,
            'answer_text' =>
                $db_answer_text,
            'is_correct' =>
                $is_correct,
            'awarded_score' =>
                null,
            'answered_at' =>
                $now,
            'updated_at' =>
                $now,
        );


    if ($existing_answer) {
        $saved =
            $wpdb->update(
                $tables['answers'],
                $answer_data,
                array(
                    'id' =>
                        absint(
                            $existing_answer['id']
                        ),
                    'user_id' =>
                        $user_id,
                )
            );

    } else {
        $answer_data['attempt_id'] =
            $attempt_id;

        $answer_data['exam_id'] =
            absint(
                $attempt['exam_id']
            );

        $answer_data['user_id'] =
            $user_id;

        $answer_data['question_id'] =
            $question_id;

        $answer_data['created_at'] =
            $now;


        $saved =
            $wpdb->insert(
                $tables['answers'],
                $answer_data
            );
    }


    if ($saved === false) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'answer_save_failed',
            'ذخیره خودکار پاسخ با خطا مواجه شد: ' .
            $wpdb->last_error
        );
    }


    $attempt_updated =
        $wpdb->update(
            $tables['attempts'],
            array(
                'last_activity_at' =>
                    $now,
                'updated_at' =>
                    $now,
            ),
            array(
                'id' =>
                    $attempt_id,
                'user_id' =>
                    $user_id,
                'current_position' =>
                    $current_position,
            )
        );


    if ($attempt_updated === false) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'attempt_activity_failed',
            'ثبت فعالیت آزمون با خطا مواجه شد.'
        );
    }


    $wpdb->query('COMMIT');


    return array(
        'saved' => true,
        'cleared' => false,
        'has_answer' => $has_answer,
    );
}


/*
|--------------------------------------------------------------------------
| AJAX Auto-save
|--------------------------------------------------------------------------
*/

function online_exam_ajax_autosave_answer()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(
            array(
                'code' => 'login_required',
                'message' => 'برای ذخیره پاسخ باید وارد سامانه باشید.',
            ),
            401
        );
    }


    $attempt_id =
        isset($_POST['attempt_id'])
            ? absint(
                $_POST['attempt_id']
            )
            : 0;


    $nonce =
        isset($_POST['nonce'])
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['nonce']
                )
            )
            : '';


    if (
        !$attempt_id ||
        !wp_verify_nonce(
            $nonce,
            'online_exam_autosave_' .
            $attempt_id
        )
    ) {
        wp_send_json_error(
            array(
                'code' => 'invalid_nonce',
                'message' => 'درخواست ذخیره خودکار معتبر نیست.',
            ),
            403
        );
    }


    $question_id =
        isset($_POST['question_id'])
            ? absint(
                $_POST['question_id']
            )
            : 0;


    $selected_option_id =
        isset($_POST['selected_option_id'])
            ? sanitize_key(
                wp_unslash(
                    $_POST['selected_option_id']
                )
            )
            : '';


    $answer_text =
        isset($_POST['answer_text'])
            ? sanitize_textarea_field(
                wp_unslash(
                    $_POST['answer_text']
                )
            )
            : '';


    $result =
        online_exam_autosave_current_answer(
            $attempt_id,
            get_current_user_id(),
            $question_id,
            $selected_option_id,
            $answer_text
        );


    if (is_wp_error($result)) {
        wp_send_json_error(
            array(
                'code' =>
                    $result->get_error_code(),
                'message' =>
                    $result->get_error_message(),
            ),
            400
        );
    }


    wp_send_json_success(
        array(
            'saved' =>
                !empty($result['saved']),
            'cleared' =>
                !empty($result['cleared']),
            'has_answer' =>
                !empty($result['has_answer']),
            'saved_at' =>
                wp_date(
                    'H:i:s',
                    time(),
                    wp_timezone()
                ),
        )
    );
}


add_action(
    'wp_ajax_online_exam_autosave_answer',
    'online_exam_ajax_autosave_answer'
);


/*
|--------------------------------------------------------------------------
| Save Current Answer And Move Forward
|--------------------------------------------------------------------------
*/

function online_exam_save_current_answer(
    $attempt_id,
    $user_id,
    $question_id,
    $selected_option_id = '',
    $answer_text = ''
) {
    global $wpdb;

    $attempt_id = absint($attempt_id);
    $user_id = absint($user_id);
    $question_id = absint($question_id);

    if (
        !$attempt_id ||
        !$user_id ||
        !$question_id
    ) {

        return new WP_Error(
            'invalid_answer_data',
            'اطلاعات پاسخ معتبر نیست.'
        );
    }


    $tables =
        online_exam_get_tables();


    /*
    |--------------------------------------------------------------------------
    | Transaction
    |--------------------------------------------------------------------------
    */

    $wpdb->query('START TRANSACTION');


    /*
    |--------------------------------------------------------------------------
    | Lock Attempt
    |--------------------------------------------------------------------------
    */

    $attempt =
        $wpdb->get_row(
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
            'Attempt آزمون پیدا نشد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    if (
        $attempt['status'] !==
        'in_progress'
    ) {

        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'attempt_not_active',
            'این آزمون دیگر فعال نیست.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Timer
    |--------------------------------------------------------------------------
    */

    $remaining_seconds =
        online_exam_get_remaining_seconds(
            $attempt
        );


    if ($remaining_seconds <= 0) {

        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'exam_expired',
            'زمان آزمون به پایان رسیده است.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Question Order
    |--------------------------------------------------------------------------
    */

    $question_order =
        online_exam_get_attempt_question_order(
            $attempt
        );


    if (empty($question_order)) {

        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'invalid_question_order',
            'ترتیب سوالات آزمون معتبر نیست.'
        );
    }


    $current_position =
        absint(
            $attempt['current_position']
        );


    /*
    |--------------------------------------------------------------------------
    | Finished Questions
    |--------------------------------------------------------------------------
    */

    if (
        !isset(
            $question_order[
                $current_position
            ]
        )
    ) {

        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'questions_finished',
            'تمام سوالات پاسخ داده شده‌اند.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Server-side Forward Only Validation
    |--------------------------------------------------------------------------
    |
    | question_id ارسال شده از مرورگر حتماً باید
    | همان سوال current_position باشد.
    |
    */

    $expected_question_id =
        absint(
            $question_order[
                $current_position
            ]
        );


    if (
        $question_id !==
        $expected_question_id
    ) {

        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'invalid_question',
            'امکان پاسخ‌گویی به این سوال وجود ندارد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Question
    |--------------------------------------------------------------------------
    */

    $question =
        online_exam_get_question_data(
            $question_id
        );


    if (!$question) {

        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'question_not_found',
            'سوال موردنظر پیدا نشد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Existing Auto-saved Answer
    |--------------------------------------------------------------------------
    |
    | Auto-save ممکن است قبلاً همین پاسخ را ساخته باشد.
    | در زمان «سوال بعدی» پاسخ نهایی Form روی همان رکورد Update می‌شود.
    |
    */

    $existing_answer =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tables['answers']}
                WHERE attempt_id = %d
                AND user_id = %d
                AND question_id = %d
                LIMIT 1
                ",
                $attempt_id,
                $user_id,
                $question_id
            ),
            ARRAY_A
        );


    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    */

    $db_selected_option = null;
    $db_answer_text = null;
    $is_correct = null;


    /*
    |--------------------------------------------------------------------------
    | MCQ
    |--------------------------------------------------------------------------
    */

    if (
        $question['type'] === 'mcq'
    ) {

        $selected_option_id =
            sanitize_key(
                $selected_option_id
            );


        if (
            $selected_option_id === ''
        ) {

            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'answer_required',
                'لطفاً یکی از گزینه‌ها را انتخاب کنید.'
            );
        }


        /*
         * بررسی کنیم Option واقعاً متعلق
         * به همین Question باشد.
         */

        $valid_option_ids =
            array();


        foreach (
            $question['options']
            as $option
        ) {

            if (
                !empty(
                    $option['id']
                )
            ) {

                $valid_option_ids[] =
                    sanitize_key(
                        $option['id']
                    );
            }
        }


        if (
            !in_array(
                $selected_option_id,
                $valid_option_ids,
                true
            )
        ) {

            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'invalid_option',
                'گزینه انتخاب‌شده معتبر نیست.'
            );
        }


        /*
         * پاسخ صحیح فقط Server-side خوانده می‌شود.
         */

        $correct_option_id =
            sanitize_key(
                get_post_meta(
                    $question_id,
                    '_online_exam_correct_option_id',
                    true
                )
            );


        $is_correct =
            $selected_option_id ===
            $correct_option_id
                ? 1
                : 0;


        $db_selected_option =
            $selected_option_id;
    }


    /*
    |--------------------------------------------------------------------------
    | Essay
    |--------------------------------------------------------------------------
    */

    elseif (
        $question['type'] === 'essay'
    ) {

        $answer_text =
            trim(
                sanitize_textarea_field(
                    $answer_text
                )
            );


        if ($answer_text === '') {

            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'answer_required',
                'لطفاً پاسخ سوال را وارد کنید.'
            );
        }


        $db_answer_text =
            $answer_text;
    }


    /*
    |--------------------------------------------------------------------------
    | Invalid Type
    |--------------------------------------------------------------------------
    */

    else {

        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'invalid_question_type',
            'نوع سوال معتبر نیست.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Date
    |--------------------------------------------------------------------------
    */

    $now =
        gmdate(
            'Y-m-d H:i:s'
        );


    /*
    |--------------------------------------------------------------------------
    | Insert / Update Answer
    |--------------------------------------------------------------------------
    */

    $answer_data =
        array(
            'selected_option_id' =>
                $db_selected_option,
            'answer_text' =>
                $db_answer_text,
            'is_correct' =>
                $is_correct,
            'awarded_score' =>
                null,
            'answered_at' =>
                $now,
            'updated_at' =>
                $now,
        );


    if ($existing_answer) {
        $answer_saved =
            $wpdb->update(
                $tables['answers'],
                $answer_data,
                array(
                    'id' =>
                        absint(
                            $existing_answer['id']
                        ),
                    'user_id' =>
                        $user_id,
                )
            );

    } else {
        $answer_data['attempt_id'] =
            $attempt_id;

        $answer_data['exam_id'] =
            absint(
                $attempt['exam_id']
            );

        $answer_data['user_id'] =
            $user_id;

        $answer_data['question_id'] =
            $question_id;

        $answer_data['created_at'] =
            $now;


        $answer_saved =
            $wpdb->insert(
                $tables['answers'],
                $answer_data
            );
    }


    if ($answer_saved === false) {
        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'answer_save_failed',
            'ذخیره پاسخ با خطا مواجه شد: ' .
            $wpdb->last_error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Move Forward
    |--------------------------------------------------------------------------
    */

    $next_position =
        $current_position + 1;


    $updated =
        $wpdb->update(
            $tables['attempts'],
            array(

                'current_position' =>
                    $next_position,

                'last_activity_at' =>
                    $now,

                'updated_at' =>
                    $now,
            ),
            array(

                'id' =>
                    $attempt_id,

                'user_id' =>
                    $user_id,

                'current_position' =>
                    $current_position,
            )
        );


    if ($updated !== 1) {

        $wpdb->query('ROLLBACK');

        return new WP_Error(
            'attempt_update_failed',
            'انتقال به سوال بعدی انجام نشد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $wpdb->query('COMMIT');


    return array(

        'current_position' =>
            $next_position,

        'total_questions' =>
            count(
                $question_order
            ),

        'all_answered' =>
            $next_position >=
            count(
                $question_order
            ),
    );
}
/*
|--------------------------------------------------------------------------
| Get Attempt Result
|--------------------------------------------------------------------------
*/

function online_exam_get_attempt_result(
    $attempt_id,
    $user_id = 0
) {
    global $wpdb;

    $attempt_id = absint($attempt_id);
    $user_id    = absint($user_id);

    if (!$attempt_id) {
        return false;
    }

    $tables =
        online_exam_get_tables();


    if ($user_id) {

        $result =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$tables['results']}
                    WHERE attempt_id = %d
                    AND user_id = %d
                    LIMIT 1
                    ",
                    $attempt_id,
                    $user_id
                ),
                ARRAY_A
            );

    } else {

        $result =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$tables['results']}
                    WHERE attempt_id = %d
                    LIMIT 1
                    ",
                    $attempt_id
                ),
                ARRAY_A
            );
    }


    return $result ?: false;
}


/*
|--------------------------------------------------------------------------
| Submit Exam Attempt
|--------------------------------------------------------------------------
*/

function online_exam_submit_attempt(
    $attempt_id,
    $user_id
) {
    global $wpdb;

    $attempt_id = absint($attempt_id);
    $user_id    = absint($user_id);

    if (
        !$attempt_id ||
        !$user_id
    ) {

        return new WP_Error(
            'invalid_submit_data',
            'اطلاعات ثبت آزمون معتبر نیست.'
        );
    }


    $tables =
        online_exam_get_tables();


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
    | Lock Attempt
    |--------------------------------------------------------------------------
    */

    $attempt =
        $wpdb->get_row(
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

    if (
        $attempt['status'] ===
        'submitted'
    ) {

        $result =
            online_exam_get_attempt_result(
                $attempt_id,
                $user_id
            );


        $wpdb->query(
            'COMMIT'
        );


        return array(
            'attempt' =>
                $attempt,

            'result' =>
                $result,

            'already_submitted' =>
                true,
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Status Validation
    |--------------------------------------------------------------------------
    */

    if (
        $attempt['status'] !==
        'in_progress'
    ) {

        $wpdb->query(
            'ROLLBACK'
        );


        return new WP_Error(
            'attempt_not_active',
            'این آزمون دیگر فعال نیست.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Question Order
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


    if (!$total_questions) {

        $wpdb->query(
            'ROLLBACK'
        );


        return new WP_Error(
            'no_questions',
            'سوالات آزمون قابل دریافت نیستند.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Check All Answers
    |--------------------------------------------------------------------------
    */

    $answered_count =
        absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$tables['answers']}
                    WHERE attempt_id = %d
                    AND user_id = %d
                    ",
                    $attempt_id,
                    $user_id
                )
            )
        );


    if (
        $answered_count <
        $total_questions
    ) {

        $wpdb->query(
            'ROLLBACK'
        );


        return new WP_Error(
            'unanswered_questions',
            'هنوز همه سوالات آزمون پاسخ داده نشده‌اند.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Count Question Types
    |--------------------------------------------------------------------------
    */

    $mcq_count   = 0;
    $essay_count = 0;


    foreach (
        $question_ids as $question_id
    ) {

        $type =
            get_post_meta(
                $question_id,
                '_online_exam_question_type',
                true
            );


        if ($type === 'mcq') {

            $mcq_count++;

        } elseif ($type === 'essay') {

            $essay_count++;
        }
    }


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


/*
|--------------------------------------------------------------------------
| Correct MCQ Count
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
| Weighted Objective Score
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
| Result Status
|--------------------------------------------------------------------------
*/

if (
    $scoring['essay_count'] > 0
) {

    /*
     * سوال تشریحی داریم.
     * فعلاً فقط نمره تستی مشخص است.
     */

    $result_status =
        'pending';


    $manual_score =
        0;


    /*
     * نمره فعلی فقط بخش تستی است.
     * نتیجه هنوز نهایی نیست.
     */

    $total_score =
        $objective_score;


    $max_score =
        $scoring[
            'total_score'
        ];


    $percentage =
        0;


    $graded_at =
        null;

} else {

    /*
     * کاملاً تستی
     */

    $result_status =
        'graded';


    $manual_score =
        0;


    $total_score =
        $objective_score;


    $max_score =
        $scoring[
            'total_score'
        ];


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


    $graded_at =
        gmdate(
            'Y-m-d H:i:s'
        );
}
    /*
    |--------------------------------------------------------------------------
    | Time
    |--------------------------------------------------------------------------
    */

    $now =
        gmdate(
            'Y-m-d H:i:s'
        );


    /*
    |--------------------------------------------------------------------------
    | Create Result
    |--------------------------------------------------------------------------
    */

    $existing_result =
        online_exam_get_attempt_result(
            $attempt_id,
            $user_id
        );


    $result_data =
        array(

            'attempt_id' =>
                $attempt_id,

            'exam_id' =>
                absint(
                    $attempt['exam_id']
                ),

            'user_id' =>
                $user_id,

            'objective_score' =>
                $objective_score,

            'manual_score' =>
                $manual_score,

            'total_score' =>
                $total_score,

            'max_score' =>
                $max_score,

            'percentage' =>
                $percentage,

            'passed' =>
                null,

            'status' =>
                $result_status,

            'graded_at' =>
                $graded_at,

            'updated_at' =>
                $now,
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

            $wpdb->query(
                'ROLLBACK'
            );


            return new WP_Error(
                'result_update_failed',
                'ذخیره نتیجه آزمون با خطا مواجه شد.'
            );
        }

    } else {

        $result_data[
            'created_at'
        ] = $now;


        $result_saved =
            $wpdb->insert(
                $tables['results'],
                $result_data
            );


        if ($result_saved === false) {

            $wpdb->query(
                'ROLLBACK'
            );


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

                'status' =>
                    'submitted',

                'submitted_at' =>
                    $now,

                'last_activity_at' =>
                    $now,

                'updated_at' =>
                    $now,
            ),
            array(

                'id' =>
                    $attempt_id,

                'user_id' =>
                    $user_id,

                'status' =>
                    'in_progress',
            )
        );


    if ($attempt_updated !== 1) {

        $wpdb->query(
            'ROLLBACK'
        );


        return new WP_Error(
            'attempt_submit_failed',
            'ثبت نهایی آزمون انجام نشد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $wpdb->query(
        'COMMIT'
    );


    /*
    |--------------------------------------------------------------------------
    | Read Final Data
    |--------------------------------------------------------------------------
    */

    $final_attempt =
        online_exam_get_attempt(
            $attempt_id
        );


    $final_result =
        online_exam_get_attempt_result(
            $attempt_id,
            $user_id
        );


    return array(

        'attempt' =>
            $final_attempt,

        'result' =>
            $final_result,

        'has_essay' =>
            $essay_count > 0,

        'already_submitted' =>
            false,
    );
}
/*
|--------------------------------------------------------------------------
| Get Attempt Statistics
|--------------------------------------------------------------------------
*/

function online_exam_get_attempt_statistics(
    $attempt_id,
    $user_id
) {
    global $wpdb;

    $attempt_id = absint($attempt_id);
    $user_id    = absint($user_id);


    if (
        !$attempt_id ||
        !$user_id
    ) {
        return false;
    }


    $attempt =
        online_exam_get_attempt(
            $attempt_id
        );


    if (
        !$attempt ||
        absint($attempt['user_id']) !== $user_id
    ) {
        return false;
    }


    $tables =
        online_exam_get_tables();


    /*
    |--------------------------------------------------------------------------
    | Question Order
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
    | Question Types
    |--------------------------------------------------------------------------
    */

    $mcq_count   = 0;
    $essay_count = 0;


    foreach ($question_ids as $question_id) {

        $type =
            get_post_meta(
                $question_id,
                '_online_exam_question_type',
                true
            );


        if ($type === 'mcq') {

            $mcq_count++;

        } elseif ($type === 'essay') {

            $essay_count++;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Answered
    |--------------------------------------------------------------------------
    */

    $answered_count =
        absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$tables['answers']}
                    WHERE attempt_id = %d
                    AND user_id = %d
                    ",
                    $attempt_id,
                    $user_id
                )
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Correct MCQ
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
    | Incorrect MCQ
    |--------------------------------------------------------------------------
    */

    $incorrect_count =
        absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$tables['answers']}
                    WHERE attempt_id = %d
                    AND user_id = %d
                    AND is_correct = 0
                    ",
                    $attempt_id,
                    $user_id
                )
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Essay Answers
    |--------------------------------------------------------------------------
    */

    $essay_answered =
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
    | Unanswered
    |--------------------------------------------------------------------------
    */

    $unanswered_count =
        max(
            0,
            $total_questions -
            $answered_count
        );


    return array(

        'total_questions' =>
            $total_questions,

        'answered_count' =>
            $answered_count,

        'unanswered_count' =>
            $unanswered_count,

        'mcq_count' =>
            $mcq_count,

        'essay_count' =>
            $essay_count,

        'correct_count' =>
            $correct_count,

        'incorrect_count' =>
            $incorrect_count,

        'essay_answered' =>
            $essay_answered,
    );
}
/*
|--------------------------------------------------------------------------
| Get Exam Scoring
|--------------------------------------------------------------------------
*/

function online_exam_get_exam_scoring($exam_id)
{
    $exam_id = absint($exam_id);

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


    /*
    |--------------------------------------------------------------------------
    | Total Score
    |--------------------------------------------------------------------------
    */

    $total_score =
        floatval(
            get_post_meta(
                $exam_id,
                '_online_exam_total_score',
                true
            )
        );


    /*
     * سازگاری با آزمون‌های قدیمی:
     * اگر نمره کل نداشتند، هر سوال را 1 امتیاز فرض می‌کنیم.
     */

    if ($total_score <= 0) {

        $total_score =
            max(
                1,
                absint(
                    $exam['question_count']
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Questions
    |--------------------------------------------------------------------------
    */

    $questions =
        online_exam_get_exam_questions(
            $exam_id
        );


    if (empty($questions)) {

        return new WP_Error(
            'questions_not_found',
            'سوالات آزمون پیدا نشد.'
        );
    }


    $mcq_count = 0;

    $essay_count = 0;

    $essay_total_score = 0;

    $essay_scores = array();


    foreach ($questions as $question) {

        if ($question['type'] === 'mcq') {

            $mcq_count++;

            continue;
        }


        if ($question['type'] === 'essay') {

            $essay_count++;


            $max_score =
                floatval(
                    get_post_meta(
                        $question['id'],
                        '_online_exam_max_score',
                        true
                    )
                );


            /*
             * Legacy fallback
             */

            if ($max_score <= 0) {

                $max_score = 1;
            }


            $essay_scores[
                $question['id']
            ] = $max_score;


            $essay_total_score +=
                $max_score;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $essay_total_score >
        $total_score + 0.0001
    ) {

        return new WP_Error(
            'invalid_essay_score',
            'مجموع بارم سوالات تشریحی از نمره کل آزمون بیشتر است.'
        );
    }


    $mcq_total_score =
        $total_score -
        $essay_total_score;


    if (
        $mcq_count > 0 &&
        $mcq_total_score <= 0
    ) {

        return new WP_Error(
            'invalid_mcq_score',
            'برای سوالات تستی نمره‌ای باقی نمانده است.'
        );
    }


    if (
        $mcq_count === 0 &&
        abs(
            $essay_total_score -
            $total_score
        ) > 0.0001
    ) {

        return new WP_Error(
            'invalid_essay_total',
            'در آزمون کاملاً تشریحی، مجموع بارم سوالات باید برابر نمره کل آزمون باشد.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MCQ Score
    |--------------------------------------------------------------------------
    */

    $mcq_score =
        $mcq_count > 0
            ? $mcq_total_score /
              $mcq_count
            : 0;


    return array(

        'total_score' =>
            $total_score,

        'mcq_count' =>
            $mcq_count,

        'essay_count' =>
            $essay_count,

        'essay_total_score' =>
            $essay_total_score,

        'mcq_total_score' =>
            $mcq_total_score,

        'mcq_score' =>
            $mcq_score,

        'essay_scores' =>
            $essay_scores,
    );
}