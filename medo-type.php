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


add_shortcode('type', 'snippet_pre_shortcode_callback');
add_shortcode('pre', 'snippet_pre_shortcode_callback');

add_shortcode('csharp', 'snippet_pre_shortcode_callback');
add_shortcode('php', 'snippet_pre_shortcode_callback');
add_shortcode('plain', 'snippet_pre_shortcode_callback');
add_shortcode('sql', 'snippet_pre_shortcode_callback');

add_shortcode('sourcecode', 'snippet_pre_shortcode_callback');

add_filter('no_texturize_shortcodes', 'snippet_pre_shortcode_notexturize_filter');
wp_enqueue_style('medo64type', plugins_url('/css/style.css', __FILE__), null, null, 'all');


function snippet_pre_shortcode_callback($atts, $content = null) {
    extract(
        shortcode_atts(
            array(
                'name' => '',
                'highlight' => '',
                'title' => ''
            ),
            $atts
        )
    );
    $atts = (array)$atts;

    $atts_highlight = isset($atts['highlight']) ? $atts['highlight'] : "";
    $atts_title = isset($atts['title']) ? $atts['title'] : "";

    $content = snippet_pre_clean($content);

    $content = snippet_pre_style($content, "**", "<strong>", "</strong>");
    $content = snippet_pre_style($content, "__", "<em>", "</em>");
    $content = snippet_pre_style($content, "``", "<span style=\"opacity:0.5;\">", "</span>");
    $content = snippet_pre_style($content, "^^", "<span style=\"background-color:yellow;\">", "</span>");
    $content = snippet_pre_style($content, "!!", "<span style=\"background-color:red;\">", "</span>");

    if ($atts_highlight != '') { //compatibility with syntax highlighter
        $lines = explode(PHP_EOL, $content);
        $lineIndices = explode(',', $atts_highlight);
        foreach ($lineIndices as $lineIndex) {
            $index = intval($lineIndex);
            if ($index > 0) {
                $lines[$index - 1] = "<span style=\"background-color:yellow;\">" . $lines[$index - 1] . "</span>";
            }
        }
        $content = implode(PHP_EOL, $lines);
    }

    $header = "";
    if (strlen($title)) {
        $header .= "<div>";
        $header .= "<span>" .$title . "</span>";
        $header .= "<button onclick=\"medo64type_copy('id')\">Copy</button>";
        $header .= "</div>";
    }


    return "<pre class=\"medo64type\">" . $header . "<code>" . $content . "</code></pre>";
}


function snippet_pre_shortcode_notexturize_filter($shortcodes) {
    $shortcodes[] = 'type';
    $shortcodes[] = 'pre';
    $shortcodes[] = 'csharp';
    $shortcodes[] = 'php';
    $shortcodes[] = 'plain';
    $shortcodes[] = 'sql';
    $shortcodes[] = 'sourcecode';

    return $shortcodes;
}


function snippet_pre_style($content, $symbol, $tagOpen, $tagClose) {
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


function snippet_pre_clean($content) {
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

?>
