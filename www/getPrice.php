<?php

require_once '../config.php';

$pr_id = $_GET["id"];
//$ADDWHERE = '';
$ADDSEL = '';
$node = (integer)$_REQUEST["nodeid"];
// detect if here we post the data from allready loaded tree
// we can make here other checks
if( $node >0) {
   $n_lft = (integer)$_REQUEST["n_left"];
   $n_rgt = (integer)$_REQUEST["n_right"];
   $n_lvl = (integer)$_REQUEST["n_level"];		
   $ADDWHERE = " node.lft > ".$n_lft." AND node.rgt < ".$n_rgt." AND ";
//   $ADDSEL = "(COUNT(parent.name)-1) AS level,";
   $n_lvl++;
} else { 
   // initial grid
//   $ADDSEL = "(COUNT(parent.name) - 1) AS level,";
   $n_lvl =0;
}

try {
	$dbh = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_user, $db_pswd);
	$stmt = $dbh->prepare(
			"SELECT 
				node.id,
				node.code,
				node.name,
				node.level,
				node.lft,
				node.rgt,
				node.guarantee,
				node.article,
				node.store,
				node.store2,
				node.store3,
				IF(node.cost=0, NULL, TRUNCATE(node.cost, 2)) as cost
			FROM
				price_ml_row AS node
			WHERE ".$ADDWHERE."
				node.level = ".$n_lvl." AND node.price_id = ".$pr_id."
			GROUP BY node.name
			ORDER BY node.lft 
			");

//	$stmt_cnt = $dbh->prepare("SELECT COUNT(*) as records FROM price_ml_row");
//	$stmt_cnt->execute();    
//	$count = (int) $stmt_cnt->fetch(PDO::FETCH_OBJ)->records;

	$stmt_rate = $dbh->prepare("SELECT rate_rub FROM price_ml WHERE id = ".$pr_id);
	$stmt_rate->execute();    
	$price_rate = $stmt_rate->fetchColumn(0);
	

//	if( $count >0 ) {
//		$total_pages = ceil($count/$limit);
//	} else {
//		$total_pages = 0;
//	}
//	if ($page > $total_pages) $page=$total_pages;
//	$start = $limit*$page - $limit; // do not put $limit*($page - 1)

	header("Content-type: application/json;charset=utf-8");
	
	$stmt->execute();    

	$response = new stdClass();
	$response->page = 1;
	$response->total = 1;
	$response->records = $count;
	$i=0;
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if($row['level'] == 0) {$row['name'] = $row['name'].'    / '.$price_rate. ' /';}
		if($row['rgt'] == $row['lft']+1) {
			$leaf = 'true';
			$row['name'] = $row['name'].' (art. '.$row['article'].')'; 
			$row['store'] = $row['store'].' / '.$row['store2'].' / '.$row['store3']; 
			$cost_rub = $row['cost'] * $price_rate;
			$cost_rub20 = number_format($cost_rub * 1.20, 2, ',', ' ');
			$cost_rub = number_format($cost_rub, 2, ',', ' ');
		} else {
			$leaf='false';
			$cost_rub = null;
			$cost_rub20 = null;
		}
//		if( $n_lvl ==  $row['level']) { // we output only the needed level
			$response->rows[$i]['id']= $row['id'];
			$response->rows[$i]['cell']=
				array($row['id'],
					$row['code'],
					$row['name'],
					$row['guarantee'],
					$row['article'],
					$row['store'],
					$row['store2'],
					$row['store3'],
					$row['cost'],
					$cost_rub,
					$cost_rub20,
 					$row['level'],
					$row['lft'],
					$row['rgt'],
					$leaf,
					'false'
				);
			$i++;
//		}
	}        

	echo json_encode($response);

	$dbh = null;

} catch(PDOException $ex) {
	$dbh = null;
//		echo $ex->getMessage();
}


?>
