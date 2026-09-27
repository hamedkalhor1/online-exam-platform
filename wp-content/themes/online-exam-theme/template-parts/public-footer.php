<?php

if (!defined('ABSPATH')) {
    exit;
}


// =============================
// FOOTER ACF OPTIONS DATA
// =============================

$footer_description =
    get_field('footer_description','option')
    ?: 'سامانه آنلاین برگزاری آزمون و ارائه منابع آموزشی';


$footer_links =
    get_field('footer_links','option');


$footer_exam_links =
    get_field('footer_exam_links','option');


$footer_phone =
    get_field('footer_phone','option');


$footer_email =
    get_field('footer_email','option');


$footer_address =
    get_field('footer_address','option');


$footer_copyright =
    get_field('footer_copyright','option')
    ?: 'تمامی حقوق برای سعادت آزمون محفوظ است';


?>



<footer class="public-footer" id="publicContact">


<div class="public-container">


<div class="public-footer-grid">



<!-- Brand -->

<div class="public-footer-about">


<a
href="<?php echo esc_url(home_url('/')); ?>"
class="public-brand public-footer-brand"
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


<p>
<?php echo esc_html($footer_description); ?>
</p>


</div>





<!-- Quick Links -->

<div class="public-footer-column">


<h3>
دسترسی سریع
</h3>


<?php if($footer_links): ?>

<?php foreach($footer_links as $link): ?>


<a href="<?php echo esc_url($link['url']); ?>">

<?php echo esc_html($link['title']); ?>

</a>


<?php endforeach; ?>

<?php endif; ?>


</div>





<!-- Exam -->

<div class="public-footer-column">


<h3>
سامانه آزمون
</h3>


<?php if($footer_exam_links): ?>


<?php foreach($footer_exam_links as $link): ?>


<a href="<?php echo esc_url($link['url']); ?>">

<?php echo esc_html($link['title']); ?>

</a>


<?php endforeach; ?>


<?php endif; ?>


</div>





<!-- Contact -->


<div class="public-footer-column">


<h3>
ارتباط با ما
</h3>


<?php if($footer_address): ?>

<p>
📍 <?php echo esc_html($footer_address); ?>
</p>

<?php endif; ?>


<?php if($footer_phone): ?>

<p>
📞 <?php echo esc_html($footer_phone); ?>
</p>

<?php endif; ?>


<?php if($footer_email): ?>

<p>
✉ <?php echo esc_html($footer_email); ?>
</p>

<?php endif; ?>


</div>



</div>





<div class="public-footer-bottom">


<span>
<?php echo esc_html($footer_copyright); ?>
</span>


<span>
<?php echo esc_html(wp_date('Y')); ?>
</span>


</div>



</div>


</footer>