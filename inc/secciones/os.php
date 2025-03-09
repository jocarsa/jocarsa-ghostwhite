<?php
$q = "SELECT platform as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtOS = $db->prepare($q);
        $stmtOS->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtOS->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtOS->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resOS = $stmtOS->execute();
        while ($row = $resOS->fetchArray(SQLITE3_ASSOC)) {
            $dataOS[] = $row;
        }
?>
