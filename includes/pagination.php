<?php
function paginate($total, $per_page = 12, $page = 1, $url = '?') {
    $total_pages = ceil($total / $per_page);
    $prev = $page > 1 ? "<a href='{$url}page=" . ($page - 1) . "'>Previous</a>" : "";
    $next = $page < $total_pages ? "<a href='{$url}page=" . ($page + 1) . "'>Next</a>" : "";
    return "<div class='pagination'>{$prev} Page {$page} of {$total_pages} {$next}</div>";
}
?>
