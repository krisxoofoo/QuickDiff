<?php
function checkLineChangedLeft ($linenumber, $arrChanges) {
	/* This function simply returns true or false
	true: if the linenumber has a matching friend in $arrChanges
	false: if the linenumber doesn't match a change in $arrChanges

	$arrChanges: an array of diff-style changes
	 such as: 
	 Array ( 
		[0] => 2c2 
		[1] => 7d6 
		[2] => 9a9 
		[3] => 11a12 
	 )

	For a good read on understanding these lines, see:
	URL: http://lowfatlinux.com/linux-compare-files-diff.html */

	foreach ($arrChanges as $change) {
		$charcount = strlen((string) $linenumber);
		preg_match("/^([0-9]+)[,]?([0-9]+)?/", $change, $arrMatches);
		$changeLinenumber = $arrMatches[1];
		$changeEnd = isset($arrMatches[2]) ? $arrMatches[2] : null;

		if ($linenumber == $changeLinenumber) {
			// This line has changed
			//echo "Linenumber: ". $linenumber .", Diff: ". $change ."<br />";
			return $change;
		} elseif ($changeEnd > 0 && $changeEnd > $changeLinenumber) {
			// A source range has been given, ie "3,15"
			// So we check lines 3 to 15
			for ($i = $changeLinenumber; $i <= $changeEnd; $i++) {
				if ($i == $linenumber) {
					// Line has changed
					return $change;
				}
			}
		}
	}

	return false;
}

function checkLineChangedRight ($linenumber, $arrChanges) {
        /* This function simply returns true or false
        true: if the linenumber has a matching friend in $arrChanges
        false: if the linenumber doesn't match a change in $arrChanges

        For a good read on understanding these lines, see:
        URL: http://lowfatlinux.com/linux-compare-files-diff.html */

        foreach ($arrChanges as $change) {
                $charcount = strlen((string) $linenumber);
                preg_match("/^([0-9]+)[,]?([0-9]+)?([a|d|c])([0-9]+)+[,]?([0-9]+)?/", $change, $arrMatches);
                $changeLinenumber = $arrMatches[4];
                $changeEnd = isset($arrMatches[5]) ? $arrMatches[5] : null;

                if ($linenumber == $changeLinenumber) {
                        // This line has changed
                        //echo "Linenumber: ". $linenumber .", Diff: ". $change ."<br />";
                        return $change;
                } elseif ($changeEnd > 0 && $changeEnd > $changeLinenumber) {
                        // A source range has been given, ie "3,15"
                        // So we check lines 3 to 15
                        for ($i = $changeLinenumber; $i <= $changeEnd; $i++) {
                                if ($i == $linenumber) {
                                        // Line has changed
                                        return $change;
                                }
                        }
                }
        }

        return false;
}

function parseChange ($change) {
	/* This function parses the change from a diff. The input is something like:
	2c2: line 2 has changed
	7d6: line 7 has been deleted
	9a9: line 9 has been added
	11a12,14: line 11 has been added */

	preg_match("/^([0-9]+)[,]?([0-9]+)?([a|d|c])([0-9]+)+[,]?([0-9]+)?/", $change, $arrMatches);
	#die(print_r($arrMatches));
	/* Should produce something like:
	Source: 12,105c215,10
	Result: Array
	(
	    [0] => 12,105c215,10
	    [1] => 12
	    [2] => 105
	    [3] => c			==> operation: c, d, a
	    [4] => 215
	    [5] => 10
	)
	*/

	if (is_array($arrMatches)) {
		if (isset($arrMatches[5]) && $arrMatches[5] > 0)
			$end = $arrMatches[5];
		else
			$end = $arrMatches[4];

		if (isset($arrMatches[2]) && $arrMatches[2] > 0)
			$lineEnd = $arrMatches[2];
		else
			$lineEnd = $arrMatches[1];

		$arr = array(	"operation" 	=> $arrMatches[3],
				"start" 	=> $arrMatches[4],
				"end"		=> $end,
				"line_start"	=> $arrMatches[1],
				"line_end"	=> $lineEnd);
		return $arr;
	} else {
		return false;
	}
}

function fakeIndenting ($string) {
	return str_replace("\t", "&nbsp; &nbsp;", $string);
}

?>
