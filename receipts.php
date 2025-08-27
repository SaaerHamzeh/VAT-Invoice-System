<?php
define('DOC_ROOT', realpath(dirname(__FILE__) . '/./'));
function my_autoloader($class_name)
{
    require DOC_ROOT . '/classes/' . strtolower($class_name) . '.php';
}
spl_autoload_register('my_autoloader');

// Get the database connection
$db = Database::getDatabase();

// Variables for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$include_tax = isset($_GET['Taxable']) ? true : false;
$not_include_tax = isset($_GET['Non-taxable']);
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query for fetching payments
$query = "SELECT * FROM invoice_in WHERE 1";

// Append filters
if ($start_date && $end_date) {
    $query .= " AND issue_date BETWEEN '$start_date' AND '$end_date'";
}
if ($include_tax) {
    $query .= " AND vat_included = 1"; // Only show invoices with VAT included
}
if ($not_include_tax) {
    $query .= " AND vat_included = 0"; // Only show invoices without VAT
}
if (!empty($search)) {
    $query .= " AND (invoice_number LIKE '%$search%' OR source LIKE '%$search%' OR reason LIKE '%$search%')";
}

// Fetch all filtered rows for total calculation
$all_rows = $db->getRows($query);

// Initialize totals
$total_amount = 0;
$total_amount_with_tax = 0;

// Calculate totals from all rows
foreach ($all_rows as $row) {
    $total_amount += $row['amount'];
    $total_amount_with_tax += $row['amount_after_vat'];
}

// Pagination logic
$records_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Modify the main query to include LIMIT for pagination
$query .= " LIMIT $offset, $records_per_page";
$rows = $db->getRows($query);

// Count total records for pagination
$total_rows_query = "SELECT COUNT(*) AS total FROM invoice_in WHERE 1";
if ($start_date && $end_date) {
    $total_rows_query .= " AND issue_date BETWEEN '$start_date' AND '$end_date'";
}
if ($include_tax) {
    $total_rows_query .= " AND vat_included = 1";
}
if ($not_include_tax) {
    $total_rows_query .= " AND vat_included = 0";
}
if (!empty($search)) {
    $total_rows_query .= " AND (invoice_number LIKE '%$search%' OR source LIKE '%$search%' OR reason LIKE '%$search%')";
}

$total_rows_result = $db->getRow($total_rows_query);
$total_records = $total_rows_result['total'];
$total_pages = ceil($total_records / $records_per_page);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <div class="back-container">
        <a href="index.php" class="back-btn">← Back</a>
    </div>

    <title>Receipts Overview</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        h1 {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            color: #333;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        button {
            padding: 8px 12px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .btn-invoice {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #007BFF;
            /* Bootstrap primary color */
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .btn-invoice:hover {
            background-color: #0056b3;
        }

        .btn-container {
            text-align: right;
            /* Aligns the content to the right */
            margin: 20px 0;
        }

        form {
            margin-bottom: 20px;
        }

        .UDform {
            margin: 0px;
            display: inline-block;
        }

        #UDform {
            display: flex;
            align-content: space-around;
            align-items: center;
        }

        form input,
        form button {
            margin-right: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        form input[type="date"] {
            max-width: 150px;
        }

        .total {
            font-weight: bold;
        }

        .AED {
            font-weight: bold;
            color: red;
        }

        /* ____________________________ */
        .popupForm {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
        }

        /* تغبيش الخلفية */
        #overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }

        a {
            text-decoration: none;
        }

        /* ___________________________ */
        .back-container {
            margin-bottom: 15px;
        }

        .back-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #007BFF;
            color: white;
            font-size: 16px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background-color: #0056b3;
        }

        /* _____________________update form___________*/
        #editInvoiceForm {
            background-color: #ffffff;
            /* لون الخلفية للنموذج */
            padding: 20px;
            border-radius: 8px;
            /* زوايا دائرية */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            /* ظل */
        }

        .form-group {
            margin-bottom: 15px;
            margin-right: 20px;
            /* فراغ بين الحقول */
        }

        .form-group label {
            font-weight: bold;
            /* جعل النص عريض */
        }

        .form-control {
            width: 100%;
            /* عرض كامل */
            padding: 10px;
            /* فراغ داخلي */
            border: 1px solid #ced4da;
            /* لون الحدود */
            border-radius: 5px;
            /* زوايا دائرية */
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            /* ظل داخلي */
        }
    </style>
</head>

<body>
    <h1>Receipts Overview</h1>

    <!-- Filter Form -->
    <form method="GET" action="">
        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" value="<?php echo $start_date; ?>">

        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" value="<?php echo $end_date; ?>">

        <label for="Taxable">Taxable :</label>
        <input type="checkbox" name="Taxable">

        <label for="Non-taxable">Non-taxable :</label>
        <input type="checkbox" name="Non-taxable">
        <label for="search">Search:</label>
        <input type="text" name="search" placeholder="Search by Invoice Number or Source" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">


        <button type="submit">Filter</button>
    </form>
    <div class="btn-container">
        <a href="InvoiceIn-Adding.php" class="btn-invoice">Add New Receipt</a>
    </div>
    <!-- Receipts Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Invoice Number</th>
                <th>Amount</th>
                <th>Source</th>
                <th>Receipt Reason</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>VAT Included</th>
                <th>Amount After VAT</th>
                <th>Remark</th>
                <th>File</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row) {?>
                <?php

    ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['invoice_number']; ?></td>
                    <td><?php echo number_format($row['amount'], ); ?><span class="AED"> A.E.D</span></td>
                    <td><?php echo $row['source']; ?></td>
                    <td><?php echo $row['reason']; ?></td>
                    <td><?php echo $row['issue_date']; ?></td>
                    <td><?php echo $row['due_date']; ?></td>
                    <td><?php echo $row['vat_included'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo number_format($row['amount_after_vat'], ); ?><span class="AED"> A.E.D</span></td>
                    <td><?php echo $row['remark']; ?></td>
                    <td>

                        <?php if (!empty($row['invoice_file'])): ?>

                            <a href="view_file.php?id=<?php echo urlencode($row['id']); ?>&table=invoice_in&btn=view" source="_blank">
                                <button type="button">View File</button>
                            </a>
                            <a href="view_file.php?id=<?php echo urlencode($row['id']); ?>&table=invoice_in&btn=download" source="_blank">
                                <button type="button">Download File</button>
                            </a>
                            <form action="download_file.php?id=<?php echo urlencode($row['id']); ?>&table=invoice_in" method="POST" enctype="multipart/form-data">
                                <input type="file" name="downloaded_file" required>
                                <button type="submit">Upload File</button>
                            </form>
                        <?php else: ?>

                            <form action="download_file.php?id=<?php echo urlencode($row['id']); ?>&table=invoice_in" method="POST" enctype="multipart/form-data">
                                <input type="file" name="downloaded_file" required>
                                <button type="submit">Upload File</button>
                            </form>
                        <?php endif;?>

                    </td>
                    <td id="UDform">
                        <button type="button" class="showFormBtn" data-form="modifying" data-id="<?php echo $row['id']; ?>">Edit</button>

                        <form action="CRUD-in.php" method="POST" class="UDform" onsubmit="return confirm('Are you sure?')">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="Delete-invoice">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php }?>
        </tbody>
    </table>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&search=<?php echo htmlspecialchars($search); ?>">Prev</a>
        <?php endif;?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&search=<?php echo htmlspecialchars($search); ?>"><?php echo $i; ?></a>
        <?php endfor;?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&search=<?php echo htmlspecialchars($search); ?>">Next</a>
        <?php endif;?>
    </div>
    <style>
        .pagination {
            text-align: center;
            margin: 20px 0;
        }

        .pagination a {
            margin: 0 5px;
            padding: 8px 12px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .pagination a:hover {
            background-color: #0056b3;
        }
    </style>
    <!-- _______________________________________________________________________________________ -->
    <!-- _______________pop_up_empty_div____________ -->
    <div id="overlay"></div>
    <!-- _______________pop_up_empty_div____________ -->
    <!-- edit button Modal -->
    <div class="modal fade popupForm" id="modifying" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">

        <form id="editInvoiceForm" method="POST" action="CRUD-in.php">
            <input type="hidden" id="form-id" name="id" value="">

            <div class="form-group">
                <label for="invoice_number">Invoice Number:</label>
                <input type="text" class="form-control" id="invoice_number" name="invoice_number" required>
            </div>
            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
            </div>
            <div class="form-group">
                <label for="source">Source:</label>
                <input type="text" class="form-control" id="source" name="source" required>
            </div>
            <div class="sub-add-form">
                <label for="issue_date">Issue Date:</label>
                <input type="date" id="issue_date" name="issue_date" required>

                <label for="due_date">Due Date:</label>
                <input type="date" id="due_date" name="due_date" required><br>

                <label for="reason" >Receipt Reason:</label>
                <input type="text" id="reason" name="reason" style=" margin-bottom: 10px; margin-top: 10px;" required>

                <div style="display: flex; align-items: center;">
                    <label for="vat_included" style="margin-right: 10px; white-space: nowrap;">Subject to VAT:</label>

                    <div style="display: flex; align-items: center; margin-left: 10px; margin-bottom: 10px;">
                        <input type="radio" name="vat_included" value="1" id="vat_included_yes" style="margin-left: 10px;">
                        <label for="vat_included_yes" style="margin-left: 5px; margin-right: 20px;">Yes</label>

                        <input type="radio" name="vat_included" value="0" id="vat_included_no" style="margin-left: 10px;">
                        <label for="vat_included_no" style="margin-left: 5px;">No</label>
                    </div>
                </div>
            </div>
            <label for="remark">Remark:</label>
            <input type="text" id="remark" name="remark">
            <button type="submit" class="btn btn-primary" name="Edit-invoice">Save changes</button>
        </form>
        <script>
            document.querySelectorAll('.showFormBtn').forEach(function(button) {
                button.addEventListener('click', function() {
                    fetchProductDetails.call(this);
                });
            });

            function fetchProductDetails() {
                var invoiceId = this.getAttribute('data-id');
                console.log("Selected Invoice ID:", invoiceId);

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "fetch_edit_info.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        console.log("AJAX Call Completed");
                        console.log("Response Text:", xhr.responseText);
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                console.log("Parsed Response:", response);

                                if (response.error) {
                                    console.error("Error:", response.error);
                                }else {


                                    document.getElementById('invoice_number').value = response.invoice_number || '';
                                    document.getElementById('amount').value = response.amount || '';
                                    document.getElementById('source').value = response.source || '';
                                    document.getElementById('reason').value = response.reason || '';
                                    document.getElementById('remark').value = response.remark || '';
                                    document.getElementById('issue_date').value = response.issue_date || '';
                                    document.getElementById('due_date').value = response.due_date || '';
                                    document.getElementById('form-id').value = invoiceId || '';
                                    if (response.vat_included == 1) {
                                        document.getElementById('vat_included_yes').checked = true;
                                    } else {
                                     document.getElementById('vat_included_no').checked = true;
                                    }
                                }
                            } catch (e) {
                                console.error("Error parsing JSON:", e) ;
                            }
                        } else {
                            console.error("AJAX Error:", xhr.statusText);
                        }
                    }
                };

                xhr.send("id=" + encodeURIComponent(invoiceId) + "&table=invoice_in");
            }
        </script>
    </div>
    <script src="./pop_up_form.js"></script>
    <!-- _______________________________________________________________________________________ -->
    <!-- Display Totals -->
    <h3>Amount totals for payments </h3>
    <?php if ($not_include_tax): ?>
        <p class="total">Without Tax(vat):
            <?php echo number_format($total_amount, ); ?><span class="AED"> A.E.D
        </p>
    <?php else: ?>
        <p class="total">Without Tax(vat):
            <?php echo number_format($total_amount, ); ?><span class="AED"> A.E.D
        </p>
        <p class="total">With Tax(vat):
            <?php echo number_format($total_amount_with_tax, ); ?><span class="AED"> A.E.D
        </p>
        <p class="total">Total VAT:
            <?php echo number_format($total_amount_with_tax - $total_amount, ); ?><span class="AED"> A.E.D
        </p>
    <?php endif;?>

</body>


</html>