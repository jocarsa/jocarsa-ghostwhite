<?php
$q = "SELECT ip as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtIps = $db->prepare($q);
        $stmtIps->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtIps->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtIps->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resIps = $stmtIps->execute();
        while ($row = $resIps->fetchArray(SQLITE3_ASSOC)) {
            $dataIps[] = $row;
        }
?>
