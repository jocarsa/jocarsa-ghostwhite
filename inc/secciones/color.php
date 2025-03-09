<?php
	$q = "SELECT screen_color_depth as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtColorDepth = $db->prepare($q);
        $stmtColorDepth->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtColorDepth->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtColorDepth->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resColorDepth = $stmtColorDepth->execute();
        while ($row = $resColorDepth->fetchArray(SQLITE3_ASSOC)) {
            $dataColorDepth[] = $row;
        }
?>
