<?php

use app\Models\Product;

$product = new Product();

echo '<pre>';
print_r($product->all());
echo '</pre>';

 ?>
