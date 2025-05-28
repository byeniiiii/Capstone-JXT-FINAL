<?php
// Update the main query to filter only sublimation orders
$query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone_number,
          s.completion_date, s.template_id, s.design_type, s.printing_type
          FROM orders o
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          INNER JOIN sublimation_orders s ON o.order_id = s.order_id
          WHERE o.order_type = 'sublimation'";

// Add status filter if selected
if ($status_filter != 'all') {
    $query .= " AND o.order_status = ?";
}

// Add search functionality using existing fields
if (!empty($search_term)) {
    $query .= " AND (o.order_id LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.phone_number LIKE ?)";
}

// Add sorting using existing fields
$query .= " ORDER BY 
    CASE o.order_status
        WHEN 'pending_approval' THEN 1
        WHEN 'approved' THEN 2
        WHEN 'in_process' THEN 3
        WHEN 'ready_for_pickup' THEN 4
        WHEN 'completed' THEN 5
        ELSE 6
    END,
    o.created_at DESC
    LIMIT ? OFFSET ?";

// Update the count query as well
$count_query = "SELECT COUNT(*) as total 
                FROM orders o 
                INNER JOIN sublimation_orders s ON o.order_id = s.order_id
                WHERE o.order_type = 'sublimation'";

if ($status_filter != 'all') {
    $count_query .= " AND o.order_status = ?";
}