<?php

namespace App\Domains\Customers\Policies;

use App\Domains\Customers\Enums\CustomerAnalyticsPermission;
use App\Domains\Customers\Enums\CustomerApplicationPermission;
use App\Domains\Customers\Enums\CustomerCommunicationPermission;
use App\Domains\Customers\Enums\CustomerContactPermission;
use App\Domains\Customers\Enums\CustomerDocumentPermission;
use App\Domains\Customers\Enums\CustomerLicensePermission;
use App\Domains\Customers\Enums\CustomerPermission;
use App\Domains\Customers\Enums\CustomerSubscriptionPermission;
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

    public function anonymize(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::ANONYMIZE);
    }

    public function exportContacts(User $user): bool
    {
        return $user->can(CustomerContactPermission::EXPORT);
    }

    public function importContacts(User $user): bool
    {
        return $user->can(CustomerContactPermission::IMPORT);
    }

    public function viewContacts(User $user): bool
    {
        return $user->can(CustomerContactPermission::VIEW);
    }

    public function manageContacts(User $user): bool
    {
        return $user->can(CustomerContactPermission::CREATE);
    }

    public function viewContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerContactPermission::VIEW);
    }

    public function updateContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerContactPermission::UPDATE);
    }

    public function deleteContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerContactPermission::DELETE);
    }

    public function restoreContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerContactPermission::RESTORE);
    }

    public function viewApplications(User $user): bool
    {
        return $user->can(CustomerApplicationPermission::VIEW);
    }

    public function manageApplications(User $user): bool
    {
        return $user->can(CustomerApplicationPermission::CREATE);
    }

    public function viewApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerApplicationPermission::VIEW);
    }

    public function updateApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerApplicationPermission::UPDATE);
    }

    public function deleteApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerApplicationPermission::DELETE);
    }

    public function restoreApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerApplicationPermission::RESTORE);
    }

    public function viewSubscriptions(User $user): bool
    {
        return $user->can(CustomerSubscriptionPermission::VIEW);
    }

    public function manageSubscriptions(User $user): bool
    {
        return $user->can(CustomerSubscriptionPermission::CREATE);
    }

    public function viewSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerSubscriptionPermission::VIEW);
    }

    public function updateSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerSubscriptionPermission::UPDATE);
    }

    public function cancelSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerSubscriptionPermission::CANCEL);
    }

    public function deleteSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerSubscriptionPermission::DELETE);
    }

    public function restoreSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerSubscriptionPermission::RESTORE);
    }

    public function viewLicenses(User $user): bool
    {
        return $user->can(CustomerLicensePermission::VIEW);
    }

    public function manageLicenses(User $user): bool
    {
        return $user->can(CustomerLicensePermission::CREATE);
    }

    public function viewLicense(User $user, License $license): bool
    {
        return $user->can(CustomerLicensePermission::VIEW);
    }

    public function updateLicense(User $user, License $license): bool
    {
        return $user->can(CustomerLicensePermission::UPDATE);
    }

    public function revokeLicense(User $user, License $license): bool
    {
        return $user->can(CustomerLicensePermission::REVOKE);
    }

    public function deleteLicense(User $user, License $license): bool
    {
        return $user->can(CustomerLicensePermission::DELETE);
    }

    public function restoreLicense(User $user, License $license): bool
    {
        return $user->can(CustomerLicensePermission::RESTORE);
    }

    public function viewDocuments(User $user): bool
    {
        return $user->can(CustomerDocumentPermission::VIEW);
    }

    public function manageDocuments(User $user): bool
    {
        return $user->can(CustomerDocumentPermission::CREATE);
    }

    public function viewDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerDocumentPermission::VIEW);
    }

    public function downloadDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerDocumentPermission::DOWNLOAD);
    }

    public function updateDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerDocumentPermission::UPDATE);
    }

    public function deleteDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerDocumentPermission::DELETE);
    }

    public function restoreDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerDocumentPermission::RESTORE);
    }

    public function viewCommunications(User $user): bool
    {
        return $user->can(CustomerCommunicationPermission::VIEW);
    }

    public function manageCommunications(User $user): bool
    {
        return $user->can(CustomerCommunicationPermission::CREATE);
    }

    public function viewCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerCommunicationPermission::VIEW);
    }

    public function updateCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerCommunicationPermission::UPDATE);
    }

    public function deleteCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerCommunicationPermission::DELETE);
    }

    public function restoreCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerCommunicationPermission::RESTORE);
    }

    public function viewAnalytics(User $user): bool
    {
        return $user->can(CustomerAnalyticsPermission::VIEW);
    }

    public function manageAnalytics(User $user): bool
    {
        return $user->can(CustomerAnalyticsPermission::REFRESH);
    }

    public function viewAnalyticsSnapshot(User $user, CustomerAnalyticsSnapshot $snapshot): bool
    {
        return $user->can(CustomerAnalyticsPermission::VIEW);
    }
}
