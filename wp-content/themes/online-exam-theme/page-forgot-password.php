<?php
/*
Template Name: فراموشی رمز عبور
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
| Page State
|--------------------------------------------------------------------------
*/

$message       = '';
$message_type  = '';
$account_value = '';


/*
|--------------------------------------------------------------------------
| Current Page ID
|--------------------------------------------------------------------------
*/

$page_id =
    get_queried_object_id();


/*
|--------------------------------------------------------------------------
| Handle Reset Request
|--------------------------------------------------------------------------
*/

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' &&
    isset($_POST['online_exam_forgot_password'])
) {

    /*
    |--------------------------------------------------------------------------
    | Nonce
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_POST['online_exam_forgot_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['online_exam_forgot_nonce']
                )
            ),
            'online_exam_forgot_action'
        )
    ) {

        $message =
            'درخواست معتبر نیست. لطفاً دوباره تلاش کنید.';

        $message_type =
            'error';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Username / Email
        |--------------------------------------------------------------------------
        */

        $account_value =
            isset($_POST['user_login'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['user_login']
                    )
                )
                : '';


        if ($account_value === '') {

            $message =
                'نام کاربری یا ایمیل خود را وارد کنید.';

            $message_type =
                'error';

        } else {

            /*
            |--------------------------------------------------------------------------
            | WordPress Password Reset
            |--------------------------------------------------------------------------
            */

            $reset_result =
                retrieve_password(
                    $account_value
                );


            /*
             * برای امنیت، مشخص نمی‌کنیم که کاربر
             * حتماً در سیستم وجود دارد یا خیر.
             */

            if (is_wp_error($reset_result)) {

                /*
                 * فقط خطاهای فنی واقعی را جدا می‌کنیم.
                 * خطای کاربر ناموجود هم پاسخ عمومی می‌گیرد.
                 */

                $error_code =
                    $reset_result
                        ->get_error_code();


                if (
                    in_array(
                        $error_code,
                        array(
                            'invalidcombo',
                            'invalid_email',
                            'invalid_username',
                        ),
                        true
                    )
                ) {

                    $message =
                        'اگر حساب کاربری با این اطلاعات وجود داشته باشد، لینک بازیابی رمز عبور برای آن ارسال خواهد شد.';

                    $message_type =
                        'success';

                } else {

                    $message =
                        'ارسال لینک بازیابی با مشکل مواجه شد. لطفاً کمی بعد دوباره تلاش کنید.';

                    $message_type =
                        'error';
                }

            } else {

                $message =
                    'لینک بازیابی رمز عبور برای شما ارسال شد. ایمیل خود را بررسی کنید.';

                $message_type =
                    'success';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Global Branding
|--------------------------------------------------------------------------
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
                        بازیابی حساب کاربری
                    </span>


                    <h1>
                        فراموشی رمز عبور
                    </h1>


                    <p>
                        نام کاربری یا ایمیل حساب خود را وارد کنید تا لینک بازیابی رمز عبور برای شما ارسال شود.
                    </p>

                </div>


                <!-- =================================================
                     Message
                ================================================== -->

                <?php if ($message !== '') : ?>

                    <div
                        class="auth-message auth-message-<?php echo esc_attr(
                            $message_type
                        ); ?>"
                        role="alert"
                        style="
                            margin-bottom: 20px;
                            padding: 13px 15px;
                            border-radius: 8px;
                            line-height: 1.8;

                            <?php if ($message_type === 'success') : ?>

                                background: #ecfdf3;
                                color: #067647;

                            <?php else : ?>

                                background: #fff1f1;
                                color: #b42318;

                            <?php endif; ?>
                        "
                    >

                        <?php
                        echo esc_html(
                            $message
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     Reset Form
                ================================================== -->

                <form
                    class="auth-form"
                    method="post"
                    action=""
                >

                    <?php

                    wp_nonce_field(
                        'online_exam_forgot_action',
                        'online_exam_forgot_nonce'
                    );

                    ?>


                    <div class="form-group">

                        <label for="user_login">
                            نام کاربری یا ایمیل
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
                                id="user_login"
                                name="user_login"
                                value="<?php echo esc_attr(
                                    $account_value
                                ); ?>"
                                placeholder="نام کاربری یا ایمیل خود را وارد کنید"
                                autocomplete="username"
                                required
                            >

                        </div>

                    </div>


                    <button
                        type="submit"
                        name="online_exam_forgot_password"
                        value="1"
                        class="auth-submit"
                    >

                        ارسال لینک بازیابی

                        <svg
                            viewBox="0 0 24 24"
                            width="20"
                            height="20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path d="M5 12h14" />

                            <path d="m13 6 6 6-6 6" />

                        </svg>

                    </button>

                </form>


                <!-- =================================================
                     Back To Login
                ================================================== -->

                <p
                    class="auth-help"
                    style="margin-top: 22px;"
                >

                    رمز عبور خود را به خاطر آوردید؟

                    <a
                        href="<?php echo esc_url(
                            home_url('/login/')
                        ); ?>"
                    >
                        بازگشت به صفحه ورود
                    </a>

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


                <h2>
                    بازیابی سریع و امن
                    <br>
                    حساب کاربری
                </h2>


                <p>
                    برای بازیابی دسترسی به سامانه، اطلاعات حساب خود را وارد کنید.
                </p>


                <div class="auth-feature-list">

                    <div class="auth-feature">

                        <span class="feature-check">
                            ✓
                        </span>

                        <span>
                            ارسال لینک امن بازیابی
                        </span>

                    </div>


                    <div class="auth-feature">

                        <span class="feature-check">
                            ✓
                        </span>

                        <span>
                            تعیین رمز عبور جدید
                        </span>

                    </div>


                    <div class="auth-feature">

                        <span class="feature-check">
                            ✓
                        </span>

                        <span>
                            بازگشت سریع به سامانه
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </section>

</main>


<?php
get_footer();
?>