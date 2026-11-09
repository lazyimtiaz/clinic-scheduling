<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentBookedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Appointment $appointment
    ) {}

    /**
     * Determine notification channels (mail, database, etc.)
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the markdown email notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $doctorName = $this->appointment->doctor->name;
        $clinicName = $this->appointment->clinic->name;
        $appointmentTime = $this->appointment->start_time->format('l, F j, Y \a\t h:i A');

        return (new MailMessage)
            ->subject("Appointment Confirmed: Ref #{$this->appointment->appointment_number}")
            ->greeting("Hello {$this->appointment->patient->first_name},")
            ->line("Your medical consultation with **{$doctorName}** at **{$clinicName}** has been confirmed.")
            ->line("**Scheduled Time:** {$appointmentTime}")
            ->line("**Reference Number:** `{$this->appointment->appointment_number}`")
            ->action('View Appointment Portal', url('/?view=patient&ref='.$this->appointment->appointment_number))
            ->line('Please arrive 10 minutes prior to your consultation for check-in.')
            ->salutation("Best regards,\n{$clinicName} Medical Team");
    }
}
