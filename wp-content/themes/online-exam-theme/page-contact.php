<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

get_template_part('template-parts/public-header');

?>
<?php

$contact_address =
    get_field('contact_address')
    ?: 'تهران، ایران';


$contact_phone =
    get_field('contact_phone')
    ?: '021-xxxxxxx';


$contact_email =
    get_field('contact_email')
    ?: 'info@example.com';

?>

<main class="contact-page">


<div class="public-container">


<div class="contact-box">


<h1>
ارتباط با ما
</h1>


<p>
برای دریافت اطلاعات بیشتر، پشتیبانی و ارتباط با مجموعه سعادت آزمون با ما در تماس باشید.
</p>



<div class="contact-items">


<div class="contact-item">

<strong>
📍 آدرس
</strong>

<span>
<?php echo esc_html($contact_address); ?>
</span>

</div>



<div class="contact-item">

<strong>
📞 تلفن تماس
</strong>

<span>
<?php echo esc_html($contact_phone); ?>
</span>

</div>



<div class="contact-item">

<strong>
✉ ایمیل
</strong>

<span>
<?php echo esc_html($contact_email); ?>
</span>

</div>



</div>



</div>


</div>


</main>



<?php

get_template_part(
    'template-parts/public-footer'
);


get_footer();

?>