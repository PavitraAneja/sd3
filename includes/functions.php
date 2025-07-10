<?php
function build_filter_query($params) {
    $conditions = [];
    if (!empty($params['min'])) $conditions[] = "L_SystemPrice >= " . intval($params['min']);
    if (!empty($params['max'])) $conditions[] = "L_SystemPrice <= " . intval($params['max']);
    if (!empty($params['beds'])) $conditions[] = "L_Keyword2 >= " . intval($params['beds']);
    if (!empty($params['baths'])) $conditions[] = "LM_Dec_3 >= " . intval($params['baths']);
    if (!empty($params['city'])) $conditions[] = "L_City LIKE '%" . addslashes($params['city']) . "%'";
    return $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
}
?>
