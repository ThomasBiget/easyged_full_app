<?php

namespace App\Models;

class LineItem
{
    public ?int $id = null;
    public int $invoice_id;
    public string $description;
    public float $quantity;
    public float $unit_price;
    public float $total_price;
    public bool $verified = false;
    public ?string $created_at = null;
}

