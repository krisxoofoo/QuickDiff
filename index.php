<?php
// Avoid unicode problems
setlocale(LC_CTYPE, "en_US.UTF-8");
require_once("functions.php");

if (isset($_POST['cmdDiff'])) {
	// Get our POST variables
	$txtField1 = $_POST['txtField1'] ."\n";
	$txtField2 = $_POST['txtField2'] ."\n";

	// This is ugly: diff can only see the difference of files
	// So we save them (temporarily) to files
	$randomHex = md5(time() * rand());
	$fileField1 = "/tmp/quickdiff_". $randomHex ."_1.txt";
	$fileField2 = "/tmp/quickdiff_". $randomHex ."_2.txt";

	// Using 'w' in fopen(), to avoid possible loading another diff-source
	// 'w': "Open for writing only; place the file pointer at the beginning of the 
	// file and truncate the file to zero length. If the file does not exist, 
	// attempt to create it. "
	$handleField1 = fopen($fileField1, 'w');
	$handleField2 = fopen($fileField2, 'w');
	fwrite($handleField1, $txtField1);
	fwrite($handleField2, $txtField2);
	fclose($handleField1);
	fclose($handleField2);

	// Generate the diff command with options
	$options = "";
	if (isset($_POST['chkWhitespace']))
		$options .= " -b";
	if (isset($_POST['chkBlankLines']))
		$options .= " -B";
	if (isset($_POST['chkCase']))
		$options .= " -i";

	// Execute the diff, but don't show the actual changes, just the lines that changed
	// The rest of the logic is handled below
	$cmdDiff = "/usr/bin/diff ". $options ." ". $fileField1 ." ". $fileField2 ." | grep -Pv '>|<|---'";
	exec($cmdDiff, $arrDiff);

	// Remove the temp file
	unlink($fileField1);
	unlink($fileField2);

	// We now have an array of changes, filter out only the good parts
	//echo "Command: ". $cmdDiff ."<br />";

	// There will be 2 div's, showing the left and right column of the content.
	// Thse will now be known as $divLeft and $divRight
	$divLeft = "";
	$divRight = "";

	$arrLeft = explode("\n", $txtField1);
	$arrRight = explode("\n", $txtField2);

	// We build the left part first
	$linecount = 1;
	foreach ($arrLeft as $line) {
		$lineChange = checkLineChangedLeft($linecount, $arrDiff);
		$line = fakeIndenting(htmlspecialchars($line));

		if ($lineChange !== false) {
			// This line has changed, find out whati
			$dowhat = parseChange($lineChange);
			switch ($dowhat["operation"]) {
				case "c":
					// Changed line
					$divLeft .= "<div class='diff_changed'>". $line ."</div>\n";
					if ($dowhat["start"] < $dowhat["end"] && $dowhat["line_start"] == $dowhat["line_end"]) {
						while ($dowhat["start"] != $dowhat["end"]) {
                                                        $dowhat["start"]++;
                                                 	$divLeft .= "<div class='diff_changed'>&nbsp;</div>\n";
                                                }
					}
					break;
				case "a":
					// A line was added to the new file
					// Deleted line, add new lines to cover, but show the normal line first
					$divLeft .= "<div class='diff_normal'>". $line ."</div>\n";
					if ($dowhat["start"] == $dowhat["end"]) {
						// Just add 1 line
						$divLeft .= "<div class='diff_removed'>&nbsp;</div>\n";
					} else {
						while ($dowhat["start"] != ($dowhat["end"] + 1)) {
							$dowhat["start"]++;
							$divLeft .= "<div class='diff_removed'>&nbsp;</div>\n";
						}
					}
					break;
				case "d":
					// Line was removed
					$divLeft .= "<div class='diff_added'>". $line ."</div>\n";
					break;
				default:
					break;
			}
		} else {
			// Line hasn't changed, just output
			$divLeft .= "<div class='diff_normal'>". $line ."</div>\n";
		}

		$linecount++;
	}

	// Now we build the right part
	$linecount = 1;
        foreach ($arrRight as $line) {
                $lineChange = checkLineChangedRight($linecount, $arrDiff);
                $line = fakeIndenting(htmlspecialchars($line));

                if ($lineChange !== false) {
                        // This line has changed, find out whati
                        $dowhat = parseChange($lineChange);
                        switch ($dowhat["operation"]) {
                                case "c":
                                        // Changed line
                                        $divRight .= "<div class='diff_changed'>". $line ."</div>\n";
					if ($dowhat["line_start"] < $dowhat["line_end"] && $dowhat["start"] == $dowhat["end"]) {
                                                while ($dowhat["line_start"] != $dowhat["line_end"]) {
                                                        $dowhat["line_start"]++;
                                                        $divLeft .= "<div class='diff_changed'>&nbsp;</div>\n";
                                                }
                                        }
                                        break;
                                case "d":
                                        // A line was removed from the new file
                                        // Add blank lines to indicate this, but first show the normal line
					$divRight .= "<div class='diff_normal'>". $line ."</div>\n";
                                        if ($dowhat["line_start"] == $dowhat["line_end"]) {
                                                // Just add 1 line
                                                $divRight .= "<div class='diff_removed'>&nbsp;</div>\n";
                                        } else {
                                                while ($dowhat["line_start"] != ($dowhat["line_end"] +1)) {
                                                        $dowhat["line_start"]++;
                                                        $divRight .= "<div class='diff_removed'>&nbsp;</div>\n";
                                                }
                                        }
                                        break;
                                case "a":
                                        // Line was added in the new file
                                        $divRight .= "<div class='diff_added'>". $line ."</div>\n";
                                        break;
                                default:
                                        break;
                        }
                } else {
                        // Line hasn't changed, just output
                        $divRight .= "<div class='diff_normal'>". $line ."</div>\n";
                }

                $linecount++;
        }

	//die(print_r($arrDiff));
}
?>
<HTML>
<HEAD>
	<TITLE>QuickDiff: See the differences between 2 texts</TITLE>
	<link href="style.css" media="screen" rel="stylesheet" type="text/css" />

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-24911242-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</HEAD>
<BODY>
<h1>QuickDiff: visually see the differences between 2 blocks of text</h1>

<div class="diffWrapper">
<?php
	$outputShown = false;
	if (isset($divLeft) && strlen($divLeft) > 0) {
		$outputShown = true;
	?>
	<h2>Your results</h2>

	<div class="divLeft">
		<?=$divLeft?>
	</div>
	<?php
	}

	if (isset($divRight) && strlen($divRight) > 0) {
		$outputShown = true;
	?>
	<div class="divRight">
		<?=$divRight?>
	</div>
	<?php
	}

?>
</div>
<?php 
if ($outputShown) { 
	echo '<br clear="all" />';
	echo "<br /><br />Changes in diff-format: ". implode(" -- ", $arrDiff); 
}
?>
<h2>What's this?</h2>
<p>Input your text in the two textarea's below, and click on submit at the bottom. It will graphically show you the differences between the 2 textareas by highlighting those areas that have changed.</p>

<p>Created by <a href="http://mattiasgeniar.be/">Mattias Geniar</a>. Feel free to contact me via <b>m@ttias.be</b> or via Twitter at <a href="https://twitter.com/#!/mattiasgeniar">@mattiasgeniar</a>.</p>

<h2>Your input</h2>
<form method="post" name="quickDiff" action="/">
	<textarea class="textarea_left" name="txtField1" cols="80" rows="25"><?=isset($_POST['txtField1']) ? $_POST['txtField1'] : ''; ?></textarea>
	<textarea class="textarea_right" name="txtField2" cols="80" rows="25"><?=isset($_POST['txtField2']) ? $_POST['txtField2'] : ''; ?></textarea>

	<br /><br />
	Options:<br />
	<input type="checkbox" name="chkWhitespace" id="chkWhitespace" <?=isset($_POST['chkWhitespace']) ? 'checked="true"' : '' ?>> 
		<label for="chkWhitespace">Ignore changes in amount of white space.</label><br />
	<input type="checkbox" name="chkBlankLines" id="chkBlankLines" <?=isset($_POST['chkBlankLines']) ? 'checked="true"' : '' ?>> 
		<label for="chkBlankLines">Ignore changes that just insert or delete blank lines.</label><br />
	<input type="checkbox" name="chkCase" id="chkCase" <?=isset($_POST['chkCase']) ? 'checked="true"' : '' ?>> 
		<label for="chkCase">Ignore changes in case; consider upper- and lower-case letters equivalent.</label><br />
	
	<br />
	<input type="submit" name="cmdDiff" value="Check for differences" />
</form>
<br />
<h2>What's this?</h2>
<p><b>QuickDiff</b> is a wrapper around the popular <b>diff</b> tool on Linux, designed to make viewing the changes between 2 blocks of texts more easy. This allows you to see the difference in text, lists, mails, config files, ...</p>

<p>It was created because I needed an easy way to see the difference between 2 blocks of text, but didn't want to use external services to use it. Since I often use this to <b>diff</b> config files or other "personal" stuff, I feel more confident that I do it on my own system, where I know all data is being deleted. I know it's still plain text, so don't use this for passwords, but at least it's not being stored on a remote system.</p>

<p>I can not guarantee you that I don't save your input (since that would mean I need to give you root access to my server), but I give you my word: <b>your input is not being saved on the server, at all.</b> I save it temporarily to 2 files, to <b>diff</b> them, then delete them aftwards.</p>

<h2>Dude, this stuff doesn't work.</h2>
<p>It's a Work-In-Progress. It does what I need it to do, but it won't be perfect. If what you're <b>diff'ing</b> isn't top secret, mail me your 2 text-field contents via <b>m@ttias.be</b> so I can debug it further. If you have hints, either mail me or contact me via Twitter at <a href="https://twitter.com/#!/mattiasgeniar">@mattiasgeniar</a>.</p>

<p class="footer">Created by <a href="http://mattiasgeniar.be/">Mattias Geniar</a>. Feel free to contact me via <b>m@ttias.be</b> or via Twitter at <a href="https://twitter.com/#!/mattiasgeniar">@mattiasgeniar</a>.</p>
</BODY>
</HTML>
