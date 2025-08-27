<?php

define('DOC_ROOT', realpath(dirname(__FILE__) . '/./'));

function my_autoloader($class_name)
{
    require DOC_ROOT . '/classes/' . strtolower($class_name) . '.php';
}
spl_autoload_register('my_autoloader');

$db = Database::getDatabase();

if (isset($_POST['id']) && isset($_POST['table'])) {
    $invoiceId = $_POST['id'];
    $table_name = $_POST['table'];

    $allowed_tables = ['invoice_out', 'invoice_in'];

    if (!in_array($table_name, $allowed_tables)) {
        die("Invalid table name.");
    }
    if ($table_name == 'invoice_in') {
        $query = "SELECT invoice_number, amount, source, reason, remark, issue_date, due_date, vat_included FROM invoice_in WHERE id = ?";
    } else {
        $query = "SELECT invoice_number, amount, target, reason, remark, issue_date, due_date, vat_included FROM invoice_out WHERE id = ?";
    }
    $stmt = $db->db->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $db->db->error);
    }

    $stmt->bind_param("i", $invoiceId);
    if (!$stmt->execute()) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(["error" => "The invoice not found"]);
    }
} else {
    echo json_encode(['error' => 'ID or table parameter is missing.']);
}
