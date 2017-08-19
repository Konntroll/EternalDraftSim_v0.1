<?PHP
require_once "login.php";

/*$sql = "CREATE TABLE metrics (param VARCHAR(6), value INT(5) NOT NULL)";
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);*/

/*$sql = "INSERT INTO metrics (param, value) VALUES ('compl', '0')";
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);*/

//$conn->query("INSERT INTO metrics (param, value) VALUES ('test', '0')"); //delete the first string so that the next player is working with a different one

//$conn->query("UPDATE metrics SET value = 1 WHERE param = 'player'"); //this is to update the number of players for future passes

/*$sql = "SELECT value FROM metrics WHERE param='player'"; //this is to get the key of the first draft string in the drafts table
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);

while ($row = $result->fetch_row()) {
	$line = $row[0];
}

echo $line . "<br>";

$sql = "SELECT draft FROM drafts WHERE line=" . $line . ""; //get a draft string to work with
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);

while ($row = $result->fetch_row()) {
	$array = $row[0];
}

echo $array;*/

$sql = "SELECT draft FROM drafts WHERE line='6'"; //get a draft string to work with
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);

while ($row = $result->fetch_row()) {
	$array = $row[0];
}

//echo $array;

$conn->query("UPDATE drafts SET draft = \"" . $array . "\" WHERE line = " . $line . "");

?>