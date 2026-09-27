<?php

if (!defined('ABSPATH')) {
    exit;
}


get_header();


get_template_part(
    'template-parts/public-header'
);



$media_query = new WP_Query(array(

    'post_type'      => 'post',

    'posts_per_page' => -1,

    'category_name'  => 'media',

));



?>


<main class="media-page">


<!-- =========================================================
     HERO
========================================================== -->


<section class="media-hero">


<div class="public-container">


<div class="media-hero-content">


<span class="media-eyebrow">
رسانه آموزشی
</span>



<h1>
رسانه
</h1>



<p>
ویدئوهای آموزشی، اطلاعیه‌ها و محتوای چندرسانه‌ای
مرتبط با آزمون‌ها را از این بخش دنبال کنید.
</p>



</div>


</div>


</section>





<!-- =========================================================
     MEDIA CONTENT
========================================================== -->


<section class="media-content">


<div class="public-container">



<div class="media-topbar">


<div>


<h2>
آخرین محتواها
</h2>


<p>
جدیدترین ویدئوها و محتوای آموزشی منتشرشده
</p>


</div>



</div>






<div class="media-grid">



<?php if ($media_query->have_posts()) : ?>



<?php while ($media_query->have_posts()) : $media_query->the_post(); ?>



<article class="media-card">



<div class="media-thumbnail">


<?php if (has_post_thumbnail()) : ?>


<?php

the_post_thumbnail(
    'medium',
    array(
        'class' => 'media-feature-image'
    )
);

?>


<?php else : ?>


<div class="media-thumbnail-bg media-bg-one"></div>


<?php endif; ?>



<span class="media-play-button">
▶
</span>



</div>







<div class="media-card-body">



<div class="media-card-meta">


<span class="media-category video">
ویدئوی آموزشی
</span>



<span>
<?php echo esc_html(get_the_date()); ?>
</span>



</div>





<h3>
<?php the_title(); ?>
</h3>





<p>


<?php


if (has_excerpt()) {


    echo esc_html(
        get_the_excerpt()
    );


} else {


    echo wp_trim_words(
        get_the_content(),
        20
    );


}


?>


</p>





<a
href="<?php the_permalink(); ?>"
class="media-card-link"
>

مشاهده محتوا

<span>
←
</span>


</a>





</div>




</article>





<?php endwhile; ?>


<?php wp_reset_postdata(); ?>



<?php else : ?>



<p>
هنوز محتوای آموزشی ثبت نشده است.
</p>



<?php endif; ?>





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