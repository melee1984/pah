<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerVoucher extends Model
{
    protected $table = 'partner_voucher';
	protected $fillable = array('partner_id', 'amount','expire_at', 'begin_at', 'limit', 'active');
	public $timestamps = true;
}
