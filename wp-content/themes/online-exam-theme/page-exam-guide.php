<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

get_template_part(
    'template-parts/public-header'
);


// =============================
// ACF DATA
// =============================

$guide_title = get_field('guide_title')
    ?: 'راهنمای شرکت در آزمون آنلاین';


$exam_steps = get_field('exam_steps');


?>


<main class="exam-guide-page">



<!-- =========================================================
     HERO
========================================================== -->


<section class="guide-hero">


<div class="public-container">


<div class="guide-hero-content">


<span class="guide-eyebrow">
راهنمای شرکت در آزمون
</span>



<h1>

<?php echo esc_html($guide_title); ?>

</h1>



<p>
پیش از شروع آزمون، مراحل شرکت را به ترتیب مطالعه کنید
تا بدون مشکل آزمون خود را آغاز کنید.
</p>



</div>


</div>


</section>






<!-- =========================================================
     STEPS
========================================================== -->


<section class="guide-steps-section">


<div class="public-container">


<div class="guide-section-heading">


<span>
مراحل شرکت در آزمون
</span>


<h2>
از ورود تا ثبت نهایی
</h2>


<p>
برای شرکت در آزمون کافی است مراحل زیر را به ترتیب انجام دهید.
</p>


</div>





<div class="guide-steps">



<?php if (!empty($exam_steps)) : ?>



<?php $step_number = 1; ?>



<?php foreach ($exam_steps as $step) : ?>



<article class="guide-step">



<div class="guide-step-number">

<?php echo esc_html($step_number); ?>

</div>





<div class="guide-step-icon">


<svg viewBox="0 0 24 24">

<circle cx="12" cy="12" r="9"></circle>

<path d="m8 12 3 3 5-6"></path>

</svg>


</div>





<div class="guide-step-content">


<h3>

<?php

echo esc_html(
    $step['step_title']
);

?>

</h3>



</div>




</article>





<?php $step_number++; ?>



<?php endforeach; ?>



<?php endif; ?>





</div>



</div>


</section>








<!-- =========================================================
     IMPORTANT RULES
========================================================== -->


<section class="guide-rules-section">


<div class="public-container">


<div class="guide-rules-box">


<div class="guide-rules-header">


<div class="guide-rules-icon">


<svg viewBox="0 0 24 24">

<path d="M12 3 3 20h18L12 3Z"></path>

<path d="M12 9v4"></path>

<path d="M12 17h.01"></path>

</svg>


</div>



<div>

<span>
قبل از شروع بخوانید
</span>


<h2>
نکات مهم آزمون
</h2>


</div>


</div>





<div class="guide-rules-grid">



<div class="guide-rule">

<span class="guide-rule-check">
✓
</span>


<div>

<strong>
اینترنت پایدار
</strong>


<p>
پیش از شروع از اتصال مناسب اینترنت دستگاه خود مطمئن شوید.
</p>


</div>


</div>





<div class="guide-rule">

<span class="guide-rule-check">
✓
</span>


<div>

<strong>
زمان آزمون
</strong>


<p>
زمان آزمون از لحظه شروع محاسبه می‌شود.
</p>


</div>


</div>





<div class="guide-rule">

<span class="guide-rule-check">
✓
</span>


<div>

<strong>
ثبت نهایی
</strong>


<p>
پس از پایان آزمون از ثبت موفق آن اطمینان حاصل کنید.
</p>


</div>


</div>




</div>



</div>


</div>


</section>








<!-- =========================================================
     CTA
========================================================== -->


<section class="guide-cta-section">


<div class="public-container">


<div class="guide-cta">


<div>


<span>
آماده شرکت در آزمون هستید؟
</span>



<h2>
وارد سامانه سعادت آزمون شوید
</h2>



<p>
برای مشاهده آزمون فعال خود وارد حساب کاربری شوید.
</p>



</div>





<a
href="<?php echo esc_url(home_url('/login/')); ?>"
class="guide-login-button"
>

ورود به سامانه

<span>
←
</span>


</a>



</div>



</div>


</section>




</main>





<?php

get_template_part(
    'template-parts/public-footer'
);


get_footer();

?>