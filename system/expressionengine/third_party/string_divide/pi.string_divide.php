<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
						'pi_name'        => 'String Divide',
						'pi_version'     => '1.3',
						'pi_author'      => 'Shoe Shine Design & Development',
						'pi_author_url'  => 'www.shoeshinedesign.com',
						'pi_description' => 'Inserts text/html into a text/html string after the specified character count, word count, or search string',
						'pi_usage'       => string_divide::usage()
					);

class String_divide {

	var $return_data;

	/**
	 * Constructor
	 *
	 */
	function __construct($str = '')
	{
		$this->EE =& get_instance();
		$str = ($str == '') ? $this->EE->TMPL->tagdata : $str;
		$str = trim($str);

		$type = $this->EE->TMPL->fetch_param('type', 'char');
		$type =  ( ! ($type == 'char' || $type == 'word' || $type == 'find') ) ? 'char' : $type;

		$count = $this->EE->TMPL->fetch_param('count', 0);
		$count = ( ! is_numeric($count)) ? 0 : $count;

		$place_holders = array("*BLANK*");
		$to_insert     = array(' ');

		$insert = $this->EE->TMPL->fetch_param('insert', '');
		$insert = str_replace($place_holders, $to_insert, $insert);
		$insert_before = $this->EE->TMPL->fetch_param('insert_before', '');
		$insert_before = str_replace($place_holders, $to_insert, $insert_before);
		
		$else_insert = $this->EE->TMPL->fetch_param('else_insert', '');
		
		$round_words = $this->EE->TMPL->fetch_param('round_words', 'yes');
		
		$split_after = $this->EE->TMPL->fetch_param('split_after', '');
		// keep split_after_first for backwards compatibility
		$split_after_first = $this->EE->TMPL->fetch_param('split_after_first', '');
		if ($split_after_first != '' && $split_after == '') {
			$split_after = $split_after_first;
		}

		$split_before = $this->EE->TMPL->fetch_param('split_before', '');

		$split_when  = $this->EE->TMPL->fetch_param('split_when', 'first');
		$word_offset = $this->EE->TMPL->fetch_param('word_offset', 0);
		$word_offset = ( ! is_numeric($word_offset)) ? 0 : $word_offset;
				
		if ($type == 'char') {
			$this->return_data = $this->char_divide($count, $str, $round_words, $insert, $else_insert);

		} elseif ($type == 'find') {
			$new_str = $str;
			if ($word_offset != 0) {
				// offset the each insertion point by $word_offset number of words
				$new_str = $this->word_offset_divide($word_offset, $split_after, $insert, $new_str, $split_when);
			}
			if ($word_offset == 0 || $split_before != '') {
				// insert immediately after/before the find string
				$new_str = $this->find_divide($word_offset, $new_str, $split_when, $split_after, $insert, $split_before, $insert_before);				
			}
			$this->return_data = $new_str;
			
		} elseif ($type == 'word') {
			$this->return_data = 	$this->word_divide($str, $count, $insert, $else_insert);

		} else {
			$this->return_data = $str;
		}
	}
	
	function char_divide($count, $str, $round_words, $insert, $else_insert)
	{
		$cur_count = $count;
		$cur_char = substr($str, $cur_count-1, 1);
		if (($round_words == 'yes') && ($cur_char != ' ')) {
			$next_char = substr($str, $cur_count, 1);
			while (($next_char != ' ') && ($next_char !== FALSE) && ($next_char != '')) {
				$cur_count++;
				$next_char = substr($str, $cur_count, 1);
			}
		}
		$first_part = substr($str, 0, $cur_count);
		$last_part = substr($str, $cur_count);
		return $first_part . ($cur_count <= strlen($str) ? $insert : $else_insert) . $last_part;
	}
	
	function word_offset_divide($word_offset, $split_after, $insert, $str, $split_when)
	{
		$word_offset_plus = $word_offset + 1;
		$pattern = '/('.$split_after.')';
		$replacement = '';
		for ($i=1; $i<$word_offset_plus; $i++) {
			$pattern.= '(.+? )';
			$replacement.= '$'.$i;
		}
		$pattern.= '/';
		$replacement.= '$'.$i.$insert;
		if ($split_when == 'all') {
			$limit = -1;
			$to_return = preg_replace($pattern, $replacement, $str, $limit);
		} elseif ($split_when == 'first') {
			$limit = 1;
			$to_return = preg_replace($pattern, $replacement, $str, $limit);
		} else { // $split_when == 'last'
			$split_begin = strrpos($str, $split_after);
			$first_part = substr($str, 0, $split_begin);
			$last_part = substr($str, $split_begin);
			$new_last_part = preg_replace($pattern, $replacement, $last_part);
			$to_return = $first_part . $new_last_part;
		}
		return $to_return;
	}

	function find_divide($word_offset, $new_str, $split_when, $split_after, $insert, $split_before, $insert_before)
	{
		//$new_str = $str;
		if ($split_when == 'all') {
			// all occurrences
			if ($split_after != '' && $word_offset == 0) {
				// split_after
				$new_str = str_replace($split_after, $split_after . $insert, $new_str);
			}
			if ($split_before != '') {
				// split_before
				$new_str = str_replace($split_before, $insert_before . $split_before, $new_str);
			}
		} else {
			// only first or last
			if ($split_after != '' && $word_offset == 0) {
				// split_after
				if ($split_when == 'first') {
					$split_begin = strpos($new_str, $split_after);
				} else { // $split_when == 'last'
					$split_begin = strrpos($new_str, $split_after);
				}
				if ($split_begin > 0) {
					$split_end = $split_begin + strlen($split_after);
				} else {
					$split_end = 0;
				}
				$first_part = substr($new_str, 0, $split_end);
				$last_part = substr($new_str, $split_end);
				$new_str = $first_part . $insert . $last_part;
			}
			if ($split_before != '') {
				// split_before
				if ($split_when == 'first') {
					$split_begin = strpos($new_str, $split_before);
				} else { // $split_when == 'last'
					$split_begin = strrpos($new_str, $split_before);
				}
				if ($split_begin > 0) {
					$split_end = $split_begin + strlen($split_before);
				} else {
					$split_end = 0;
				}
				$first_part = substr($new_str, 0, $split_begin);
				$last_part = substr($new_str, $split_begin);
				$new_str = $first_part . $insert_before . $last_part;
			}
		}
		return $new_str;
	}

	function word_divide($str, $count, $insert, $else_insert)
	{
		$str_array = str_word_count($str, 2);
		$cur_word = 0;
		$word_count = str_word_count($str);
		foreach ($str_array as $index => $word) {
			$cur_word++;
			if ($cur_word == $count) {
				break;
			}
			if ($cur_word >= $word_count) {
				$index = strlen($str) + 1;
				break;
			}
		}
		$split_pos = $index + strlen($word);
		$first_part = substr($str, 0, $split_pos);
		$last_part = substr($str, $split_pos);
		return $first_part . ($index <= strlen($str) ? $insert : $else_insert) . $last_part;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Usage
	 *
	 * Plugin Usage
	 *
	 * @access	public
	 * @return	string
	 */
	function usage()
	{
		ob_start(); 
		?>
		Divide any html/text string at the exact point you want so you can insert another custom text/html string.


		USAGE
		**********************
		Wrap the text/html you want to divide between the String Divide tag pairs. There are three ways you can divide the string.

		Example 1: After specific word count
		{exp:string_divide type='word' count='2' insert='&nbsp;green'}
			I love apples.
		{/exp:string_divide}

		Example 2: After specific character count
		{exp:string_divide type='char' count='6' insert='&nbsp;red'}
			I love apples.
		{/exp:string_divide}

		Example 3: After first occurrence of a custom string
		{exp:string_divide type='find' split_after='</p>' insert='</div><div class="toggle">'}
		<div>
			<p>paragraph one</p>
			<p>paragraph two</p>
			<p>paragraph three</p>
		</div>
		{/exp:string_divide}


		PARAMETERS
		**********************
		type = 'char', 'word', or 'find'
		to determine if count will count words or characters, or if it will find the first/last/all occurrence(s) of split_after or split_before. Default is 'char', if not specified.

		when using type='char' these parameters apply:
		count
		insert
		else_insert
		round_words
		
		when using type='word' these parameters apply:
		count
		insert
		else_insert
		
		when using type='find' these parameters apply:
		split_after
		insert (used with split_after)
		split_before
		insert_before (used with split_before)
		split_when (applies to both split_after and split_before)
		word_offset (applies only to split_after)

		count = 
		position after which to insert the insert parameter text. Only applies to 'word' char 'char' types. Default is '0', if not specified. HTML is counted, so be sure to consider your markup when counting.

		insert = 
		text/html to be inserted IF length of original string between tag pairs is >= count parameter. Required for type='word', type='char', and when the split_after parameter is used with type='find'.

		insert_before =
		text/html to be inserted before the split_before string when type='find'. Required when split_before parameter is used.

		else_insert = 
		text/html to be inserted IF length of original string between tag pairs is less than count parameter. Only applies to 'word' and 'char' types. Optional.

		round_words = 'y' or 'n'
		Default is 'y'. For type='char' round_words='y' will round up to the end of the current word before inserting insert. End of word is defined as a space. Optional.

		split_after = 
		search string for type='find' when splitting after this search string. Either split_after or split_before is required when type='find'.

		split_before =
		search string for type='find' when splitting before this search string. Either split_after or split_before is required when type='find'.

		split_when = 'first', 'last', 'all'
		Default is 'first'. For type='find' split_when='all' will split each occurrence of split_after or split_before. For split_when='first' it will only split the first occurrence of split_after or split_before. For split_when='last' it will only split the last occurrence of split_after or split_before.

		word_offset =
		instead of inserting the value of the insert parameter immediately after where search_after is found, word_offset will offset the insert position by word_offset number of words. Currently only works with split_after. Optional.
		

		EXAMPLES
		**********************
		Example 1 above will return the following:
		I love green apples.

		Example 2 will return:
		I love red apples.

		Example 3 will return:
		<div>
			<p>paragraph one</p>
		</div><div class="toggle">
			<p>paragraph two</p>
			<p>paragraph three</p>
		</section>

		Example 4: Dividing at another custom string
		{exp:string_divide type='find' split_after='-->' insert='</p></div><div class="toggle"><p>'}
		<div>
			<p>This is a long paragraph and I want to manually break it here.<!-- divide here -->Then this long paragraph will appear in the next div.</p>
		</div>
		{/exp:string_divide}

			returns:
			<div>
		    	<p>This is a long paragraph and I want to manually break it here.<!-- divide here --></p>
		    </div>
		    <div class="toggle">
		    	<p>Then this long paragraph will appear in the next div.</p>
			</div>

		Example 5: Insert inner wrapper with initial offset of 2 words for all occurances
		{exp:string_divide type='find' split_after='<h1>' insert='<span>' words_offset='2' split_before='</h1>' insert_before='</span>' split_when='all'}
		<h1>You're So Freaking Sexy!</h1>
		<p>paragraph here</p>
		<h1>You're So Smooth on the Dance Floor!</h1>
		<p>paragraph here</p>
		{/exp:string_divide}

			returns:
			<h1>You're So <span>Freaking Sexy!</span></h1>
			<p>paragraph here</p>
			<h1>You're So <span>Smooth on the Dance Floor!</span></h1>
			<p>paragraph here</p>


		ADDITIONAL NOTES
		**********************
		Remember to include in the count value any carriage returns between the tag pairs.
		Leading and trailing spaces are trimmed off from the insert parameter but it is possible to insert a space using '&nbsp;'


		CHANGELOG
		**********************
		Version 1.3 2013-07-14
		- renamed split_after_first to split_after
		- added split_before parameter to insert something before a search string; split_after and split_before can be used together
		- added split_when (first, last, all; default=first) for type="find"
		- added word_offset parameter to insert something word_offset words after split_after; only works for type="find" split_after and not split_before

		Version 1.2 2013-04-15
		- fixed type="word" to not drop punctuation (like < and /)
		- added type="find" and split_after parameter
		- trim spaces, returns, tabs, etc from original string

		Version 1.1 2012-12-11
		- added else_insert parameter for if count is beyond the end of str
		- added round_words parameter to "round up" to the end of the word when type = 'char'

		Version 1.0 2012-10-05
		- original release

		<?php
		$buffer = ob_get_contents();
	
		ob_end_clean(); 

		return $buffer;
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file pi.string_divide.php */
/* Location: ./system/expressionengine/third_party/string_divide/pi.string_divide.php */