<?php

define('DOC_ROOT', realpath(dirname(__FILE__) . '/./'));

function my_autoloader($class_name)
{
    require DOC_ROOT . '/classes/' . strtolower($class_name) . '.php';
}
spl_autoload_register('my_autoloader');

$db = Database::getDatabase();

if (isset($_GET['id']) && isset($_GET['table'])) {
    $id = intval($_GET['id']);
    $table = $_GET['table'];
    $button = $_GET['btn'];
    $allowed_tables = ['invoice_in', 'invoice_out'];
    $allowed_buttons = ['view', 'download'];


    if (in_array($table, $allowed_tables)) {
        if (in_array($button, $allowed_buttons)) {
            $query = "SELECT invoice_file, invoice_file_type FROM $table WHERE id = $id";
            $result = $db->query($query);

            if ($result && $result->num_rows > 0) {
                $file = $result->fetch_assoc();
                $fileData = $file['invoice_file']; 
                $fileType = $file['invoice_file_type']; // MIME type


                if ($fileType === 'application/pdf') {

                    // Set headers to display PDF inline in the browser
                    header("Content-Type: application/pdf");
                    if ($button === 'view') {
                        header("Content-Disposition: inline; filename=\"invoice.pdf\"");
                    } else {
                        header("Content-Disposition: attachment; filename=\"invoice.pdf\"");
                    }
                    header("Content-Length: " . strlen($fileData));

                    // Make sure no additional output is sent before the file
                    if (ob_get_length()) {
                        ob_end_clean();
                    }

                    // Output the binary file data
                    echo $fileData;
                    exit;
                } else {
                    echo "Error: File is not a PDF.";
                }
            } else {
                echo "File not found.";
            }
        } else {
            echo "Invalid button.";
        }
    } else {
        echo "Invalid table name.";
    }
} else {

    echo "Invalid request.";
}
