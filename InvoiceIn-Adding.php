<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipts</title>
    <div class="back-container">
        <a href="receipts.php" class="back-btn">← Back</a>
    </div>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        h1 {
            color: #333;
        }

        form {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 60%;
            max-width: 900px;
        }

        h2 {
            text-align: center;
            color: #007BFF;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .add-form {
            display: flex;
            justify-content: space-between;
            gap: 30px;
        }

        .sub-add-form {
            flex-grow: 1;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: bold;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }

        input[type="date"] {
            width: 100%;
        }

        button {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        @media (max-width: 768px) {
            .add-form {
                flex-direction: column;
            }
        }

        /* ___________________________ */
        .back-container {
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .back-btn {
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
    </style>
</head>

<body>

    <form action="CRUD-in.php" method="POST" enctype="multipart/form-data">
        <h2>Add Receipt (Invoice-in)</h2>
        <div class="add-form">
            <div class="sub-add-form">
                <label for="invoice_number">Invoice Number:</label>
                <input type="text" id="invoice_number" name="invoice_number" required>

                <label for="amount">Amount:</label>
                <input type="number" step="0.01" id="amount" name="amount" required>

                <label for="source">Source:</label>
                <input type="text" id="source" name="source" required>

                <label for="reason">Receipt Reason:</label>
                <input type="text" id="reason" name="reason" required>


            </div>

            <div class="sub-add-form">
                <label for="issue_date">Issue Date:</label>
                <input type="date" id="issue_date" name="issue_date" required>

                <label for="due_date">Due Date:</label>
                <input type="date" id="due_date" name="due_date" required>

                <label for="remark">Remark:</label>
                <input type="text" id="remark" name="remark">

                <div style="display: flex; align-items: center;">
                    <label for="vat_included" style="margin-right: 10px; white-space: nowrap;">Subject to VAT:</label>

                    <div style="display: flex; align-items: center; margin-left: 10px;">
                        <input type="radio" name="vat_included" value="1" id="vat_included_yes" style="margin-left: 10px;">
                        <label for="vat_included_yes" style="margin-left: 5px; margin-right: 20px;">Yes</label>

                        <input type="radio" name="vat_included" value="0" id="vat_included_no" style="margin-left: 10px;" checked>
                        <label for="vat_included_no" style="margin-left: 5px;">No</label>
                    </div>
                </div>

            </div>
        </div>
        <label for="invoice_file">Invoice File:</label>
        <input type="file" id="invoice_file" name="invoice_file">
        <button type="submit" name="Add-invoice">Add Invoice</button>
    </form>


</body>

</html>