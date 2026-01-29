<?php

namespace App\Notifications;

use App\Models\Answer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnswerPostedOnYourQuestion extends Notification
{
    use Queueable;

    public function __construct(private readonly Answer $answer)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $question = $this->answer->question;
        $author = $this->answer->author;
        $snippet = str($this->answer->body_markdown)->limit(200)->toString();

        return [
            'question_id' => $question->id,
            'answer_id' => $this->answer->id,
            'actor_user_id' => $author?->id,
            'question_title' => $question->title,
            'snippet' => $snippet,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('New answer on your question')
            ->line('Someone answered your question: '.$this->answer->question->title)
            ->action('View answer', url(route('questions.show', $this->answer->question_id)));
    }
}
