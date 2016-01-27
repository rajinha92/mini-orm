<?php

namespace app\Models;

class Product extends AbstractModel
{
	protected $table='products';
	protected $columns=[
		'name',
		'sku',
		'price'
	];

}
