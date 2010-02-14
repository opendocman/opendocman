/*
function.archive.php - old functions that are no longer used
Copyright (C) 2002, 2003, 2004  Stephen Lawrence, Khoa Nguyen

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

function int_array2D_sort($int_array, $sort_order)
    {
        $start_time = time();
        $arraysize = sizeof($int_array);
        $largest_num = 0;
        for($i = 0; $i<$arraysize; $i++)
        {
            if($int_array[$i][1]> $largest_num)
            {   $largest_num = $int_array[$i][1];   }
        }
        $str_largest_num = ''.$largest_num;
        $prefix_zeros = '';
        for($i = 0; $i < strlen($str_largest_num)-1; $i++)
        {   $prefix_zeros = $prefix_zeros.'0';  }
        $smallest_allow_num = (int)('1'.$prefix_zeros);
        $smallest_allow_num_digits = strlen($prefix_zeros)+1;
        for($i = 0; $i<$arraysize; $i++)
        {
            if($int_array[$i][1]<$smallest_allow_num)
            {
                $current_num_digits = strlen((string)($int_array[$i][1]));
                $num_of_zeros = $smallest_allow_num_digits - $current_num_digits;
                for($j = 0; $j < $num_of_zeros; $j++)
                {   $int_array[$i][1] = '0'.$int_array[$i][1];  }
            }
        }
        $sorted_array = str_array2D_sort($int_array, $sort_order);
        echo '<br> <b> int_array2D_sort Time: ' . (time() - $start_time) . ' </b><br>';
        return $sorted_array;
    }
	function str_array2D_sort($str_array, $sort_order)
	{
		$start_time = time();
		switch($sort_order)
		{
			case 'asc':
				$str_array_len = sizeof($str_array);
				$sorted_array = array();
				$current_index = 0;
				$swap_array = array();
				for($i = 0; $i<$str_array_len; $i++)
				{
					$current_index = $i;
					for($j = $i - 1; $j>=0; $j--)
					{
						$result = strcasecmp($str_array[$j][1], $str_array[$current_index][1]);
						if($result > 0)
						{
							$swap_array=$str_array[$j];
							$str_array[$j] = $str_array[$current_index];
							$str_array[$current_index] = $swap_array;
							$current_index = $j;
						}
					}
				}
				
				break;
			case 'desc':
				$str_array_len = sizeof($str_array);
				$sorted_array = array();
				$current_index = 0;
				$swap_array = array();
				for($i = 0; $i<$str_array_len; $i++)
				{
					$current_index = $i;
					for($j = $i - 1; $j>=0; $j--)
					{
						$result = strcasecmp($str_array[$j][1], $str_array[$current_index][1]);
						if($result < 0)
						{
							$swap_array=$str_array[$j];
							$str_array[$j] = $str_array[$current_index];
							$str_array[$current_index] = $swap_array;
							$current_index = $j;
						}
					}
				}
				break;		
		}
		echo '<br> <b> str_array2D_sort Time: ' . (time() - $start_time) . ' </b><br>';
		return $str_array;		
	}
	function obj_array_sort_interface($obj_array, $sort_order, $sort_by)
	{
		if(sizeof($obj_array)<=1 and !isset($obj_array) )
			return $obj_array;
		switch($sort_by)
		{
			case 'file_name':
				$obj_array_len = sizeof($obj_array);
				$str_array = array();
				for($i = 0; $i< $obj_array_len; $i++)
				{
					$str_array[$i] = array($i, $obj_array[$i]->getName());
				}
				break;
			case 'description':
				$obj_array_len = sizeof($obj_array);
				$str_array = array();
				for($i = 0; $i< $obj_array_len; $i++)
				{
					$str_array[$i] = array($i, $obj_array[$i]->getDescription());
				}
				break;
			case 'modified_on':
				$obj_array_len = sizeof($obj_array);
				$str_array = array();
				for($i = 0; $i< $obj_array_len; $i++)
				{
					$str_array[$i] = array($i, $obj_array[$i]->getModifiedDate());
				}
				break;
			case 'author':
				$obj_array_len = sizeof($obj_array);
				$str_array = array();
				for($i = 0; $i< $obj_array_len; $i++)
				{
				   	$full_name_array = $obj_array[$i]->getOwnerFullName();           	
					$str_array[$i] = array($i, $full_name_array[1] . ', ' . $full_name_array[0]);
				}
				break;
			case 'created_date':
				$obj_array_len = sizeof($obj_array);
				$str_array = array();
				for($i = 0; $i< $obj_array_len; $i++)
				{
					$str_array[$i] = array($i, $obj_array[$i]->getCreatedDate());
				}
				break;
			case 'size':
				$obj_array_len = sizeof($obj_array);
				$str_array = array();
				for($i = 0; $i< $obj_array_len; $i++)
				{
					$str_array[$i] = array($i, $obj_array[$i]->getId());
					$str_array[$i][1] = filesize($GLOBALS['CONFIG']['dataDir'].$str_array[$i][1].'.dat');
				}
				break;
			case 'id':
				$obj_array_len = sizeof($obj_array);
				$str_array = array();
				for($i = 0; $i< $obj_array_len; $i++)
				{
					$str_array[$i] = array($i, $obj_array[$i]->getId());
				}
				break;
			case 'department':
				$obj_array_len = sizeof($obj_array);
				$str_array = array();
				for($i = 0; $i< $obj_array_len; $i++)
				{
					$str_array[$i] = array($i, $obj_array[$i]->getDeptName());
				}
				break;
			default : // do an id sort
				$obj_array_len = sizeof($obj_array);
				$str_array = array();
				for($i = 0; $i< $obj_array_len; $i++)
				{
					$str_array[$i] = array($i, $obj_array[$i]->getId());
				}
				break;
		}
		if($sort_by == 'id' or $sort_by == 'size')
		{	$str_sorted_array = int_array2D_sort($str_array, $sort_order);	}
		else
		{	$str_sorted_array = str_array2D_sort($str_array, $sort_order);	}
			
		$str_sorted_array_len = sizeof($str_sorted_array);
		$obj_sorted_array = array();
		for($i = 0; $i<$str_sorted_array_len; $i++)
		{
			$obj_sorted_array[$i] = $obj_array[$str_sorted_array[$i][0]];
		}
		return $obj_sorted_array;
	}
	function my_sort2 ($lquery, $sort_order = 'asc', $sort_by = 'id')
	{
		if (strlen($lquery) == 0 )
			return $lquery;
		if($sort_order == 'asc')
			$sort_order = 'asc';
		else
			$sort_order = 'desc';
		$clauses = array();
		$clauses[0] = substr($lquery, 0, strpos($lquery, 'from'));
		
		if( $sort_by == 'id' )
		{
			$lquery = 'SELECT id from data WHERE ';
			$lquery .= $lwhere_or_clause . ' ORDER BY id ' . $sort_order;
		}
		elseif($sort_by == 'author')
		{
			$lquery = 'SELECT data.id FROM data, user WHERE data.owner = user.id AND (';
			$lquery .= $lwhere_or_clause . ') ORDER BY user.last_name ' . $sort_order . ' , user.first_name ' . $sort_order  . ', data.id asc';
		}
		elseif($sort_by == 'file_name')
		{
			$lquery = 'SELECT data.id FROM data WHERE ';
			$lquery .= $lwhere_or_clause . ' ORDER BY data.realname ' . $sort_order . ', data.id asc';
		}
		elseif($sort_by == 'department')
		{
			$lquery = 'SELECT data.id FROM data, department WHERE data.department = department.id AND (';
			$lquery .= $lwhere_or_clause . ') ORDER BY department.name ' . $sort_order . ', data.id asc';
		}
		elseif($sort_by == 'created_date' )
		{
			$lquery = 'SELECT data.id FROM data WHERE ';
            $lquery .= $lwhere_or_clause . ' ORDER BY data.created ' . $sort_order . ', data.id asc';
		}
		elseif($sort_by == 'modified_on')
		{
			$lquery = 'SELECT data.id FROM log, data WHERE data.id = log.id AND log.revision="current" AND (';
			$lquery .= $lwhere_or_clause . ') GROUP BY id ORDER BY modified_on ' . $sort_order . ', data.id asc';
		}
		elseif($sort_by == 'description')
		{
			$lquery = 'SELECT data.id FROM data WHERE  (';
			$lquery .= $lwhere_or_clause . ') ORDER BY data.description ' . $sort_order . ', data.id asc';
		}
		elseif($sort_by == 'size')
		{
			$lquery = 'SELECT data.id FROM data WHERE  (';
			$lquery .= $lwhere_or_clause . ') ORDER BY data.filesize ' . $sort_order . ', data.id asc';
		}
		$time = time(); 
		$lresult = mysql_query($lquery) or die('Error in querying:' . $lquery . mysql_error());
		echo "load mysql time: " . (time() - $time);
		for($li = 0; $li<mysql_num_rows($lresult); $li++)
			list($array[$li]) = mysql_fetch_row($lresult);
		return $array;
	}
	function view_file($filename)
        {
                header('Content-Length: '.filesize($GLOBALS['CONFIG']['dataDir'].'/'.$filename));
                //header("Content-Type: application/pdf");
                header('Content-Disposition: inline; filename='.$GLOBALS['CONFIG']['dataDir'].'/'.$filename);
                // Apache is sending Last Modified header, so we'll do it, too
                //header("Last-Modified:  Just now");   // something like Thu, 0
                readfile($GLOBALS['CONFIG']['dataDir'].'/'.$filename);
        }

        function parse_string($string, $delimiter)
        {
                $parsedArray = array();
                for($i = 0; $i<strlen($string); $i++)
                {
                        $pos = strpos($string, $delimiter, $i);
                        if($pos ==NULL)
                        { 
                                $pos = strlen($string);
                                $parsedArray[sizeof($parsedArray)]=substr($string, $i, ($pos-$i));
                                $i = strlen($string);
                        }
                        else
                        {
                                $parsedArray[sizeof($parsedArray)]=substr($string, $i, ($pos-$i));
                                $i = $pos;
                        }
                }
                return $parsedArray;
        }
	function add_arrays($array1, $array2)
	{
		$result_array = $array1;
		$index = sizeof($result_array);
		for($i = 0; $i<sizeof($array2); $i++)
			$result_array[$index++] = $array2[$i];
		return $result_array;
	}
	function trim_strings($str_array, $percentage)
	{
		$result_array = array();
		$result_array_index = 0;
		for($i=0; $i<sizeof($str_array); $i++)
		{
			$word_len = strlen($str_array[$i]);
			$matching_len = (int)($percentage / 100 * $word_len);
			$temp_array = array();
			$temp_array_index = 0;
			for($j = 0; $j<=$word_len-$matching_len; $j++)
			{
				$temp_array[$temp_array_index++] = substr($str_array[$i], $j, $matching_len);
			}
			$result_array[$result_array_index++] = $temp_array;
		}
		return $result_array;
	}
	function len_filter_str_array($str_array, $len)
	{
		$array_len = sizeof($str_array);
		$result_array = array();
		$index = 0;
		for($i = 0; $i<$array_len; $i++)
		{
			if(strlen($str_array[$i])>=$len)
				$result_array[$index++] = $str_array[$i];
		}
		return $result_array;
	}
			
	function word_count($string)
	{
		$count = 0;
		for($i = 0; $i<strlen($string); $i++)
			if($string[$i] == " ")
				$count++;
		return (count+1);
	}
        function str_search($query, $str_array, $exactWord, $case_sensitivity = false)
        {
                $found_array = array();
                if(!isset($case_sensitivity))
                {
                        $query = strToLower($query);
                        for($i = 0; $i<sizeof($str_array); $i++)
                        {
                                $str_array[$i] = strToLower($str_array[$i]);
                        }
                }

                if(isset($exactWord))
                {
                        $index = 0 ;
                        for($i = 0; $i<sizeof($str_array); $i++)
                        {
                                $found_array[$index++]=($query == $str_array[$i]);
                        }

                        return $found_array;
                }
                else
                {
                        $found_exact_array = str_search($query, $str_array, true, $case_sensitivity);
                        $found_not_exact_array = array();
                        $index = 0;
                        for($i = 0; $i<sizeof($str_array); $i++)
                        {
                                $small_array = parse_string($str_array[$i], " ");
                                $temp_array = str_search($query, $small_array, true, $case_sensitivity);
                                $temp_array_size = sizeof($temp_array);
                                $found_flag=false;
                                for($j = 0; $j<$temp_array_size; $j++)
                                {
                                        if($temp_array[$j]==1)
                                        {
                                                $found_not_exact_array[$index++] = true;
                                                $j = $temp_array_size;
                                                $found_flag = 1;
                                        }
                                }
                                if(!$found_flag)
                                {
                                	$found_not_exact_array[$index++] = false;
                                }


                        }
                        return mergeArrays($found_not_exact_array, $found_exact_array, true);
                }
        }
        function mergeArrays($high_priority_array, $low_priority_array, $priority_factor)
        {
                $array_len = sizeof($high_priority_array);
                if($array_len != sizeof($low_priority_array) )
                        return 0;
                for($i = 0; $i < $array_len; $i++)
                {
                        if($high_priority_array[$i] != $priority_factor && $low_priority_array[$i] == $priority_factor)
                        {
                                $high_priority_array[$i] = $low_priority_array[$i];
                        }
                }
                return $high_priority_array;
        }
        function merge2DArrays($high_priority_array, $low_priority_array, $priority_factor, $comparison_index)
        {
                $array_len = sizeof($high_priority_array);
                if($array_len != sizeof($low_priority_array) )
                        return 0;
                for($i = 0; $i < $array_len; $i++)
                {
                        if($high_priority_array[$i][$comparison_index] != $priority_factor && $low_priority_array[$i][$comparison_index] == $priority_factor)
                        {
                                $high_priority_array[$i] = $low_priority_array[$i];
                        }
                }
                return $high_priority_array;
        }
		/* fixQuote is used to make a normal string with embbed single and double quotation
	 into a proper HTML string that a browser can understand without loosing any characters
	 in it.  Let's say your string is 
	$your_str1 = "I like the author "Khoa Nguyen".  That's the author i like".  
	$your_str2 = "I like the author \"Khoa Nguyen\".  That's the author i like".  
	Let's say you want to echo this string to the value of some textfield in your HTML
	Then have this in your HTML:
	<INPUT type="text" value="<?php echo(fixQuote($your_str1)); ?>"
	<INPUT type="text" value="<?php echo(fixQuote($your_str2)); ?>"
	*/
	function fixQuote($string)
	{
		$string[$i] = str_replace('"', '&quot;', $string);
		$string[$i] = str_replace('\\', '', $string);
	}
	
	function removeElements($master_array, $removing_array)
	{
		$found = false;
		$result_array = array();
		for($i = 0; $i < sizeof($master_array); $i++)
		{
			$found=false;
			for($j=0;$j < sizeof($removing_array); $j++)
			{
				if($master_array[$i] == $removing_array[$j])
				{	$found=true;break;	}
			}
			if(!$found)
			{	$result_array[sizeof($result_array)] = $master_array[$i];	}
		}
		return $result_array;
	}
