<?php

$q = "SELECT (screen_width || 'x' || screen_height) as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
              
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtRes = $db->prepare($q);
        $stmtRes->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtRes->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtRes->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resRes = $stmtRes->execute();
        
        while ($row = $resRes->fetchArray(SQLITE3_ASSOC)) {
        
            $dataResolutions[] = $row;
        }
?>
