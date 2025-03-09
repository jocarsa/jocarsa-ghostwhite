<?php
$q = "SELECT url as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtUrls = $db->prepare($q);
        $stmtUrls->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtUrls->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtUrls->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resUrls = $stmtUrls->execute();
        while ($row = $resUrls->fetchArray(SQLITE3_ASSOC)) {
            $dataUrls[] = $row;
        }
?>
