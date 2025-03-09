<?php
	$q = "SELECT language as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtLang = $db->prepare($q);
        $stmtLang->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtLang->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtLang->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resLang = $stmtLang->execute();
        while ($row = $resLang->fetchArray(SQLITE3_ASSOC)) {
            $dataLanguages[] = $row;
        }
?>
