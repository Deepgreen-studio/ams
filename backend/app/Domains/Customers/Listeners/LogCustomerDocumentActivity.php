<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\CustomerDocumentDeleted;
use App\Domains\Customers\Events\CustomerDocumentRestored;
use App\Domains\Customers\Events\CustomerDocumentUpdated;
use App\Domains\Customers\Events\CustomerDocumentUploaded;
use App\Domains\Customers\Events\CustomerDocumentVersionUploaded;

class LogCustomerDocumentActivity
{
    public function handleCustomerDocumentUploaded(CustomerDocumentUploaded $event): void
    {
        activity('customer_documents')
            ->causedBy($event->actor)
            ->performedOn($event->document)
            ->withProperties([
                'event' => 'customer_document_uploaded',
                'customer_id' => $event->document->customer_id,
                'category' => $event->document->category?->value ?? $event->document->category,
                'version' => $event->document->version,
                'original_filename' => $event->document->original_filename,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer document uploaded');
    }

    public function handleCustomerDocumentVersionUploaded(CustomerDocumentVersionUploaded $event): void
    {
        activity('customer_documents')
            ->causedBy($event->actor)
            ->performedOn($event->document)
            ->withProperties([
                'event' => 'customer_document_version_uploaded',
                'document_group_uuid' => $event->document->document_group_uuid,
                'version' => $event->document->version,
                'original_filename' => $event->document->original_filename,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer document version uploaded');
    }

    public function handleCustomerDocumentUpdated(CustomerDocumentUpdated $event): void
    {
        activity('customer_documents')
            ->causedBy($event->actor)
            ->performedOn($event->document)
            ->withProperties([
                'event' => 'customer_document_updated',
                'category' => $event->document->category?->value ?? $event->document->category,
                'status' => $event->document->status?->value ?? $event->document->status,
                'expires_at' => $event->document->expires_at,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer document updated');
    }

    public function handleCustomerDocumentDeleted(CustomerDocumentDeleted $event): void
    {
        activity('customer_documents')
            ->causedBy($event->actor)
            ->performedOn($event->document)
            ->withProperties([
                'event' => 'customer_document_archived',
                'customer_id' => $event->document->customer_id,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer document archived');
    }

    public function handleCustomerDocumentRestored(CustomerDocumentRestored $event): void
    {
        activity('customer_documents')
            ->causedBy($event->actor)
            ->performedOn($event->document)
            ->withProperties([
                'event' => 'customer_document_restored',
                'customer_id' => $event->document->customer_id,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer document restored');
    }
}
