<?php
define('DOC_ROOT', realpath(dirname(__FILE__) . '/./'));

function my_autoloader($class_name)
{
    require DOC_ROOT . '/classes/' . strtolower($class_name) . '.php';
}
spl_autoload_register('my_autoloader');


$db = Database::getDatabase();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['downloaded_file'])) {

    // Trim the id to avoid any unwanted whitespace or newline issues
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $file = $_FILES['downloaded_file'];
    $table_name = isset($_GET['table']) ? $_GET['table'] : '';
    $invoice_file_type = null;

    if (!empty($_FILES['downloaded_file']['tmp_name'])) {

        $file_info = preg_replace("/[^a-zA-Z0-9_\.-]/", "", $_FILES['downloaded_file']['name']);
        $file_extension = strtolower(pathinfo($_FILES['downloaded_file']['name'], PATHINFO_EXTENSION));

        $allowed_tables = ['invoice_out', 'invoice_in'];
        if (!in_array($table_name, $allowed_tables)) {
            die("Invalid table name.");
        }

        $allowed_extensions = ['pdf'];
        if (!in_array($file_extension, $allowed_extensions)) {
            echo "<script>alert('File type is not allowed. Only PDF files are accepted.');</script>";
            if ($table_name == 'invoice_out') {
                echo "<script>window.location.href = 'payments.php';</script>";
            } else {
           echo "<script>window.location.href = 'receipts.php';</script>";
            }
            exit();
    }

        if ($_FILES['downloaded_file']['size'] > 10000000) {
            $redirect_page = ($table_name == 'invoice_out') ? 'payments.php' : 'receipts.php';
            echo "<script>alert('File is too large.');</script>";
            echo "<script>window.location.href = '$redirect_page';</script>";
            exit();
        }

        $mime_types = ['pdf' => 'application/pdf'];

        $invoice_file = file_get_contents($_FILES['downloaded_file']['tmp_name']);
        $invoice_file_type = isset($mime_types[$file_extension]) ? $mime_types[$file_extension] : 'application/pdf';
    }



    $query = "UPDATE $table_name 
              SET invoice_file = ?, 
                  invoice_file_type = ?
              WHERE id = ?";

    $stmt = $db->db->prepare($query);
    if ($stmt === false) {
        error_log('Prepare failed: ' . $db->db->error);
        die('Prepare failed: ' . $db->db->error);
    }

    $stmt->bind_param(
        'ssi',
        $invoice_file,
        $invoice_file_type,
        $id
    );

    $stmt->execute();

    // Check if the query was successful
    if ($stmt->affected_rows > 0) {
        // Success message or redirection
        if ($table_name === 'invoice_out') {
            header('Location: payments.php');
        } else {
            header('Location: receipts.php');
        }
        exit;
    } else {
        // Error handling with debug info
        echo "Error updating record: " . $db->lastQuery();
        echo "<br>Debug info: ID = " . htmlspecialchars($id);
    }
} else {
    echo "Error uploading file.";
}
