<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Register Exam Post Type
|--------------------------------------------------------------------------
*/

function online_exam_register_exam_post_type()
{
    $labels = array(
        'name'               => 'آزمون‌ها',
        'singular_name'      => 'آزمون',
        'menu_name'          => 'آزمون‌ها',
        'add_new'            => 'افزودن آزمون',
        'add_new_item'       => 'افزودن آزمون جدید',
        'edit_item'          => 'ویرایش آزمون',
        'new_item'           => 'آزمون جدید',
        'view_item'          => 'مشاهده آزمون',
        'search_items'       => 'جستجوی آزمون‌ها',
        'not_found'          => 'آزمونی پیدا نشد',
        'not_found_in_trash' => 'آزمونی در زباله‌دان نیست',
        'all_items'          => 'همه آزمون‌ها',
    );

    register_post_type(
        'exam',
        array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'query_var'           => false,
            'rewrite'             => false,
            'has_archive'         => false,
            'supports'            => array('title'),
            'menu_icon'           => 'dashicons-welcome-learn-more',
            'menu_position'       => 25,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'show_in_rest'        => false,
        )
    );
}

add_action('init', 'online_exam_register_exam_post_type');


/*
|--------------------------------------------------------------------------
| Register Question Post Type
|--------------------------------------------------------------------------
*/

function online_exam_register_question_post_type()
{
    $labels = array(
        'name'               => 'سوالات',
        'singular_name'      => 'سوال',
        'menu_name'          => 'سوالات',
        'add_new'            => 'افزودن سوال',
        'add_new_item'       => 'افزودن سوال جدید',
        'edit_item'          => 'ویرایش سوال',
        'new_item'           => 'سوال جدید',
        'view_item'          => 'مشاهده سوال',
        'search_items'       => 'جستجوی سوالات',
        'not_found'          => 'سوالی پیدا نشد',
        'not_found_in_trash' => 'سوالی در زباله‌دان نیست',
        'all_items'          => 'همه سوالات',
    );

    register_post_type(
        'question',
        array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=exam',
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'query_var'           => false,
            'rewrite'             => false,
            'has_archive'         => false,
            'supports'            => array('title', 'editor'),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'show_in_rest'        => false,
        )
    );
}

add_action('init', 'online_exam_register_question_post_type');


/*
|--------------------------------------------------------------------------
| Admin Columns
|--------------------------------------------------------------------------
*/

function online_exam_exam_columns($columns)
{
    return array(
        'cb'      => $columns['cb'],
        'title'   => 'عنوان آزمون',
        'exam_id' => 'شناسه آزمون',
        'date'    => 'تاریخ',
    );
}

add_filter('manage_exam_posts_columns', 'online_exam_exam_columns');


function online_exam_exam_column_content($column, $post_id)
{
    if ($column === 'exam_id') {
        echo esc_html($post_id);
    }
}

add_action(
    'manage_exam_posts_custom_column',
    'online_exam_exam_column_content',
    10,
    2
);


function online_exam_question_columns($columns)
{
    return array(
        'cb'          => $columns['cb'],
        'title'       => 'عنوان سوال',
        'question_id' => 'شناسه سوال',
        'date'        => 'تاریخ',
    );
}

add_filter(
    'manage_question_posts_columns',
    'online_exam_question_columns'
);


function online_exam_question_column_content($column, $post_id)
{
    if ($column === 'question_id') {
        echo esc_html($post_id);
    }
}

add_action(
    'manage_question_posts_custom_column',
    'online_exam_question_column_content',
    10,
    2
);



/*
|--------------------------------------------------------------------------
| Exam Schedule Meta Box
|--------------------------------------------------------------------------
*/

function online_exam_register_exam_schedule_meta_box()
{
    add_meta_box(
        'online_exam_schedule_meta_box',
        'زمان‌بندی آزمون',
        'online_exam_render_exam_schedule_meta_box',
        'exam',
        'side',
        'high'
    );
}

add_action(
    'add_meta_boxes_exam',
    'online_exam_register_exam_schedule_meta_box'
);


function online_exam_get_exam_schedule_local_values($post_id)
{
    $start_at_utc =
        get_post_meta(
            $post_id,
            '_online_exam_start_at',
            true
        );

    $end_at_utc =
        get_post_meta(
            $post_id,
            '_online_exam_end_at',
            true
        );


    $values = array(
        'date'       => '',
        'start_time' => '',
        'end_time'   => '',
    );


    if (
        !$start_at_utc ||
        !$end_at_utc
    ) {
        return $values;
    }


    try {
        $utc_timezone =
            new DateTimeZone('UTC');

        $local_timezone =
            wp_timezone();


        $start =
            new DateTimeImmutable(
                $start_at_utc,
                $utc_timezone
            );


        $end =
            new DateTimeImmutable(
                $end_at_utc,
                $utc_timezone
            );


        $start_local =
            $start->setTimezone(
                $local_timezone
            );


        $end_local =
            $end->setTimezone(
                $local_timezone
            );


        $values['date'] =
            $start_local->format(
                'Y-m-d'
            );


        $values['start_time'] =
            $start_local->format(
                'H:i'
            );


        $values['end_time'] =
            $end_local->format(
                'H:i'
            );


        return $values;

    } catch (Exception $exception) {

        return $values;
    }
}


function online_exam_render_exam_schedule_meta_box($post)
{
    $values =
        online_exam_get_exam_schedule_local_values(
            $post->ID
        );


    wp_nonce_field(
        'online_exam_save_exam_schedule',
        'online_exam_schedule_nonce'
    );

    ?>

    <div
        dir="rtl"
        style="
            display:flex;
            flex-direction:column;
            gap:14px;
        "
    >

        <div>

            <label
                for="online_exam_schedule_date"
                style="
                    display:block;
                    font-weight:600;
                    margin-bottom:6px;
                "
            >
                تاریخ آزمون
            </label>


            <input
                type="date"
                id="online_exam_schedule_date"
                name="online_exam_schedule_date"
                value="<?php echo esc_attr($values['date']); ?>"
                style="
                    width:100%;
                    direction:ltr;
                "
            >

        </div>


        <div>

            <label
                for="online_exam_schedule_start_time"
                style="
                    display:block;
                    font-weight:600;
                    margin-bottom:6px;
                "
            >
                ساعت شروع
            </label>


            <input
                type="time"
                id="online_exam_schedule_start_time"
                name="online_exam_schedule_start_time"
                value="<?php echo esc_attr($values['start_time']); ?>"
                style="
                    width:100%;
                    direction:ltr;
                "
            >

        </div>


        <div>

            <label
                for="online_exam_schedule_end_time"
                style="
                    display:block;
                    font-weight:600;
                    margin-bottom:6px;
                "
            >
                ساعت پایان
            </label>


            <input
                type="time"
                id="online_exam_schedule_end_time"
                name="online_exam_schedule_end_time"
                value="<?php echo esc_attr($values['end_time']); ?>"
                style="
                    width:100%;
                    direction:ltr;
                "
            >

        </div>


        <p
            class="description"
            style="
                margin:0;
                line-height:1.8;
            "
        >
            آزمون در همین روز برگزار می‌شود و ساعت پایان باید بعد از ساعت شروع باشد.
        </p>

    </div>

    <?php
}


/*
|--------------------------------------------------------------------------
| Schedule Admin Error Helper
|--------------------------------------------------------------------------
*/

function online_exam_set_schedule_admin_error($message)
{
    $user_id =
        get_current_user_id();


    if (!$user_id) {
        return;
    }


    set_transient(
        'online_exam_schedule_error_' .
        $user_id,
        sanitize_text_field(
            $message
        ),
        60
    );
}


function online_exam_schedule_admin_error_notice()
{
    $user_id =
        get_current_user_id();


    if (!$user_id) {
        return;
    }


    $transient_key =
        'online_exam_schedule_error_' .
        $user_id;


    $message =
        get_transient(
            $transient_key
        );


    if (!$message) {
        return;
    }


    delete_transient(
        $transient_key
    );

    ?>

    <div class="notice notice-error is-dismissible">
        <p>
            <strong>
                خطای زمان‌بندی آزمون:
            </strong>

            <?php echo esc_html($message); ?>
        </p>
    </div>

    <?php
}

add_action(
    'admin_notices',
    'online_exam_schedule_admin_error_notice'
);


/*
|--------------------------------------------------------------------------
| Save Exam Schedule Meta Box
|--------------------------------------------------------------------------
*/

function online_exam_save_exam_schedule_meta_box(
    $post_id,
    $post
) {
    if (
        !isset(
            $_POST[
                'online_exam_schedule_nonce'
            ]
        )
    ) {
        return;
    }


    $nonce =
        sanitize_text_field(
            wp_unslash(
                $_POST[
                    'online_exam_schedule_nonce'
                ]
            )
        );


    if (
        !wp_verify_nonce(
            $nonce,
            'online_exam_save_exam_schedule'
        )
    ) {
        return;
    }


    if (
        defined('DOING_AUTOSAVE') &&
        DOING_AUTOSAVE
    ) {
        return;
    }


    if (
        wp_is_post_revision(
            $post_id
        )
    ) {
        return;
    }


    if (
        !$post ||
        $post->post_type !== 'exam'
    ) {
        return;
    }


    if (
        !current_user_can(
            'edit_post',
            $post_id
        )
    ) {
        return;
    }


    $exam_date =
        isset(
            $_POST[
                'online_exam_schedule_date'
            ]
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST[
                        'online_exam_schedule_date'
                    ]
                )
            )
            : '';


    $start_time =
        isset(
            $_POST[
                'online_exam_schedule_start_time'
            ]
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST[
                        'online_exam_schedule_start_time'
                    ]
                )
            )
            : '';


    $end_time =
        isset(
            $_POST[
                'online_exam_schedule_end_time'
            ]
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST[
                        'online_exam_schedule_end_time'
                    ]
                )
            )
            : '';


    /*
     * اگر هر سه فیلد خالی باشند،
     * زمان‌بندی آزمون حذف می‌شود.
     */
    if (
        $exam_date === '' &&
        $start_time === '' &&
        $end_time === ''
    ) {
        delete_post_meta(
            $post_id,
            '_online_exam_start_at'
        );

        delete_post_meta(
            $post_id,
            '_online_exam_end_at'
        );

        return;
    }


    /*
     * زمان‌بندی ناقص ذخیره نمی‌شود.
     */
    if (
        $exam_date === '' ||
        $start_time === '' ||
        $end_time === ''
    ) {
        online_exam_set_schedule_admin_error(
            'تاریخ آزمون و هر دو ساعت شروع و پایان را وارد کنید.'
        );

        return;
    }


    $start_at_utc =
        online_exam_parse_local_exam_datetime_to_utc(
            $exam_date,
            $start_time
        );


    $end_at_utc =
        online_exam_parse_local_exam_datetime_to_utc(
            $exam_date,
            $end_time
        );


    $validation =
        online_exam_validate_schedule(
            $start_at_utc,
            $end_at_utc
        );


    if (
        is_wp_error(
            $validation
        )
    ) {
        online_exam_set_schedule_admin_error(
            $validation->get_error_message()
        );

        return;
    }


    update_post_meta(
        $post_id,
        '_online_exam_start_at',
        $start_at_utc
    );


    update_post_meta(
        $post_id,
        '_online_exam_end_at',
        $end_at_utc
    );
}

add_action(
    'save_post_exam',
    'online_exam_save_exam_schedule_meta_box',
    10,
    2
);


/*
|--------------------------------------------------------------------------
| Word Import Admin Menu
|--------------------------------------------------------------------------
*/

function online_exam_register_word_import_page()
{
    add_submenu_page(
        'edit.php?post_type=exam',
        'ایجاد آزمون از Word',
        'ایجاد از Word',
        'manage_options',
        'online-exam-word-import',
        'online_exam_render_word_import_page'
    );
}

add_action('admin_menu', 'online_exam_register_word_import_page');


/*
|--------------------------------------------------------------------------
| Text Helpers
|--------------------------------------------------------------------------
*/

function online_exam_normalize_import_text($text)
{
    $text = (string) $text;

    $text = str_replace(
        array(
            "\xC2\xA0",
            'ي',
            'ى',
            'ك',
            'ۀ',
            'ة',
        ),
        array(
            ' ',
            'ی',
            'ی',
            'ک',
            'ه',
            'ه',
        ),
        $text
    );

    $text = preg_replace(
        '/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u',
        '',
        $text
    );

    $text = preg_replace(
        '/[ \t]+/u',
        ' ',
        $text
    );

    return trim($text);
}


function online_exam_import_english_digits($value)
{
    return str_replace(
        array(
            '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹',
            '٠','١','٢','٣','٤','٥','٦','٧','٨','٩',
        ),
        array(
            '0','1','2','3','4','5','6','7','8','9',
            '0','1','2','3','4','5','6','7','8','9',
        ),
        (string) $value
    );
}


/*
|--------------------------------------------------------------------------
| Extract DOCX Paragraphs
|--------------------------------------------------------------------------
*/

function online_exam_extract_docx_lines($file_path)
{
    if (!class_exists('ZipArchive')) {
        return new WP_Error(
            'zip_missing',
            'افزونه PHP ZipArchive روی سرور فعال نیست.'
        );
    }

    $zip = new ZipArchive();
    $opened = $zip->open($file_path);

    if ($opened !== true) {
        return new WP_Error(
            'invalid_docx',
            'فایل Word معتبر نیست یا امکان باز کردن آن وجود ندارد.'
        );
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false) {
        return new WP_Error(
            'document_xml_missing',
            'ساختار فایل Word معتبر نیست.'
        );
    }

    $dom = new DOMDocument();

    libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();

    if (!$loaded) {
        return new WP_Error(
            'xml_invalid',
            'امکان خواندن محتوای فایل Word وجود ندارد.'
        );
    }

    $xpath = new DOMXPath($dom);

    $xpath->registerNamespace(
        'w',
        'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
    );

    $paragraphs = $xpath->query('//w:body//w:p');

    $lines = array();

    foreach ($paragraphs as $paragraph) {
        $text_nodes = $xpath->query('.//w:t', $paragraph);

        $text = '';

        foreach ($text_nodes as $node) {
            $text .= $node->nodeValue;
        }

        $text = online_exam_normalize_import_text($text);

        if ($text !== '') {
            $lines[] = $text;
        }
    }

    if (empty($lines)) {
        return new WP_Error(
            'empty_document',
            'هیچ متن قابل خواندنی داخل فایل Word پیدا نشد.'
        );
    }

    return $lines;
}


/*
|--------------------------------------------------------------------------
| Question Parsing
|--------------------------------------------------------------------------
*/

function online_exam_parse_question_start($line)
{
    $normalized = online_exam_import_english_digits($line);

    if (
        preg_match(
            '/^س[ؤو]ال\s+([0-9]+)\s*[:\-]\s*(.+)$/u',
            $normalized,
            $matches
        )
    ) {
        return array(
            'number' => (int) $matches[1],
            'text'   => trim($matches[2]),
        );
    }

    if (
        preg_match(
            '/^([0-9]+)\s*[\.\)]\s*(.+)$/u',
            $normalized,
            $matches
        )
    ) {
        return array(
            'number' => (int) $matches[1],
            'text'   => trim($matches[2]),
        );
    }

    return false;
}


function online_exam_parse_option_line($line)
{
    if (
        !preg_match(
            '/^(الف|ب|ج|د)\s*[\)\.\:\-]\s*(.+)$/u',
            $line,
            $matches
        )
    ) {
        return false;
    }

    $map = array(
        'الف' => 'a',
        'ب'   => 'b',
        'ج'   => 'c',
        'د'   => 'd',
    );

    return array(
        'id'    => $map[$matches[1]],
        'label' => trim($matches[2]),
    );
}


function online_exam_parse_correct_answer($line)
{
    if (
        !preg_match(
            '/^پاسخ\s*صحیح\s*[:：]\s*(الف|ب|ج|د)\s*[\)\.]?$/u',
            $line,
            $matches
        )
    ) {
        return false;
    }

    $map = array(
        'الف' => 'a',
        'ب'   => 'b',
        'ج'   => 'c',
        'د'   => 'd',
    );

    return $map[$matches[1]];
}


function online_exam_extract_question_type($question_text)
{
    if (
        preg_match(
            '/\(\s*تشریحی\s*\)\s*$/u',
            $question_text
        )
    ) {
        $clean_text = preg_replace(
            '/\s*\(\s*تشریحی\s*\)\s*$/u',
            '',
            $question_text
        );

        return array(
            'type' => 'essay',
            'text' => trim($clean_text),
        );
    }

    return array(
        'type' => 'mcq',
        'text' => trim($question_text),
    );
}


function online_exam_validate_import_question($question)
{
    $number = absint($question['number']);

    if (trim((string) $question['text']) === '') {
        return new WP_Error(
            'question_empty',
            sprintf(
                'متن سؤال شماره %d خالی است.',
                $number
            )
        );
    }

    if ($question['type'] === 'essay') {
        return true;
    }

    foreach (array('a', 'b', 'c', 'd') as $option_id) {
        if (empty($question['options'][$option_id])) {
            return new WP_Error(
                'option_missing',
                sprintf(
                    'سؤال شماره %d گزینه‌های کامل الف تا د ندارد.',
                    $number
                )
            );
        }
    }

    if (count($question['options']) !== 4) {
        return new WP_Error(
            'option_count_invalid',
            sprintf(
                'تعداد گزینه‌های سؤال شماره %d معتبر نیست.',
                $number
            )
        );
    }

    if (empty($question['correct_option'])) {
        return new WP_Error(
            'correct_answer_missing',
            sprintf(
                'پاسخ صحیح سؤال شماره %d مشخص نشده است.',
                $number
            )
        );
    }

    if (
        !isset(
            $question['options'][
                $question['correct_option']
            ]
        )
    ) {
        return new WP_Error(
            'correct_answer_invalid',
            sprintf(
                'پاسخ صحیح سؤال شماره %d با گزینه‌های آن مطابقت ندارد.',
                $number
            )
        );
    }

    return true;
}


function online_exam_parse_docx_questions($lines)
{
    $questions = array();
    $current = null;

    foreach ($lines as $line) {
        $line = online_exam_normalize_import_text($line);

        $question_start =
            online_exam_parse_question_start($line);

        if ($question_start !== false) {
            if ($current !== null) {
                $validation =
                    online_exam_validate_import_question($current);

                if (is_wp_error($validation)) {
                    return $validation;
                }

                $questions[] = $current;
            }

            $type_data =
                online_exam_extract_question_type(
                    $question_start['text']
                );

            $current = array(
                'number'         => $question_start['number'],
                'text'           => $type_data['text'],
                'type'           => $type_data['type'],
                'options'        => array(),
                'correct_option' => null,
            );

            continue;
        }

        if ($current === null) {
            continue;
        }

        if ($current['type'] === 'essay') {
            return new WP_Error(
                'essay_extra_content',
                sprintf(
                    'بعد از سؤال تشریحی شماره %d متن اضافه پیدا شد: "%s"',
                    $current['number'],
                    $line
                )
            );
        }

        $option = online_exam_parse_option_line($line);

        if ($option !== false) {
            if (isset($current['options'][$option['id']])) {
                return new WP_Error(
                    'duplicate_option',
                    sprintf(
                        'در سؤال شماره %d یک گزینه تکراری وجود دارد.',
                        $current['number']
                    )
                );
            }

            $current['options'][$option['id']] =
                $option['label'];

            continue;
        }

        $correct =
            online_exam_parse_correct_answer($line);

        if ($correct !== false) {
            if ($current['correct_option'] !== null) {
                return new WP_Error(
                    'duplicate_correct_answer',
                    sprintf(
                        'برای سؤال شماره %d بیش از یک پاسخ صحیح تعریف شده است.',
                        $current['number']
                    )
                );
            }

            $current['correct_option'] = $correct;

            continue;
        }

        if (
            empty($current['options']) &&
            $current['correct_option'] === null
        ) {
            $current['text'] =
                trim(
                    $current['text'] .
                    ' ' .
                    $line
                );

            continue;
        }

        return new WP_Error(
            'unknown_line',
            sprintf(
                'در سؤال شماره %d یک خط با فرمت ناشناخته پیدا شد: "%s"',
                $current['number'],
                $line
            )
        );
    }

    if ($current !== null) {
        $validation =
            online_exam_validate_import_question($current);

        if (is_wp_error($validation)) {
            return $validation;
        }

        $questions[] = $current;
    }

    if (empty($questions)) {
        return new WP_Error(
            'questions_not_found',
            'هیچ سؤال معتبری داخل فایل Word پیدا نشد.'
        );
    }

    $numbers = array();

    foreach ($questions as $question) {
        if (
            in_array(
                $question['number'],
                $numbers,
                true
            )
        ) {
            return new WP_Error(
                'duplicate_question_number',
                sprintf(
                    'شماره سؤال %d تکراری است.',
                    $question['number']
                )
            );
        }

        $numbers[] = $question['number'];
    }

    return $questions;
}


/*
|--------------------------------------------------------------------------
| Question Counts
|--------------------------------------------------------------------------
*/

function online_exam_get_import_question_counts($questions)
{
    $mcq = 0;
    $essay = 0;

    foreach ($questions as $question) {
        if ($question['type'] === 'essay') {
            $essay++;
        } else {
            $mcq++;
        }
    }

    return array(
        'total' => count($questions),
        'mcq'   => $mcq,
        'essay' => $essay,
    );
}


/*
|--------------------------------------------------------------------------
| Process DOCX Upload
|--------------------------------------------------------------------------
*/

function online_exam_process_word_upload()
{
    if (!current_user_can('manage_options')) {
        return new WP_Error(
            'permission_denied',
            'شما اجازه انجام این عملیات را ندارید.'
        );
    }

    if (empty($_FILES['exam_word_file'])) {
        return new WP_Error(
            'file_missing',
            'فایل Word انتخاب نشده است.'
        );
    }

    $file = $_FILES['exam_word_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return new WP_Error(
            'upload_error',
            'آپلود فایل با خطا مواجه شد.'
        );
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        return new WP_Error(
            'file_too_large',
            'حجم فایل Word نباید بیشتر از 10 مگابایت باشد.'
        );
    }

    $extension =
        strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );

    if ($extension !== 'docx') {
        return new WP_Error(
            'invalid_extension',
            'فقط فایل با فرمت DOCX قابل قبول است.'
        );
    }

    $lines =
        online_exam_extract_docx_lines(
            $file['tmp_name']
        );

    if (is_wp_error($lines)) {
        return $lines;
    }

    return online_exam_parse_docx_questions($lines);
}


/*
|--------------------------------------------------------------------------
| Exam Schedule Helpers - Gregorian
|--------------------------------------------------------------------------
|
| یک تاریخ برای آزمون داریم و دو ساعت:
|
| exam_date
| exam_start_time
| exam_end_time
|
| زمان‌ها با Timezone وردپرس خوانده می‌شوند و در دیتابیس UTC ذخیره می‌شوند.
|
*/

function online_exam_parse_local_exam_datetime_to_utc(
    $date_value,
    $time_value
) {
    $date_value =
        trim(
            online_exam_import_english_digits(
                (string) $date_value
            )
        );

    $time_value =
        trim(
            online_exam_import_english_digits(
                (string) $time_value
            )
        );


    if (
        !preg_match(
            '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            $date_value
        )
    ) {
        return false;
    }


    if (
        !preg_match(
            '/^[0-9]{2}:[0-9]{2}$/',
            $time_value
        )
    ) {
        return false;
    }


    try {
        $timezone =
            wp_timezone();


        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d H:i',
                $date_value . ' ' . $time_value,
                $timezone
            );


        if (!$date) {
            return false;
        }


        $errors =
            DateTimeImmutable::getLastErrors();


        if (
            is_array($errors) &&
            (
                !empty($errors['warning_count']) ||
                !empty($errors['error_count'])
            )
        ) {
            return false;
        }


        /*
         * مطمئن می‌شویم PHP تاریخ را Normalize نکرده باشد.
         */
        if (
            $date->format('Y-m-d H:i') !==
            $date_value . ' ' . $time_value
        ) {
            return false;
        }


        return $date
            ->setTimezone(
                new DateTimeZone('UTC')
            )
            ->format(
                'Y-m-d H:i:s'
            );

    } catch (Exception $exception) {

        return false;
    }
}


function online_exam_format_utc_datetime_for_admin(
    $utc_value
) {
    $utc_value =
        trim(
            (string) $utc_value
        );


    if ($utc_value === '') {
        return '';
    }


    try {
        $date =
            new DateTimeImmutable(
                $utc_value,
                new DateTimeZone('UTC')
            );


        return $date
            ->setTimezone(
                wp_timezone()
            )
            ->format(
                'Y/m/d - H:i'
            );

    } catch (Exception $exception) {

        return '';
    }
}


function online_exam_validate_schedule(
    $start_at_utc,
    $end_at_utc
) {
    if (
        !$start_at_utc ||
        !$end_at_utc
    ) {
        return new WP_Error(
            'invalid_exam_schedule',
            'تاریخ آزمون و ساعت شروع و پایان را به‌صورت صحیح وارد کنید.'
        );
    }


    $start_timestamp =
        strtotime(
            $start_at_utc . ' UTC'
        );


    $end_timestamp =
        strtotime(
            $end_at_utc . ' UTC'
        );


    if (
        !$start_timestamp ||
        !$end_timestamp
    ) {
        return new WP_Error(
            'invalid_exam_schedule',
            'تاریخ یا ساعت آزمون معتبر نیست.'
        );
    }


    if (
        $end_timestamp <=
        $start_timestamp
    ) {
        return new WP_Error(
            'invalid_exam_schedule_range',
            'ساعت پایان آزمون باید بعد از ساعت شروع باشد.'
        );
    }


    return true;
}


/*
|--------------------------------------------------------------------------
| Score Helpers
|--------------------------------------------------------------------------
*/

function online_exam_parse_score_value($value)
{
    $value =
        online_exam_import_english_digits(
            (string) $value
        );

    $value =
        str_replace(
            array('٫', '،', ','),
            '.',
            $value
        );

    $value = trim($value);

    if (
        $value === '' ||
        !is_numeric($value)
    ) {
        return false;
    }

    return round(
        (float) $value,
        2
    );
}


function online_exam_validate_import_scoring(
    $total_score,
    $questions,
    $essay_scores = array()
) {
    $total_score =
        online_exam_parse_score_value(
            $total_score
        );

    if (
        $total_score === false ||
        $total_score <= 0
    ) {
        return new WP_Error(
            'invalid_total_score',
            'نمره کل آزمون باید بیشتر از صفر باشد.'
        );
    }

    $counts =
        online_exam_get_import_question_counts(
            $questions
        );

    $normalized_essay_scores = array();
    $essay_total = 0.0;

    foreach ($questions as $question) {
        if ($question['type'] !== 'essay') {
            continue;
        }

        $question_number =
            absint(
                $question['number']
            );

        $raw_score =
            isset(
                $essay_scores[
                    $question_number
                ]
            )
                ? $essay_scores[
                    $question_number
                ]
                : '';

        $score =
            online_exam_parse_score_value(
                $raw_score
            );

        if (
            $score === false ||
            $score <= 0
        ) {
            return new WP_Error(
                'invalid_essay_score',
                sprintf(
                    'بارم سؤال تشریحی شماره %d را به‌صورت صحیح وارد کنید.',
                    $question_number
                )
            );
        }

        $normalized_essay_scores[
            $question_number
        ] = $score;

        $essay_total += $score;
    }

    $essay_total =
        round(
            $essay_total,
            2
        );

    if (
        $essay_total >
        $total_score + 0.001
    ) {
        return new WP_Error(
            'essay_score_too_high',
            sprintf(
                'مجموع بارم تشریحی (%s) از نمره کل آزمون (%s) بیشتر است.',
                $essay_total,
                $total_score
            )
        );
    }

    if ($counts['mcq'] === 0) {
        if (
            abs(
                $essay_total -
                $total_score
            ) > 0.001
        ) {
            return new WP_Error(
                'essay_score_mismatch',
                sprintf(
                    'این آزمون کاملاً تشریحی است؛ مجموع بارم سوالات باید دقیقاً %s باشد. مجموع فعلی %s است.',
                    $total_score,
                    $essay_total
                )
            );
        }

        $mcq_total_score = 0;
        $mcq_score = 0;

    } else {
        $mcq_total_score =
            round(
                $total_score -
                $essay_total,
                4
            );

        if ($mcq_total_score <= 0) {
            return new WP_Error(
                'mcq_score_empty',
                'برای سوالات تستی باید مقداری از نمره کل باقی بماند.'
            );
        }

        $mcq_score =
            $mcq_total_score /
            $counts['mcq'];
    }

    return array(
        'total_score'       => $total_score,
        'essay_total_score' => $essay_total,
        'mcq_total_score'   => $mcq_total_score,
        'mcq_score'         => $mcq_score,
        'essay_scores'      => $normalized_essay_scores,
        'counts'            => $counts,
    );
}


/*
|--------------------------------------------------------------------------
| Create Imported Exam
|--------------------------------------------------------------------------
*/

function online_exam_create_imported_exam(
    $title,
    $duration,
    $total_score,
    $start_at_utc,
    $end_at_utc,
    $questions,
    $essay_scores = array()
) {
    $scoring =
        online_exam_validate_import_scoring(
            $total_score,
            $questions,
            $essay_scores
        );

    if (is_wp_error($scoring)) {
        return $scoring;
    }

    $schedule_validation =
        online_exam_validate_schedule(
            $start_at_utc,
            $end_at_utc
        );

    if (is_wp_error($schedule_validation)) {
        return $schedule_validation;
    }

    $exam_id =
        wp_insert_post(
            array(
                'post_type'   => 'exam',
                'post_status' => 'publish',
                'post_title'  => $title,
            ),
            true
        );

    if (is_wp_error($exam_id)) {
        return $exam_id;
    }

    $created_question_ids = array();
    $counts = $scoring['counts'];

    update_post_meta(
        $exam_id,
        '_online_exam_duration',
        absint($duration)
    );

    update_post_meta(
        $exam_id,
        '_online_exam_start_at',
        $start_at_utc
    );

    update_post_meta(
        $exam_id,
        '_online_exam_end_at',
        $end_at_utc
    );

    update_post_meta(
        $exam_id,
        '_online_exam_total_score',
        $scoring['total_score']
    );

    update_post_meta(
        $exam_id,
        '_online_exam_question_count',
        $counts['total']
    );

    update_post_meta(
        $exam_id,
        '_online_exam_mcq_count',
        $counts['mcq']
    );

    update_post_meta(
        $exam_id,
        '_online_exam_essay_count',
        $counts['essay']
    );

    update_post_meta(
        $exam_id,
        '_online_exam_source',
        'word_import'
    );

    foreach ($questions as $index => $question) {
        $question_id =
            wp_insert_post(
                array(
                    'post_type'   => 'question',
                    'post_status' => 'publish',
                    'post_title'  => sprintf(
                        'سؤال %d - %s',
                        $index + 1,
                        $title
                    ),
                    'post_content' => $question['text'],
                ),
                true
            );

        if (is_wp_error($question_id)) {
            foreach ($created_question_ids as $created_question_id) {
                wp_delete_post(
                    $created_question_id,
                    true
                );
            }

            wp_delete_post(
                $exam_id,
                true
            );

            return new WP_Error(
                'question_creation_failed',
                sprintf(
                    'ساخت سؤال شماره %d با خطا مواجه شد و آزمون ایجاد نشد.',
                    $index + 1
                )
            );
        }

        $created_question_ids[] = $question_id;

        update_post_meta(
            $question_id,
            '_online_exam_exam_id',
            $exam_id
        );

        update_post_meta(
            $question_id,
            '_online_exam_question_type',
            $question['type']
        );

        update_post_meta(
            $question_id,
            '_online_exam_question_order',
            $index + 1
        );

        if ($question['type'] === 'mcq') {
            $options = array(
                array(
                    'id'    => 'a',
                    'label' => $question['options']['a'],
                ),
                array(
                    'id'    => 'b',
                    'label' => $question['options']['b'],
                ),
                array(
                    'id'    => 'c',
                    'label' => $question['options']['c'],
                ),
                array(
                    'id'    => 'd',
                    'label' => $question['options']['d'],
                ),
            );

            update_post_meta(
                $question_id,
                '_online_exam_options',
                $options
            );

            update_post_meta(
                $question_id,
                '_online_exam_correct_option_id',
                $question['correct_option']
            );

        } elseif ($question['type'] === 'essay') {
            $question_number =
                absint(
                    $question['number']
                );

            update_post_meta(
                $question_id,
                '_online_exam_max_score',
                $scoring[
                    'essay_scores'
                ][
                    $question_number
                ]
            );
        }
    }

    return $exam_id;
}


/*
|--------------------------------------------------------------------------
| Confirm Import - runs before admin HTML
|--------------------------------------------------------------------------
*/

function online_exam_handle_word_import_confirm()
{
    if (
        empty($_GET['page']) ||
        $_GET['page'] !== 'online-exam-word-import'
    ) {
        return;
    }

    if (
        ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ||
        !isset($_POST['online_exam_confirm'])
    ) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(
            'شما اجازه انجام این عملیات را ندارید.'
        );
    }

    check_admin_referer(
        'online_exam_word_confirm',
        'online_exam_confirm_nonce'
    );

    $preview_token =
        isset($_POST['preview_token'])
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['preview_token']
                )
            )
            : '';

    if ($preview_token === '') {
        wp_die(
            'شناسه پیش‌نمایش معتبر نیست.',
            'خطای ایجاد آزمون',
            array(
                'back_link' => true,
            )
        );
    }

    $transient_key =
        'online_exam_import_' .
        get_current_user_id() .
        '_' .
        $preview_token;

    $stored_preview =
        get_transient(
            $transient_key
        );

    if (!$stored_preview) {
        wp_die(
            'اطلاعات پیش‌نمایش منقضی شده است. فایل Word را دوباره بارگذاری کنید.',
            'خطای ایجاد آزمون',
            array(
                'back_link' => true,
            )
        );
    }

    $essay_scores =
        isset($_POST['essay_scores']) &&
        is_array($_POST['essay_scores'])
            ? wp_unslash(
                $_POST['essay_scores']
            )
            : array();

    $scoring =
        online_exam_validate_import_scoring(
            $stored_preview['total_score'],
            $stored_preview['questions'],
            $essay_scores
        );

    if (is_wp_error($scoring)) {
        wp_die(
            esc_html(
                $scoring->get_error_message()
            ),
            'خطای بارم‌بندی',
            array(
                'back_link' => true,
            )
        );
    }

    $exam_id =
        online_exam_create_imported_exam(
            $stored_preview['title'],
            $stored_preview['duration'],
            $stored_preview['total_score'],
            $stored_preview['start_at_utc'],
            $stored_preview['end_at_utc'],
            $stored_preview['questions'],
            $scoring['essay_scores']
        );

    if (is_wp_error($exam_id)) {
        wp_die(
            esc_html(
                $exam_id->get_error_message()
            ),
            'خطای ایجاد آزمون',
            array(
                'back_link' => true,
            )
        );
    }

    delete_transient(
        $transient_key
    );

    $redirect_url =
        add_query_arg(
            array(
                'post'                 => $exam_id,
                'action'               => 'edit',
                'online_exam_imported' => 1,
            ),
            admin_url('post.php')
        );

    wp_safe_redirect($redirect_url);
    exit;
}

add_action(
    'admin_init',
    'online_exam_handle_word_import_confirm'
);


/*
|--------------------------------------------------------------------------
| Import Page
|--------------------------------------------------------------------------
*/

function online_exam_render_word_import_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(
            'شما اجازه دسترسی به این صفحه را ندارید.'
        );
    }

    $error = null;
    $preview = null;
    $preview_token = '';
    $title = '';
    $duration = 45;
    $total_score = 20;
    $exam_date = '';
    $exam_start_time = '';
    $exam_end_time = '';

    /*
    |--------------------------------------------------------------------------
    | STEP 1 - Parse and Preview
    |--------------------------------------------------------------------------
    */

    if (isset($_POST['online_exam_preview'])) {
        check_admin_referer(
            'online_exam_word_import',
            'online_exam_word_nonce'
        );

        $title =
            isset($_POST['exam_title'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['exam_title']
                    )
                )
                : '';

        $duration =
            isset($_POST['exam_duration'])
                ? absint(
                    $_POST['exam_duration']
                )
                : 0;

        $total_score_raw =
            isset($_POST['exam_total_score'])
                ? wp_unslash(
                    $_POST['exam_total_score']
                )
                : '';

        $exam_date =
            isset($_POST['exam_date'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['exam_date']
                    )
                )
                : '';

        $exam_start_time =
            isset($_POST['exam_start_time'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['exam_start_time']
                    )
                )
                : '';

        $exam_end_time =
            isset($_POST['exam_end_time'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['exam_end_time']
                    )
                )
                : '';

        $start_at_utc =
            online_exam_parse_local_exam_datetime_to_utc(
                $exam_date,
                $exam_start_time
            );

        $end_at_utc =
            online_exam_parse_local_exam_datetime_to_utc(
                $exam_date,
                $exam_end_time
            );

        $schedule_validation =
            online_exam_validate_schedule(
                $start_at_utc,
                $end_at_utc
            );

        $parsed_total_score =
            online_exam_parse_score_value(
                $total_score_raw
            );

        if ($parsed_total_score !== false) {
            $total_score = $parsed_total_score;
        }

        if ($title === '') {
            $error =
                'عنوان آزمون را وارد کنید.';

        } elseif (
            $duration < 1 ||
            $duration > 1440
        ) {
            $error =
                'مدت زمان آزمون باید بین 1 تا 1440 دقیقه باشد.';

        } elseif (
            $parsed_total_score === false ||
            $parsed_total_score <= 0
        ) {
            $error =
                'نمره کل آزمون باید بیشتر از صفر باشد.';

        } elseif (
            is_wp_error(
                $schedule_validation
            )
        ) {
            $error =
                $schedule_validation
                    ->get_error_message();

        } else {
            $questions =
                online_exam_process_word_upload();

            if (is_wp_error($questions)) {
                $error =
                    $questions->get_error_message();

            } else {
                $preview = array(
                    'title'        => $title,
                    'duration'     => $duration,
                    'total_score'  => $parsed_total_score,
                    'start_at_utc' => $start_at_utc,
                    'end_at_utc'   => $end_at_utc,
                    'questions'    => $questions,
                );

                $preview_token =
                    wp_generate_password(
                        24,
                        false,
                        false
                    );

                $transient_key =
                    'online_exam_import_' .
                    get_current_user_id() .
                    '_' .
                    $preview_token;

                set_transient(
                    $transient_key,
                    $preview,
                    15 * MINUTE_IN_SECONDS
                );
            }
        }
    }

    ?>

    <div
        class="wrap"
        dir="rtl"
        style="max-width: 1100px;"
    >
        <h1>
            ایجاد آزمون از فایل Word
        </h1>

        <p>
            عنوان، تاریخ آزمون، بازه زمانی، مدت آزمون، نمره کل و فایل Word استاندارد آزمون را وارد کنید.
        </p>


        <?php if ($error) : ?>

            <div class="notice notice-error">
                <p>
                    <?php echo esc_html($error); ?>
                </p>
            </div>

        <?php endif; ?>


        <div
            class="card"
            style="
                max-width: none;
                padding: 24px;
                margin-top: 20px;
            "
        >
            <form
                method="post"
                enctype="multipart/form-data"
            >
                <?php
                wp_nonce_field(
                    'online_exam_word_import',
                    'online_exam_word_nonce'
                );
                ?>

                <table class="form-table">

                    <tr>
                        <th>
                            <label for="exam_title">
                                عنوان آزمون
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="exam_title"
                                name="exam_title"
                                class="regular-text"
                                required
                                value="<?php echo esc_attr($title); ?>"
                            >
                        </td>
                    </tr>


                    <tr>
                        <th>
                            <label for="exam_duration">
                                مدت آزمون
                            </label>
                        </th>

                        <td>
                            <input
                                type="number"
                                id="exam_duration"
                                name="exam_duration"
                                min="1"
                                max="1440"
                                required
                                value="<?php echo esc_attr($duration); ?>"
                            >
                            دقیقه
                        </td>
                    </tr>


                    <tr>
                        <th>
                            <label for="exam_date">
                                تاریخ آزمون
                            </label>
                        </th>

                        <td>
                            <input
                                type="date"
                                id="exam_date"
                                name="exam_date"
                                required
                                value="<?php echo esc_attr($exam_date); ?>"
                                style="
                                    width: 170px;
                                    direction: ltr;
                                "
                            >

                            <p class="description">
                                تاریخ آزمون را از تقویم مرورگر انتخاب کنید.
                            </p>
                        </td>
                    </tr>


                    <tr>
                        <th>
                            بازه زمانی آزمون
                        </th>

                        <td>
                            <label for="exam_start_time">
                                ساعت شروع
                            </label>

                            <input
                                type="time"
                                id="exam_start_time"
                                name="exam_start_time"
                                required
                                value="<?php echo esc_attr($exam_start_time); ?>"
                                style="
                                    width: 120px;
                                    direction: ltr;
                                    margin: 0 8px 0 16px;
                                "
                            >


                            <label for="exam_end_time">
                                ساعت پایان
                            </label>

                            <input
                                type="time"
                                id="exam_end_time"
                                name="exam_end_time"
                                required
                                value="<?php echo esc_attr($exam_end_time); ?>"
                                style="
                                    width: 120px;
                                    direction: ltr;
                                    margin-right: 8px;
                                "
                            >


                            <p class="description">
                                آزمون در همین روز برگزار می‌شود؛ ساعت پایان باید بعد از ساعت شروع باشد.
                            </p>
                        </td>
                    </tr>


                    <tr>
                        <th>
                            <label for="exam_total_score">
                                نمره کل آزمون
                            </label>
                        </th>

                        <td>
                            <input
                                type="number"
                                id="exam_total_score"
                                name="exam_total_score"
                                min="0.01"
                                step="0.01"
                                required
                                value="<?php echo esc_attr($total_score); ?>"
                            >

                            <p class="description">
                                مثال: 20، 30، 100 و ...
                            </p>
                        </td>
                    </tr>


                    <tr>
                        <th>
                            <label for="exam_word_file">
                                فایل Word
                            </label>
                        </th>

                        <td>
                            <input
                                type="file"
                                id="exam_word_file"
                                name="exam_word_file"
                                accept=".docx"
                                required
                            >

                            <p class="description">
                                فقط فایل DOCX
                            </p>
                        </td>
                    </tr>

                </table>

                <?php
                submit_button(
                    'بررسی و پیش‌نمایش',
                    'primary',
                    'online_exam_preview'
                );
                ?>

            </form>
        </div>


        <?php if ($preview) : ?>

            <?php
            $counts =
                online_exam_get_import_question_counts(
                    $preview['questions']
                );
            ?>

            <div
                class="card"
                style="
                    max-width: none;
                    margin-top: 25px;
                    padding: 24px;
                "
            >
                <h2>
                    پیش‌نمایش آزمون
                </h2>

                <p>
                    <strong>تعداد کل:</strong>
                    <?php echo esc_html($counts['total']); ?>

                    &nbsp; | &nbsp;

                    <strong>تستی:</strong>
                    <?php echo esc_html($counts['mcq']); ?>

                    &nbsp; | &nbsp;

                    <strong>تشریحی:</strong>
                    <?php echo esc_html($counts['essay']); ?>

                    &nbsp; | &nbsp;

                    <strong>نمره کل:</strong>
                    <?php echo esc_html($preview['total_score']); ?>

                    <br><br>

                    <strong>شروع آزمون:</strong>
                    <?php
                    echo esc_html(
                        online_exam_format_utc_datetime_for_admin(
                            $preview['start_at_utc']
                        )
                    );
                    ?>

                    &nbsp; | &nbsp;

                    <strong>پایان آزمون:</strong>
                    <?php
                    echo esc_html(
                        online_exam_format_utc_datetime_for_admin(
                            $preview['end_at_utc']
                        )
                    );
                    ?>
                </p>


                <form method="post">

                    <?php
                    wp_nonce_field(
                        'online_exam_word_confirm',
                        'online_exam_confirm_nonce'
                    );
                    ?>

                    <input
                        type="hidden"
                        name="preview_token"
                        value="<?php echo esc_attr($preview_token); ?>"
                    >


                    <?php foreach ($preview['questions'] as $question) : ?>

                        <div
                            style="
                                border: 1px solid #dcdcde;
                                border-radius: 8px;
                                padding: 18px;
                                margin: 15px 0;
                                background: #fff;
                            "
                        >
                            <strong>
                                سؤال
                                <?php echo esc_html($question['number']); ?>
                                —
                                <?php
                                echo $question['type'] === 'essay'
                                    ? 'تشریحی'
                                    : 'تستی';
                                ?>
                            </strong>

                            <p
                                style="
                                    font-size: 14px;
                                    margin-top: 12px;
                                "
                            >
                                <?php
                                echo esc_html(
                                    $question['text']
                                );
                                ?>
                            </p>


                            <?php if ($question['type'] === 'mcq') : ?>

                                <ul>
                                    <?php
                                    $option_names = array(
                                        'a' => 'الف',
                                        'b' => 'ب',
                                        'c' => 'ج',
                                        'd' => 'د',
                                    );
                                    ?>

                                    <?php
                                    foreach (
                                        $question['options']
                                        as $option_id => $option_text
                                    ) :
                                    ?>

                                        <li>
                                            <strong>
                                                <?php
                                                echo esc_html(
                                                    $option_names[
                                                        $option_id
                                                    ] ?? $option_id
                                                );
                                                ?>)
                                            </strong>

                                            <?php
                                            echo esc_html(
                                                $option_text
                                            );
                                            ?>

                                            <?php
                                            if (
                                                $question[
                                                    'correct_option'
                                                ] === $option_id
                                            ) :
                                            ?>
                                                <strong>
                                                    ← پاسخ صحیح
                                                </strong>
                                            <?php endif; ?>
                                        </li>

                                    <?php endforeach; ?>
                                </ul>


                            <?php else : ?>

                                <div
                                    style="
                                        background: #f6f7f7;
                                        padding: 12px;
                                        border-radius: 6px;
                                        margin-top: 12px;
                                    "
                                >
                                    <label>
                                        <strong>
                                            بارم این سؤال:
                                        </strong>

                                        <input
                                            type="number"
                                            class="online-exam-essay-score"
                                            name="essay_scores[<?php echo esc_attr(
                                                $question['number']
                                            ); ?>]"
                                            min="0.01"
                                            step="0.01"
                                            required
                                            style="
                                                width: 110px;
                                                margin-right: 10px;
                                            "
                                        >
                                    </label>
                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>


                    <div
                        id="onlineExamScoreSummary"
                        data-total-score="<?php echo esc_attr(
                            $preview['total_score']
                        ); ?>"
                        data-mcq-count="<?php echo esc_attr(
                            $counts['mcq']
                        ); ?>"
                        style="
                            background: #f0f6fc;
                            border: 1px solid #c5d9ed;
                            border-radius: 8px;
                            padding: 18px;
                            margin: 20px 0;
                        "
                    >
                        <h3 style="margin-top: 0;">
                            محاسبه بارم
                        </h3>

                        <p>
                            نمره کل:
                            <strong id="scoreTotal">
                                <?php
                                echo esc_html(
                                    $preview['total_score']
                                );
                                ?>
                            </strong>
                        </p>

                        <p>
                            مجموع بارم تشریحی:
                            <strong id="scoreEssayTotal">
                                0
                            </strong>
                        </p>

                        <p>
                            نمره باقی‌مانده برای تستی:
                            <strong id="scoreMcqTotal">
                                <?php
                                echo esc_html(
                                    $preview['total_score']
                                );
                                ?>
                            </strong>
                        </p>

                        <?php if ($counts['mcq'] > 0) : ?>

                            <p>
                                تعداد سوالات تستی:
                                <strong>
                                    <?php
                                    echo esc_html(
                                        $counts['mcq']
                                    );
                                    ?>
                                </strong>
                            </p>

                            <p>
                                بارم هر سوال تستی:
                                <strong id="scorePerMcq">
                                    —
                                </strong>
                            </p>

                        <?php endif; ?>

                        <p
                            id="scoreValidationMessage"
                            style="
                                font-weight: 600;
                                margin-bottom: 0;
                            "
                        ></p>
                    </div>


                    <?php
                    submit_button(
                        'ایجاد نهایی آزمون',
                        'primary',
                        'online_exam_confirm'
                    );
                    ?>

                </form>
            </div>


            <script>
            document.addEventListener(
                'DOMContentLoaded',
                function () {

                    const summary =
                        document.getElementById(
                            'onlineExamScoreSummary'
                        );

                    if (!summary) {
                        return;
                    }

                    const totalScore =
                        parseFloat(
                            summary.dataset.totalScore
                        ) || 0;

                    const mcqCount =
                        parseInt(
                            summary.dataset.mcqCount,
                            10
                        ) || 0;

                    const essayInputs =
                        document.querySelectorAll(
                            '.online-exam-essay-score'
                        );

                    const essayTotalElement =
                        document.getElementById(
                            'scoreEssayTotal'
                        );

                    const mcqTotalElement =
                        document.getElementById(
                            'scoreMcqTotal'
                        );

                    const perMcqElement =
                        document.getElementById(
                            'scorePerMcq'
                        );

                    const messageElement =
                        document.getElementById(
                            'scoreValidationMessage'
                        );

                    function formatScore(value)
                    {
                        return Number(
                            value.toFixed(4)
                        ).toString();
                    }

                    function calculate()
                    {
                        let essayTotal = 0;

                        essayInputs.forEach(
                            function (input) {
                                const value =
                                    parseFloat(
                                        input.value
                                    );

                                if (
                                    !Number.isNaN(
                                        value
                                    )
                                ) {
                                    essayTotal += value;
                                }
                            }
                        );

                        const mcqTotal =
                            totalScore -
                            essayTotal;

                        essayTotalElement.textContent =
                            formatScore(
                                essayTotal
                            );

                        mcqTotalElement.textContent =
                            formatScore(
                                mcqTotal
                            );

                        if (
                            perMcqElement &&
                            mcqCount > 0
                        ) {
                            perMcqElement.textContent =
                                mcqTotal > 0
                                    ? formatScore(
                                        mcqTotal /
                                        mcqCount
                                    )
                                    : '0';
                        }

                        if (
                            essayTotal >
                            totalScore
                        ) {
                            messageElement.textContent =
                                'مجموع بارم تشریحی از نمره کل بیشتر است.';

                        } else if (
                            mcqCount > 0 &&
                            mcqTotal <= 0
                        ) {
                            messageElement.textContent =
                                'برای سوالات تستی باید مقداری از نمره باقی بماند.';

                        } else if (
                            mcqCount === 0 &&
                            Math.abs(
                                essayTotal -
                                totalScore
                            ) > 0.001
                        ) {
                            messageElement.textContent =
                                'در آزمون کاملاً تشریحی، مجموع بارم‌ها باید برابر نمره کل باشد.';

                        } else {
                            messageElement.textContent =
                                'بارم‌بندی معتبر است.';
                        }
                    }

                    essayInputs.forEach(
                        function (input) {
                            input.addEventListener(
                                'input',
                                calculate
                            );
                        }
                    );

                    calculate();
                }
            );
            </script>

        <?php endif; ?>

    </div>

    <?php
}


/*
|--------------------------------------------------------------------------
| Imported Notice
|--------------------------------------------------------------------------
*/

function online_exam_import_success_notice()
{
    if (
        empty(
            $_GET['online_exam_imported']
        )
    ) {
        return;
    }

    $screen =
        get_current_screen();

    if (
        !$screen ||
        $screen->post_type !== 'exam'
    ) {
        return;
    }

    ?>

    <div class="notice notice-success is-dismissible">
        <p>
            آزمون، سوالات، بارم‌بندی و زمان‌بندی آن با موفقیت از فایل Word ایجاد شدند.
        </p>
    </div>

    <?php
}

add_action(
    'admin_notices',
    'online_exam_import_success_notice'
);
