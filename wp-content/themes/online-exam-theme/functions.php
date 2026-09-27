<?php

if (!defined('ABSPATH')) {
    exit;
}
/*
|--------------------------------------------------------------------------
| Backend Includes
|--------------------------------------------------------------------------
*/
require_once get_template_directory() . '/inc/auth.php';
require_once get_template_directory() . '/inc/database.php';
require_once get_template_directory() . '/inc/admin.php';
require_once get_template_directory() . '/inc/exam.php';
require_once get_template_directory() . '/inc/acf.php';
require_once get_template_directory() . '/inc/grading.php';
require_once get_template_directory() . '/inc/users.php';
require_once get_template_directory() . '/inc/timeout.php';
require_once get_template_directory() . '/inc/cleanup.php';


register_nav_menus(
    array(
        'primary_menu' => 'منوی اصلی سایت',
    )
);
/*
|--------------------------------------------------------------------------
| Theme Setup
|--------------------------------------------------------------------------
*/

function online_exam_theme_setup()
{
    add_theme_support('title-tag');
}

add_action(
    'after_setup_theme',
    'online_exam_theme_setup'
);


/*
|--------------------------------------------------------------------------
| Hide Admin Bar For Non Admin Users
|--------------------------------------------------------------------------
*/

function online_exam_hide_admin_bar()
{
    if (!current_user_can('manage_options')) {
        show_admin_bar(false);
    }
}

add_action(
    'after_setup_theme',
    'online_exam_hide_admin_bar'
);
add_theme_support('post-thumbnails');


/*
|--------------------------------------------------------------------------
| Asset Version
|--------------------------------------------------------------------------
|
| با هر Save فایل CSS/JS نسخه تغییر می‌کند و Cache مرورگر مشکل ایجاد نمی‌کند.
|
*/

function online_exam_asset_version($relative_path)
{
    $file_path =
        get_stylesheet_directory() .
        $relative_path;

    if (file_exists($file_path)) {
        return filemtime($file_path);
    }

    return time();
}


/*
|--------------------------------------------------------------------------
| Load Theme Assets
|--------------------------------------------------------------------------
*/

function online_exam_enqueue_assets()
{
    $theme_uri =
        get_stylesheet_directory_uri();


    /*
    |--------------------------------------------------------------------------
    | Vazirmatn Font
    |--------------------------------------------------------------------------
    |
    | روی تمام صفحات استفاده می‌شود.
    |
    */

    wp_enqueue_style(
        'vazirmatn-font',
        'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap',
        array(),
        null
    );


    /*
    |--------------------------------------------------------------------------
    | Global CSS
    |--------------------------------------------------------------------------
    |
    | روی تمام صفحات لود می‌شود.
    |
    */

    wp_enqueue_style(
        'online-exam-global',
        $theme_uri . '/assets/css/global.css',
        array(
            'vazirmatn-font'
        ),
        online_exam_asset_version(
            '/assets/css/global.css'
        )
    );

    /*
|--------------------------------------------------------------------------
| Public Website
|--------------------------------------------------------------------------
*/

if (
    is_front_page() ||
    is_page('books-notes') ||
    is_page('media') ||
    is_page('exam-guide')||
    is_page('contact')
) {

    wp_enqueue_style(
        'online-exam-public',
        $theme_uri . '/assets/css/public.css',
        array(
            'online-exam-global'
        ),
        online_exam_asset_version(
            '/assets/css/public.css'
        )
    );


    wp_enqueue_script(
        'online-exam-public',
        $theme_uri . '/assets/js/public.js',
        array(),
        online_exam_asset_version(
            '/assets/js/public.js'
        ),
        false
    );
}
/*
|--------------------------------------------------------------------------
| Contact
|--------------------------------------------------------------------------
*/

if (is_page('contact')) {

    wp_enqueue_style(
        'online-exam-contact',
        $theme_uri . '/assets/css/contact.css',
        array(
            'online-exam-global',
            'online-exam-public'
        ),
        online_exam_asset_version(
            '/assets/css/contact.css'
        )
    );
}
/*
|--------------------------------------------------------------------------
| Books & Notes
|--------------------------------------------------------------------------
*/

if (is_page('books-notes')) {

    wp_enqueue_style(
        'online-exam-resources',
        $theme_uri . '/assets/css/resources.css',
        array(
            'online-exam-global',
            'online-exam-public'
        ),
        online_exam_asset_version(
            '/assets/css/resources.css'
        )
    );


    wp_enqueue_script(
        'online-exam-resources',
        $theme_uri . '/assets/js/resources.js',
        array(),
        online_exam_asset_version(
            '/assets/js/resources.js'
        ),
        false
    );
}
/*
|--------------------------------------------------------------------------
| Media
|--------------------------------------------------------------------------
*/

if (is_page('media')) {

    wp_enqueue_style(
        'online-exam-media',
        $theme_uri . '/assets/css/media.css',
        array(
            'online-exam-global',
            'online-exam-public'
        ),
        online_exam_asset_version(
            '/assets/css/media.css'
        )
    );


    wp_enqueue_script(
        'online-exam-media',
        $theme_uri . '/assets/js/media.js',
        array(),
        online_exam_asset_version(
            '/assets/js/media.js'
        ),
        false
    );

}
/*
|--------------------------------------------------------------------------
| Exam Guide
|--------------------------------------------------------------------------
*/

if (is_page('exam-guide')) {

    wp_enqueue_style(
        'online-exam-exam-guide',
        $theme_uri . '/assets/css/exam-guide.css',
        array(
            'online-exam-global',
            'online-exam-public'
        ),
        online_exam_asset_version(
            '/assets/css/exam-guide.css'
        )
    );
}
if (is_page_template('page-exam-intro.php')) {

    wp_enqueue_style(
        'online-exam-intro',
        get_template_directory_uri() . '/assets/css/exam-intro.css',
        array(),
        filemtime(
            get_template_directory() . '/assets/css/exam-intro.css'
        )
    );
}
/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
*/

if (is_front_page()) {

    wp_enqueue_style(
        'online-exam-home',
        $theme_uri . '/assets/css/home.css',
        array(
            'online-exam-global',
            'online-exam-public'
        ),
        online_exam_asset_version(
            '/assets/css/home.css'
        )
    );
}

    /*
    |--------------------------------------------------------------------------
    | Login + Forgot Password
    |--------------------------------------------------------------------------
    */

    if (
        is_page('login') ||
        is_page('forgot-password')
    ) {

        wp_enqueue_style(
            'online-exam-auth',
            $theme_uri . '/assets/css/auth.css',
            array(
                'online-exam-global'
            ),
            online_exam_asset_version(
                '/assets/css/auth.css'
            )
        );


        wp_enqueue_script(
            'online-exam-main',
            $theme_uri . '/assets/js/main.js',
            array(),
            online_exam_asset_version(
                '/assets/js/main.js'
            ),
            false
        );
    }


    /*
    |--------------------------------------------------------------------------
    | My Exams
    |--------------------------------------------------------------------------
    */

    if (is_page('my-exams')) {

        wp_enqueue_style(
            'online-exam-exams',
            $theme_uri . '/assets/css/exams.css',
            array(
                'online-exam-global'
            ),
            online_exam_asset_version(
                '/assets/css/exams.css'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Exam Intro
    |--------------------------------------------------------------------------
    */

    if (is_page('exam-intro')) {

        wp_enqueue_style(
            'online-exam-exams',
            $theme_uri . '/assets/css/exams.css',
            array(
                'online-exam-global'
            ),
            online_exam_asset_version(
                '/assets/css/exams.css'
            )
        );


        wp_enqueue_style(
            'online-exam-intro',
            $theme_uri . '/assets/css/exam-intro.css',
            array(
                'online-exam-global',
                'online-exam-exams'
            ),
            online_exam_asset_version(
                '/assets/css/exam-intro.css'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Take Exam
    |--------------------------------------------------------------------------
    */

    if (is_page('take-exam')) {

        wp_enqueue_style(
            'online-exam-exam',
            $theme_uri . '/assets/css/exam.css',
            array(
                'online-exam-global'
            ),
            online_exam_asset_version(
                '/assets/css/exam.css'
            )
        );


        /*
        |----------------------------------------------------------------------
        | Exam JavaScript
        |----------------------------------------------------------------------
        |
        | false = داخل Head
        |
        | چون قبلاً روی Footer مشکل داشتیم فعلاً همین حالت را نگه می‌داریم.
        |
        */
/*
        wp_enqueue_script(
            'online-exam-exam',
            $theme_uri . '/assets/js/exam.js',
            array(),
            online_exam_asset_version(
                '/assets/js/exam.js'
            ),
            false
        );
*/

    }


    /*
    |--------------------------------------------------------------------------
    | Submit Success
    |--------------------------------------------------------------------------
    */

    if (is_page('submit-success')) {

        wp_enqueue_style(
            'online-exam-submit-success',
            $theme_uri . '/assets/css/submit-success.css',
            array(
                'online-exam-global'
            ),
            online_exam_asset_version(
                '/assets/css/submit-success.css'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Result
    |--------------------------------------------------------------------------
    */

    if (is_page('result')) {

        /*
        | Student Header
        */

        wp_enqueue_style(
            'online-exam-exams',
            $theme_uri . '/assets/css/exams.css',
            array(
                'online-exam-global'
            ),
            online_exam_asset_version(
                '/assets/css/exams.css'
            )
        );


        /*
        | Result Page
        */

        wp_enqueue_style(
            'online-exam-result',
            $theme_uri . '/assets/css/result.css',
            array(
                'online-exam-global',
                'online-exam-exams'
            ),
            online_exam_asset_version(
                '/assets/css/result.css'
            )
        );
    }
}

add_action(
    'wp_enqueue_scripts',
    'online_exam_enqueue_assets'
);
