<?PHP
require_once "classes.php";

function boostgen() {
	
	//establish connection to MySQL and access the magic DB
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "eternal";

	$conn = new mysqli($servername, $username, $password, $dbname);
	
	//provisional clause for output in case of connection failure
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	//set up a multi-array with index[0] corresponding to Rares/Legendaries, [1-3] to Uncommons, [4-11] to Commons
	$booster = array();

	//assign a rare or legendary
	if (rand(1, 10) == 8) {
		$sql = "SELECT number FROM TET WHERE rarity='L'";
		$result = $conn->query($sql);
	} else {
		$sql = "SELECT number FROM TET WHERE rarity='R'";
		$result = $conn->query($sql);
	}
		$offset = 0;
		while($row = $result->fetch_assoc()) {
			$temp[$offset] = $row["number"];
			++$offset;
		}
		shuffle($temp);
		$booster[0] = $temp[0];
	
	unset($temp); //purge the array previously created as fetching a new set of cards by a different rarity somehow fails to override the previously assigned values
	//assign uncommons
	$sql = "SELECT number FROM TET WHERE rarity='U'";
	$result = $conn->query($sql);
	$offset = 0;
	while($row = $result->fetch_assoc()) {
		$temp[$offset] = $row["number"];
		++$offset;
	}
	shuffle($temp);
	for ($unc = 1; $unc <=3; $unc++) {
		$booster[$unc] = $temp[$unc];
	}
	
	unset($temp); //purge the array previously created as fetching a new set of cards by a different rarity somehow fails to override the previously assigned values
	//assign commons
	$sql = "SELECT number FROM TET WHERE rarity='C'";
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()) {
		$temp[$offset] = $row["number"];
		++$offset;
	}
	shuffle($temp);
	for ($com = 4; $com <=11; $com++) {
		$booster[$com] = $temp[$com];
	}
	
	unset($temp); //purge the array previously created as fetching a new set of cards by a different rarity somehow fails to override the previously assigned values

	for ($card = 0; $card < 12; $card++) {
		$booster[$card] = new Card ($booster[$card], $conn); //makes each card into an object
	}
	return $booster;
}
?>