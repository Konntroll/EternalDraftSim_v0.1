<?PHP
require_once "login.php";
require_once "boostgen.php";
if (isset($_POST['draft'])) {
	$array = json_decode($_POST['draft']);
	$line = $array[0][11][0] + 20;
	$conn->query("UPDATE metrics SET value = value + 1 WHERE param = 'compl'"); //this is to update the number of players for future passes
	foreach ($array as &$subarray) { //this will replace each card with its number as we no longer need objects, just numbers, and then collapse the matrix into a single string of numbers
		array_pop($subarray);
		$append = boostgen();
		foreach ($append as &$card) {
			$card = $card->number;
		}
		array_unshift($subarray, $append);
		foreach ($subarray as &$pack) {
			$pack = implode(", ", $pack);
		}
		$subarray = implode(" / ", $subarray);
	}
	$array = implode(" * ", $array);
	$conn->query("UPDATE drafts SET draft = \""	. $array . "\" WHERE line = " . $line . ""); //this is to update the number of players for future passes
	echo json_encode($array);
}

?>