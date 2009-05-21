<?php
/*  Copyright © 2009 Transposh Team (website : http://transposh.org)
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

require_once("shd/simple_html_dom.php");
require_once("logging.php");

/**
 * Parser class - allows phrase marking and translation with callback functions
 */
class parser {
    public $url_rewrite_func = null;
    public $fetch_translate_func = null;
    private $segment_id = 0;
    private $currentnode;
    private $html; // the document
    public $dir_rtl;
    public $lang;
    private $inbody = false;
    public $is_edit_mode;
    public $is_auto_translate;
    protected $ignore_tags = array('script'=>1, 'style'=>1, 'code'=>1);

    /**
     * Determine if the current position in buffer is a white space.
     * @param $char
     * @return bool true if current position marks a white space
     */
    function is_white_space($char)
    {
        if (!$char) return TRUE;
        return strspn($char, " \t\r\n\0\x0B");
    }

    /**
     * Determine if the current position in page points to a character in the
     * range of a-z (case insensetive).
     * @return bool true if a-z
     */
    function is_a_to_z_character($char)
    {
        return (($char >= 'a' && $char <= 'z') || ($char >= 'A' && $char <= 'Z')) ? true : false;
    }

    /**
     * Determine if the current position is a digit.
     * @return bool true if a digit
     */
    function is_digit($char)
    {
        return (($char >= '0' && $char <= '9')) ? true : false;
    }

    /**
     * Determine if the current position is an html entity - such as &amp; or &#8220;.
     * @param $string string to evalute
     * @param $position where to check for entities
     * @return int length of entity
     */
    function is_html_entity($string, $position)
    {
        if ($string[$position] == "&")
        {
            $end_pos = $position + 1;
            while($string[$end_pos] == "#" || $this->is_digit($string[$end_pos]) || $this->is_a_to_z_character($string[$end_pos])) ++$end_pos;
            if ($string[$end_pos] == ';') return $end_pos - $position +1;
        }
        return 0;
    }

    /**
     * Some entities will not cause a break if they don't have whitespace after them
     * such as Jack`s apple.
     * `uncatagorized` will break on the later entity
     * @param $entity - html entity to check
     * @return - true if not a breaker (apostrophy)
     */
    function is_entity_breaker($entity) {
        return !(strpos('&#8217;&apos;&#039;&#39;', $entity) !== FALSE);
    }

    /**
     * Determine if the current position in buffer is a sentence breaker, e.g. '.' or ',' .
     * Note html markups are not considered sentence breaker within the scope of this function.
     * @param $char charcter checked if breaker
     * @param $nextchar needed for checking if . or - breaks
     * @return bool true if current position marks a break in sentence
     */
    function is_sentence_breaker($char, $nextchar)
    {
        if (($char == '.' || $char == '-') && ($this->is_white_space($nextchar))) return true;
        return (strpos(',?()[]"!:|;',$char) !== false) ? true : false;
    }

    /**
     * Determines if the current position marks the begining of a number, e.g. 123 050-391212232
     * @return length of number.
     */
    function is_number($page, $position)
    {
        return strspn($page,'0123456789-+,.\\/',$position);
    }

    /**
     * Create a pharse tag in the html dom tree
     * @param int $start - beginning of pharse in element
     * @param int $end - end of pharse in element
     */
    function tag_phrase($string,$start, $end) {
        $phrase = trim(substr($string,$start,$end-$start));
        if ($phrase) {
            $node = new simple_html_dom_node($this->html);
            $node->tag = 'phrase';
            $node->parent = $this->currentnode;
            $this->currentnode->nodes[] = $node;
            $node->_[HDOM_INFO_OUTER] = '';
            $node->phrase = $phrase;
            if ($this->inbody)
            $node->inbody = $this->inbody;
        }
    }

    /**
     * Breaks strings into substring according to some rules and common sense
     * @param string $string - the string which is "broken" into smaller strings
     */
    function parsetext($string) {
        $pos = 0;
        //	$pos = skip_white_space($string, $pos);
        $start = $pos;

        while($pos < strlen($string))
        {
            // Some HTML entities make us break, almost all but apostrophies
            if($len_of_entity = $this->is_html_entity($string,$pos))
            {
                if($this->is_white_space($string[$pos+$len_of_entity]) || $this->is_entity_breaker(substr($string,$pos,$len_of_entity)))
                {
                    $this->tag_phrase($string,$start,$pos);
                    $start = $pos + $len_of_entity;
                }
                //skip past entity
                $pos += $len_of_entity;
            }
            // will break translation unit when there's a breaker ",.[]()..."
            else if($this->is_sentence_breaker($string[$pos],$string[$pos+1]))
            {
                $this->tag_phrase($string,$start,$pos);
                $pos++;
                $start = $pos;
            }
            // Numbers also break, if they are followed by whitespace (don't break 42nd)
            else if($num_len = $this->is_number($string,$pos))
            {
                if ($this->is_white_space($string[$pos+$num_len])) {
                    $this->tag_phrase($string,$start,$pos);
                    $start = $pos + $num_len + 1;
                }
                $pos += $num_len + 1;
            }
            else
            {
                $pos++;
            }
        }

        // the end is also some breaker
        if($pos > $start)
        {
            $this->tag_phrase($string,$start,$pos);
        }
    }

    /**
     * This recursive function works on the $html dom and adds phrase nodes to translate as needed
     * it currently also rewrites urls, and should consider if this is smart
     * @param <type> $node
     */
    function translate_tagging($node) {
        $this->currentnode = $node;
        // we don't want to translate non-translatable classes
        if (stripos($node->class,NO_TRANSLATE_CLASS) !== false) return;
        if (isset($this->ignore_tags[$node->tag])) return;
        elseif ($node->tag == 'text') {
            // this prevents translation of a link that just surrounds its address
            if ($node->parent->tag == 'a' && $node->parent->href == $node->outertext) {
                return;
            }
            if (trim($node->outertext)) {
                $this->parsetext($node->outertext);
            };
        }
        // for anchors we will rewrite urls if we can
        elseif ($node->tag == 'a') {
            if ($this->url_rewrite_func != null) {
                $node->href = call_user_func_array($this->url_rewrite_func,array($node->href));
            }
        }
        // same for options, although normally not required (ticket #34)
        elseif ($node->tag == 'option') {
            if ($this->url_rewrite_func != null) {
                $node->value = call_user_func_array($this->url_rewrite_func,array($node->value));
            }
        }
        // we can only do translation for elements which are in the body, not in other places
        elseif ($node->tag == 'body') {
            $this->inbody = true;
        }

        // titles are also good places to translate, exist in a, img, abbr, acronym
        if ($node->title) $this->parsetext($node->title);

        // recurse
        foreach($node->nodes as $c) {
            $this->translate_tagging($c);
        }
    }

    /**
     * Creates a span used in translation and editing
     * @param string $original_text
     * @param string $translated_text
     * @param int $source (Either "0" for Human, "1" for Machine or "" for untouched)
     * @param boolean $for_hidden_element
     * @return string
     */
    function create_edit_span ($original_text , $translated_text, $source, $for_hidden_element = false) {
        // Use base64 encoding to make that when the page is translated (i.e. update_translation) we
        // get back exactlly the same string without having the client decode/encode it in anyway.
        $span = '<span class ="'.SPAN_PREFIX.'" id="'.SPAN_PREFIX.$this->segment_id.'" token="' . base64_url_encode($original_text)."\" source=\"$source\"";
        // those are needed for on the fly image creation / hidden elements translations
        if ($this->is_edit_mode || $for_hidden_element) {
            $span .= " orig=\"$original_text\"";
            if ($for_hidden_element) {
                $span.= ' hidden="y"';
                // hidden elements currently have issues figuring what they translated in the JS
                if ($translated_text != null) {
                    $span.= " trans=\"$translated_text\"";
                }
            }
        }
        $span .= '>';
        ++$this->segment_id;
        return $span;
    }

    /**
     * Main function - actually translates a given HTML
     * @param string $string
     * @return string Translated content is here
     */
    function fix_html($string) {
        // create our dom
        $this->html = str_get_html($string);
        // mark translateable elements
        $this->translate_tagging($this->html->root);

        // first fix the html tag itself - we might need to to the same for all such attributes with flipping
        if ($this->dir_rtl)
        $this->html->find('html',0)->dir="rtl";

        if ($this->lang)
        $this->html->find('html',0)->lang=$lang;

        // not much point in further processing if we don't have a function that does it
        if ($this->fetch_translate_func == null) {
            return $this->html;
        }

        // actually translate tags
        // texts are first
        foreach ($this->html->find('text') as $e) {
            $right = '';
            $newtext = '';
            foreach ($e->nodes as $ep) {
                list ($translated_text, $source) = call_user_func_array($this->fetch_translate_func,array($ep->phrase, $this->lang));
                if (($this->is_edit_mode || ($this->is_auto_translate && $translated_text == null)) && $ep->inbody) {
                    $span = $this->create_edit_span($ep->phrase, $translated_text, $source);
                    $spanend = "</span>";
                    if ($translated_text == null) $translated_text = $ep->phrase;
                } else {
                    $span = '';
                    $spanend = '';
                }
                if ($translated_text) {
                    list ($left, $right) = explode($ep->phrase, $e->outertext, 2);
                    $newtext .= $left.$span.$translated_text.$spanend;
                    $e->outertext = $right;
                }
            }
            if ($newtext) {
                $e->outertext = $newtext.$right;
            }
        }

        // now we handle the title attributes
        $hidden_phrases = array();
        foreach ($this->html->find('[title]') as $e) {
            $span = '';
            $spanend = '';
            $right = '';
            $newtext = '';
            // when we already have a parent outertext we'll have to update it directly
            if ($e->parent->_[HDOM_INFO_OUTER]) {
                $saved_outertext = $e->outertext;
            }

            foreach ($e->nodes as $ep) {
                if ($ep->tag == 'phrase') {
                    list ($translated_text, $source) = call_user_func_array($this->fetch_translate_func,array($ep->phrase, $this->lang));
                    if (($this->is_edit_mode || ($this->is_auto_translate && $translated_text == null)) && $ep->inbody) {
                        // prevent duplicate translation (title = text)
                        if (strpos($e->innertext,base64_url_encode($ep->phrase)) === false) {
                            //no need to translate span the same hidden phrase more than once
                            if (!in_array($ep->phrase, $hidden_phrases)) {
                                $span .= $this->create_edit_span($ep->phrase, $translated_text, $source, true)."</span>";
                                //    logger ($span);
                                $hidden_phrases[] = $ep->phrase;
                            }
                        }
                    }
                    if ($translated_text) {
                        list ($left, $right) = explode($ep->phrase, $e->title, 2);
                        $newtext .= $left.$translated_text;
                        $e->title = $right;
                    }
                }
            }
            if ($newtext)
            $e->title = $newtext.$right;
            
            $e->outertext .= $span;
            // this is where we update in the outercase issue
            if ($e->parent->_[HDOM_INFO_OUTER]) {
                $e->parent->outertext = implode ($e->outertext,explode($saved_outertext,$e->parent->outertext,2));
            }

        }

        return $this->html;
    }
}
?>