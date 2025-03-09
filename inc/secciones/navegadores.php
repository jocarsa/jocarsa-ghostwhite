<?php
$q = "SELECT
                CASE
                  WHEN user_agent LIKE '%Chrome%' AND user_agent NOT LIKE '%Edge%' THEN 'Chrome'
                  WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                  WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                  WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                  ELSE 'Other'
                END as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtBrowsers = $db->prepare($q);
        $stmtBrowsers->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtBrowsers->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtBrowsers->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resBrowsers = $stmtBrowsers->execute();
        while ($row = $resBrowsers->fetchArray(SQLITE3_ASSOC)) {
            $dataBrowsers[] = $row;
        }
?>
