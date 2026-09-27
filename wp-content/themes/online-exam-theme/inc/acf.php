<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| ACF OPTIONS PAGES
|--------------------------------------------------------------------------
*/

function online_exam_register_acf_options_pages()
{

    if (!function_exists('acf_add_options_page')) {
        return;
    }


    // Main Settings

    acf_add_options_page(array(

        'page_title' => 'تنظیمات سامانه',
        'menu_title' => 'تنظیمات سامانه',
        'menu_slug'  => 'online-exam-settings',
        'capability' => 'manage_options',
        'redirect'   => true,
        'icon_url'   => 'dashicons-admin-generic',
        'position'   => 26,

    ));



    // Header Footer

    acf_add_options_sub_page(array(

        'page_title'  => 'تنظیمات هدر و فوتر',
        'menu_title'  => 'هدر و فوتر',
        'menu_slug'   => 'online-exam-header-footer',
        'parent_slug' => 'online-exam-settings',
        'capability'  => 'manage_options',

    ));



    // Exam

    acf_add_options_sub_page(array(

        'page_title'  => 'تنظیمات آزمون',
        'menu_title'  => 'تنظیمات آزمون',
        'menu_slug'   => 'online-exam-exam-settings',
        'parent_slug' => 'online-exam-settings',
        'capability'  => 'manage_options',

    ));



    // Login

    acf_add_options_sub_page(array(

        'page_title'  => 'تنظیمات صفحه ورود',
        'menu_title'  => 'صفحه ورود',
        'menu_slug'   => 'online-exam-login-settings',
        'parent_slug' => 'online-exam-settings',
        'capability'  => 'manage_options',

    ));


}

add_action(
    'acf/init',
    'online_exam_register_acf_options_pages'
);




/*
|--------------------------------------------------------------------------
| OPTION HELPER
|--------------------------------------------------------------------------
*/

function online_exam_get_option($name, $default = '')
{

    if (!function_exists('get_field')) {
        return $default;
    }


    $value = get_field(
        $name,
        'option'
    );


    return !empty($value)
        ? $value
        : $default;

}




/*
|--------------------------------------------------------------------------
| HOME PAGE FIELDS
|--------------------------------------------------------------------------
*/

function online_exam_register_home_fields()
{

    if (!function_exists('acf_add_local_field_group')) {
        return;
    }



    acf_add_local_field_group(array(

        'key' => 'group_online_exam_home',

        'title' => 'تنظیمات صفحه اصلی',


        'fields' => array(


            array(

                'key' => 'field_home_title',

                'label' => 'عنوان اصلی',

                'name' => 'home_title',

                'type' => 'text',

                'default_value' => 'سامانه آزمون آنلاین',

            ),



            array(

                'key' => 'field_home_brand',

                'label' => 'عنوان برند',

                'name' => 'home_brand_title',

                'type' => 'text',

                'default_value' => 'سعادت آزمون',

            ),



            array(

                'key' => 'field_home_description',

                'label' => 'توضیحات صفحه',

                'name' => 'home_description',

                'type' => 'textarea',

            ),



            array(

                'key' => 'field_home_features',

                'label' => 'ویژگی‌ها',

                'name' => 'home_features',

                'type' => 'repeater',

                'layout' => 'row',


                'sub_fields' => array(


                    array(

                        'key' => 'field_feature_title',

                        'label' => 'عنوان',

                        'name' => 'title',

                        'type' => 'text',

                    ),


                    array(

                        'key' => 'field_feature_description',

                        'label' => 'توضیح',

                        'name' => 'description',

                        'type' => 'text',

                    ),

                ),

            ),



            array(

                'key' => 'field_home_cards',

                'label' => 'کارت‌ها',

                'name' => 'home_cards',

                'type' => 'repeater',

                'layout' => 'row',


                'sub_fields' => array(


                    array(

                        'key' => 'field_card_title',

                        'label' => 'عنوان',

                        'name' => 'title',

                        'type' => 'text',

                    ),


                    array(

                        'key' => 'field_card_description',

                        'label' => 'توضیح',

                        'name' => 'description',

                        'type' => 'textarea',

                    ),


                    array(

                        'key' => 'field_card_link',

                        'label' => 'لینک',

                        'name' => 'link',

                        'type' => 'url',

                    ),


                ),

            ),


        ),



        'location' => array(

            array(

                array(

                    'param' => 'page_type',

                    'operator' => '==',

                    'value' => 'front_page',

                ),

            ),

        ),

    ));

}


add_action(
    'acf/init',
    'online_exam_register_home_fields'
);