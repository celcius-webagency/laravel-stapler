<?php

namespace Codesleeve\LaravelStapler;

use Codesleeve\LaravelStapler\Jobs\ProcessDeferedAttachmentStyles;
use Codesleeve\Stapler\ORM\EloquentTrait;
use Codesleeve\Stapler\ORM\StaplerableInterface;
use Illuminate\Foundation\Bus\DispatchesJobs;

trait QueueingEloquentTrait {

    use EloquentTrait, DispatchesJobs;

    /**
     * The "booting" method of the model.
     */
    public static function boot()
    {
        parent::boot();

        static::bootStapler();

        static::bootQueueing();
    }

    public static function bootQueueing()
    {
        static::saved(function ($instance) {
            if (!$instance instanceof StaplerableInterface) {
                return;
            }

            $attachmentsToProcess = array_filter($instance->getAttachedFiles(), function ($attachment) {
                return $attachment->getUploadedFile() && $attachment->defer_all_except;
            });

            if ($attachmentsToProcess) {
                $job = new ProcessDeferedAttachmentStyles($instance);
                $instance->dispatch($job);
            }
        });
    }

}