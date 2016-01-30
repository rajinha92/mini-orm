<?php

use app\Models\Product;

$product = new Product();

echo '<pre>';
print_r($product->join('sku','products.sku = sku.sku')->toSql());
echo '</pre>';

 ?>
