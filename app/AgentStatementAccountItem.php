<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentStatementAccountItem extends Model
{
    protected $fillable = [
        'agent_commission_id',
        'order_id',
        'restaurant_id',
        'restaurant_name',
        'order_number',
        'qualified_at',
        'order_amount',
        'pahatud_commission_percentage',
        'pahatud_commission_amount',
        'agent_commission_percentage',
        'agent_commission_amount',
        'commission_status',
    ];

    protected function casts(): array
    {
        return [
            'qualified_at' => 'datetime',
            'order_amount' => 'decimal:2',
            'pahatud_commission_percentage' => 'decimal:2',
            'pahatud_commission_amount' => 'decimal:2',
            'agent_commission_percentage' => 'decimal:2',
            'agent_commission_amount' => 'decimal:2',
        ];
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(AgentStatementAccount::class, 'agent_statement_account_id');
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(AgentCommission::class, 'agent_commission_id');
    }
}
