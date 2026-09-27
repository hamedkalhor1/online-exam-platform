<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

get_template_part(
    'template-parts/public-header'
);



$resources_query = new WP_Query(array(

    'post_type'      => 'post',

    'posts_per_page' => -1,

    'category_name'  => 'resources',

));

?>


<main class="resources-page">


<!-- =========================================================
     PAGE HERO
========================================================== -->

<section class="resources-hero">

    <div class="public-container">

        <div class="resources-hero-content">


            <span class="resources-eyebrow">
                منابع آموزشی
            </span>


            <h1>
                کتاب‌ها و جزوات
            </h1>


            <p>
                مجموعه‌ای از کتاب‌ها، جزوات و منابع آموزشی
                موردنیاز برای مطالعه و آمادگی پیش از آزمون.
            </p>


        </div>

    </div>

</section>





<!-- =========================================================
     RESOURCES
========================================================== -->


<section class="resources-content">


<div class="public-container">



<div class="resources-grid">



<?php if ($resources_query->have_posts()) : ?>


<?php while ($resources_query->have_posts()) : $resources_query->the_post(); ?>



<article class="resource-item">



<!-- COVER -->

<div class="resource-cover">


<?php if (has_post_thumbnail()) : ?>


<?php

the_post_thumbnail(
    'medium',
    array(
        'class' => 'resource-feature-image'
    )
);

?>


<?php else : ?>


<div class="resource-cover-book cover-blue">


<span class="book-mini-label">
سعادت آزمون
</span>


<strong>

<?php the_title(); ?>

</strong>


<small>
منبع آموزشی
</small>


</div>


<?php endif; ?>


</div>





<!-- BODY -->


<div class="resource-item-body">


<span class="resource-type">

منبع آموزشی

</span>




<h2>

<?php the_title(); ?>

</h2>





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





<div class="resource-item-footer">


<span>
PDF
</span>



<?php

$content = get_the_content();

preg_match(
    '/href="([^"]+\.pdf[^"]*)"/i',
    $content,
    $matches
);

$pdf_link = !empty($matches[1])
    ? $matches[1]
    : '#';

?>


<a 
href="<?php echo esc_url($pdf_link); ?>"
download
>
    دانلود PDF
    <b>←</b>
</a>


</div>



</div>



</article>



<?php endwhile; ?>


<?php wp_reset_postdata(); ?>


<?php else : ?>


<p>
هنوز منبع آموزشی ثبت نشده است.
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