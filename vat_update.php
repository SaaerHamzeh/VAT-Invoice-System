<?php
define('DOC_ROOT', realpath(dirname(__FILE__) . '/./'));
function my_autoloader($class_name)
{
    require DOC_ROOT . '/classes/' . strtolower($class_name) . '.php';
}
spl_autoload_register('my_autoloader');

$db = Database::getDatabase();

$new_vat_value = $_POST['vat_value'];
$current_date = date("Y-m-d");


$update_query = "UPDATE vat_rates SET end_date = :current_date WHERE end_date IS NULL";
$db->query($update_query, array('current_date' => $current_date));

$insert_query = "INSERT INTO vat_rates (vat_value, start_date) VALUES (:new_vat_value, :current_date)";
$db->query($insert_query, array('new_vat_value' => $new_vat_value, 'current_date' => $current_date));


// echo "VAT value updated successfully!";
header("location: index.php");
exit;
