<?php

if (!defined('ABSPATH')) {
    exit;
}



/*
|--------------------------------------------------------------------------
| Parse DOCX
|--------------------------------------------------------------------------
*/


function oewi_parse_word(
    $file
) {


    if (
        !file_exists($file)
    ) {

        return 'File not found';

    }



    $zip = new ZipArchive();



    if (
        $zip->open($file) !== true
    ) {

        return 'Cannot open DOCX';

    }



    $content =
        $zip->getFromName(
            'word/document.xml'
        );



    $zip->close();



    if (!$content) {

        return 'No document content';

    }



    /*
    |--------------------------------------------------------------------------
    | Convert XML to text
    |--------------------------------------------------------------------------
    */


    $content =
        str_replace(
            '</w:p>',
            "\n",
            $content
        );



    $content =
        strip_tags(
            $content
        );



    $content =
        html_entity_decode(
            $content
        );



    return $content;

}