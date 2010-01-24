<?php
/*  Copyright © 2009-2010 Transposh Team (website : http://transposh.org)
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
 * parserstats class - holds parser statistics
 */
class parserstats {
    /** @var int Holds the total phrases the parser encountered */
    public $total_phrases;
    /** @var int Holds the number of phrases that had translation */
    public $translated_phrases;
    /** @var int Holds the number of phrases that had human translation */
    public $human_translated_phrases;

    /** @var int Holds the number of phrases that are hidden - yet still somewhat viewable (such as the title attribure) */
    public $hidden_phrases;
    /** @var int Holds the number of phrases that are hidden and translated */
    public $hidden_translated_phrases;
    /** @var int Holds the amounts of hidden spans created for translation */
    public $hidden_translateable_phrases;

    /** @var int Holds the number of phrases that are hidden and probably won't be viewed - such as meta keys */
    public $meta_phrases;
    /** @var int Holds the number of translated phrases that are hidden and probably won't be viewed - such as meta keys */
    public $meta_translated_phrases;
    /** @var float Holds the time translation took */
    public $time;

    /** @var int Holds the time translation started */
    private $start_time;

    /**
     * This function is when the object is initialized, which is a good time to start ticking.
     */
    function parserstats() {
        $this->start_time = microtime(true);
    }

    /**
     * Calculated values - computer translated phrases
     * @return int How many phrases were auto-translated
     */
    function get_computer_translated_phrases() {
        return $this->translated_phrases - $this->human_translated_phrases;
    }
    /**
     * Calculated values - missing phrases
     * @return int How many phrases are missing
     */
    function get_missing_phrases() {
        return $this->total_phrases - $this->translated_phrases;
    }
    /**
     * Start the timer
     */
    function start_timing() {
        $this->start_time = microtime(true);
    }
    /**
     * Stop timing, store time for reference
     */
    function stop_timing() {
        $this->time = number_format(microtime(true) - $this->start_time,3);
    }
}

/**
 * Parser class - allows phrase marking and translation with callback functions
 */
class parser {
    public $url_rewrite_func = null;
    public $fetch_translate_func = null;
    private $segment_id = 0;
    /** @var simple_html_dom_node Contains the current node */
    private $currentnode;
    /** @var simple_html_dom Contains the document dom model */
    private $html; // the document
    public $dir_rtl;
    public $lang;
    private $inbody = false;
    private $inselect;
    public $is_edit_mode;
    public $is_auto_translate;
    public $feed_fix;
    //first three are html, later 3 come from feeds xml (link is problematic...)
    protected $ignore_tags = array('script'=>1, 'style'=>1, 'code'=>1,'wfw:commentrss'=>1,'comments'=>1,'guid'=>1);
    /** @var parserstats Contains parsing statistics */
    private $stats;

    /**
     * Determine if the current position in buffer is a white space.
     * @param char $char
     * @return boolean true if current position marks a white space
     */
    function is_white_space($char) {
        if (!$char) return TRUE;
        return strspn($char, " \t\r\n\0\x0B");
    }

    /**
     * Determine if the current position in page points to a character in the
     * range of a-z (case insensetive).
     * @return boolean true if a-z
     */
    function is_a_to_z_character($char) {
        return (($char >= 'a' && $char <= 'z') || ($char >= 'A' && $char <= 'Z')) ? true : false;
    }

    /**
     * Determine if the current position is a digit.
     * @return boolean true if a digit
     */
    function is_digit($char) {
        return (($char >= '0' && $char <= '9')) ? true : false;
    }

    /**
     * Determine if the current position is an html entity - such as &amp; or &#8220;.
     * @param string $string string to evalute
     * @param int $position where to check for entities
     * @return int length of entity
     */
    function is_html_entity($string, $position) {
        if ($string[$position] == "&") {
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
     * Added " quotes to this claim, as it is used in some languages in a similar fashion
     * @param string $entity - html entity to check
     * @return boolean true if not a breaker (apostrophy)
     */
    function is_entity_breaker($entity) {
        return !(stripos('&#8217;&apos;&quot;&#039;&#39;', $entity) !== FALSE);
    }

    /**
     * Some entities are to be regarded as simple letters in most cases
     &Agrave;    &#192;  	À  	À  	latin capital letter A with grave
     &Aacute;    &#193; 	Á 	Á 	latin capital letter A with acute
     &Acirc;     &#194; 	Â 	Â 	latin capital letter A with circumflex
     &Atilde;    &#195; 	Ã 	Ã 	latin capital letter A with tilde
     &Auml;      &#196; 	Ä 	Ä 	latin capital letter A with diaeresis
     &Aring;     &#197; 	Å 	Å 	latin capital letter A with ring above
     &AElig;     &#198; 	Æ 	Æ 	latin capital letter AE
     &Ccedil;    &#199; 	Ç 	Ç 	latin capital letter C with cedilla
     &Egrave;    &#200; 	È 	È 	latin capital letter E with grave
     &Eacute;    &#201; 	É 	É 	latin capital letter E with acute
     &Ecirc;     &#202; 	Ê 	Ê 	latin capital letter E with circumflex
     &Euml;      &#203; 	Ë 	Ë 	latin capital letter E with diaeresis
     &Igrave;    &#204; 	Ì 	Ì 	latin capital letter I with grave
     &Iacute;    &#205; 	Í 	Í 	latin capital letter I with acute
     &Icirc;     &#206; 	Î 	Î 	latin capital letter I with circumflex
     &Iuml;      &#207; 	Ï 	Ï 	latin capital letter I with diaeresis
     &ETH;       &#208; 	Ð 	Ð 	latin capital letter ETH
     &Ntilde;    &#209; 	Ñ 	Ñ 	latin capital letter N with tilde
     &Ograve;    &#210; 	Ò 	Ò 	latin capital letter O with grave
     &Oacute;    &#211; 	Ó 	Ó 	latin capital letter O with acute
     &Ocirc;     &#212; 	Ô 	Ô 	latin capital letter O with circumflex
     &Otilde;    &#213; 	Õ 	Õ 	latin capital letter O with tilde
     &Ouml;      &#214; 	Ö 	Ö 	latin capital letter O with diaeresis
     //&times;     &#215; 	× 	× 	multiplication sign
     &Oslash;    &#216; 	Ø 	Ø 	latin capital letter O with stroke
     &Ugrave;    &#217; 	Ù 	Ù 	latin capital letter U with grave
     &Uacute;    &#218; 	Ú 	Ú 	latin capital letter U with acute
     &Ucirc;     &#219; 	Û 	Û 	latin capital letter U with circumflex
     &Uuml;      &#220; 	Ü 	Ü 	latin capital letter U with diaeresis
     &Yacute;    &#221; 	Ý 	Ý 	latin capital letter Y with acute
     &THORN;     &#222; 	Þ 	Þ 	latin capital letter THORN
     &szlig;     &#223; 	ß 	ß 	latin small letter sharp s
     &agrave;    &#224; 	à 	à 	latin small letter a with grave
     &aacute;    &#225; 	á 	á 	latin small letter a with acute
     &acirc;     &#226; 	â 	â 	latin small letter a with circumflex
     &atilde;    &#227; 	ã 	ã 	latin small letter a with tilde
     &auml;      &#228; 	ä 	ä 	latin small letter a with diaeresis
     &aring;     &#229; 	å 	å 	latin small letter a with ring above
     &aelig;     &#230; 	æ 	æ 	latin small letter ae
     &ccedil;    &#231; 	ç 	ç 	latin small letter c with cedilla
     &egrave;    &#232; 	è 	è 	latin small letter e with grave
     &eacute;    &#233; 	é 	é 	latin small letter e with acute
     &ecirc;     &#234; 	ê 	ê 	latin small letter e with circumflex
     &euml;      &#235; 	ë 	ë 	latin small letter e with diaeresis
     &igrave;    &#236; 	ì 	ì 	latin small letter i with grave
     &iacute;    &#237; 	í 	í 	latin small letter i with acute
     &icirc;     &#238; 	î 	î 	latin small letter i with circumflex
     &iuml;      &#239; 	ï 	ï 	latin small letter i with diaeresis
     &eth;       &#240; 	ð 	ð 	latin small letter eth
     &ntilde;    &#241; 	ñ 	ñ 	latin small letter n with tilde
     &ograve;    &#242; 	ò 	ò 	latin small letter o with grave
     &oacute;    &#243; 	ó 	ó 	latin small letter o with acute
     &ocirc;     &#244; 	ô 	ô 	latin small letter o with circumflex
     &otilde;    &#245; 	õ 	õ 	latin small letter o with tilde
     &ouml;      &#246; 	ö 	ö 	latin small letter o with diaeresis
     //&divide;  &#247; 	÷ 	÷ 	division sign
     &oslash;    &#248; 	ø 	ø 	latin small letter o with stroke
     &ugrave;    &#249; 	ù 	ù 	latin small letter u with grave
     &uacute;    &#250; 	ú 	ú 	latin small letter u with acute
     &ucirc;     &#251; 	û 	û 	latin small letter u with circumflex
     &uuml;      &#252; 	ü 	ü 	latin small letter u with diaeresis
     &yacute;    &#253; 	ý 	ý 	latin small letter y with acute
     &thorn;     &#254; 	þ 	þ 	latin small letter thorn
     &yuml;      &#255; 	ÿ 	ÿ 	latin small letter y with diaeresis
     */

    function is_entity_letter($entity) {
        logger ("checking ($entity) - ".htmlentities($entity),4);
        $entnum = (int)substr($entity,2);
        if (($entnum >= 192 && $entnum <= 214) || ($entnum >= 216 && $entnum <= 246) || ($entnum >= 248 && $entnum <= 255)) {
            return true;
        }
        $entities = '&Agrave;&Aacute;&Acirc;&Atilde;&Auml;&Aring;&AElig;&Ccedil;&Egrave;&Eacute;&Ecirc;&Euml;&Igrave;&Iacute;&Icirc;&Iuml;&ETH;'.
                '&Ntilde;&Ograve;&Oacute;&Ocirc;&Otilde;&Ouml;&Oslash;&Ugrave;&Uacute;&Ucirc;&Uuml;&Yacute;&THORN;&szlig;'.
                '&oslash;&ugrave;&yuml;';
        return (stripos($entities, $entity) !== FALSE);
    }

    /**
     * Determine if the current position in buffer is a sentence breaker, e.g. '.' or ',' .
     * Note html markups are not considered sentence breaker within the scope of this function.
     * @param char $char charcter checked if breaker
     * @param char $nextchar needed for checking if . or - breaks
     * @return int length of breaker if current position marks a break in sentence
     */
    function is_sentence_breaker($char, $nextchar, $nextnextchar) {
        if (($char == '.' || $char == '-') && ($this->is_white_space($nextchar))) return 1;
        if (ord($char) == 226 && ord($nextchar) == 136 && ord($nextnextchar) == 153) return 3; //∙
        if (ord($char) == 194 && ord($nextchar) == 183) return 2; //·
        return (strpos(',?()[]"!:|;',$char) !== false) ? 1 : 0; // TODO: might need to add < and > here
    }

    /**
     * Determines if the current position marks the begining of a number, e.g. 123 050-391212232
     * @return int length of number.
     */
    function is_number($page, $position) {
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
            logger ($phrase,4);
            $node = new simple_html_dom_node($this->html);
            $node->tag = 'phrase';
            $node->parent = $this->currentnode;
            $this->currentnode->nodes[] = $node;
            $node->_[HDOM_INFO_OUTER] = '';
            $node->phrase = $phrase;
            if ($this->inbody)
                $node->inbody = $this->inbody;
            if ($this->inselect)
                $node->inselect = true;
        }
    }

    /**
     * Breaks strings into substring according to some rules and common sense
     * @param string $string - the string which is "broken" into smaller strings
     */
    function parsetext($string) {
        $pos = 0;
        //	$pos = skip_white_space($string, $pos);
        // skip CDATA in feed_fix mode
        if ($this->feed_fix) {
            if (strpos($string,'<![CDATA[') === 0) {
                $pos = 9; // CDATA length
                $string = substr($string,0,-3); // chop the last ]]>;
            }
        }

        $start = $pos;

        while($pos < strlen($string)) {
            // Some HTML entities make us break, almost all but apostrophies
            if($len_of_entity = $this->is_html_entity($string,$pos)) {
                $entity = substr($string,$pos,$len_of_entity);
                if(($this->is_white_space($string[$pos+$len_of_entity]) || $this->is_entity_breaker($entity)) && !$this->is_entity_letter($entity)) {
                    logger ("entity ($entity) breaks",5);
                    $this->tag_phrase($string,$start,$pos);
                    $start = $pos + $len_of_entity;
                }
                //skip past entity
                $pos += $len_of_entity;
            }
            // we have a special case for <> tags which might have came to us (maybe in xml feeds) (we'll skip them...)
            elseif ($string[$pos] == '<') {
                $this->tag_phrase($string,$start,$pos);
                while ($string[$pos] != '>' && $pos < strlen($string)) $pos ++;
                $pos++;
                $start = $pos;
            }
            // will break translation unit when there's a breaker ",.[]()..."
            elseif($senb_len = $this->is_sentence_breaker($string[$pos],$string[$pos+1],$string[$pos+2])) {
                $this->tag_phrase($string,$start,$pos);
                $pos += $senb_len;
                $start = $pos;
            }
            // Numbers also break, if they are followed by whitespace (or a sentence breaker) (don't break 42nd) // TODO: probably by breaking entities too...
            elseif($num_len = $this->is_number($string,$pos)) {
                if ($this->is_white_space($string[$pos+$num_len]) ||  $this->is_sentence_breaker($string[$pos+$num_len],$string[$pos+$num_len+1],$string[$pos+$num_len+2])) {
                    $this->tag_phrase($string,$start,$pos);
                    $start = $pos + $num_len + 1;
                }
                $pos += $num_len + 1;
            }
            else {
                $pos++;
            }
        }

        // the end is also some breaker
        if($pos > $start) {
            $this->tag_phrase($string,$start,$pos);
        }
    }

    /**
     * This recursive function works on the $html dom and adds phrase nodes to translate as needed
     * it currently also rewrites urls, and should consider if this is smart
     * @param simple_html_dom_node $node
     */
    function translate_tagging($node, $level = 0) {
        $this->currentnode = $node;
        // we don't want to translate non-translatable classes
        if (stripos($node->class,NO_TRANSLATE_CLASS) !== false) return;

        if (!($this->inselect && $level > $this->inselect))
            $this->inselect = false;

        if (isset($this->ignore_tags[$node->tag])) return;
        if ($node->tag == 'text') {
            // this prevents translation of a link that just surrounds its address
            if ($node->parent->tag == 'a' && $node->parent->href == $node->outertext) {
                return;
            }
            // link tags inners are to be ignored
            if ($node->parent->tag == 'link') {
                return;
            }
            if (trim($node->outertext)) {
                $this->parsetext($node->outertext);
            }
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
        elseif ($node->tag == 'select') {
            $this->inselect = $level;
        }
        // in submit type inputs, we want to translate the value
        elseif ($node->tag == 'input' && $node->type =='submit') {
            $this->parsetext($node->value);
        }

        // titles are also good places to translate, exist in a, img, abbr, acronym
        if ($node->title) $this->parsetext($node->title);

        // Meta content (keywords, description) are also good places to translate
        if ($node->tag == 'meta' && $node->content) $this->parsetext($node->content);

        // recurse
        foreach($node->nodes as $c) {
            $this->translate_tagging($c, $level +1);
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
     * @param string $string containing HTML
     * @return string Translated content is here
     */
    function fix_html($string) {
        // ready our stats
        $this->stats = new parserstats();
        // create our dom
        $this->html = str_get_html($string);
        // mark translateable elements
        $this->translate_tagging($this->html->root);

        // first fix the html tag itself - we might need to to the same for all such attributes with flipping
        if ($this->dir_rtl)
            $this->html->find('html',0)->dir="rtl";
        else
            $this->html->find('html',0)->dir="ltr";

        if ($this->lang)
            $this->html->find('html',0)->lang=$this->lang;

        // not much point in further processing if we don't have a function that does it
        if ($this->fetch_translate_func == null) {
            return $this->html;
        }

        // fix feed
        if ($this->feed_fix) {
            // fix urls on feed
            logger ("fixing feed");
            foreach (array('link','wfw:commentrss','comments') as $tag) {
                foreach ($this->html->find($tag) as $e) {
                    $e->innertext = call_user_func_array($this->url_rewrite_func,array($e->innertext));
                    // no need to translate anything here
                    unset($e->nodes);
                }
            }
            // guid is not really a url -- in some future, we can check if permalink is true and probably falsify it
            foreach ($this->html->find('guid') as $e) {
                $e->innertext = $e->innertext.'-'.$this->lang;
                unset($e->nodes);
            }
            // fix feed language
            $this->html->find('language', 0)->innertext = $this->lang;
            unset($this->html->find('language', 0)->nodes);
        } else {
            // since this is not a feed, we might have references to such in the <link rel="alternate">
            foreach ($this->html->find('link') as $e) {
                if (strcasecmp($e->rel, 'alternate') == 0 || strcasecmp($e->rel, 'canonical') == 0) {
                    $e->href = call_user_func_array($this->url_rewrite_func,array($e->href));
                }
            }
        }

        // actually translate tags
        // texts are first
        foreach ($this->html->find('text') as $e) {
            $right = '';
            $newtext = '';
            foreach ($e->nodes as $ep) {
                list ($translated_text, $source) = call_user_func_array($this->fetch_translate_func,array($ep->phrase, $this->lang));
                //stats
                $this->stats->total_phrases++;
                if ($translated_text) {
                    $this->stats->translated_phrases++;
                    if ($source == 0) $this->stats->human_translated_phrases++;
                }
                if (($this->is_edit_mode || ($this->is_auto_translate && $translated_text == null))/* && $ep->inbody*/) {
                    $spanend = "</span>";
                    if ($ep->inselect || !$ep->inbody) {
                        $savedspan .= $this->create_edit_span($ep->phrase, $translated_text, $source,true).$spanend;
                        $span = '';
                        $spanend = '';
                    } else {
                        $span = $this->create_edit_span($ep->phrase, $translated_text, $source);
                        if ($translated_text == null) $translated_text = $ep->phrase;
                    }
                }
                else {
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
                logger ("phrase: $newtext",4);
            }
            // hmm?
            if (!$ep->inselect && $savedspan && $ep->inbody) {
                $e->outertext = $savedspan.$e->outertext;
                $savedspan = "";
            }

        }

        // now we handle the title attributes (and the value of submit buttons)
        foreach (array('title','value') as $title) {
            $hidden_phrases = array();
            foreach ($this->html->find('['.$title.']') as $e) {
                $span = '';
                $spanend = '';
                $right = '';
                $newtext = '';
                // when we already have a parent outertext we'll have to update it directly
                if ($e->parent->_[HDOM_INFO_OUTER]) {
                    $saved_outertext = $e->outertext;
                }
                logger ("$title-original: $e->$title}",4);
                foreach ($e->nodes as $ep) {
                    if ($ep->tag == 'phrase') {
                        list ($translated_text, $source) = call_user_func_array($this->fetch_translate_func,array($ep->phrase, $this->lang));
                        // more stats
                        $this->stats->total_phrases++;
                        if ($ep->inbody) $this->stats->hidden_phrases++; else $this->stats->meta_phrases++;
                        if ($translated_text) {
                            $this->stats->translated_phrases++;
                            if ($ep->inbody) $this->stats->hidden_translated_phrases++; else $this->stats->meta_translated_phrases++;
                            if ($source == 0) $this->stats->human_translated_phrases++;
                        }
                        if (($this->is_edit_mode || ($this->is_auto_translate && $translated_text == null)) && $ep->inbody) {
                            // prevent duplicate translation (title = text)
                            if (strpos($e->innertext,base64_url_encode($ep->phrase)) === false) {
                                //no need to translate span the same hidden phrase more than once
                                if (!in_array($ep->phrase, $hidden_phrases)) {
                                    $this->stats->hidden_translateable_phrases++;
                                    $span .= $this->create_edit_span($ep->phrase, $translated_text, $source, true)."</span>";
                                    //    logger ($span);
                                    $hidden_phrases[] = $ep->phrase;
                                }
                            }
                        }
                        if ($translated_text) {
                            list ($left, $right) = explode($ep->phrase, $e->$title, 2);
                            $newtext .= $left.$translated_text;
                            $e->$title = $right;
                        }
                    }
                }
                if ($newtext) {
                    $e->$title = $newtext.$right;
                    logger ("$title-phrase: $newtext",4);
                }

                $e->outertext .= $span;
                // this is where we update in the outercase issue
                if ($e->parent->_[HDOM_INFO_OUTER]) {
                    $e->parent->outertext = implode ($e->outertext,explode($saved_outertext,$e->parent->outertext,2));
                }
            }
        }

        // now we handle the meta content - which is simpler because they can't be edited or auto-translated
        // we also don't expect any father modifications here
        foreach ($this->html->find('[content]') as $e) {
            $right = '';
            $newtext = '';

            foreach ($e->nodes as $ep) {
                if ($ep->tag == 'phrase') {
                    // even more stats
                    $this->stats->total_phrases++;
                    $this->stats->meta_phrases++;
                    list ($translated_text, $source) = call_user_func_array($this->fetch_translate_func,array($ep->phrase, $this->lang));
                    if ($translated_text) {
                        $this->stats->translated_phrases++;
                        $this->stats->meta_translated_phrases++;
                        if ($source == 0) $this->stats->human_translated_phrases++;
                        list ($left, $right) = explode($ep->phrase, $e->content, 2);
                        $newtext .= $left.$translated_text;
                        $e->content = $right;
                    }
                }
            }
            if ($newtext) {
                $e->content = $newtext.$right;
                logger ("content-phrase: $newtext",4);
            }

        }

        // This adds a meta tag with our statistics json-encoded inside...
        $this->stats->stop_timing();
        $head = $this->html->find('head',0);
        if ($head != null)
            $head->lastChild()->outertext .= "\n<meta name=\"translation-stats\" content='".json_encode($this->stats)."'/>";

        // Changed because of places where tostring failed
        //return $this->html;
        return $this->html->outertext;
    }

    /**
     * This functions returns a list of phrases from a given HTML string
     * @param string $string Html with phrases to extract
     * @return array List of phrases (or an empty one)
     * @since 0.3.5
     */
    function get_phrases_list($string) {
        $result = array();
        // create our dom
        $this->html = str_get_html($string);
        // mark translateable elements
        $this->translate_tagging($this->html->root);
        foreach ($this->html->nodes as $ep) {
            if ($ep->tag == 'phrase') {
                $result[$ep->phrase] = $ep->phrase;
            }
        }
        return $result;
    }
}
?>