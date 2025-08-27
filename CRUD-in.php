<?php

define('DOC_ROOT', realpath(dirname(__FILE__) . '/./'));
function my_autoloader($class_name)
{
    require DOC_ROOT . '/classes/' . strtolower($class_name) . '.php';
}
spl_autoload_register('my_autoloader');


$db = Database::getDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["Add-invoice"])) {

        $invoice_number = $_POST['invoice_number'];
        $amount = $_POST['amount'];
        $source = $_POST['source'];
        $reason = $_POST['reason'];
        $remark = !empty($_POST['remark']) ? $_POST['remark'] : null;
        $issue_date = $_POST['issue_date'];
        $due_date = $_POST['due_date'];
        $vat_included = $_POST['vat_included'];
        $vat_rate_id = !empty($_POST['vat_rate_id']) ? $_POST['vat_rate_id'] : null;
        $current_time = date('Y-m-d H:i:s');
        // Handle the file upload

        $invoice_file = null;
        $invoice_file_type = null;
        if (!empty($_FILES['invoice_file']['tmp_name'])) {

            $allowed_types = ['application/pdf'];

            // Check the file type
            if (!in_array($_FILES['invoice_file']['type'], $allowed_types)) {
                echo "<script>alert('File type is not allowed. Only PDF files are accepted.');</script>";
                echo "<script>window.location.href = 'InvoiceIn-Adding.php';</script>";
                exit(); // Stop further script execution
            }

            if ($_FILES['invoice_file']['size'] > 10000000) {
                die("File size is too large");
            }

            // Get the file content as binary data
            $invoice_file = file_get_contents($_FILES['invoice_file']['tmp_name']);
            $invoice_file_type = $_FILES['invoice_file']['type'];
        }


        // Prepare the SQL statement to insert data, including the file
        $stmt = $db->db->prepare("INSERT INTO invoice_in (invoice_number, amount, source, reason, remark, issue_date, due_date, vat_included, vat_rate_id, amount_after_vat, invoice_file, invoice_file_type, created_at) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Bind the parameters (note 'b' for binary data)
        $stmt->bind_param(
            "sisssssisisss",
            $invoice_number,
            $amount,
            $source,
            $reason,
            $remark,
            $issue_date,
            $due_date,
            $vat_included,
            $vat_rate_id,
            $amount_after_vat,
            $invoice_file,
            $invoice_file_type, // Bind the file type
            $current_time
        );

        // Execute the query
        if ($stmt->execute()) {
            echo "an invoice added succesfully";
            // Redirect to the same page or a success page
            header("Location: InvoiceIn-Adding.php");
        } else {
            echo "adding error : " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else if (isset($_POST["Edit-invoice"])) {
        if (!empty($_POST["id"])) {
            // Debugging output to check incoming POST data
            echo "<pre>";
            print_r($_POST);
            echo "</pre>";

            // Trim the id to avoid any unwanted whitespace or newline issues
            $id = trim($_POST["id"]);
            $invoice_number = $_POST['invoice_number'];
            $amount = $_POST['amount'];
            $source = $_POST['source'];
            $reason = $_POST['reason'];
            $remark = !empty($_POST['remark']) ? $_POST['remark'] : null;
            $issue_date = $_POST['issue_date'];
            $due_date = $_POST['due_date'];
            $vat_included = $_POST['vat_included'];
            $updated_at = date('Y-m-d H:i:s');
            // Prepare the update query with placeholders for parameters
            $query = "UPDATE invoice_in 
                      SET invoice_number = :invoiceNumber, 
                          amount = :invoiceAmount, 
                          source = :source, 
                          reason = :reason,
                          remark = :remark,
                          issue_date = :issueDate, 
                          due_date = :dueDate, 
                          vat_included = :vatIncluded,
                          updated_at = :updated_at
                      WHERE id = :id";

            // Execute the update query
            $result = $db->query($query, [
                'invoiceNumber' => $invoice_number,
                'invoiceAmount' => $amount,
                'source' => $source,
                'reason' => $reason,
                'remark' => $remark,
                'issueDate' => $issue_date,
                'dueDate' => $due_date,
                'vatIncluded' => $vat_included,
                'updated_at' => $updated_at,
                'id' => $id
            ]);

            // Check if the query was successful
            if ($db->affectedRows()) {
                // Success message or redirection
                header('Location: receipts.php');
                exit;
            } else {
                // Error handling with debug info
                // echo "Error updating record: " . $db->lastQuery();
                // echo "<br>Debug info: ID = " . htmlspecialchars($id);
                header('Location: receipts.php');
            }
        } else {
            echo "Required fields are missing or ID is empty.";
        }
    } else if (isset($_POST["Delete-invoice"])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        echo "ID to be deleted: " . $id;
        if ($id > 0) {
            $sql = $db->query("DELETE FROM invoice_in WHERE id=$id");

            if ($sql == true) {
                header("Location: receipts.php");
            } else {
                echo $sql->error();
            }
        } else {
            echo "Invalid ID!";
        }
    }
}
