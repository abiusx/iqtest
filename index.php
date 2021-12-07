<?php
if (isset($_GET['img'])) {
	header('Content-type: image/png');
	$questions_path = __DIR__ . "/questions/";
	$file = realpath($questions_path . $_GET['img']);
	if (substr($file, 0, strlen($questions_path))===$questions_path)
		readfile($file);
	die();
}


session_start();
$_SESSION['current_question'] = $_SESSION['current_question'] ?? null;
$_SESSION['start_time'] = $_SESSION['start_time'] ?? null;
if (isset($_POST['start'])) {
	$_SESSION['start_time'] = $_SESSION['start_time'] ?? time();
	$_SESSION['current_question'] = 1;
} elseif (!isset($_SESSION['finish_time']) and
	isset($_POST['answered']) and
	$_POST['answered'] == $_SESSION['current_question'] and
	$_SESSION['current_question'] <= 35) {
	$_SESSION['answers'][$_SESSION['current_question']] = $_POST['answer'] ?? null;
	$_SESSION['current_question']++;
} elseif (isset($_POST['result'])) {
	$data = [
		"answers" => $_SESSION['answers'],
		"start_time" => $_SESSION['start_time'],
		"finish_time" => $_SESSION['finish_time']
	];
	$string = base64_encode(gzcompress(serialize($data)));
	$url = "me=".urlencode($string);
	header("Location: ?{$url}");
	die();
}
$q = $_SESSION['current_question'];
if ($_SESSION['start_time']) {
	$remaining_time = (25 * 60) - (time() - @$_SESSION['start_time']);
	if ($q > 35 || $remaining_time <= 0) {
		$_SESSION['finish_time'] = $_SESSION['finish_time'] ?? time();
	}
}
session_write_close();
?>
<style>
body {
	text-align: center;
	margin: auto;
	width: 500px;
}
[type=radio] {
	position: absolute;
	opacity: 0;
	width: 0;
	height: 0;
}
[type=radio] + img {
  	cursor: pointer;
	padding: 5px;
	margin: 5px;
	border: 3px solid white;

}
[type=radio]:checked + img {
	border: 3px inset;
}
button[type=submit] {
	min-width: 250px;
}
#timer {
	color: red;
	font-size: 1.5em;
}
</style>
<?php

if (isset($_GET['me'])) { // Results page.
	$data = unserialize(gzuncompress(base64_decode($_GET['me'])));
	$start_time = @$data['start_time'];
	$finish_time = @$data['finish_time'];
	$answers = @$data['answers'];
	if (!$answers or !$start_time or !$finish_time) {
		die("404 Not Found.");
	}
	$corret_answers = [1,5,6,6,4,5,5,3,4,4,1,1,2,1,6,2,4,3,1,1,2,5,6,6,5,6,1,1,3,5,4,5,5,1,4];
	$score = 0;
	foreach ($corret_answers as $index => $correct_answer) {
		if (@$answers[$index+1] == $correct_answer)
			$score++;
	}
	$test_time = $finish_time - $start_time;
	$iq = max(60, 150 - (35 - $score)*5);
	if ($test_time < 20 * 60) $iq += 5;
	?>
	<h1>IQ Test Results</h1>
	<div style='text-align:left;'>
		<p>
			Test taken on: <?=date("F j, Y, g:i a", $start_time);?>
		</p>
		<p style='font-size: 1.5em;'>
			Correct answers: <?=$score;?>/35.
		</p>
		<p style='font-size: 1.5em;'>
			Test duration: <?=floor($test_time/60);?> minutes and <?=$test_time%60;?> seconds.
		</p>
		<p style='font-size: 2em;'>
			IQ: <strong><?=$iq;?></strong>
		</p>

		<p>Feel free to share <a href='?me=<?=urlencode($_GET['me'])?>'>this page</a> if you want to show your result to others.</p>
		<p>You can visit <a href='https://www.iqcomparisonsite.com/iqtable.aspx'>this website</a>
			to see your IQ percentile. This test used standard deviation 15.
		</p>
	</div>
	<?php
} elseif (!$q) {
	?>
	<h1>Welcome to Simple IQ test!</h1>

	<p>
		You have 25 minutes to respond to 35 questions. Your IQ will be calculated
		based on the correct answers to questions. All questions are equal, and
		they become gradually harder.
	</p>

	<form method='post'>
		<button type='submit' name='start'>Start the Test</button>
	</form>

	<?php
} elseif ($q <= 35 and !@$_SESSION['finish_time']) {
	?>
	<h1>Question <?=$q;?></h1>
	<div id='timer'></div>
	<form method='post'>
	<div class='question'>
		<img src='?img=q<?=$q;?>/q.png' />
	</div>
	<div class='answers'>
		<?php for ($i=1; $i<=6; ++$i): ?>
		<label for="input-<?=$i;?>">
			<input id="input-<?=$i;?>" name="answer" type="radio" value="<?=$i;?>" />
			<img src='?img=q<?=$q;?>/<?=$i;?>.png' />
		</label>
		<?php endfor; ?>
	</div>
	<div class='submit'>
		<button type='submit' name='answered' value='<?=$q;?>'>Next Question</button>
	</div>
	</form>
	<script>
function startTimer(duration, display) {
	var timer = duration, minutes, seconds;
	const f = function () {
		minutes = parseInt(timer / 60, 10);
		seconds = parseInt(timer % 60, 10);

		minutes = minutes < 10 ? "0" + minutes : minutes;
		seconds = seconds < 10 ? "0" + seconds : seconds;

		display.textContent = minutes + ":" + seconds;

		if (--timer < 0) {
			window.location.href = window.location.href;
		}
	};
	setInterval(f, 1000);
	f();
}
window.onload = function () {
	startTimer(<?=$remaining_time;?>, document.getElementById("timer"));
};
	</script>


	<?php
} else {
	$time_diff = $_SESSION['finish_time'] - $_SESSION['start_time'];
	$responses = array_reduce($_SESSION['answers'], function ($carry, $x) {
		return $carry + ($x !== null);
	});
	$blanks = array_reduce($_SESSION['answers'], function ($carry, $x) {
		return $carry + ($x === null);
	});
	?>
		<h1>Test Done!</h1>
		<p>
			You finished the test in <?=floor($time_diff/60);?> minutes and <?=$time_diff%60;?> seconds.
			You responded to <?=$responses;?> questions and left <?=$blanks;?> questions blank.
		</p>
		<p>
			Click the button below to see the results.
		</p>
		<form method='post'>
			<button type='submit' name='result'>IQ Test Results</button>
		</form>

	<?php
}
