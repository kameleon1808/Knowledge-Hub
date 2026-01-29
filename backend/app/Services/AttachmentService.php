<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Attachment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    public function storeForQuestion(Question $question, array $files, User $user): Collection
    {
        return $this->storeForAttachable($question, "questions/{$question->id}", $files, $user);
    }

    public function storeForAnswer(Answer $answer, array $files, User $user): Collection
    {
        return $this->storeForAttachable($answer, "answers/{$answer->id}", $files, $user);
    }

    public function deleteByIds(Model $attachable, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $attachments = $attachable->attachments()
            ->whereIn('id', $ids)
            ->get();

        $this->deleteAttachments($attachments);
    }

    public function deleteForAttachable(Model $attachable): void
    {
        $this->deleteAttachments($attachable->attachments()->get());
    }

    public function deleteForQuestion(Question $question): void
    {
        $question->load('answers.attachments');

        $this->deleteForAttachable($question);

        foreach ($question->answers as $answer) {
            $this->deleteAttachments($answer->attachments);
        }
    }

    private function storeForAttachable(Model $attachable, string $directory, array $files, User $user): Collection
    {
        $disk = config('attachments.disk', 'public');
        $stored = collect();

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $filename, $disk);

            $stored->push($attachable->attachments()->create([
                'user_id' => $user->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]));
        }

        return $stored;
    }

    private function deleteAttachments(Collection $attachments): void
    {
        foreach ($attachments as $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
        }
    }
}
