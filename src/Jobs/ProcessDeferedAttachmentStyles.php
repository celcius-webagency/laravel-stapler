<?php

namespace Codesleeve\LaravelStapler\Jobs;

use Codesleeve\LaravelStapler\Jobs\Job;
use Codesleeve\Stapler\ORM\StaplerableInterface;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDeferedAttachmentStyles extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $attachmentsToProcess;

    protected $instance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(StaplerableInterface $instance)
    {
        $attachments = $instance->getAttachedFiles();

        // Only handle attachments that are just uploaded and have defered styles.
        $attachmentsToProcess = array_filter($attachments, function ($attachment) {
            return $attachment->getUploadedFile() && $attachment->defer_all_except;
        });

        $this->attachmentsToProcess = array_keys($attachmentsToProcess);

        if ($this->attachmentsToProcess) {
            $this->instance = $instance;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->attachmentsToProcess as $attachmentName) {
            $attachment = $this->instance->{$attachmentName};

            $styles = array_filter($attachment->styles, function ($style) use ($attachment) {
                return !in_array($style->name, $attachment->defer_all_except);
            });

            $attachment->reprocess($styles);
        }
    }
}
