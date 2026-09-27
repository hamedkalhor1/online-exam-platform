<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Exam User Helper
|--------------------------------------------------------------------------
|
| در این سامانه نقش اختصاصی برای کارشناس تعریف نمی‌کنیم.
| هر کاربری که دسترسی مدیریتی manage_options نداشته باشد،
| به عنوان کارشناس سامانه در نظر گرفته می‌شود.
|
*/

function online_exam_is_exam_user($user_id = 0)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $user_id = absint($user_id);

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return false;
    }

    return !user_can(
        $user,
        'manage_options'
    );
}


/*
|--------------------------------------------------------------------------
| Block Non-admin Users From wp-admin
|--------------------------------------------------------------------------
*/

function online_exam_block_exam_user_admin()
{
    if (!is_user_logged_in()) {
        return;
    }

    /*
     * AJAX باید همچنان قابل استفاده باشد.
     */
    if (
        defined('DOING_AJAX') &&
        DOING_AJAX
    ) {
        return;
    }

    /*
     * مدیران محدود نمی‌شوند.
     */
    if (current_user_can('manage_options')) {
        return;
    }

    /*
     * همه کاربران عادی سامانه = کارشناس
     */
    wp_safe_redirect(
        home_url('/my-exams/')
    );

    exit;
}

add_action(
    'admin_init',
    'online_exam_block_exam_user_admin'
);


/*
|--------------------------------------------------------------------------
| Hide Admin Bar For Exam Users
|--------------------------------------------------------------------------
*/

function online_exam_hide_exam_user_admin_bar($show)
{
    if (
        is_user_logged_in() &&
        !current_user_can('manage_options')
    ) {
        return false;
    }

    return $show;
}

add_filter(
    'show_admin_bar',
    'online_exam_hide_exam_user_admin_bar'
);


/*
|--------------------------------------------------------------------------
| Login Redirect
|--------------------------------------------------------------------------
*/

function online_exam_user_login_redirect(
    $redirect_to,
    $requested_redirect_to,
    $user
) {
    if (
        $user instanceof WP_User &&
        !user_can($user, 'manage_options')
    ) {
        return home_url('/my-exams/');
    }

    return $redirect_to;
}

add_filter(
    'login_redirect',
    'online_exam_user_login_redirect',
    10,
    3
);


/*
|--------------------------------------------------------------------------
| Logout Redirect
|--------------------------------------------------------------------------
*/

function online_exam_user_logout_redirect(
    $redirect_to,
    $requested_redirect_to,
    $user
) {
    if (
        $user instanceof WP_User &&
        !user_can($user, 'manage_options')
    ) {
        return home_url('/login/');
    }

    return $redirect_to;
}

add_filter(
    'logout_redirect',
    'online_exam_user_logout_redirect',
    10,
    3
);


/*
|--------------------------------------------------------------------------
| Hide Unused User Fields
|--------------------------------------------------------------------------
|
| Website و Language برای کاربران سامانه کاربردی ندارند.
|
*/

function online_exam_hide_unused_user_fields()
{
    ?>
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const websiteField =
                    document.getElementById('url');

                if (websiteField) {
                    const row =
                        websiteField.closest('tr');

                    if (row) {
                        row.style.display = 'none';
                    }
                }


                const languageField =
                    document.getElementById('locale');

                if (languageField) {
                    const row =
                        languageField.closest('tr');

                    if (row) {
                        row.style.display = 'none';
                    }
                }

            }
        );
    </script>
    <?php
}


add_action(
    'admin_footer-user-new.php',
    'online_exam_hide_unused_user_fields'
);

add_action(
    'admin_footer-user-edit.php',
    'online_exam_hide_unused_user_fields'
);

add_action(
    'admin_footer-profile.php',
    'online_exam_hide_unused_user_fields'
);
