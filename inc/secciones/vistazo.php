<?php
// Visits per day.
        $q = "SELECT date(timestamp) as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY label";
        $stmtDay = $db->prepare($q);
        $stmtDay->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtDay->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtDay->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resDay = $stmtDay->execute();
        while ($row = $resDay->fetchArray(SQLITE3_ASSOC)) {
            $dataDay[] = $row;
        }
        // Visits per week.
        $q = "SELECT strftime('%Y-%W', timestamp) as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY label";
        $stmtWeek = $db->prepare($q);
        $stmtWeek->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtWeek->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtWeek->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resWeek = $stmtWeek->execute();
        while ($row = $resWeek->fetchArray(SQLITE3_ASSOC)) {
            $dataWeek[] = $row;
        }
        // Visits per month.
        $q = "SELECT strftime('%Y-%m', timestamp) as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY label";
        $stmtMonth = $db->prepare($q);
        $stmtMonth->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtMonth->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtMonth->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resMonth = $stmtMonth->execute();
        while ($row = $resMonth->fetchArray(SQLITE3_ASSOC)) {
            $dataMonth[] = $row;
        }
        // Visits per hour (last 24 hours).
        $q = "SELECT strftime('%H', timestamp) as label, count(*) as visits
              FROM logs
              WHERE timestamp >= datetime('now', '-24 hours')" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY label";
        $stmtHour = $db->prepare($q);
        if ($filterUser !== '') { $stmtHour->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resHour = $stmtHour->execute();
        while ($row = $resHour->fetchArray(SQLITE3_ASSOC)) {
            $dataHour[] = $row;
        }
?>
