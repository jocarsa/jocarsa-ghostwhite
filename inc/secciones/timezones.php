<?php
$q = "SELECT timezone_offset as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtTimezones = $db->prepare($q);
        $stmtTimezones->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtTimezones->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtTimezones->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resTimezones = $stmtTimezones->execute();
        while ($row = $resTimezones->fetchArray(SQLITE3_ASSOC)) {
            $dataTimezones[] = $row;
        }
?>
