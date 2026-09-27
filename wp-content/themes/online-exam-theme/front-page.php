<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

get_template_part('template-parts/public-header');


// =============================
// ACF DATA
// =============================

$home_title =
    get_field('home_title')
    ?: 'سامانه آزمون آنلاین';


$home_brand_title =
    get_field('home_brand_title')
    ?: 'سعادت آزمون';


$home_description =
    get_field('home_description')
    ?: 'محیطی امن، سریع و مطمئن برای برگزاری آزمون‌های آنلاین';


$home_features =
    get_field('home_features');


$home_cards =
    get_field('home_cards');

?>

<main class="home-screen">

<div class="home-screen-inner">


<section class="home-main">


<!-- =========================================================
     Illustration
========================================================== -->

<div class="home-illustration">

<div class="illustration-bg"></div>


<div class="illustration-dots">

<?php for($i=0;$i<9;$i++): ?>

<span></span>

<?php endfor; ?>

</div>



<div class="home-laptop">


<div class="laptop-screen">

<div class="laptop-question-line line-one"></div>
<div class="laptop-question-line line-two"></div>


<div class="laptop-answer">
<span>A</span>
<i></i>
</div>


<div class="laptop-answer selected">
<span>B</span>
<i></i>
</div>


<div class="laptop-answer">
<span>C</span>
<i></i>
</div>


<div class="laptop-answer">
<span>D</span>
<i></i>
</div>


</div>


<div class="laptop-keyboard">

<div class="keyboard-lines">

<?php for($i=0;$i<28;$i++): ?>

<span></span>

<?php endfor; ?>

</div>


<div class="touchpad"></div>


</div>


</div>



<div class="home-books">

<div class="book book-blue"></div>
<div class="book book-yellow"></div>

</div>



<div class="home-plant">

<div class="plant-leaf leaf-one"></div>
<div class="plant-leaf leaf-two"></div>
<div class="plant-leaf leaf-three"></div>

<div class="plant-pot"></div>

</div>


</div>



<!-- =========================================================
     Content
========================================================== -->


<div class="home-content">


<h1>
<?php echo esc_html($home_title); ?>
</h1>


<h2>
<?php echo esc_html($home_brand_title); ?>
</h2>



<p class="home-description">

<?php

echo nl2br(
    esc_html($home_description)
);

?>

</p>



<!-- FEATURES -->

<div class="home-mini-features">


<?php if(!empty($home_features)): ?>


<?php foreach($home_features as $index=>$feature): ?>


<div class="mini-feature">


<span class="mini-icon">

<svg viewBox="0 0 24 24">

<path d="M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6l-7-3Z"/>

</svg>

</span>



<div>


<strong>

<?php echo esc_html(
    $feature['title']
); ?>

</strong>


<small>

<?php echo esc_html(
    $feature['description']
); ?>

</small>


</div>


</div>



<?php if($index < count($home_features)-1): ?>

<div class="mini-divider"></div>

<?php endif; ?>


<?php endforeach; ?>


<?php endif; ?>


</div>




<div class="home-actions">


<a
href="<?php echo esc_url(home_url('/login/')); ?>"
class="home-login-button"
>

<span>←</span>

ورود به سامانه

</a>


</div>


</div>


</section>





<!-- =========================================================
     RESOURCE CARDS
========================================================== -->


<section class="home-resource-cards">


<?php if(!empty($home_cards)): ?>


<?php foreach($home_cards as $index => $card): ?>


<a
href="<?php echo esc_url($card['link']); ?>"
class="home-resource-card"
>



<?php if ($index === 0): ?>


<div class="resource-visual resource-books">

<span class="small-book book-a"></span>
<span class="small-book book-b"></span>
<span class="small-book book-c"></span>

</div>



<?php elseif ($index === 1): ?>


<div class="resource-visual media-monitor">


<div class="monitor-screen">

<span>
▶
</span>

</div>


<div class="monitor-stand"></div>


</div>



<?php elseif ($index === 2): ?>


<div class="resource-visual guide-board">


<div class="guide-board-paper">

<span>✓</span>
<span>✓</span>


</div>


</div>



<?php endif; ?>




<div class="resource-text">


<h3>

<?php echo esc_html(
    $card['title']
); ?>

</h3>



<p>

<?php echo nl2br(
    esc_html(
        $card['description']
    )
); ?>

</p>



<span class="resource-more">

مشاهده بیشتر

<b>
‹
</b>

</span>



</div>



</a>



<?php endforeach; ?>


<?php endif; ?>


</section>



</div>


</main>



<?php

get_template_part(
    'template-parts/public-footer'
);


get_footer();

?>