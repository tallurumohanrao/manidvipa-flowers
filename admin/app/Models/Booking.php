<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = ['booking_type', 'transaction_id', 'payment_amount', 'payment_method', 'payment_status', 'amount', 'name', 'email', 'whatsapp_number', 'theater_name', 'decoration_name', 'cake_name', 'theater_id', 'theater_price', 'decoration_id', 'decoration_price', 'cake_id', 'cake_price', 'slot_id', 'booking_date', 'slot_timings', 'no_of_persons', 'booking_status_id', 'name_one_label', 'name_one', 'name_two_label', 'name_two'];

}
