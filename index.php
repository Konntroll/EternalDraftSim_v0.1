<?PHP
require_once "login.php";

$sql = "SELECT value FROM metrics WHERE param='player'"; //this is to get the key of the first draft string in the drafts table
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);

while ($row = $result->fetch_row()) {
	$line = $row[0];
}

$conn->query("UPDATE metrics SET value = value + 1 WHERE param = 'player'"); //this is to update the number of players for future passes

$sql = "SELECT draft FROM drafts WHERE line=" . $line . ""; //get a draft string to work with
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);

while ($row = $result->fetch_row()) {
	$array = $row[0];
}

$conn->query("DELETE FROM drafts WHERE line=" . $line . ""); //delete the first string so that the next player is working with a different one
$conn->query("INSERT INTO drafts (draft) VALUES (" . "\"" . $array . "\"" . ")"); //append the first string to the end of the database to retain it in case the draft is not completed

?>

<html>
	<head>
	</head>
	<body>
		<table id="table" align="center" border="1">
			<tr>
				<td id="deck">
					<div id="booster" style="height:950px; width:950px"></div>
				</td>
				<td id="pool" rowspan="3">
					<table>
						<tr>
							<td colspan="2"><div id="chart" style="width:350px; height:175px;"></div></td>
						</tr>
						<tr>
							<td width="150" align="center" bgcolor="green" onclick="mainDeck(mainDeckOutput)">Maindeck</td>
							<td width="150" align="center" bgcolor="grey" onclick="sideBoard()">Sideboard</td>
						</tr>
						<tr>
							<td id="cardsInDeck" width="150" align="center" bgcolor="tan"></td>
							<td id="pickNumber" width="150" align="center" bgcolor="white"></td>
						</tr>
						<tr>
							<td colspan="2" width="240">
								<div id="poolList" style="overflow:auto; height:700px; width:350px">
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>

<script type="text/javascript" src="/limgen/eternal/Flotr2-master/flotr2.min.js"></script>
<script type="text/javascript">

	var draft = <?PHP echo json_encode($array); ?>; //this provides a string taken by PHP from a MySQL DB
	var player = <?PHP echo json_encode($line); ?>; //this will be used to make adjustments to the drafts database if this draft process is completed
	draft = draft.split(" * "); //this and the following loop split the string into an array of four subarrays containing twelve further subarrays of twelve sets of numbers, each set containing one less number
	for (i = 0; i < 4; i++) {
		draft[i] = draft[i].split(" / ");
		for (y = 0; y < 12; y++) {
			draft[i][y] = draft[i][y].split(", ");
		}
	}

	var jsonCall = new XMLHttpRequest(); //this is to pull all card data from a JSON file based on TET DB
	jsonCall.open ('GET', 'tetcards.json');
	jsonCall.onload = function() {
		cardData = JSON.parse(jsonCall.responseText); //Why does the program throw an error with "var"?
	}
	jsonCall.send();

	var pool = []; //creates an array to hold all of the user's cards
	var deck = []; //creates an array to hold the user's maindeck
	var side = []; //creates an array to hold the user's sideboard
	for (i = 0; i < 26; i++) {
		deck[i] = [];
		side[i] = [];
	}
	var costs = []; //array for building of the card cost graph
	for (i = 0; i < 8; i++) {
		costs[i] = [i, 0];
	}
	var round = 0; //an index to navigate the 4 subarrays
	var sequence = 0; //an index to navigate the 12 sub-subarrays
	var factions = {
		F: 'B9231E',
		J: '508232',
		P: '415FE1',
		S: 'A541B9',
		T: 'FFAA46',
		N: '969BAA'
	}
	var mainDeckOutput = "poolList"; //this is to store a DOM element ID for the mainDeck(ID) function as it changes its output target after the draft is over and deck building begins

	window.onload = costGraph();
	window.onload = cardCounter();
	window.onload = boosterDisplay(draft[0][0]);

function cardPick(pick) { //this adds the user's selection to the pool array and displays the next set of images in the current subarray or moves to the next subarray if the last set is empty
	var addCard = cloneCard(cardData[draft[round][sequence][pick]-1]);
	for (faction in factions) {
		if (addCard.faction.length == 1) {
			if (addCard.faction == faction) {
				addCard.color = factions[faction];
			}
		} else {
			addCard.faction = 'N';
			addCard.color = factions.N;
		}
	}
	if (addCard.cost == "N") addCard.cost = 25;
	pool.push(addCard);
	if (deck[addCard.cost].length != 0) {
		result = deck[addCard.cost].filter(function(card){return card.number == addCard.number;});
	} else {
		result = 0;
	}
	if (result != 0) {
		index = deck[addCard.cost].findIndex(function(card){
			return card.number == addCard.number;
		});
		deck[addCard.cost][index].quantity++;
	} else {deck[addCard.cost].push(addCard);}
	mainDeck(mainDeckOutput);
	cardCounter();
	draft[round][sequence].splice(pick, 1);
	if (draft[round][sequence].length != 0) {
		sequence++;
	} else {
		sequence = 0;
		if (round != 3) round++;
	}
	boosterDisplay(draft[round][sequence]);
	if (pool.length == 48) { //displays all of the images selected by the user
		draft[0][11][0] = player;
		params = ('draft=' + JSON.stringify(draft));
		var draftReturn = new XMLHttpRequest();
		draftReturn.open('POST', 'receiver.php');
		draftReturn.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
		draftReturn.send(params);
		location.reload();
	}
}

function boosterDisplay(array) {
	document.getElementById("booster").innerHTML = "";
	for (card = 0; card < array.length; card++) {
		var display = document.getElementById("booster");
		var showCard = document.createElement('div');
		showCard.innerHTML = "<img id=" + "\"" + array[card] + "\"" + "onclick=cardPick(" + card + ")" + " src=\"TET/" + array[card] + ".png\" width=\"230\" height=\"315\">";
		while (showCard.firstChild) {
			display.appendChild(showCard.firstChild);
		}
	}
}

function mainDeck(ID) {
	document.getElementById(ID).innerHTML = "";
	for (cost = 0; cost < deck.length; cost++) {
		for (card = 0; card < deck[cost].length; card++) {
			deck[cost].sort(function(a, b) {
					if(a.name < b.name) return -1;
					if(a.name > b.name) return 1;
					return 0;
				}
			);
			var spoiler = document.getElementById(ID);
			var addPick = document.createElement('div');
			h = (deck[cost][card].cost < 25 ? 55 : 45);
			icon = (deck[cost][card].card_type == "power" ? deck[cost][card].faction : deck[cost][card].cost);
			addPick.innerHTML =
			['<table>',
				'<tr bgcolor="' + deck[cost][card].color + '">',
					'<td onclick=setAside(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"Assets/' + icon + '.png" width="45" height="' + h + '">',
					'</td>',
					'<td onclick=setAside(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"thumbs/' + deck[cost][card].number + '.png" width="55" height="55">',
					'</td>',
					'<td onclick=setAside(\'' + cost + '\',\'' + card + '\')>',
						'<div style="width:160px">',
							deck[cost][card].name,
						'</div>',
					'</td>',
					'<td>',
						'<img src=\"Assets/qty/' + deck[cost][card].quantity + '.png" width="55" height="55">' + "<br>",
					'</td>',
				'</tr>',
			'</table>'].join('\n');
			while(addPick.firstChild) {
				spoiler.appendChild(addPick.firstChild);
			}
		}
	}
	costGraph();
	cardCounter();
}

function sideBoard() {
	document.getElementById("poolList").innerHTML = "";
	for (cost = 0; cost < deck.length; cost++) {
		for (card = 0; card < side[cost].length; card++) {
			side[cost].sort(function(a, b) {
					if(a.name < b.name) return -1;
					if(a.name > b.name) return 1;
					return 0;
				}
			);
			var spoiler = document.getElementById("poolList");
			var addPick = document.createElement('div');
			h = (side[cost][card].cost < 25 ? 55 : 45);
			icon = (side[cost][card].card_type == "power" ? side[cost][card].faction : side[cost][card].cost);
			addPick.innerHTML =
			['<table>',
				'<tr bgcolor="' + side[cost][card].color + '">',
					'<td onclick=backToMain(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"Assets/' + icon + '.png" width="45" height="' + h + '">',
					'</td>',
					'<td onclick=backToMain(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"thumbs/' + side[cost][card].number + '.png" width="55" height="55">',
					'</td>',
					'<td onclick=backToMain(\'' + cost + '\',\'' + card + '\')>',
						'<div style="width:160px">',
							side[cost][card].name,
						'</div>',
					'</td>',
					'<td>',
						'<img src=\"Assets/qty/' + side[cost][card].quantity + '.png" width="55" height="55">' + "<br>",
					'</td>',
				'</tr>',
			'</table>'].join('\n');
			while(addPick.firstChild) {
				spoiler.appendChild(addPick.firstChild);
			}
		}
	}
	cardCounter();
}

function setAside(cost, cardAside) {
	cardClone = cloneCard(deck[cost][cardAside]); //clone the card object for subsequent operations to take effect on a different object from the one stored in the original array (i.e. deck)
	if (side[cost].length != 0) {
		result = side[cost].filter(function(card){return card.number == cardClone.number;});
	} else {
		result = 0;
	}
	if (result != 0) {
		index = side[cost].findIndex(function(card){
			return card.number == cardClone.number;
		});
		side[cost][index].quantity++;
	} else {
		side[cost].push(cardClone);
		side[cost][side[cost].length - 1].quantity = 1;
	}
	if (deck[cost][cardAside].quantity > 1) {
		deck[cost][cardAside].quantity--;
	} else {
		deck[cost].splice(cardAside, 1);
	}
	mainDeck(mainDeckOutput); //to refresh the maindeck list
	cardCounter();
}

function backToMain(cost, cardToMain) {
	cardClone = cloneCard(side[cost][cardToMain]); //clone the card object for subsequent operations to take effect on a different object from the one stored in the original array (i.e. side)
	if (side[cost].length != 0) {
		result = deck[cost].filter(function(card){return card.number == cardClone.number;});
	} else {
		result = 0;
	}
	if (result != 0) {
		index = deck[cost].findIndex(function(card){
			return card.number == cardClone.number;
		});
		deck[cost][index].quantity++;
	} else {
		deck[cost].push(cardClone);
		deck[cost][deck[cost].length - 1].quantity = 1;
	}
	if (side[cost][cardToMain].quantity > 1) {
		side[cost][cardToMain].quantity--;
	} else {
		side[cost].splice(cardToMain, 1);
	}
	costGraph(); //to adjust the maindeck costs graph according to changes made to the maindeck
	sideBoard(); //to refresh the sideboard list
	cardCounter();
}

function costGraph() {
	qtyIncr = 0; //quantity increment to account for multiple copies of a single card
	costs[7][1] = 0; //this has to be reset because this value incerements across several cost levels instead of being set to a single specific one every time the function is called
	maxCost = 7; //relative graph cieling to be replaced by the number of cards of a single cost once that number exceeds 7
	for (cost = 0; cost < 25; cost++) {
		if (cost < 7) {
				for (card = 0; card < deck[cost].length; card++) {
					qtyIncr += deck[cost][card].quantity - 1;
				}
				costs[cost] = [cost, deck[cost].length + qtyIncr];
				qtyIncr = 0;
		} else {
			if (deck[cost].length > 0) {
				for (card = 0; card < deck[cost].length; card++) {
					qtyIncr += deck[cost][card].quantity - 1;
				}
				costs[7][1] += (deck[cost].length + qtyIncr);
				qtyIncr = 0;
			}
		}
	}
	for (max = 0; max < 8; max++) {
		if (costs[max][1] > maxCost) maxCost = costs[max][1];
	}
	allCosts = []; //wrapper array to make the costs readable by the Flotr.draw function
	allCosts[0] = costs; //wrap the costs array for the Flotr.draw function, which requires array structure of [[[],..[]]]
	marks = [];
	for (tick = 0; tick < 8; tick++) {
		if (tick < 7) {
			marks[tick] = [tick, tick];
		} else marks[tick] = [tick, "7+"];
	}
	Flotr.draw(
		document.getElementById("chart"),
		allCosts,
		{
			bars: {
				show: true,
				barWidth: 0.5
			},
			yaxis: {
				min: 0,
				max: maxCost,
				ticks: []
			},
			xaxis: {
				min: -0.75,
				max: 7.75,
				ticks: marks
			},
			grid: {
				horizontalLines: false,
				verticalLines: false
			}
		}
	);
}

function cloneCard(original) {
	clone = {
		name: original.name,
		cost: original.cost,
		faction: original.faction,
		card_type: original.card_type,
		number: original.number,
		quantity: 1, //this is always set to 1 because no copies of the object exist in the new location, otherwise only the quantity of copies in the new location is changed
		color: original.color
	}
	return clone;
}

function cardCounter() {
	cardsInDeck = 0;
	for (cost in deck) {
		cardsInDeck += deck[cost].length;
		for (card = 0; card < deck[cost].length; card++) {
			cardsInDeck += deck[cost][card].quantity - 1;
		}
	}
	document.getElementById("cardsInDeck").innerHTML = "Cards in deck: " + cardsInDeck + "";
	pick = (pool.length < 48 ? 1 + pool.length : pool.length);
	document.getElementById("pickNumber").innerHTML = "Pick " + pick + "/48";
}

</script>