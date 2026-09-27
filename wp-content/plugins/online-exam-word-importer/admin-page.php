<?php

if (!defined('ABSPATH')) {
    exit;
}



/*
|--------------------------------------------------------------------------
| Admin Menu
|--------------------------------------------------------------------------
*/

add_action(
    'admin_menu',
    'oewi_register_menu'
);



function oewi_register_menu()
{

    add_menu_page(

        'ایجاد آزمون از Word',

        'Word Import آزمون',

        'manage_options',

        'online-exam-word-import',

        'oewi_render_page',

        'dashicons-media-document',

        30

    );

}



/*
|--------------------------------------------------------------------------
| Admin Page
|--------------------------------------------------------------------------
*/


function oewi_render_page()
{

    ?>


    <div class="wrap" dir="rtl">

        <h1>
            ایجاد آزمون از فایل Word
        </h1>


        <form
            method="post"
            enctype="multipart/form-data"
        >


            <table class="form-table">


                <tr>

                    <th>
                        عنوان آزمون
                    </th>

                    <td>

                        <input
                            type="text"
                            name="exam_title"
                            class="regular-text"
                        >

                    </td>

                </tr>



                <tr>

                    <th>
                        مدت آزمون (دقیقه)
                    </th>

                    <td>

                        <input
                            type="number"
                            name="exam_time"
                            value="45"
                        >

                    </td>

                </tr>



                <tr>

                    <th>
                        نمره کل
                    </th>

                    <td>

                        <input
                            type="number"
                            name="exam_score"
                            value="20"
                        >

                    </td>

                </tr>



                <tr>

                    <th>
                        فایل Word
                    </th>

                    <td>

                        <input
                            type="file"
                            name="exam_word"
                            accept=".docx"
                        >

                    </td>

                </tr>


            </table>



            <?php

            submit_button(
                'بررسی فایل Word'
            );

            ?>


        </form>



    <?php


    if (
        isset($_FILES['exam_word'])
    ) {


        echo '<hr>';

        echo '<h2>خروجی فایل</h2>';



        $result =
            oewi_parse_word(
                $_FILES['exam_word']['tmp_name']
            );



        echo '<pre>';

        print_r($result);

        echo '</pre>';

    }



    echo '</div>';

}