<?php

require_once '../config.php';

$code_id = $_GET["id"];
$getRows = $_GET["getRows"];
$gd_code = $_GET["code"];
$page = $_GET['page']; // get the requested page
$limit = $_GET['rows']; // get how many rows we want to have into the grid
$sidx = $_GET['sidx']; // get index row - i.e. user click to sort
$sord = $_GET['sord']; // get the direction

if(!$sidx) $sidx ="1";

try {
	$dbh = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_user, $db_pswd);

	if ($getRows == 1) { 
		$stmt = $dbh->prepare("SELECT COUNT(id) as records FROM price_ml_row WHERE code = ".$code_id);
	} else {
		$stmt = $dbh->prepare("SELECT COUNT(id) as records FROM price_ml_row WHERE code = ".$gd_code);
	}
	$stmt->execute();    
	$count = (int) $stmt->fetch(PDO::FETCH_OBJ)->records;
	

	if( $count >0 ) {
		$total_pages = ceil($count/$limit);
	} else {
		$total_pages = 0;
	}
	if ($page > $total_pages) $page=$total_pages;
	$start = $limit*$page - $limit; // do not put $limit*($page - 1)

	if ($getRows == 1) { 
		$stmt = $dbh->prepare(
				"SELECT
					r.id,
					TRUNCATE(r.cost, 2) as cost,
					p.price_date,
					p.rate_rub
				FROM
					price_ml_row r,
					price_ml p
				WHERE r.price_id = p.id AND code = ".$code_id." ORDER BY p.price_date DESC");
	} else {
		$stmt = $dbh->prepare(
				"SELECT
					code as id,
					code,
					name
				FROM
					price_ml_row
				WHERE code = ".$gd_code." ORDER BY $sidx $sord LIMIT $start , $limit");
	}

	$stmt->execute();    

	$responce = new stdClass();
	$responce->page = $page;
	$responce->total = $total_pages;
	$responce->records = $count;
	$i=0;

	if ($getRows == 1) { 
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$cost_rub = $row['cost'] * $row['rate_rub'];
			$cost_rub20 = number_format($cost_rub * 1.20, 2, ',', ' ');
			$cost_rub = number_format($cost_rub, 2, ',', ' ');
			$responce->rows[$i]['id']= $row['id'];
			$responce->rows[$i]['cell']=
				array(
					$row['price_date'],
					$row['rate_rub'],
					$row['cost'],
					$cost_rub ,
					$cost_rub20,
				);
			$i++;
		}        
	} else {
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$responce->rows[0]['id']= $row['id'];
			$responce->rows[0]['cell']=
				array(
					$row['id'],
					$row['code'],
					$row['name'],
				);
	}

header("Content-type: application/json;charset=utf-8");

	echo json_encode($responce);

	$dbh = null;

} catch(PDOException $ex) {
	$dbh = null;
//		echo $ex->getMessage();
}


?>
