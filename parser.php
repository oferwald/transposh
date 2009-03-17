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

/*
 * Contains the core functionality of the html parser. I.e. break into translation segments,
 * fetch translation and update the translated page.
 * This file should include only general purpose parser functionality while using callbacks
 * to obtain WorkdPress specific capabilities, e.g. db access.
 */

require_once("logging.php");
require_once("constants.php");

//The language to which the current page will be translated to.
$lang;

//The html page which starts contains the content being translated
$page;

//Marks the current position of the translation process within the page
$pos = 0;

//Contains the stack of tag in the current position within the page
$tags_list = array();

//The translated html page
$tr_page;

//Points to the last character that have been copied from the original to the translated page.
$tr_mark = 0;

//Is the current use is in edit mode.
$is_edit_mode = FALSE;

//Segment identifier within tags (span/img) mainly for use by js code on the client
$segment_id = 0;

//Is current position within the body tag
$is_in_body = FALSE;

//Is current position within the channel tag, i.e. RSS feed
$is_in_channel = FALSE;

/*
 * Parse the html page into tags, identify translateable string which
 * will be translated.
 */
function process_html()
{
	logger("Enter " . __METHOD__, 4);

	global $page, $tr_page, $pos, $tags_list, $lang;
	$no_translate = 0;
	$page_length = strlen($page);

	while($pos < $page_length)
	{
		//find beginning of next tag
		$pos = strpos($page, '<', $pos);
		if($pos === FALSE)
		{
			//logger("Error finding < in pos " . $pos . " page: " . $page);
			break;
		}
		$pos++;

		//Get the element identifying this tag
		$element = get_element();

		if(should_skip_element($element))
		{
			logger ("skipping element: $element");
			//do nothing
		}
		else if($element == '![CDATA[')
		{
			process_cdata_section();
		}
		else
		{
			//Mark tag start position
			$tag_start = $pos;
			$pos = strpos($page, '>', $pos);

			//Mark tag end position
			$tag_end = $pos;

			if($page[$pos-1] == '/')
			{
				//single line tag - no need to update tags list
				process_tag_init($element, $tag_start, $tag_end);
			}
			else if($element[0] != '/')
			{
				if(!$no_translate)
				{
					process_tag_init($element, $tag_start, $tag_end);
				}

				$tags_list[] = $element;

				//Look for the no translate class
				if(stripos($element, NO_TRANSLATE_CLASS) !== FALSE)
				{
					$no_translate++;
				}
			}
			else
			{
				$popped_element = array_pop($tags_list);
				if(!$no_translate)
				{
					process_tag_termination($element);
				}

				//Look for the no translate class
				if(stripos($popped_element, NO_TRANSLATE_CLASS) !== FALSE)
				{
					$no_translate--;
				}
			}

			$pos++;

			//skip processing while enclosed within a tag marked by no_translate
			if(!$no_translate)
			{
				process_current_tag();
			}

		}
	}

	if(strlen($tr_page) > 0)
	{
		//Some translation has been taken place. Complete the translated
		//page up to the full contents of the original page.
		update_translated_page(strlen($page), -1, "");
	}

	logger("Exit " . __METHOD__, 4);
}

/*
 * Determine if the specified element should be skipped. If so the position
 * is moved past end of tag.
 * Return TRUE if element is skipped otherwise FALSE.
 */
function should_skip_element(&$element)
{
	global $page, $pos;
	$rc = TRUE;

	if(strncmp($element, "!DOCTYPE", 8) == 0)
	{
		$pos = strpos($page, '>', $pos);
	}
	else if(strncmp($element, "!--", 3) == 0)
	{
		$pos = strpos($page, '-->', $pos);
	}
	else
	{
		$rc = FALSE;
	}

	return $rc;
}

/*
 * Process tag init for the specified element, with the current start and
 * end positions within the page buffer.
 */
function process_tag_init(&$element, $start, $end)
{
	switch ($element)
	{
		case 'a':
			process_anchor_tag($start, $end);
			break;
		case 'div' :
		case 'span':
			process_span_or_div_tag($element, $start, $end);
			break;
		case 'html':
			process_html_tag($start, $end);
			break;
		case 'body':
			global $is_in_body;
			$is_in_body = TRUE;
			break;
		case 'channel':
			global $is_in_channel;
			$is_in_channel = TRUE;
			break;
	}

}

/*
 * Handle span tags. Looks for 'no_tranlate' identifier that will disable
 * translation for the enclosed text.
 *
 */
function process_span_or_div_tag(&$element, $start, $end)
{

	$cls = get_attribute($start, $end, 'class');

	if($cls == NULL)
	{
		return;
	}

	//Look for the no translate class
	if(stripos($cls, NO_TRANSLATE_CLASS) === FALSE)
	{
		return;
	}

	//Mark the element as not translatable
	$element .= "." . NO_TRANSLATE_CLASS;
}

/*
 * Process html tag. Set the direction for rtl languages.
 *
 */
function process_html_tag($start, $end)
{
	global $lang, $rtl_languages;

	if(!(in_array ($lang, $rtl_languages)))
	{
		return;
	}

	$dir = get_attribute($start, $end, 'dir');

	if($dir == NULL)
	{

		//attribute does not exist - add it
		update_translated_page($end, -1, 'dir="rtl"');
	}
	else
	{
		$dir = 'rtl';

		//rewrite url in translated page
		update_translated_page($start, $end, $dir);

	}
	logger(__METHOD__ . " Changed page direction to rtl");
}


/*
 * Process tag termination.
 * Note: The current position in buffer points to the '>' character
 */
function process_tag_termination(&$element)
{
	global $pos, $tags_list, $page;

	logger(__METHOD__ . " $element ". $page[$pos], 4);
}


/*
 * Return the element id within the current tag.
 */
function get_element()
{
	global $page, $pos;
	logger("Enter " . __METHOD__. ": $pos", 5);

	skip_white_space();

	$start = $pos;

	//check first for a character data section - treat it like an element
	if(is_word('![CDATA['))
	{
		$pos += 8; //skip to end of ![CDATA[
	}
	else
	{
		//keep scanning till the first white space or the '>' mark
		while($pos < strlen($page) && !is_white_space($pos)  &&	 $page[$pos] != '>')
		{
			$pos++;
		}
	}

	logger("Exit " . __METHOD__. ": $pos", 5);
	return substr($page,$start, $pos - $start);
}

/*
 * Search for the given attribute within the limit of the start and
 * end position within the buffer.
 * Returns the string containing the attribute if available otherwise NULL.
 * In addition the start and end position are moved to boundaries of the
 * attribute's value.
 */
function get_attribute(&$start, &$end, $id)
{
	global $page;

	//look for the id within the given limits.
	while($start < $end)
	{
		$index = 0;

		while($start < $end && $page[$start + $index] == $id[$index]
		&& $index < strlen($id))
		{
			$index++;
		}

		if($index == strlen($id))
		{
			//we have match
			break;
		}

		$start++;
	}

	if($start == $end)
	{
		return NULL;
	}

	//look for the " or ' marking start of attribute's value
	while($start < $end && $page[$start] != '"' && $page[$start] != "'")
	{
		$start++;
	}

	$start++;
	if($start >= $end)
	{
		return NULL;
	}

	$tmp = $start + 1;
	//look for the " or ' marking the end of attribute's value
	while($tmp < $end && $page[$tmp] != '"' && $page[$tmp] != "'")
	{
		$tmp++;
	}

	$end = $tmp - 1;


	return substr($page, $start, $end - $start + 1);
}

/*
 * Attempt to process the content of the tag (if exists). If the current
 * is of a type that need translation then translate, otherwise skip.
 *
 */
function process_current_tag()
{
	global $page, $pos;

	logger("Enter " . __METHOD__ , 4);

	//translate only known elements within the body or channel
	if(is_translatable_section())
	{
		skip_white_space();
		$start = $pos;
		$page_length =  strlen($page);

		// Indicates if the html entity should break into a new translation segment.
		$is_breaker = FALSE;

		while($pos < $page_length && $page[$pos] != '<')
		{
			//will break translation unit when one of the following characters is reached: .,
			if(($end_of_entity = is_html_entity($pos, $is_breaker)))
			{
				//Check if should break - value has been set by the is_html_entity function
				if($is_breaker)
				{
					translate_text($start);
					$start = $end_of_entity;
				}

				//skip past entity
				$pos = $end_of_entity;
			}
			else if(is_sentence_breaker($pos))
			{
				translate_text($start);
				$pos++;
				$start = $pos;
			}
			else
			{
				$pos++;
			}
		}

		if($pos > $start)
		{
			translate_text($start);
		}
	}
	logger("Exit" .  __METHOD__ . " : $current_tag" , 4);
}

/*
 * Translate the content of a cdata section.  For now we only expect to handle it
 * within RSS feeds.
 */
function process_cdata_section()
{
	global $page, $pos;

	logger("Enter " . __METHOD__  , 4);

	//translate only known elements within rss feeds
	if(is_translatable_section())
	{
		skip_white_space();
		$start = $pos;
		$page_length =  strlen($page);

		while($pos < $page_length && !is_word(']]>'))
		{
			//will break translation unit when one of the following characters is reached: .,
			if(is_sentence_breaker($pos) ||
			   $page[$pos] == '<' || $page[$pos] == '>')  //only in cdata the < > are valid breakers as well
			{
				translate_text($start);
				$pos++;
				$start = $pos;
			}
			else
			{
				$pos++;
			}
		}

		if($pos > $start)
		{
			translate_text($start);
		}
	}
	logger("Exit " .  __METHOD__ , 4);
}

/**
 * Determines position in page marks a transaltable tag in html page or rss feed section.
 * Return TRUE if should be translated otherwise FALSE.
 */
function is_translatable_section()
{
	global $tags_list, $is_in_channel, $is_in_body;
	$rc = FALSE;
	$current_tag = end($tags_list);

	if($is_in_body || $current_tag == 'title')
	{
		$rc = TRUE;
	}
	else if($is_in_channel &&
	        ($current_tag == 'title' || $current_tag == 'description' || $current_tag == 'category'))
	{
		$rc = TRUE;
	}

	logger("Exit " . __METHOD__ . " $current_tag, translate: " . ($rc ? "yes" : "no"), 4);
	return $rc;
}

/*
 * Determine if the current position in buffer is a sentence breaker, e.g. '.' or ',' .
 * Note html markups are not considered sentence breaker within the scope of this function.
 * Return TRUE is current position marks a break in sentence otherwise FALSE
 */
function is_sentence_breaker($position)
{
	global $page;
	$rc = FALSE;

	if($page[$position] == '.' || $page[$position] == '-')
	{
		//Only break if the next character is a white space,
		//in order to avoid breaks on cases like this: (hello world.)
		if(is_white_space($position + 1) || $page[$position + 1] == '<')
		{
			$rc = TRUE;
		}
	}
	else if($page[$position] == ',' || $page[$position] == '?' ||
		    $page[$position] == '(' || $page[$position] == ')' ||
		    $page[$position] == '[' || $page[$position] == ']' ||
			$page[$position] == '"' || $page[$position] == '!' ||
			$page[$position] == ':' || $page[$position] == '|' ||
			$page[$position] == ';' ||
			//break on numbers but not like: 3rd, 4th
			(is_digit($position) && !is_a_to_z_character($position+1)))
	{
		//break the sentence into segments regardless of the next character.
		$rc = TRUE;
	}

	return $rc;
}

/*
 * Determines if the current position marks the begining of an html
 * entity. E.g &amp;
 * Return 0 if not an html entity otherwise return the position past this entity. In addition
 *        the $is_breaker will be set to TRUE if entity should break translation into a new segment.
 *
 */
function is_html_entity($position, &$is_breaker)
{
	global $page;
	$is_breaker = FALSE;
	if($page[$position] == "&" )
	{
		$end_pos = $position + 1;

		while($page[$end_pos] == "#" ||
		is_digit($end_pos) || is_a_to_z_character($end_pos))
		{
			$end_pos++;
		}

		if($page[$end_pos] == ';')
		{
			$entity = substr($page, $position, $end_pos - $position + 1);

			//Don't break on ` so for our use we don't consider it an entity
			//e.g. Jack`s apple
			if(!($entity ==  "&#8217;" || $entity == "&apos;" || $entity == "&#039;"))
			{
				$is_breaker = TRUE;
			}

			//It is an html entity.
			return $end_pos + 1;
		}
	}

	return 0;
}


/*
 * Determine if the current position in page points to a character in the
 * range of a-z (case insensetive).
 * Return TRUE if a-z otherwise FALSE
 *
 */

function is_a_to_z_character($position)
{
	global $page;

	if(($page[$position] >= 'a' && $page[$position] <= 'z') ||
	($page[$position] >= 'A' && $page[$position] <= 'Z'))
	{
		return TRUE;
	}

	return FALSE;
}

/*
 * Determine if the current position is a number.
 * Return TRUE if a number otherwise FALSE
 */
function is_digit($position)
{
	global $page;

	if($page[$position] >= '0' && $page[$position] <= '9')
	{
		return TRUE;
	}

	return FALSE;
}

/*
 * Determine if the current position in buffer is a white space.
 * return TRUE if current position marks a white space otherwise FALSE.
 */
function is_white_space($position)
{
	global $page;

	if($page[$position] == " "  || $page[$position] ==  ""    ||
	$page[$position] == "\t" || $page[$position] == "\r"   ||
	$page[$position] == "\n" || $page[$position] == "\x0B" ||
	$page[$position] == "\0")
	{
		return TRUE;
	}
}

/*
 * Skip within buffer past unreadable characters , i.e. white space
 * and characters considred to be a sentence breaker. Staring from the specified
 * position going either forward or backward.
 * param forward - indicate direction going either backward of forward.
 */
function skip_unreadable_chars(&$index, $forward=TRUE)
{
	global $page, $pos;

	if(!isset($index))
	{
		//use $pos as the default position if not specified otherwise
		$index = &$pos;
	}
	$start = $index;

	while($index < strlen($page) && $index > 0 &&
	(is_white_space($index) || is_sentence_breaker($index)))
	{
		($forward ? $index++ : $index--);
	}

	return $index;
}

/*
 * Skip within buffer past white space characters , Staring from the specified
 * position going either forward or backward.
 * param forward - indicate direction going either backward of forward.
 */
function skip_white_space(&$index, $forward=TRUE)
{
	global $page, $pos;

	if(!isset($index))
	{
		//use $pos as the default position if not specified otherwise
		$index = &$pos;
	}

	while($index < strlen($page) && $index > 0 && is_white_space($index))
	{
		($forward ? $index++ : $index--);
	}

	return $index;
}

/*
 * Check within page buffer position for the given word.
 * param word The word to look for.
 * param index1 Optional position within the page buffer, if not available then the current
 *              position ($pos) is used.
 * Return TRUE if the word matches otherwise FALSE
 */
function is_word($word, $index1)
{
	global $page, $pos;
	$rc = FALSE;

	if(!isset($index1))
	{
		//use $pos as the default position if not specified otherwise
		$index1 = $pos;
	}

	$index2 = 0; //position within word
	$word_len =   strlen($word);
	$page_length =  strlen($page);

	while($index1 < $page_length && $index2 < $word_len)
	{
		if($page[$index1] == $word[$index2])
		{
			$index1++;
			$index2++;
		}
		else
		{
			break;
		}
	}

	//check if we have full match
	if($index2 == $word_len)
	{
		$rc = TRUE;
	}

	return $rc;
}

/*
 * Translate the text between the given start position and the current
 * position (pos) within the buffer.
 */
function translate_text($start)
{
	logger("Enter " . __METHOD__  . " : $start", 4);
	global $page, $pos, $is_edit_mode;

	//trim white space from the start position going forward
	skip_white_space($start);

	//Set the end position of the string to one back from current position
	//(i.e. current position points to '<' or a breaker '.') and then trim
	//white space from the right backwards
	$end = $pos - 1;
	$end = skip_white_space($end, $forward=FALSE);

	if($start >= $end)
	{
		//empty string - nothing to do
		return;
	}

	$original_text = substr($page, $start, $end - $start + 1);

	//Cleanup and prepare text
	$original_text = scrub_text($original_text);
	if($original_text == NULL)
	{
		//nothing left from the text
		return;
	}

	list($translated_text, $source) = fetch_translation($original_text);

	insert_translation($original_text, $translated_text, $source, $start, $end);
}

/*
 * Update the translated page with the specified translation at the given position.
 * param original_text Text in the original page. Will not be NULL.
 * param translated_text The translated text, can be NULL in case no translation is available
 * param start Marks the start position of the text to be replaced within the original page
 * param end Marks the end position of the text to be replaced within the original page
 */
function insert_translation(&$original_text, &$translated_text, $source, $start, $end)
{
	global $segment_id, $is_edit_mode, $tags_list;

	$is_translated = FALSE;

	if(!$is_edit_mode || !in_array('body', $tags_list))
	{
		if($translated_text != NULL)
		{
			update_translated_page($start, $end, $translated_text);
		}
	}
	else
	{
		$span_prefix = SPAN_PREFIX;
		// We will mark translated text with tr_t class and untranslated with tr_u
		$span = "<span class=\"$span_prefix";

		if($translated_text == NULL)
		{
			$span .= "u\" id=\"{$span_prefix}{$segment_id}\">";
			$span .= $original_text . '</span>';
		}
		else
		{
			$span .= "t\" id=\"{$span_prefix}{$segment_id}\">";
			$span .= $translated_text . "</span>";
			$is_translated = TRUE;
		}

		//Insert text (either original or translated) marked by a <span>
		update_translated_page($start, $end, $span);


		//Insert image to allow editing this segment
		$img = get_img_tag($original_text, $translated_text, $source, $segment_id, $is_translated);
		update_translated_page($end + 1, - 1, $img);

		//Increment only after both text and image are generated so they
		//will be the same for each translated segement
		$segment_id++;

	}

	logger("Exit " . __METHOD__  . " : $original_text" , 4);
}

/*
 * Scrubs text prior to translation to remove/encode special
 * characters.
 * Return the scurbed text, or NULL if nothing left to translate
 */
function scrub_text(&$text)
{
	//skip strings like without any readable characters (i.e. ".")
	//Todo: need a broader defintion for non-ascii characters as well
	if(preg_match("/^[.?!|\(\)\[\],0-9]+$/", $text))
	{
		return NULL;
	}

	//replace multi space chars with a single space
	$text = preg_replace("/\s\s+/", " ", $text);

	return $text;
}

/**
 * Insert a translated text to the translated page.
 * Currentlly assume that we always insert and move forward - not moving
 * back in buffer.
 * param start - marks the starting position of the replaced string in the original page.
 * param end - marks the end position of the replaced string in the original page.
 Use -1 to do insert instead of replace.
 * param translated_text - text to be inserted.
 */
function update_translated_page($start, $end, $translated_text)
{
	global $page, $tr_page, $tr_mark;

	//Bring the translated up to date up to the start position.
	while($tr_mark < $start)
	{
		$tr_page .= $page[$tr_mark++];
	}

	$tr_page .= $translated_text;

	if($end > $start)
	{
		//Move mark to correlate the posistion between the two pages.
		//Only do this when some content has been replaced, i.e. not
		//an insert.
		$tr_mark = $end + 1;
	}

}
?>