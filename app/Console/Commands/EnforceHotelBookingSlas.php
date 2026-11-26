<?php

namespace App\Console\Commands;

use App\Models\HotelBooking;
use Illuminate\Console\Command;

class EnforceHotelBookingSlas extends Command
{
    protected $signature = 'kanguro:enforce-hotel-slas';

    protected $description = 'Cancela reservas de hotel sem confirmação do parceiro';

    public function handle(): int
    {
        $pending = HotelBooking::query()
            ->where('status', HotelBooking::STATUS_PENDING)
            ->whereNotNull('confirm_deadline_at')
            ->where('confirm_deadline_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($pending as $booking) {
            $booking->update([
                'status' => HotelBooking::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'payment_status' => $booking->payment_status === 'completed' ? 'refunded' : 'failed',
                'reject_reason' => $booking->reject_reason ?: 'Sem resposta do hotel (SLA)',
            ]);
            $count++;
        }

        $this->info("Hotel SLA: {$count} reserva(s) cancelada(s).");

        return self::SUCCESS;
    }
}
