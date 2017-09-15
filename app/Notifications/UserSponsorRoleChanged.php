<?php

namespace App\Notifications;

use App\Models\SponsorMember;
use App\Models\User;
use App\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserSponsorRoleChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public $oldValue;
    public $newValue;
    public $sponsorMember;
    public $instigator;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($oldValue, $newValue, SponsorMember $sponsorMember, User $instigator)
    {
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
        $this->sponsorMember = $sponsorMember;
        $this->instigator = $instigator;
        $this->actionUrl = route('sponsors.members.index', $sponsorMember->sponsor);
        $this->subjectText = trans(static::baseMessageLocation().'.subject', [
            'name' => $this->instigator->getDisplayName(),
            'sponsor' => $this->sponsorMember->sponsor->display_name,
            'old_role' => trans('messages.sponsor_member.roles.'.$this->oldValue),
            'new_role' => trans('messages.sponsor_member.roles.'.$this->newValue),
        ]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage($this, $notifiable))
                    ->subject($this->subjectText)
                    ->action(trans('messages.notifications.see_sponsor'), $this->actionUrl)
                    ;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'line' => $this->toLine(),
            'name' => static::getName(),
            'old_value' => $this->oldValue,
            'new_value' => $this->newValue,
            'sponsor_member_id' => $this->sponsorMember->id,
            'instigator_id' => $this->instigator->id,
        ];
    }


    public static function getName()
    {
        return 'madison.user.sponsor_role_changed';
    }

    public static function getType()
    {
        return static::TYPE_USER;
    }

    public function getInstigator()
    {
        return $this->instigator;
    }
}
