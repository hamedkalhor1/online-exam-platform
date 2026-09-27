<?php
/*
Template Name: ورود دانشجو
*/

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Redirect Logged-in Students
|--------------------------------------------------------------------------
*/

if (
    is_user_logged_in() &&
    !current_user_can('manage_options')
) {
    wp_safe_redirect(
        home_url('/my-exams/')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
*/

$login_error    = '';
$username_value = '';
$remember_value = false;


/*
|--------------------------------------------------------------------------
| Current Login Page ID
|--------------------------------------------------------------------------
*/

$login_page_id =
    get_queried_object_id();


/*
 * Fallback
 */

if (!$login_page_id) {

    $login_page =
        get_page_by_path('login');


    if ($login_page) {

        $login_page_id =
            absint(
                $login_page->ID
            );
    }
}


/*
|--------------------------------------------------------------------------
| Login Page ACF Helper
|--------------------------------------------------------------------------
*/

$get_login_field =
    function (
        $field_name,
        $default = ''
    ) use ($login_page_id) {

        if (
            !$login_page_id ||
            !function_exists('get_field')
        ) {
            return $default;
        }


        $value =
            get_field(
                $field_name,
                $login_page_id
            );


        if (
            $value === false ||
            $value === null ||
            $value === ''
        ) {
            return $default;
        }


        return $value;
    };


/*
|--------------------------------------------------------------------------
| Global Site Content
|--------------------------------------------------------------------------
|
| این دو مورد همچنان از تنظیمات عمومی سامانه می‌آیند.
|
*/

$site_title =
    function_exists('online_exam_get_option')
        ? online_exam_get_option(
            'site_title',
            'سامانه آزمون آنلاین'
        )
        : 'سامانه آزمون آنلاین';


$site_logo_text =
    function_exists('online_exam_get_option')
        ? online_exam_get_option(
            'site_logo_text',
            'آ'
        )
        : 'آ';


/*
|--------------------------------------------------------------------------
| Login Page ACF Content
|--------------------------------------------------------------------------
|
| این موارد مستقیماً از خود برگه ورود خوانده می‌شوند.
|
*/

$login_eyebrow =
    $get_login_field(
        'login_eyebrow',
        'ورود دانشجویان'
    );


$login_title =
    $get_login_field(
        'login_title',
        'ورود به سامانه'
    );


$login_description =
    $get_login_field(
        'login_description',
        'برای مشاهده و شرکت در آزمون‌های خود، وارد حساب کاربری شوید.'
    );


$login_help_text =
    $get_login_field(
        'login_help_text',
        'در صورت بروز مشکل در ورود به سامانه، با مدیر سامانه تماس بگیرید.'
    );


$login_visual_title =
    $get_login_field(
        'login_visual_title',
        'آزمون‌های خود را ساده و مطمئن برگزار کنید'
    );


$login_visual_description =
    $get_login_field(
        'login_visual_description',
        'محیطی سریع، ساده و قابل اعتماد برای شرکت در آزمون‌های آنلاین.'
    );


/*
|--------------------------------------------------------------------------
| Login Features Repeater
|--------------------------------------------------------------------------
*/

$login_features =
    array();


if (
    $login_page_id &&
    function_exists('get_field')
) {

    $acf_features =
        get_field(
            'login_features',
            $login_page_id
        );


    if (is_array($acf_features)) {

        foreach (
            $acf_features
            as $feature
        ) {

            if (
                !empty(
                    $feature[
                        'feature_text'
                    ]
                )
            ) {

                $login_features[] =
                    $feature[
                        'feature_text'
                    ];
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Default Features
|--------------------------------------------------------------------------
*/

if (empty($login_features)) {

    $login_features =
        array(
            'دسترسی سریع به آزمون‌ها',
            'محیط ساده و کاربرپسند',
            'مشاهده آسان نتایج',
        );
}


/*
|--------------------------------------------------------------------------
| Handle Login
|--------------------------------------------------------------------------
*/

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' &&
    isset($_POST['online_exam_login'])
) {

    /*
    |--------------------------------------------------------------------------
    | Preserve Values
    |--------------------------------------------------------------------------
    */

    $username_value =
        isset($_POST['username'])
            ? sanitize_user(
                wp_unslash(
                    $_POST['username']
                )
            )
            : '';


    $remember_value =
        !empty(
            $_POST['remember']
        );


    /*
    |--------------------------------------------------------------------------
    | Nonce
    |--------------------------------------------------------------------------
    */

    if (
        !isset(
            $_POST[
                'online_exam_login_nonce'
            ]
        ) ||
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST[
                        'online_exam_login_nonce'
                    ]
                )
            ),
            'online_exam_login_action'
        )
    ) {

        $login_error =
            'درخواست ورود معتبر نیست. لطفاً دوباره تلاش کنید.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Password
        |--------------------------------------------------------------------------
        */

        $password =
            isset($_POST['password'])
                ? (string) wp_unslash(
                    $_POST['password']
                )
                : '';


        /*
        |--------------------------------------------------------------------------
        | Required Fields
        |--------------------------------------------------------------------------
        */

        if (
            $username_value === '' ||
            $password === ''
        ) {

            $login_error =
                'نام کاربری و رمز عبور را وارد کنید.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | WordPress Authentication
            |--------------------------------------------------------------------------
            */

            $credentials =
                array(

                    'user_login' =>
                        $username_value,

                    'user_password' =>
                        $password,

                    'remember' =>
                        $remember_value,
                );


            $user =
                wp_signon(
                    $credentials,
                    is_ssl()
                );


            /*
            |--------------------------------------------------------------------------
            | Login Failed
            |--------------------------------------------------------------------------
            */

            if (is_wp_error($user)) {

                $login_error =
                    'نام کاربری یا رمز عبور صحیح نیست.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | Login Successful
                |--------------------------------------------------------------------------
                */

                wp_safe_redirect(
                    home_url(
                        '/my-exams/'
                    )
                );

                exit;
            }
        }
    }
}


get_header();
?>


<main class="auth-page">

    <section class="auth-wrapper">


        <!-- =========================================================
             Form Side
        ========================================================== -->

        <div class="auth-form-side">

            <div class="auth-form-container">


                <!-- =================================================
                     Mobile Brand
                ================================================== -->

                <div class="auth-mobile-brand">

                    <div class="auth-mobile-logo">

                        <span>

                            <?php
                            echo esc_html(
                                $site_logo_text
                            );
                            ?>

                        </span>

                    </div>


                    <span>

                        <?php
                        echo esc_html(
                            $site_title
                        );
                        ?>

                    </span>

                </div>


                <!-- =================================================
                     Heading
                ================================================== -->

                <div class="auth-heading">

                    <span class="auth-eyebrow">

                        <?php
                        echo esc_html(
                            $login_eyebrow
                        );
                        ?>

                    </span>


                    <h1>

                        <?php
                        echo esc_html(
                            $login_title
                        );
                        ?>

                    </h1>


                    <p>

                        <?php
                        echo nl2br(
                            esc_html(
                                $login_description
                            )
                        );
                        ?>

                    </p>

                </div>


                <!-- =================================================
                     Login Error
                ================================================== -->

                <?php if (
                    $login_error !== ''
                ) : ?>

                    <div
                        class="auth-error"
                        role="alert"
                        style="
                            margin-bottom: 20px;
                            padding: 12px 15px;
                            border-radius: 8px;
                            background: #fff1f1;
                            color: #b42318;
                            font-size: 14px;
                            line-height: 1.8;
                        "
                    >

                        <?php
                        echo esc_html(
                            $login_error
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     Login Form
                ================================================== -->

                <form
                    class="auth-form"
                    method="post"
                    action=""
                >

                    <?php

                    wp_nonce_field(
                        'online_exam_login_action',
                        'online_exam_login_nonce'
                    );

                    ?>


                    <!-- =============================================
                         Username
                    ============================================== -->

                    <div class="form-group">

                        <label for="username">
                            نام کاربری
                        </label>


                        <div class="input-wrapper">

                            <span class="input-icon">

                                <svg
                                    viewBox="0 0 24 24"
                                    width="21"
                                    height="21"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >

                                    <path
                                        d="M20 21a8 8 0 0 0-16 0"
                                    />

                                    <circle
                                        cx="12"
                                        cy="7"
                                        r="4"
                                    />

                                </svg>

                            </span>


                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?php echo esc_attr(
                                    $username_value
                                ); ?>"
                                placeholder="نام کاربری خود را وارد کنید"
                                autocomplete="off"
                                required
                            >

                        </div>

                    </div>


                    <!-- =============================================
                         Password
                    ============================================== -->

                    <div class="form-group">

                        <div class="form-label-row">

                            <label for="password">
                                رمز عبور
                            </label>

                        </div>


                        <div class="input-wrapper">

                            <span class="input-icon">

                                <svg
                                    viewBox="0 0 24 24"
                                    width="21"
                                    height="21"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >

                                    <rect
                                        x="4"
                                        y="10"
                                        width="16"
                                        height="11"
                                        rx="2"
                                    />

                                    <path
                                        d="M8 10V7a4 4 0 0 1 8 0v3"
                                    />

                                </svg>

                            </span>


                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="رمز عبور خود را وارد کنید"
                                autocomplete="current-password"
                                required
                            >


                            <button
                                type="button"
                                class="password-toggle"
                                aria-label="نمایش رمز عبور"
                                data-password-toggle
                            >

                                <svg
                                    class="eye-show"
                                    viewBox="0 0 24 24"
                                    width="20"
                                    height="20"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >

                                    <path
                                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                                    />

                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="3"
                                    />

                                </svg>

                            </button>

                        </div>

                    </div>


                    <!-- =============================================
                         Options
                    ============================================== -->

                    <div class="auth-options">

                        <label class="remember-me">

                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                <?php
                                checked(
                                    $remember_value,
                                    true
                                );
                                ?>
                            >

                            <span>
                                مرا به خاطر بسپار
                            </span>

                        </label>


                        <a
                            href="<?php
                            echo esc_url(
                                home_url(
                                    '/forgot-password/'
                                )
                            );
                            ?>"
                            class="forgot-password"
                        >

                            فراموشی رمز عبور

                        </a>

                    </div>


                    <!-- =============================================
                         Submit
                    ============================================== -->

                    <button
                        type="submit"
                        name="online_exam_login"
                        value="1"
                        class="auth-submit"
                    >

                        ورود به سامانه


                        <svg
                            viewBox="0 0 24 24"
                            width="20"
                            height="20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path
                                d="M5 12h14"
                            />

                            <path
                                d="m13 6 6 6-6 6"
                            />

                        </svg>

                    </button>

                </form>


                <!-- =================================================
                     Help
                ================================================== -->

                <p class="auth-help">

                    <?php
                    echo nl2br(
                        esc_html(
                            $login_help_text
                        )
                    );
                    ?>

                </p>

            </div>

        </div>


        <!-- =========================================================
             Visual Side
        ========================================================== -->

        <div class="auth-visual-side">

            <div
                class="
                    auth-decoration
                    auth-decoration-one
                "
            ></div>


            <div
                class="
                    auth-decoration
                    auth-decoration-two
                "
            ></div>


            <div class="auth-visual-content">


                <!-- =================================================
                     Brand
                ================================================== -->

                <div class="brand-logo">

                    <span>

                        <?php
                        echo esc_html(
                            $site_logo_text
                        );
                        ?>

                    </span>

                </div>


                <div class="brand-name">

                    <?php
                    echo esc_html(
                        $site_title
                    );
                    ?>

                </div>


                <!-- =================================================
                     Visual Heading
                ================================================== -->

                <h2>

                    <?php
                    echo nl2br(
                        esc_html(
                            $login_visual_title
                        )
                    );
                    ?>

                </h2>


                <p>

                    <?php
                    echo nl2br(
                        esc_html(
                            $login_visual_description
                        )
                    );
                    ?>

                </p>


                <!-- =================================================
                     Feature List
                ================================================== -->

                <div class="auth-feature-list">

                    <?php foreach (
                        $login_features
                        as $feature_text
                    ) : ?>

                        <div class="auth-feature">

                            <span class="feature-check">
                                ✓
                            </span>


                            <span>

                                <?php
                                echo esc_html(
                                    $feature_text
                                );
                                ?>

                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    </section>

</main>


<?php
get_footer();
?>