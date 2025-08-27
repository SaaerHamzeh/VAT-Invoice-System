  <?php
    define('DOC_ROOT', realpath(dirname(__FILE__) . '/./'));
    function my_autoloader($class_name)
    {
        require DOC_ROOT . '/classes/' . strtolower($class_name) . '.php';
    }
    spl_autoload_register('my_autoloader');
    $db = Database::getDatabase();

    // الحصول على معدل ضريبة القيمة المضافة من قاعدة البيانات أو استخدام القيمة الافتراضية إذا لم يتم تعيينها
    $vatRateQuery = "SELECT vat_value FROM vat_rates ORDER BY start_date DESC LIMIT 1";
    $vatRateResult = $db->query($vatRateQuery)->fetch_assoc();
    $vatRate = $vatRateResult['vat_value'] ?? 0;

    // تعيين تاريخ البدء لدورات الضريبة من 1 يونيو 2023
    $startCycleDate = new DateTime('2023-06-01');
    $currentCycle = (int)(($startCycleDate->diff(new DateTime())->m + $startCycleDate->diff(new DateTime())->y * 12) / 3);

    // تحديد الدورة المختارة بناءً على اختيار المستخدم أو الافتراضي للدورة الحالية
    $selectedCycle = isset($_POST['cycle']) ? (int)$_POST['cycle'] : $currentCycle;
    $startDate = (clone $startCycleDate)->modify("+" . ($selectedCycle * 3) . " months")->format("Y-m-01");
    $endDate = (clone $startCycleDate)->modify("+" . (($selectedCycle + 1) * 3) . " months -1 day")->format("Y-m-d");

    // استرجاع المبالغ للدورة المحددة
    $inAmountQuery = "SELECT SUM(amount) AS in_amount FROM invoice_in WHERE (issue_date BETWEEN :start_date AND :end_date) AND vat_included = 0";
    $inAmountResult = $db->query($inAmountQuery, ['start_date' => $startDate, 'end_date' => $endDate])->fetch_assoc();
    $inAmount = $inAmountResult['in_amount'] ?? 0;

    $inVatAmountQuery = "SELECT SUM(amount_after_vat) AS in_vat_amount FROM invoice_in WHERE (issue_date BETWEEN :start_date AND :end_date) AND vat_included = 1";
    $inVatAmountResult = $db->query($inVatAmountQuery, ['start_date' => $startDate, 'end_date' => $endDate])->fetch_assoc();
    $inVatAmount = $inVatAmountResult['in_vat_amount'] ?? 0;

    $outAmountQuery = "SELECT SUM(amount) AS out_amount FROM invoice_out WHERE (issue_date BETWEEN :start_date AND :end_date) AND vat_included = 0";
    $outAmountResult = $db->query($outAmountQuery, ['start_date' => $startDate, 'end_date' => $endDate])->fetch_assoc();
    $outAmount = $outAmountResult['out_amount'] ?? 0;

    $outVatAmountQuery = "SELECT SUM(amount_after_vat) AS out_vat_amount FROM invoice_out WHERE (issue_date BETWEEN :start_date AND :end_date) AND vat_included = 1";
    $outVatAmountResult = $db->query($outVatAmountQuery, ['start_date' => $startDate, 'end_date' => $endDate])->fetch_assoc();
    $outVatAmount = $outVatAmountResult['out_vat_amount'] ?? 0;

    // حسابات ضريبة القيمة المضافة
    $queryOutVAT = "SELECT SUM(amount_after_vat - amount) AS output_vat FROM invoice_out WHERE issue_date BETWEEN :start_date AND :end_date";
    $resultOutVAT = $db->query($queryOutVAT, ['start_date' => $startDate, 'end_date' => $endDate])->fetch_assoc();
    $outputVAT = $resultOutVAT['output_vat'] ?? 0;

    $queryInVAT = "SELECT SUM(amount_after_vat - amount) AS input_vat FROM invoice_in WHERE issue_date BETWEEN :start_date AND :end_date";
    $resultInVAT = $db->query($queryInVAT, ['start_date' => $startDate, 'end_date' => $endDate])->fetch_assoc();
    $inputVAT = $resultInVAT['input_vat'] ?? 0;

    // حساب ضريبة القيمة المضافة المستحقة والتعديل
    $vatDue = $inputVAT - $outputVAT;
    ?>

  <!DOCTYPE html>
  <html lang="en">

  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Finance Management - VAT Calculation</title>
  </head>
  <style>
      /* General Styling */
      /* تحسين التنسيق العام */
      body {
          font-family: Arial, sans-serif;
          padding: 20px;
          background-color: #f4f6f9;
          color: #333;
      }

      /* عنوان الصفحة */
      h1 {
          text-align: center;
          color: #4A90E2;
          margin-bottom: 20px;
      }

      /* قائمة القوائم */
      .menu {
          display: flex;
          justify-content: center;
          margin-top: 20px;
      }

      .menu a {
          padding: 10px 20px;
          border: 1px solid #4A90E2;
          text-decoration: none;
          font-size: 16px;
          color: #4A90E2;
          background-color: #fff;
          margin: 0 5px;
          border-radius: 5px;
          transition: background-color 0.3s ease;
      }

      .menu a:hover {
          background-color: #dbe7f3;
      }

      /* حاوية ضرائب */
      .tax-container {
          /* display: flex;
        flex-wrap: wrap;
        align-items: center;
        align-content: center; */
          gap: 15px;
          /* justify-content: space-between; */
          background-color: #ffffff;
          border-radius: 8px;
          padding: 20px;
          max-width: 1000px;
          margin: 20px auto;
          box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
      }

      .tax-container h2 {
          color: #4A90E2;
          width: 100%;
          text-align: center;
      }

      /* توسيع صندوق النتائج ليغطي عرض الشاشة */
      /* توسيع صندوق النتائج ليغطي العرض بالكامل تقريبًا */
      .result-box {
          width: 100%;
          margin: 0 auto;
          /* مركزة الصندوق في وسط الصفحة */
          padding: 25px;
          /* زيادة الـ padding لزيادة المساحة الداخلية */
          background-color: #f9f9f9;
          border-radius: 8px;
          box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
          margin-bottom: 20px;
      }


      /* تكبير النص داخل صندوق النتائج */
      .result-box p {
          font-size: 18px;
          /* زيادة حجم النص */
          margin: 10px 0;

      }

      .result-box .amount {
          font-weight: bold;
          color: #4A90E2;
          font-size: 20px;
          /* زيادة حجم النص داخل القيم */
      }



      /* صندوق قيمة ضريبة القيمة المضافة */
      .net-vat-due {
          background-color: #e7f3ff;
          padding: 15px;
          text-align: center;
          border: 2px solid #4A90E2;
          border-radius: 8px;
          font-size: 18px;
          font-weight: bold;
          margin: 10px auto;
          width: 620px;
      }

      .net-vat-due .net-amount {
          color: #d9534f;
          font-size: 24px;
      }

      /* نموذج الإعدادات */
      .settings {
          display: none;
          background-color: #ffffff;
          border-radius: 8px;
          padding: 20px;
          max-width: 600px;
          margin: 30px auto;
          box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
      }

      /* جدول المعاملات */
      .netVATchanges table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 20px;
      }

      .netVATchanges th,
      .netVATchanges td {
          border: 1px solid #ddd;
          padding: 8px;
          text-align: right;
      }

      .netVATchanges th {
          background-color: #f2f2f2;
          color: #333;
      }

      .netVATchanges tr:hover {
          background-color: #f5f5f5;
      }

      .toggle-button {
          display: block;
          margin: auto;
          margin-top: 20px;
          padding: 10px 20px;
          background-color: #4A90E2;
          color: white;
          border: none;
          border-radius: 5px;
          cursor: pointer;
          font-size: 16px;
          transition: background-color 0.3s ease;
      }

      .toggle-button:hover {
          background-color: #357ABD;
      }
  </style>

  <body>
      <h1>Finance Management</h1>

      <div class="menu">
          <a href="payments.php">Payments (Invoice Out)</a>
          <a href="receipts.php">Receipts (Invoice In)</a>
      </div>

      <div class="tax-container">
          <h2>VAT Calculation</h2>

          <div class="tax-calculation">
              <!-- Cycle Selection Form -->
              <form method="post" style="text-align: center; margin: 20px auto; width: 50%;">
                  <label for="cycle">Select Tax Cycle:</label>
                  <select name="cycle" id="cycle" onchange="this.form.submit()" style="display: inline-block;">
                      <?php for ($i = 0; $i <= $currentCycle; $i++): ?>
                          <?php
                            $cycleStartDate = (clone $startCycleDate)->modify("+" . ($i * 3) . " months")->format("F Y");
                            ?>
                          <option value="<?php echo $i; ?>" <?php if ($i === $selectedCycle) echo 'selected'; ?>>
                              Cycle <?php echo $i + 1; ?> (<?php echo $cycleStartDate; ?>)
                          </option>
                      <?php endfor; ?>
                  </select>
              </form>

              <p style="text-align: center;"><strong>Tax Cycle Period:</strong> <span style="font-weight: bold; color: #4A90E2;"><?php echo date("F Y", strtotime($startDate)); ?> to <?php echo date("F Y", strtotime($endDate)); ?></span></p>

              <div class="result-box" style="margin: 0 auto; width: 60%; text-align: center;">
                  <p><strong>Total Receipts Amount:</strong> <span class="amount"><?php echo number_format($inAmount, 2); ?> AED</span></p>
                  <p><strong>Total Receipts Amount with VAT:</strong> <span class="amount"><?php echo number_format($inVatAmount, 2); ?> AED</span></p>
                  <p><strong>Total VAT Receipts :</strong> <span class="amount"><?php echo number_format($inputVAT, 2); ?> AED</span></p>
                  <hr>
                  <p><strong>Total Payments Amount:</strong> <span class="amount"><?php echo number_format($outAmount, 2); ?> AED</span></p>
                  <p><strong>Total Payments Amount with VAT:</strong> <span class="amount"><?php echo number_format($outVatAmount, 2); ?> AED</span></p>
                  <p><strong>Total VAT Payments :</strong> <span class="amount"><?php echo number_format($outputVAT, 2); ?> AED</span></p>
              </div>

          </div>
          <div class="net-vat-due">
              <p><strong>VAT Due changes:</strong> <span class="net-amount"><?php echo number_format($vatDue, 2); ?> AED</span></p>
          </div>


          <!-- Export PDF Button for Selected Cycle -->
          <form action="export.php" method="post">
              <input type="hidden" name="start_date" value="<?php echo $startDate; ?>">
              <input type="hidden" name="end_date" value="<?php echo $endDate; ?>">
              <input type="hidden" name="in_amount" value="<?php echo $inAmount; ?>">
              <input type="hidden" name="in_vat_amount" value="<?php echo $inVatAmount; ?>">
              <input type="hidden" name="out_amount" value="<?php echo $outAmount; ?>">
              <input type="hidden" name="out_vat_amount" value="<?php echo $outVatAmount; ?>">
              <input type="hidden" name="output_vat" value="<?php echo $outputVAT; ?>">
              <input type="hidden" name="input_vat" value="<?php echo $inputVAT; ?>">
              <input type="hidden" name="vat_due" value="<?php echo $vatDue; ?>">
              <button type="submit" style="display: block; margin: 20px auto 30px auto; padding: 10px 20px; background-color: #4A90E2; color: white; border: none; border-radius: 5px; cursor: pointer;">Export PDF</button>
          </form>
          <hr>

          <div class="updateVAT" style="text-align: center; margin-top: 20px;">
              <button class="toggle-button" onclick="toggleSettings()" style="padding: 10px 20px; background-color: #4A90E2; color: white; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 20px;">
                  Show/Hide VAT Rate Form
              </button>

              <div class="settings" id="vatForm" style="display: none; padding: 20px 40px 20px 20px; border: 1px solid #ddd; border-radius: 8px; width: 600px; margin: 0 auto;">
                  <h2 style="color: #333; font-size: 18px; margin-bottom: 15px;">Update VAT Rate</h2>

                  <form method="post" action="vat_update.php">
                      <label for="vat_value" style="display: block; color: #555; font-weight: bold; margin-bottom: 8px;">VAT Rate (%):</label>

                      <input type="number" id="vat_value" name="vat_value" step="1" required
                          style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin:0px 15px 15px 0px;">

                      <button type="submit" style="width: 100%; padding: 10px; margin-left:7px; background-color: #4A90E2; color: white; border: none; border-radius: 5px; cursor: pointer;">
                          Update VAT Rate
                      </button>
                  </form>
              </div>
          </div>


          <script>
              // JavaScript function to toggle visibility of the VAT Rate form
              function toggleSettings() {
                  var vatForm = document.getElementById("vatForm");
                  if (vatForm.style.display === "none" || vatForm.style.display === "") {
                      vatForm.style.display = "block";
                  } else {
                      vatForm.style.display = "none";
                  }
              }
          </script>


      </div>
  </body>

  </html>