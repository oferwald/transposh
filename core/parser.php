<?php

/*
 * Transposh v%VERSION%
 * http://transposh.org/
 *
 * Copyright %YEAR%, Team Transposh
 * Licensed under the GPL Version 2 or higher.
 * http://transposh.org/license
 *
 * Date: %DATE%
 */

require_once("shd/simple_html_dom.php");
require_once("constants.php");
require_once("logging.php");
require_once("utils.php");

/**
 * parserstats class - holds parser statistics
 */
class tp_parserstats {

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
        $this->time = number_format(microtime(true) - $this->start_time, 3);
    }

}

/**
 * Parser class - allows phrase marking and translation with callback functions
 */
class tp_parser {

    private $punct_breaks = true;
    private $num_breaks = true;
    private $ent_breaks = true;
    // functions that need to be defined... //
    /** @var function */
    public $url_rewrite_func = null;

    /** @var function */
    public $fetch_translate_func = null;

    /** @var function */
    public $prefetch_translate_func = null;

    /** @var function */
    public $split_url_func = null;

    /** @var function */
    public $fix_src_tag_func = null;

    /** @var int stores the number of the last used span_id */
    private $span_id = 0;

    /** @var simple_html_dom_node Contains the current node */
    private $currentnode;

    /** @var simple_html_dom Contains the document dom model */
    private $html;
    // the document
    public $dir_rtl;

    /** @var string Contains the iso of the target language */
    public $lang;

    /** @var boolean Contains the fact that this language is the default one (only parse other lanaguage spans) */
    public $default_lang = false;

    /** @var string Contains the iso of the source language - if a lang attribute is found, assumed to be en by default */
    public $srclang;
    private $inbody = false;

    /** @var hold fact that we are in select or other similar elements */
    private $inselect = false;
    public $is_edit_mode;
    public $is_auto_translate;
    public $feed_fix;

    /** @var boolean should we attempt to handle page as json */
    public $might_json = false;
    public $allow_ad = false;
    //first three are html, later 3 come from feeds xml (link is problematic...)
    protected $ignore_tags = array('script' => 1, 'style' => 1, 'code' => 1, 'wfw:commentrss' => 1, 'comments' => 1, 'guid' => 1);

    /** @var parserstats Contains parsing statistics */
    private $stats;

    /** @var boolean Are we inside a translated gettext */
    private $in_get_text = false;

    /** @var boolean Are we inside an inner text %s in gettext */
    private $in_get_text_inner = false;

    /** @var string Additional header information */
    public $added_header;

    /** @var array Contains reference to changable a tags */
    private $atags = array();

    /** @var array Contains reference to changable option values */
    private $otags = array();
    public $edit_span_created = false;

    /** @var array store all values that may be prefetched */
    private $prefetch_phrases = array();

    /**
     * Determine if the current position in buffer is a white space.
     * @param char $char
     * @return boolean true if current position marks a white space
     */
    function is_white_space($char) {
        if (!$char)
            return TRUE;
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
        if ($string[$position] == '&') {
            $end_pos = $position + 1;
            while ($string[$end_pos] == '#' || $this->is_digit($string[$end_pos]) || $this->is_a_to_z_character($string[$end_pos]))
                ++$end_pos;
            if ($string[$end_pos] == ';')
                return $end_pos - $position + 1;
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
    function is_entity_breaker($entity) { // &#8216;&#8217;??
        return !(stripos('&#8216;&#8217;&apos;&quot;&#039;&#39;&rsquo;&lsquo;&rdquo;&ldquo;', $entity) !== FALSE);
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

      Latin-1 extended
      &OElig;     &#338;                        latin capital ligature OE
      &oelig;     &#339;                        latin small ligature oe
      &Scaron;    &#352;                        latin capital letter S with caron
      &scaron;    &#353;                        latin small letter s with caron
      &Yuml;      &#376;                        latin capital letter Y with diaeresis
     */
    function is_entity_letter($entity) {
        tp_logger("checking ($entity) - " . htmlentities($entity), 4);
        $entnum = (int) substr($entity, 2);
        // skip multiply and divide (215, 247) 
        if (($entnum >= 192 && $entnum <= 214) || ($entnum >= 216 && $entnum <= 246) || ($entnum >= 248 && $entnum <= 696)) {
            return true;
        }
        $entities = '&Agrave;&Aacute;&Acirc;&Atilde;&Auml;&Aring;&AElig;&Ccedil;&Egrave;&Eacute;&Ecirc;&Euml;&Igrave;&Iacute;&Icirc;&Iuml;&ETH;' .
                '&Ntilde;&Ograve;&Oacute;&Ocirc;&Otilde;&Ouml;&Oslash;&Ugrave;&Uacute;&Ucirc;&Uuml;&Yacute;&THORN;&szlig;' .
                '&oslash;&ugrave;&yuml;&oelig;&scaron;&nbsp;';
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
        if (($char == '.' || $char == '-') && ($this->is_white_space($nextchar)))
            return 1;
        //，
        if (ord($char) == 239 && ord($nextchar) == 188 && ord($nextnextchar) == 140)
            return 3;
        //。
        if (ord($char) == 227 && ord($nextchar) == 128 && ord($nextnextchar) == 130)
            return 3;        
        //、
        if (ord($char) == 227 && ord($nextchar) == 128 && ord($nextnextchar) == 129)
            return 3;        
        //；
        if (ord($char) == 239 && ord($nextchar) == 188 && ord($nextnextchar) == 155)
            return 3;        
        //：
        if (ord($char) == 239 && ord($nextchar) == 188 && ord($nextnextchar) == 154)
            return 3;        
        //∙
        if (ord($char) == 226 && ord($nextchar) == 136 && ord($nextnextchar) == 153)
            return 3;
        //·
        if (ord($char) == 194 && ord($nextchar) == 183)
            return 2;
        return (strpos(',?()[]{}"!:|;' . TP_GTXT_BRK . TP_GTXT_BRK_CLOSER . TP_GTXT_IBRK . TP_GTXT_IBRK_CLOSER, $char) !== false) ? 1 : 0; // TODO: might need to add < and > here
    }

    /**
     * Determines if the current position marks the begining of a number, e.g. 123 050-391212232
     * @return int length of number.
     */
    function is_number($page, $position) {
        return strspn($page, '0123456789-+$%#*,.\\/', $position);
    }

    /**
     * Create a phrase tag in the html dom tree
     * @param int $start - beginning of phrase in element
     * @param int $end - end of phrase in element
     */
    function tag_phrase($string, $start, $end) {
        $phrase = trim(substr($string, $start, $end - $start));
        $phrasefixed = trim(str_replace('&nbsp;', ' ', $phrase));
//        $logstr = str_replace(array(chr(1),chr(2),chr(3),chr(4)), array('[1]','[2]','[3]','[4]'), $string);
//        tp_logger ("p:$phrasefixed, s:$logstr, st:$start, en:$end, gt:{$this->in_get_text}, gti:{$this->in_get_text_inner}");
        if ($this->in_get_text > $this->in_get_text_inner) {
            tp_logger('not tagging ' . $phrase . ' assumed gettext translated', 4);
            return;
        }
        if ($phrase) {
            tp_logger('tagged phrase: ' . $phrase, 4);
            $node = new simple_html_dom_node($this->html);
            $node->tag = 'phrase';
            $node->parent = $this->currentnode;
            $this->currentnode->nodes[] = $node;
            $node->_[HDOM_INFO_OUTER] = '';
            $node->phrase = $phrasefixed;
            $this->prefetch_phrases[$phrasefixed] = true;
            $node->start = $start;
            $node->len = strlen($phrase);
            if ($this->srclang)
                $node->srclang = $this->srclang;
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
            if (strpos($string, '<![CDATA[') === 0) {
                $pos = 9; // CDATA length
                $string = substr($string, 0, -3); // chop the last ]]>;
            }
        }

        $start = $pos;

        while ($pos < strlen($string)) {
            // Some HTML entities make us break, almost all but apostrophies
            if ($this->ent_breaks && $len_of_entity = $this->is_html_entity($string, $pos)) {
                $entity = substr($string, $pos, $len_of_entity);
                if (($this->is_white_space(@$string[$pos + $len_of_entity]) || $this->is_entity_breaker($entity)) && !$this->is_entity_letter($entity)) {
                    tp_logger("entity ($entity) breaks", 4);
                    $this->tag_phrase($string, $start, $pos);
                    $start = $pos + $len_of_entity;
                }
                // skip nbsp starting a phrase
                tp_logger("entity ($entity)", 4);
                if ($entity === '&nbsp;' && $start === $pos) {
                    $start = $pos + $len_of_entity;
                }
                //skip past entity
                $pos += $len_of_entity;
            }
            // we have a special case for <> tags which might have came to us (maybe in xml feeds) (we'll skip them...)
            elseif ($string[$pos] == '<') {
                $this->tag_phrase($string, $start, $pos);
                while ($string[$pos] != '>' && $pos < strlen($string))
                    $pos++;
                $pos++;
                $start = $pos;
            } elseif ($string[$pos] == TP_GTXT_BRK || $string[$pos] == TP_GTXT_BRK_CLOSER) {
//                $logstr = str_replace(array(chr(1),chr(2),chr(3),chr(4)), array('[1]','[2]','[3]','[4]'), $string);
//                $closers = ($string[$pos] == TP_GTXT_BRK) ? '': 'closer';
//                tp_logger(" $closers TEXT breaker $logstr start:$start pos:$pos gt:" . $this->in_get_text, 3);
                $this->tag_phrase($string, $start, $pos);
                ($string[$pos] == TP_GTXT_BRK) ? $this->in_get_text += 1 : $this->in_get_text -= 1;
                $pos++;
                $start = $pos;
                // reset state based on string start, no need to flip
                //$this->in_get_text = ($pos == 1);
                //if (!$this->in_get_text) $this->in_get_text_inner = false;
            } elseif ($string[$pos] == TP_GTXT_IBRK || $string[$pos] == TP_GTXT_IBRK_CLOSER) {
//                $logstr = str_replace(array(chr(1),chr(2),chr(3),chr(4)), array('[1]','[2]','[3]','[4]'), $string);
//                $closers = ($string[$pos] == TP_GTXT_IBRK) ? '': 'closer';
//                tp_logger("   $closers INNER text breaker $logstr start:$start pos:$pos gt:" . $this->in_get_text_inner, 3);
                //tp_logger("inner text breaker $start $pos $string " . (($this->in_get_text_inner) ? 'true' : 'false'), 5);
                $this->tag_phrase($string, $start, $pos);
                if ($this->in_get_text)
                    ($string[$pos] == TP_GTXT_IBRK) ? $this->in_get_text_inner += 1 : $this->in_get_text_inner -=1;
                $pos++;
                $start = $pos;
                //$this->in_get_text_inner = !$this->in_get_text_inner;
            }
            // will break translation unit when there's a breaker ",.[]()..."
            elseif ($this->punct_breaks && $senb_len = $this->is_sentence_breaker($string[$pos], @$string[$pos + 1], @$string[$pos + 2])) {
//                logger ("sentence breaker...");
                $this->tag_phrase($string, $start, $pos);
                $pos += $senb_len;
                $start = $pos;
            }
            // Numbers also break, if they are followed by whitespace (or a sentence breaker) (don't break 42nd) // TODO: probably by breaking entities too...
            // also prefixed by whitespace?
            elseif ($this->num_breaks && $num_len = $this->is_number($string, $pos)) {
//                logger ("numnum... $num_len");
                // this is the case of B2 or B2,
                if (($start == $pos) || ($this->is_white_space($string[$pos - 1]) || ($this->is_sentence_breaker(@$string[$pos + $num_len - 1], @$string[$pos + $num_len], @$string[$pos + $num_len + 1]))) &&
                        ($this->is_white_space(@$string[$pos + $num_len]) || $this->is_sentence_breaker(@$string[$pos + $num_len], @$string[$pos + $num_len + 1], @$string[$pos + $num_len + 2]))) {
                    // we will now compensate on the number followed by breaker case, if we need to
//                            logger ("compensate part1?");
                    if (!(($start == $pos) || $this->is_white_space($string[$pos - 1]))) {
//                            logger ("compensate part2?");
                        if ($this->is_sentence_breaker($string[$pos + $num_len - 1], @$string[$pos + $num_len], @$string[$pos + $num_len + 1])) {
//                            logger ("compensate 3?");
                            $num_len--; //this makes the added number shorter by one, and the pos will be at a sentence breaker next so we don't have to compensate
                        }
                        $pos += $num_len;
                        $num_len = 0; // we have already added this
                    }
                    $this->tag_phrase($string, $start, $pos);
                    $start = $pos + $num_len /* +1 */;
                }
                $pos += $num_len/* + 1 */;
//                logger ("numnumpos... $pos");
            } else {
                // smarter marking of start location
                if ($start == $pos && $this->is_white_space($string[$pos]))
                    $start++;
                $pos++;
            }
        }

        // the end is also some breaker
        if ($pos > $start) {
            $this->tag_phrase($string, $start, $pos);
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
        if (stripos($node->class, NO_TRANSLATE_CLASS) !== false || stripos($node->class, NO_TRANSLATE_CLASS_GOOGLE) !== false)
            return;

        // the node lang is the current node lang or its parent lang
        if ($node->lang) {
            // allow nesting of srclang (again - local var)
            $prevsrclang = $this->srclang;
            $this->srclang = strtolower($node->lang);
            // using a local variable scope for later
            $src_set_here = true;
            // eliminate the lang tag from the html, since we aim to translate it
            unset($node->lang);
        }

        // we can only do translation for elements which are in the body, not in other places, and this must
        // move here due to the possibility of early recurse in default language
        if ($node->tag == 'body') {
            $this->inbody = true;
        }

        // this again should be here, the different behaviour on select and textarea
        // for now - we assume that they can't include each other
        elseif ($node->tag == 'select' || $node->tag == 'textarea' || $node->tag == 'noscript') {
            $this->inselect = true;
            $inselect_set_here = true;
        }

        //support only_thislanguage class, (nulling the node if it should not display)
        if (isset($src_set_here) && $src_set_here && $this->srclang != $this->lang && stripos($node->class, ONLY_THISLANGUAGE_CLASS) !== false) {
            $this->srclang = $prevsrclang; //we should return to the previous src lang or it will be kept and carried
            $node->outertext = '';
            return;
        }

        // if we are in the default lang, and we have no foreign langs classes, we'll recurse from here
        // we also avoid processing if the node lang is the target lang
        if (($this->default_lang && !$this->srclang) || ($this->srclang === $this->lang)) {
            foreach ($node->nodes as $c) {
                $this->translate_tagging($c, $level + 1);
            }
            if (isset($src_set_here) && $src_set_here)
                $this->srclang = $prevsrclang;
            if (isset($inselect_set_here) && $inselect_set_here)
                $this->inselect = false;
            return;
        }

        if (isset($this->ignore_tags[$node->tag]))
            return;

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
            array_push($this->atags, $node);
        }
        // same for options, although normally not required (ticket #34)
        elseif ($node->tag == 'option') {
            array_push($this->otags, $node);
        }
        // in submit type inputs, we want to translate the value
        elseif ($node->tag == 'input' && $node->type == 'submit') {
            $this->parsetext($node->value);
        }
        // for iframes we will rewrite urls if we can
        elseif ($node->tag == 'iframe') {
            if ($this->url_rewrite_func) {
                $node->src = call_user_func_array($this->url_rewrite_func, array($node->src));
                tp_logger('iframe: ' . $node->src, 4);
            }
        }

        // titles and placeholders are also good places to translate, exist in a, img, abbr, acronym
        if ($node->title) {
            $this->parsetext($node->title);
        }
        if ($node->placeholder) {
            $this->parsetext($node->placeholder);
        }
        if ($node->alt) {
            $this->parsetext($node->alt);
        }

        // Meta content (keywords, description) are also good places to translate (but not in robots... or http-equiv)
        if ($node->tag == 'meta' && $node->content && ($node->name != 'robots') && ($node->name != 'viewport') && ($node->{'http-equiv'} != 'Content-Type'))
            $this->parsetext($node->content);

        // recurse
        foreach ($node->nodes as $c) {
            $this->translate_tagging($c, $level + 1);
        }
        if (isset($src_set_here) && $src_set_here)
            $this->srclang = $prevsrclang;
        if (isset($inselect_set_here) && $inselect_set_here)
            $this->inselect = false;
    }

    /**
     * Creates a span used in translation and editing
     * @param string $original_text
     * @param string $translated_text
     * @param int $source (Either "0" for Human, "1" for Machine or "" for untouched)
     * @param boolean $for_hidden_element
     * @param string $src_lang - if source lang of element is different that default (eg. wrapped in lang="xx" attr)
     * @return string
     */
    function create_edit_span($original_text, $translated_text, $source, $for_hidden_element = false, $src_lang = '') {
        // Use base64 encoding to make that when the page is translated (i.e. update_translation) we
        // get back exactlly the same string without having the client decode/encode it in anyway.
        $this->edit_span_created = true;
        $span = '<span class ="' . SPAN_PREFIX . '" id="' . SPAN_PREFIX . $this->span_id . '" data-source="' . $source . '"';
        //$span = '<span class ="' . SPAN_PREFIX . '" id="' . SPAN_PREFIX . $this->span_id . '" data-token="' . transposh_utils::base64_url_encode($original_text) . '" data-source="' . $source . '"';
        // if we have a source language
        if ($src_lang) {
            $span .= ' data-srclang="' . $src_lang . '"';
        }
        // since orig replaces token too
        $span .= ' data-orig="' . $original_text . '"';
        // those are needed for hidden elements translations
        if ($for_hidden_element) {
            $span.= ' data-hidden="y"';
            // hidden elements currently have issues figuring what they translated in the JS
            if ($translated_text != null) {
                $span.= ' data-trans="' . $translated_text . '"';
            }
        }
        $span .= '>';
        if (!$for_hidden_element) {
            if ($translated_text)
                $span .= $translated_text;
            else
                $span .= $original_text;
        }
        $span .= '</span>';
        ++$this->span_id;
        return $span;
    }

    /**
     * This function does some ad replacement for transposh benefit
     */
    function do_ad_switch() {
        if (isset($this->html->noise) && is_array($this->html->noise)) {
            foreach ($this->html->noise as $key => $value) {
                if (strpos($value, 'google_ad_client') !== false) {
                    $publoc = strpos($value, 'pub-');
                    $sufloc = strpos($value, '"', $publoc);
                    if (!$sufloc)
                        $sufloc = strpos($value, "'", $publoc);
                    echo $publoc . ' ' . $sufloc;
                    if ($publoc && $sufloc)
                        $this->html->noise[$key] = substr($value, 0, $publoc) . 'pub-7523823497771676' . substr($value, $sufloc);
                }
            }
        }
        // INS TAGS
        foreach ($this->html->find('ins') as $e) {
            $e->{'data-ad-client'} = 'ca-pub-7523823497771676';
        }
    }

    /**
     * Allow changing of parsing rules, yeah, I caved
     * @param type $puncts
     * @param type $numbers
     * @param type $entities
     */
    function change_parsing_rules($puncts, $numbers, $entities) {
        $this->punct_breaks = $puncts;
        $this->num_breaks = $numbers;
        $this->ent_breaks = $entities;
    }

    /**
     * Main function - actually translates a given HTML
     * @param string $string containing HTML
     * @return string Translated content is here
     */
    function fix_html($string) {
        // ready our stats
        $this->stats = new tp_parserstats();
        // handler for possible json (buddypress)
        if ($this->might_json) {
            if ($string[0] == '{') {
                $jsoner = json_decode($string);
                if ($jsoner != null) {
                    tp_logger("json detected (buddypress?)", 4);
                    // currently we only handle contents (which buddypress heavily use)
                    if ($jsoner->contents) {
                        $jsoner->contents = $this->fix_html($jsoner->contents);
                    }
                    if ($jsoner->fragments->{'div.widget_shopping_cart_content'}) {
                        $jsoner->fragments->{'div.widget_shopping_cart_content'} = $this->fix_html($jsoner->fragments->{'div.widget_shopping_cart_content'});
                    }
                    if ($jsoner->fragments->{'div.kt-header-mini-cart-refreash'}) {
                        $jsoner->fragments->{'div.kt-header-mini-cart-refreash'} = $this->fix_html($jsoner->fragments->{'div.kt-header-mini-cart-refreash'});
                    }
                    if ($jsoner->fragments->{'a.cart-contents'}) {
                        $jsoner->fragments->{'a.cart-contents'} = $this->fix_html($jsoner->fragments->{'a.cart-contents'});
                    }
                    if ($jsoner->fragments->{'.woocommerce-checkout-review-order-table'}) {
			$jsoner->fragments->{'.woocommerce-checkout-review-order-table'} = $this->fix_html($jsoner->fragments->{'.woocommerce-checkout-review-order-table'});
		    }
		    if ($jsoner->fragments->{'.woocommerce-checkout-payment'}) {
			$jsoner->fragments->{'.woocommerce-checkout-payment'} = $this->fix_html($jsoner->fragments->{'.woocommerce-checkout-payment'});
		    }			
                    return json_encode($jsoner); // now any attempted json will actually return a json 
                }
            }
        }

        // create our dom
        $string = str_replace(chr(0xC2) . chr(0xA0), ' ', $string); // annoying NBSPs?
        $this->html = str_get_html($string, false); // false for RSS?
        //$this->stats->do_timing();
        //Log::info("Stats Build dom:" . $this->stats->time);
        // mark translateable elements
        if ($this->html->find('html', 0))
            $this->html->find('html', 0)->lang = ''; // Document defined lang may be preset to correct lang, but should be ignored TODO: Better?
        $this->translate_tagging($this->html->root);
        //$this->stats->do_timing();
        //Log::info("Stats Done tagging:" . $this->stats->time);
        // first fix the html tag itself - we might need to to the same for all such attributes with flipping
        if ($this->html->find('html', 0)) {
            if ($this->dir_rtl)
                $this->html->find('html', 0)->dir = 'rtl';
            else
                $this->html->find('html', 0)->dir = 'ltr';
        }

        if ($this->lang) {
            if ($this->html->find('html', 0))
                $this->html->find('html', 0)->lang = $this->lang;
            // add support for <meta name="language" content="<lang>">
            if ($this->html->find('meta[name=language]')) {
                @$this->html->find('meta[name=language]')->content = $this->lang;
            }
        }

        // not much point in further processing if we don't have a function that does it
        if ($this->fetch_translate_func == null) {
            return $this->html;
        }

        // fix feed
        if ($this->feed_fix) {
            // fix urls on feed
            tp_logger('fixing rss feed', 3);
            foreach (array('link', 'wfw:commentrss', 'comments') as $tag) {
                foreach ($this->html->find($tag) as $e) {
                    $e->innertext = htmlspecialchars(call_user_func_array($this->url_rewrite_func, array($e->innertext)));
                    // no need to translate anything here
                    unset($e->nodes);
                }
            }
            // guid is not really a url -- in some future, we can check if permalink is true and probably falsify it
            foreach ($this->html->find('guid') as $e) {
                $e->innertext = $e->innertext . '-' . $this->lang;
                unset($e->nodes);
            }
            // fix feed language
            @$this->html->find('language', 0)->innertext = $this->lang;
            unset($this->html->find('language', 0)->nodes);
        } else {
            // since this is not a feed, we might have references to such in the <link rel="alternate">
            foreach ($this->html->find('link') as $e) {
                if (strcasecmp($e->rel, 'alternate') == 0 || strcasecmp($e->rel, 'canonical') == 0) {
                    $e->href = call_user_func_array($this->url_rewrite_func, array($e->href));
                }
            }
        }

        // try some prefetching... (//todo - maybe move directly to the phrase create)
//        $originals = array();
        if ($this->prefetch_translate_func != null) {
            /*          foreach ($this->html->find('text') as $e) {
              foreach ($e->nodes as $ep) {
              if ($ep->phrase) $originals[$ep->phrase] = true;
              }
              }
              foreach (array('title', 'value', 'placeholder', 'alt') as $title) {
              foreach ($this->html->find('[' . $title . ']') as $e) {
              if (isset($e->nodes))
              foreach ($e->nodes as $ep) {
              if ($ep->phrase) $originals[$ep->phrase] = true;
              }
              }
              }
              foreach ($this->html->find('[content]') as $e) {
              foreach ($e->nodes as $ep) {
              if ($ep->phrase) $originals[$ep->phrase] = true;
              }
              } */
            // if we should split, we will split some urls for translation prefetching
            if ($this->split_url_func != null) {
                foreach ($this->atags as $e) {
                    foreach (call_user_func_array($this->split_url_func, array($e->href)) as $part) {
                        $this->prefetch_phrases[$part] = true;
                    }
                }
                foreach ($this->otags as $e) {
                    foreach (call_user_func_array($this->split_url_func, array($e->value)) as $part) {
                        $this->prefetch_phrases[$part] = true;
                    }
                }
            }
            call_user_func_array($this->prefetch_translate_func, array($this->prefetch_phrases, $this->lang));
        }

        //fix urls more
        // WORK IN PROGRESS
        /* foreach ($this->atags as $e) {
          $hrefspans = '';
          foreach (call_user_func_array($this->split_url_func, array($e->href)) as $part) {
          // fix - not for dashes
          list ($source, $translated_text) = call_user_func_array($this->fetch_translate_func, array($part, $this->lang));
          $hrefspans .= $this->create_edit_span($part, $translated_text, $source, true);
          }
          $e->href = call_user_func_array($this->url_rewrite_func, array($e->href));
          $e->outertext .= $hrefspans;
          } */

        // fix src for items
        if ($this->fix_src_tag_func !== null) {
            foreach ($this->html->find('[src]') as $e) {
                $e->src = call_user_func_array($this->fix_src_tag_func, array($e->src));
            }

            foreach ($this->html->find('link') as $e) {
                $e->href = call_user_func_array($this->fix_src_tag_func, array($e->href));
            }
        }

        // fix urls...
        foreach ($this->atags as $e) {
            if ($e->href)
                $e->href = call_user_func_array($this->url_rewrite_func, array($e->href));
        }
        foreach ($this->otags as $e) {
            if ($e->value)
                $e->value = call_user_func_array($this->url_rewrite_func, array($e->value));
        }

        // this is used to reserve spans we cannot add directly (out of body, metas, etc)
        $hiddenspans = '';
        $savedspan = '';

        // actually translate tags
        // texts are first
        foreach ($this->html->find('text') as $e) {
            $replace = array();
            foreach ($e->nodes as $ep) {
                list ($source, $translated_text) = call_user_func_array($this->fetch_translate_func, array($ep->phrase, $this->lang));
                //stats
                $this->stats->total_phrases++;
                if ($translated_text) {
                    $this->stats->translated_phrases++;
                    if ($source == 0)
                        $this->stats->human_translated_phrases++;
                }
                if (($this->is_edit_mode || ($this->is_auto_translate && $translated_text == null))/* && $ep->inbody */) {
                    if ($ep->inselect) {
                        $savedspan .= $this->create_edit_span($ep->phrase, $translated_text, $source, true, $ep->srclang);
                    } elseif (!$ep->inbody) {
                        $hiddenspans .= $this->create_edit_span($ep->phrase, $translated_text, $source, true, $ep->srclang);
                    } else {
                        $translated_text = $this->create_edit_span($ep->phrase, $translated_text, $source, false, $ep->srclang);
                    }
                }
                // store replacements
                if ($translated_text) {
                    $replace[] = array($translated_text, $ep);
                }
            }
            // do replacements in reverse
            foreach (array_reverse($replace) as $epag) {
                list($replacetext, $epg) = $epag;
                $e->outertext = substr_replace($e->outertext, $replacetext, $epg->start, $epg->len);
            }

            // this adds saved spans to the first not in select element which is in the body
            if ($e->nodes && !$ep->inselect && $savedspan && $ep->inbody) { // (TODO: might not be...?)
                $e->outertext = $savedspan . $e->outertext;
                $savedspan = '';
            }
        }

        // now we handle the title attributes (and the value of submit buttons)
        $hidden_phrases = array();
        foreach (array('title', 'value', 'placeholder', 'alt') as $title) {
            foreach ($this->html->find('[' . $title . ']') as $e) {
                $replace = array();
                $span = '';
                // when we already have a parent outertext we'll have to update it directly
                if (isset($e->parent->_[HDOM_INFO_OUTER])) {
                    $saved_outertext = $e->outertext;
                }
                tp_logger("$title-original: $e->$title}", 4);
                if (isset($e->nodes))
                    foreach ($e->nodes as $ep) {
                        if ($ep->tag == 'phrase') {
                            list ($source, $translated_text) = call_user_func_array($this->fetch_translate_func, array($ep->phrase, $this->lang));
                            // more stats
                            $this->stats->total_phrases++;
                            if ($ep->inbody)
                                $this->stats->hidden_phrases++;
                            else
                                $this->stats->meta_phrases++;
                            if ($translated_text) {
                                $this->stats->translated_phrases++;
                                if ($ep->inbody)
                                    $this->stats->hidden_translated_phrases++;
                                else
                                    $this->stats->meta_translated_phrases++;
                                if ($source == 0)
                                    $this->stats->human_translated_phrases++;
                            }
                            if (($this->is_edit_mode || ($this->is_auto_translate && $translated_text == null)) && $ep->inbody) {
                                // prevent duplicate translation (title = text)
                                if (strpos($e->innertext, $ep->phrase /* Transposh_utils::base64_url_encode($ep->phrase) */) === false) {
//                                if (strpos($e->innertext, transposh_utils::base64_url_encode($ep->phrase)) === false) {
                                    //no need to translate span the same hidden phrase more than once
                                    if (!in_array($ep->phrase, $hidden_phrases)) {
                                        $this->stats->hidden_translateable_phrases++;
                                        $span .= $this->create_edit_span($ep->phrase, $translated_text, $source, true, $ep->srclang);
                                        //    logger ($span);
                                        $hidden_phrases[] = $ep->phrase;
                                    }
                                }
                            }
                            // if we need to replace, we store this
                            if ($translated_text) {
                                $replace[$translated_text] = $ep;
                            }
                        }
                    }
                // and later replace
                foreach (array_reverse($replace, true) as $replace => $epg) {
                    $e->$title = substr_replace($e->$title, $replace, $epg->start, $epg->len);
                }

                $e->outertext .= $span;
                // this is where we update in the outercase issue
                if (isset($e->parent->_[HDOM_INFO_OUTER])) {
                    $e->parent->outertext = implode($e->outertext, explode($saved_outertext, $e->parent->outertext, 2));
                }
            }
        }

        // now we handle the meta content - which is simpler because they can't be edited or auto-translated in place
        // we also don't expect any father modifications here
        // so we now add all those spans right before the <body> tag end
        foreach ($this->html->find('[content]') as $e) {
            $right = '';
            $newtext = '';

            foreach ($e->nodes as $ep) {
                if ($ep->tag == 'phrase') {
                    // even more stats
                    $this->stats->total_phrases++;
                    $this->stats->meta_phrases++;
                    list ($source, $translated_text) = call_user_func_array($this->fetch_translate_func, array($ep->phrase, $this->lang));
                    if ($translated_text) {
                        $this->stats->translated_phrases++;
                        $this->stats->meta_translated_phrases++;
                        if ($source == 0)
                            $this->stats->human_translated_phrases++;
                        list ($left, $right) = explode($ep->phrase, $e->content, 2);
                        $newtext .= $left . $translated_text;
                        $e->content = $right;
                    }
                    if ($this->is_edit_mode) {
                        $hiddenspans .= $this->create_edit_span($ep->phrase, $translated_text, $source, true, $ep->srclang);
                    }
                    if (!$translated_text && $this->is_auto_translate && !$this->is_edit_mode) {
                        tp_logger('untranslated meta for ' . $ep->phrase . ' ' . $this->lang);
                        if ($this->is_edit_mode || $this->is_auto_translate) { // FIX
                        }
                    }
                }
            }
            if ($newtext) {
                $e->content = $newtext . $right;
                tp_logger("content-phrase: $newtext", 4);
            }
        }

        if ($hiddenspans) {
            $body = $this->html->find('body', 0);
            if ($body != null)
                $body->lastChild()->outertext .= $hiddenspans;
        }
        // we might show an ad for transposh in some cases
        if (($this->allow_ad && !$this->default_lang && mt_rand(1, 100) > 95) || // 5 of 100 for translated non default language pages
                ($this->allow_ad && $this->default_lang && mt_rand(1, 100) > 99) || // 1 of 100 for translated default languages pages
                (!$this->allow_ad && mt_rand(1, 1000) > 999)) { // 1 of 1000 otherwise
            $this->do_ad_switch();
        }
        // This adds a meta tag with our statistics json-encoded inside...
//      $this->stats->do_timing();
//        Log::info("Stats Done:" . $this->stats->time);

        $head = $this->html->find('head', 0);
        if ($this->edit_span_created) {
            if ($head != null) {
                $head->lastChild()->outertext .= $this->added_header;
            }
        }
        //exit;
        if ($head != null)
            $head->lastChild()->outertext .= "\n<meta name=\"translation-stats\" content='" . json_encode($this->stats) . "'/>";

        // we make sure that the result is clear from our shananigans
        return str_replace(array(TP_GTXT_BRK, TP_GTXT_IBRK, TP_GTXT_BRK_CLOSER, TP_GTXT_IBRK_CLOSER), '', $this->html->outertext);
        // Changed because of places where tostring failed
        //return $this->html;
        //return $this->html->outertext;
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
        $this->html = str_get_html('<span lang="xx">' . $string . '</span>');
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