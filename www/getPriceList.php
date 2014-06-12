<?php

require_once '../config.php';

$page = $_GET['page']; // get the requested page
$limit = $_GET['rows']; // get how many rows we want to have into the grid
$sidx = $_GET['sidx']; // get index row - i.e. user click to sort
$sord = $_GET['sord']; // get the direction

if(!$sidx) $sidx ="1";

try {
	$dbh = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_user, $db_pswd);

	$stmt = $dbh->prepare("SELECT COUNT(id) as records FROM price_ml");
	$stmt->execute();    
	$count = (int) $stmt->fetch(PDO::FETCH_OBJ)->records;
	

	if( $count >0 ) {
		$total_pages = ceil($count/$limit);
	} else {
		$total_pages = 0;
	}
	if ($page > $total_pages) $page=$total_pages;
	$start = $limit*$page - $limit; // do not put $limit*($page - 1)


	$stmt = $dbh->prepare("SELECT id, price_date, rate_uah, rate_rub, load_date FROM price_ml ORDER BY $sidx $sord LIMIT $start , $limit");
	$stmt->execute();    

	$responce = new stdClass();
	$responce->page = $page;
	$responce->total = $total_pages;
	$responce->records = $count;
	$i=0;
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$responce->rows[$i]['id']= $row['id'];
		$responce->rows[$i]['cell']=array($row['id'],$row['price_date'],$row['rate_uah'],$row['rate_rub'],$row['load_date']);
		$i++;
	}        

header("Content-type: application/json;charset=utf-8");

	echo json_encode($responce);

	$dbh = null;

} catch(PDOException $ex) {
	$dbh = null;
//		echo $ex->getMessage();
}


?>
