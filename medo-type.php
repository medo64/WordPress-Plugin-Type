<?php
/*
Plugin Name: Type
Plugin URI: https://www.medo64.com/
Description: Allows better <pre> formatting via simple short code.
Version: 0.0
Author: Josip Medved
Author URI: https://www.medo64.com/
License: MIT

    Copyright (c) 2012 Josip Medved <jmedved@jmedved.com>

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to
    deal in the Software without restriction, including without limitation the
    rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
    sell copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

        o The above copyright notice and this permission notice shall be
          included in all copies or substantial portions of the Software.

        o THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
          EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
          MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
          IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
          CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
          TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
          SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/


add_shortcode('type', 'medo64type_shortcode_callback');
add_shortcode('pre', 'medo64type_shortcode_callback');

add_shortcode('csharp', 'medo64type_shortcode_callback');
add_shortcode('php', 'medo64type_shortcode_callback');
add_shortcode('plain', 'medo64type_shortcode_callback');
add_shortcode('sql', 'medo64type_shortcode_callback');

add_shortcode('sourcecode', 'medo64type_shortcode_callback');

add_filter('no_texturize_shortcodes', 'medo64type_shortcode_notexturize_filter');
wp_enqueue_style('medo64type', plugins_url('/css/style.css', __FILE__), null, null, 'all');
wp_enqueue_script('medo64type', plugins_url('/js/script.js', __FILE__), array('jquery'), 1.1, true);

// postpone autoformatting
remove_filter('the_content', 'wpautop');
add_filter('the_content', 'wpautop', 12);


function medo64type_shortcode_callback($atts, $content = null) {
    extract(
        shortcode_atts(
            array(
                'highlight' => '',
                'prompt' => '',
                'title' => ''
            ),
            $atts
        )
    );
    $atts = (array)$atts;

    // make sure all attributes are set
    $atts_highlight = isset($atts['highlight']) ? $atts['highlight'] : "";
    $atts_prompt = isset($atts['prompt']) ? $atts['prompt'] : "";
    $atts_title = isset($atts['title']) ? $atts['title'] : "";

    $id = medo64type_get_uuid();

    // setup title
    if (strlen($title)) {
        $output .= '<div>';
        $output .= '<span>' .$title . '</span>';
        $output .= "<button onclick=\"medo64type_copy('" . $id . "')\">Copy</button>";
        $output .= '</div>';
    }

    // process code lines
    $output .= '<code id="' . $id . '">';
    $lines = preg_split('/$\R?^/m', $content);
    $index = 0;
    for ($i = 0; $i < count($lines); $i++) {
        // filter extra formatting
        $line = $lines[$i];
        if (substr($line, -4) === '</p>') { $line = substr($line, 0, strlen($line) - 4); }
        if (substr($line, -6) === '<br />') {
            $line = substr($line, 0, strlen($line) - 6);
        } else if (substr($line, -5) === '<br/>') {
            $line = substr($line, 0, strlen($line) - 5);
	}
        if (substr($line, 0, 3) === "<p>") { $line = substr($line, 3); }

        if (($i === 0) && (strlen(trim($line)) === 0)) { continue; }
        if (($i === count($lines) - 1) && (strlen($line) === 0)) { continue; }

        $index += 1;
        if ($index > 1) { $output .= '<br/>'; }
        if (strlen($atts_prompt) > 0) {
            if (substr($line, 0, strlen($atts_prompt)) === $atts_prompt) {
                $output .= '<span data-content="' . $prompt . '">';
                $line = substr($line, strlen($atts_prompt));
            } else {
                $output .= '<span data-content="' . str_pad("", strlen($atts_prompt)) . '">';
            }
        } else {
            $output .= '<span>';
        }

        $line = medo64type_clean($line);
        $line = medo64type_style($line, "**", "<strong>", "</strong>");
        $line = medo64type_style($line, "__", "<em>", "</em>");
        $line = medo64type_style($line, "``", "<span style=\"opacity:0.5;\">", "</span>");
        $line = medo64type_style($line, "^^", "<span style=\"background-color:yellow;\">", "</span>");
        $line = medo64type_style($line, "!!", "<span style=\"background-color:red;\">", "</span>");

        $output .= $line;
        $output .= '</span>';
    }
    $output .= '</code>';

    return '<pre class="medo64type">' . $output . '</pre>';
}

function medo64type_shortcode_notexturize_filter($shortcodes) {
    $shortcodes[] = 'type';
    $shortcodes[] = 'pre';
    $shortcodes[] = 'csharp';
    $shortcodes[] = 'php';
    $shortcodes[] = 'plain';
    $shortcodes[] = 'sql';
    $shortcodes[] = 'sourcecode';

    return $shortcodes;
}

function medo64type_style($content, $symbol, $tagOpen, $tagClose) {
    while(true) {
        $l = strpos($content, $symbol);
        if ($l === false) { break; }
        $r = strpos($content, $symbol, $l+1);
        if ($r === false) { break; }
        $content = substr_replace($content, $tagOpen, $l, 2);
        $content = substr_replace($content, $tagClose, $r+strlen($tagOpen)-2, 2);
    }
    return $content;
}

function medo64type_clean($content) {
    $content = str_replace(array("<br />", "</p>"), "", $content);
    $content = str_replace(array("<p>"), "\n", $content);
    $content = trim($content, "\n\r\0\x0B");

    $content = htmlspecialchars($content); //escape stuff that needs escaping

    //restore escapings if it was already escaped
    $content = str_replace('&amp;amp;', '&amp;', $content);
    $content = str_replace('&amp;lt;', '&lt;', $content);
    $content = str_replace('&amp;gt;', '&gt;', $content);
    $content = str_replace('&amp;quot;', '&quot;', $content);
    $content = str_replace('&amp;#038;', '&amp;', $content);
    $content = str_replace(array('&amp;#8211;', '&amp;#8212;'), '-', $content);
    $content = str_replace(array('&amp;#8216;', '&amp;#8217;'), '\'', $content);
    $content = str_replace(array('&amp;#8220;', '&amp;#8221;'), "&quot;", $content);
    $content = str_replace('&amp;#8230;', '&#8230;', $content);

    $content = str_replace("\xE2\x80\xA6", "<span style=\"opacity:0.25;\">\xE2\x80\xA6</span>", $content);
    return $content;
}

function medo64type_get_uuid() { //RFC 4122 (4.4.)
    return sprintf('%04x%04x-%04x-%04x-%02x%02x-%04x%04x%04x',
        mt_rand(0x0000, 0xffff), mt_rand(0, 0xffff),                              //time_low
        mt_rand(0x0000, 0xffff),                                                  //time_mid
        mt_rand(0x0000, 0x0fff) | 0x4000,                                         //time_hi_and_version
        mt_rand(0x00, 0x3f) | 0x80,                                               //clock_seq_hi_and_reserved
        mt_rand(0x00, 0xff),                                                      //clock_seq_low
        mt_rand(0x0000, 0xffff), mt_rand(0x0000, 0xffff), mt_rand(0x0000, 0xffff) //node
    );
}

?>
