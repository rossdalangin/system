<?php
/**
 * Test Team Performance View
 */
require_once('tests/mock-wp.php');

global $wpdb;
$user_id = 1;

// Instantiate Dashboard
require_once('admin/class-admin-dashboard.php');
$dashboard = Agency_Nexus_Admin_Dashboard::get_instance();

echo "Starting Team Performance View Test...\n";

// Manual Query Check (what the dashboard does)
$time_table = $wpdb->prefix . 'an_time_entries';
$tasks_table = $wpdb->prefix . 'an_tasks';

$total_seconds = $wpdb->get_var($wpdb->prepare("SELECT SUM(duration) FROM $time_table WHERE user_id = %d", $user_id));
$total_hours = round($total_seconds / 3600, 2);
$assigned_tasks = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tasks_table WHERE assigned_to = %d", $user_id));

echo "User ID: $user_id\n";
echo "Total Hours: $total_hours (Expected: 1)\n";
echo "Active Tasks Count: " . count($assigned_tasks) . " (Expected: 1)\n";

if ($total_hours == 1.00 && count($assigned_tasks) == 1) {
    echo "SUCCESS: Team Performance queries working correctly.\n";
} else {
    echo "FAILURE: Team Performance queries mismatch.\n";
    exit(1);
}

ob_start();
$reflection = new ReflectionClass('Agency_Nexus_Admin_Dashboard');
$method = $reflection->getMethod('render_performance_view');
$method->setAccessible(true);
$method->invokeArgs($dashboard, [$user_id]);
$output = ob_get_clean();

if (strpos($output, 'Performance Report') !== false && strpos($output, '1 hrs') !== false && strpos($output, 'Test Task 1') !== false) {
    echo "SUCCESS: render_performance_view output contains expected data.\n";
} else {
    echo "FAILURE: render_performance_view output missing data.\n";
    echo "Output was: " . $output . "\n";
    exit(1);
}
