<?php
/**
 *   Load xls-price to MySQL database (nested model)
 */

require_once '../libraries/PHPExcel/PHPExcel.php';
require_once '../config.php';

//echo importToDb();
   
function importToDbNested($xlsFile) {

	global $db_host, $db_name, $db_user, $db_pswd;

	/** Define some header coordinates in xls-file */
	$coord_pr_date  = array(8, 11);
	$coord_pr_rate1 = array(8, 3);
	$coord_pr_rate2 = array(8, 4);
	$coord_pr_rate3 = array(8, 5);
	$coord_pr_rate4 = array(8, 6);

	/** Processing Rows start line */
	$from_line = 14;

	/** Fetch data from xls and load to db */

	$xlfl = PHPExcel_IOFactory::load($xlsFile);
	$xlf = $xlfl->getActiveSheet();
 
	/** Get and prepare header data */
	$pr_date = trim(htmlspecialchars($xlf->getCellByColumnAndRow($coord_pr_date[0], $coord_pr_date[1])->getValue()));
	$pr_rate1 = htmlspecialchars($xlf->getCellByColumnAndRow($coord_pr_rate1[0], $coord_pr_rate1[1])->getValue());
	$pr_rate2 = htmlspecialchars($xlf->getCellByColumnAndRow($coord_pr_rate2[0], $coord_pr_rate2[1])->getValue());
	$pr_rate3 = htmlspecialchars($xlf->getCellByColumnAndRow($coord_pr_rate3[0], $coord_pr_rate3[1])->getValue());
	$pr_rate4 = htmlspecialchars($xlf->getCellByColumnAndRow($coord_pr_rate4[0], $coord_pr_rate4[1])->getValue());

	$pr_date = substr($pr_date, 6, 4) . '-' . substr($pr_date, 3, 2) . '-' . substr($pr_date, 0, 2);

	list(, $pr_rate1) = explode(":", str_replace([" ", ","],["","."],$pr_rate1));
	list(, $pr_rate2) = explode(":", str_replace([" ", ","],["","."],$pr_rate2));
	list(, $pr_rate3) = explode(":", str_replace([" ", ","],["","."],$pr_rate3));
	list(, $pr_rate4) = explode(":", str_replace([" ", ","],["","."],$pr_rate4));

	$pr_load_date = date("y-m-d H:i:s");

	try {
		$dbh = new PDO('mysql:host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_user, $db_pswd);
		$dbh->beginTransaction();

		/** Insert price header */
		$stmt =	$dbh->prepare(
				"INSERT INTO price_ml (
					price_date, 
					rate_uah, 
					rate_uah_noncash, 
					rate_rub, 
					rate_rub_noncash, 
					load_date
				)
				VALUES( 
					:pr_date,
					:pr_rate1,
					:pr_rate2,
					:pr_rate3,
					:pr_rate4,
					:pr_load_date
				)");

		$stmt->bindParam(':pr_date',      $pr_date);
		$stmt->bindParam(':pr_rate1',     $pr_rate1);
		$stmt->bindParam(':pr_rate2',     $pr_rate2);
		$stmt->bindParam(':pr_rate3',     $pr_rate3);
		$stmt->bindParam(':pr_rate4',     $pr_rate4);
		$stmt->bindParam(':pr_load_date', $pr_load_date);

		$stmt->execute();

		/** Get price parent id */
		$pr_id = $dbh->lastInsertId();

		/** Insert price rows root */
		$stmt =	$dbh->prepare(
				"INSERT INTO price_ml_row (
					name,
					price_id,
					is_group,
					level,
					lft,
					rgt
				)
				VALUES(
					:pr_name,     
					:pr_id,     
					:pr_is_group,     
					:pr_level,
					:pr_lft,
					:pr_rgt
				)");

		$stmt->bindParam(':pr_name',		$pr_name);
		$stmt->bindParam(':pr_id',			$pr_id);
		$stmt->bindParam(':pr_is_group',	$pr_is_group);
		$stmt->bindParam(':pr_level',		$pr_level);
		$stmt->bindParam(':pr_lft',			$pr_lft);
		$stmt->bindParam(':pr_rgt',			$pr_rgt);

		$pr_name = "Price list ML - ".$pr_date;
		$pr_is_group = 1;
		$pr_level = 0;
		$pr_lft = 1;
		$pr_rgt = 2;

		$stmt->execute();

		/** Set root id as group id */
		$grp_id[0] = $dbh->lastInsertId();

		/** Prepare parent select */
		$stmt_sel_parent =	$dbh->prepare(
				"SELECT * FROM price_ml_row WHERE id = :grp_id"
				);
		$stmt_sel_parent->bindParam(':grp_id', $grp_id_);

		/** Prepare update right value */
		$stmt_upd_rgt =	$dbh->prepare(
				"UPDATE price_ml_row SET rgt = rgt + 2 WHERE price_id = :pr_id AND rgt >= :parent_rgt"
				);
		$stmt_upd_rgt->bindParam(':parent_rgt', $parent_rgt);
		$stmt_upd_rgt->bindParam(':pr_id',		$pr_id);

		/** Prepare update left value */
		$stmt_upd_lft =	$dbh->prepare(
				"UPDATE price_ml_row SET lft = lft + 2 WHERE price_id = :pr_id rgt >= :parent_rgt AND lft <> 1 AND lft > :parent_lft"
				);
		$stmt_upd_lft->bindParam(':parent_rgt', $parent_rgt);
		$stmt_upd_lft->bindParam(':parent_lft', $parent_lft);
		$stmt_upd_lft->bindParam(':pr_id',		$pr_id);

		/** Prepare price row insert */
		$stmt_ins =	$dbh->prepare(
				"INSERT INTO price_ml_row (
					code,
					name,
					guarantee,
					article,
					store,
					store2,
					store3,
					cost,
					price_id,
					group_id,
					is_group,
					level,
					lft,
					rgt
				)
				VALUES(
					:pr_code,     
					:pr_name,     
					:pr_guarantee,
					:pr_article,  
					:pr_store,    
					:pr_store2,   
					:pr_store3,   
					:pr_cost,     
					:pr_id,     
					:grp_id,     
					:pr_is_group,
					:pr_level,
					:pr_lft,
					:pr_rgt     
				)");

		$stmt_ins->bindParam(':pr_code',		$pr_code);
		$stmt_ins->bindParam(':pr_name',		$pr_name);
		$stmt_ins->bindParam(':pr_guarantee',	$pr_guarantee);
		$stmt_ins->bindParam(':pr_article',		$pr_article);
		$stmt_ins->bindParam(':pr_store',		$pr_store);
		$stmt_ins->bindParam(':pr_store2',		$pr_store2);
		$stmt_ins->bindParam(':pr_store3',		$pr_store3);
		$stmt_ins->bindParam(':pr_cost',		$pr_cost);
		$stmt_ins->bindParam(':pr_id',			$pr_id);
		$stmt_ins->bindParam(':grp_id', 		$grp_id_);
		$stmt_ins->bindParam(':pr_is_group',	$pr_is_group);
                $stmt_ins->bindParam(':pr_level',                 $pr_level);
		$stmt_ins->bindParam(':pr_lft',			$pr_lft);
		$stmt_ins->bindParam(':pr_rgt',			$pr_rgt);

		/** Define empty lines counter and first processed line in XLS file */
		$empty_lines = 0;
		$i = $from_line-1;

		while ($empty_lines < 3)
		{
			$i++;

			/** For debug purpose */
			// echo $i."\n";

			/** Get row data from xls. Vars $otlv and $otlv_next - levels current and next rows */
			$otlv         = $xlf->getRowDimension($i)->getOutlineLevel();
			$otlv_next    = $xlf->getRowDimension($i+1)->getOutlineLevel();
			$pr_code      = trim(htmlspecialchars($xlf->getCellByColumnAndRow(1, $i)->getValue()));
			$pr_name      = trim(htmlspecialchars($xlf->getCellByColumnAndRow(2, $i)->getValue()));
			$pr_guarantee = trim(htmlspecialchars($xlf->getCellByColumnAndRow(3, $i)->getValue()));
			$pr_article   = trim(htmlspecialchars($xlf->getCellByColumnAndRow(4, $i)->getValue()));
			$pr_store     = trim(htmlspecialchars($xlf->getCellByColumnAndRow(5, $i)->getValue()));
			$pr_store2    = trim(htmlspecialchars($xlf->getCellByColumnAndRow(6, $i)->getValue()));
			$pr_store3    = trim(htmlspecialchars($xlf->getCellByColumnAndRow(7, $i)->getValue()));
			$pr_cost      = trim(htmlspecialchars($xlf->getCellByColumnAndRow(8, $i)->getValue()));


			/** Detect end of xls */
			if (empty($pr_code) && empty($pr_cost)) {
				$empty_lines++;
				continue;
			} else {
				$empty_lines = 0;
			}

			/** Detect group */
			if (empty($pr_name) && empty($pr_cost)) {
				if ($otlv_next == $otlv) {
					continue;
				}
				$pr_is_group = 1;
				$pr_name = $pr_code;
				$pr_code = null;
			} else {
				$pr_is_group = 0;
				$grp_id[$otlv+1] = null;
			}

			/** Set group id for current row */
			$grp_id_ = $grp_id[$otlv];

			/** Update lefts and rigts */
			$stmt_sel_parent->execute();
			$parent = $stmt_sel_parent->fetchObject();
			$parent_rgt = $parent->rgt;
			$parent_lft = $parent->lft;

			$stmt_upd_rgt->execute();
			$stmt_upd_lft->execute();

			$numberOfChildren = ($parent_rgt - $parent_lft - 1) / 2;
			$pr_lft = $parent_lft + $numberOfChildren * 2 + 1;
			$pr_rgt = $pr_lft + 1;
			$pr_level = $otlv+1;

			/** Insert row */
			$stmt_ins->execute();

			/** Set next group id */
			if ($pr_is_group == 1) {
				$grp_id[$otlv+1] = $dbh->lastInsertId();
			}
		}

		$dbh->commit();
		$dbh = null;

		return $i;

	} catch(PDOException $ex) {
		$dbh->rollBack();
		$dbh = null;
		return -1;
	}


}

?>
