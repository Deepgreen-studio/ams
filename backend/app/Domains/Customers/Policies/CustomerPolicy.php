<?php

namespace App\Domains\Customers\Policies;

use App\Domains\Customers\Enums\CustomerPermission;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerAnalyticsSnapshot;
use App\Domains\Customers\Models\CustomerApplication;
use App\Domains\Customers\Models\CustomerCommunication;
use App\Domains\Customers\Models\CustomerContact;
use App\Domains\Customers\Models\CustomerDocument;
use App\Domains\Customers\Models\CustomerNote;
use App\Domains\Customers\Models\CustomerTask;
use App\Domains\Customers\Models\License;
use App\Domains\Customers\Models\Subscription;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(CustomerPermission::CREATE);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::UPDATE);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::DELETE);
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::RESTORE) || $user->can(CustomerPermission::DELETE);
    }

    public function viewContacts(User $user): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function manageContacts(User $user): bool
    {
        return $user->can(CustomerPermission::UPDATE) || $user->can(CustomerPermission::CREATE);
    }

    public function viewContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function updateContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerPermission::UPDATE);
    }

    public function deleteContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerPermission::DELETE) || $user->can(CustomerPermission::UPDATE);
    }

    public function restoreContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerPermission::RESTORE)
            || $user->can(CustomerPermission::DELETE)
            || $user->can(CustomerPermission::UPDATE);
    }

    public function viewApplications(User $user): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function manageApplications(User $user): bool
    {
        return $user->can(CustomerPermission::UPDATE) || $user->can(CustomerPermission::CREATE);
    }

    public function viewApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function updateApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerPermission::UPDATE);
    }

    public function deleteApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerPermission::DELETE) || $user->can(CustomerPermission::UPDATE);
    }

    public function restoreApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerPermission::RESTORE)
            || $user->can(CustomerPermission::DELETE)
            || $user->can(CustomerPermission::UPDATE);
    }

    public function viewSubscriptions(User $user): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function manageSubscriptions(User $user): bool
    {
        return $user->can(CustomerPermission::UPDATE) || $user->can(CustomerPermission::CREATE);
    }

    public function viewSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function updateSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerPermission::UPDATE);
    }

    public function deleteSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerPermission::DELETE) || $user->can(CustomerPermission::UPDATE);
    }

    public function restoreSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerPermission::RESTORE)
            || $user->can(CustomerPermission::DELETE)
            || $user->can(CustomerPermission::UPDATE);
    }

    public function viewLicenses(User $user): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function manageLicenses(User $user): bool
    {
        return $user->can(CustomerPermission::UPDATE) || $user->can(CustomerPermission::CREATE);
    }

    public function viewLicense(User $user, License $license): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function updateLicense(User $user, License $license): bool
    {
        return $user->can(CustomerPermission::UPDATE);
    }

    public function deleteLicense(User $user, License $license): bool
    {
        return $user->can(CustomerPermission::DELETE) || $user->can(CustomerPermission::UPDATE);
    }

    public function restoreLicense(User $user, License $license): bool
    {
        return $user->can(CustomerPermission::RESTORE)
            || $user->can(CustomerPermission::DELETE)
            || $user->can(CustomerPermission::UPDATE);
    }

    public function viewDocuments(User $user): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function manageDocuments(User $user): bool
    {
        return $user->can(CustomerPermission::UPDATE) || $user->can(CustomerPermission::CREATE);
    }

    public function viewDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function updateDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerPermission::UPDATE);
    }

    public function deleteDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerPermission::DELETE) || $user->can(CustomerPermission::UPDATE);
    }

    public function restoreDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerPermission::RESTORE)
            || $user->can(CustomerPermission::DELETE)
            || $user->can(CustomerPermission::UPDATE);
    }

    public function viewCommunications(User $user): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function manageCommunications(User $user): bool
    {
        return $user->can(CustomerPermission::UPDATE) || $user->can(CustomerPermission::CREATE);
    }

    public function viewCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function updateCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerPermission::UPDATE);
    }

    public function deleteCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerPermission::DELETE) || $user->can(CustomerPermission::UPDATE);
    }

    public function restoreCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerPermission::RESTORE)
            || $user->can(CustomerPermission::DELETE)
            || $user->can(CustomerPermission::UPDATE);
    }

    public function viewAnalytics(User $user): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function manageAnalytics(User $user): bool
    {
        return $user->can(CustomerPermission::UPDATE) || $user->can(CustomerPermission::CREATE);
    }

    public function viewAnalyticsSnapshot(User $user, CustomerAnalyticsSnapshot $snapshot): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }
}
