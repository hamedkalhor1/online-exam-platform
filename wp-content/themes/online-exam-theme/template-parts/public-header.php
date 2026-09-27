<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<header class="public-header">


<div class="public-container public-header-inner">


<!-- Logo -->

<a
href="<?php echo esc_url(home_url('/')); ?>"
class="public-brand"
>


<span class="public-logo">
س
</span>


<span class="public-brand-text">

<strong>
سعادت آزمون
</strong>


<small>
سامانه آزمون آنلاین
</small>


</span>


</a>





<!-- Navigation -->

<nav
class="public-navigation"
aria-label="منوی اصلی"
>


<?php


wp_nav_menu(
    array(

        'theme_location' => 'primary_menu',

        'container' => false,

        'menu_class' => '',

        'fallback_cb' => false,

        'depth' => 2,

    )
);


?>


</nav>





<!-- Actions -->

<div class="public-header-actions">


<?php if (is_user_logged_in()) : ?>


<a
href="<?php echo esc_url(home_url('/dashboard/')); ?>"
class="public-login-button"
>
پنل کاربری
</a>


<?php else : ?>


<a
href="<?php echo esc_url(home_url('/login/')); ?>"
class="public-login-button"
>
ورود به سامانه
</a>


<?php endif; ?>




<button
type="button"
class="public-menu-button"
id="publicMenuButton"
aria-label="نمایش منو"
aria-expanded="false"
>

<span></span>
<span></span>
<span></span>

</button>


</div>


</div>





<!-- Mobile Menu -->


<div
class="public-mobile-menu"
id="publicMobileMenu"
>


<?php


wp_nav_menu(
    array(

        'theme_location' => 'primary_menu',

        'container' => false,

        'menu_class' => '',

        'fallback_cb' => false,

        'depth' => 2,

    )
);


?>



<?php if (is_user_logged_in()) : ?>


<a
href="<?php echo esc_url(home_url('/dashboard/')); ?>"
class="mobile-login-link"
>
پنل کاربری
</a>


<?php else : ?>


<a
href="<?php echo esc_url(home_url('/login/')); ?>"
class="mobile-login-link"
>
ورود به سامانه
</a>


<?php endif; ?>


</div>



</header>