<?php

use app\Models\Product;

$product = new Product();

echo 'List all<br>';
echo '<pre>';
print_r($product->all());
echo '</pre>';

echo '<br>Create Product<br>';
$product->name = 'new product';
$product->sku = 'PC';
$product->price = 5.29;
$product->save();
echo '<pre>';
print_r($product->all());
echo '</pre>';

echo '<br>Update product<br>';
$product->name = 'updated description';
$product->save();
echo '<pre>';
print_r($product->all());
echo '</pre>';

echo '<br>Delete product<br>';
$product->delete();
echo '<pre>';
print_r($product->all());
echo '</pre>';


 ?>
