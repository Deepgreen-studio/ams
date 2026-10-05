<?php

namespace App\Domains\Customers\Policies;

use App\Domains\Companies\Concerns\ChecksCompanyMembership;
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
    use ChecksCompanyMembership;

    public function viewAny(User $user): bool
    {
        return $user->can(CustomerPermission::VIEW);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::VIEW) && $this->inCompany($user, $customer);
    }

    public function create(User $user): bool
    {
        return $user->can(CustomerPermission::CREATE);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::UPDATE) && $this->inCompany($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::DELETE) && $this->inCompany($user, $customer);
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::RESTORE) && $this->inCompany($user, $customer);
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::FORCE_DELETE) && $this->inCompany($user, $customer);
    }

    public function viewTrash(User $user): bool
    {
        return $user->can(CustomerPermission::VIEW_TRASH);
    }

    public function anonymize(User $user, Customer $customer): bool
    {
        return $user->can(CustomerPermission::ANONYMIZE) && $this->inCompany($user, $customer);
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
        return $user->can(CustomerContactPermission::VIEW) && $this->inCompany($user, $contact);
    }

    public function updateContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerContactPermission::UPDATE) && $this->inCompany($user, $contact);
    }

    public function deleteContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerContactPermission::DELETE) && $this->inCompany($user, $contact);
    }

    public function restoreContact(User $user, CustomerContact $contact): bool
    {
        return $user->can(CustomerContactPermission::RESTORE) && $this->inCompany($user, $contact);
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
        return $user->can(CustomerApplicationPermission::VIEW) && $this->inCompany($user, $assignment);
    }

    public function updateApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerApplicationPermission::UPDATE) && $this->inCompany($user, $assignment);
    }

    public function deleteApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerApplicationPermission::DELETE) && $this->inCompany($user, $assignment);
    }

    public function restoreApplicationAssignment(User $user, CustomerApplication $assignment): bool
    {
        return $user->can(CustomerApplicationPermission::RESTORE) && $this->inCompany($user, $assignment);
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
        return $user->can(CustomerSubscriptionPermission::VIEW) && $this->inCompany($user, $subscription);
    }

    public function updateSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerSubscriptionPermission::UPDATE) && $this->inCompany($user, $subscription);
    }

    public function cancelSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerSubscriptionPermission::CANCEL) && $this->inCompany($user, $subscription);
    }

    public function deleteSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerSubscriptionPermission::DELETE) && $this->inCompany($user, $subscription);
    }

    public function restoreSubscription(User $user, Subscription $subscription): bool
    {
        return $user->can(CustomerSubscriptionPermission::RESTORE) && $this->inCompany($user, $subscription);
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
        return $user->can(CustomerLicensePermission::VIEW) && $this->inCompany($user, $license);
    }

    public function updateLicense(User $user, License $license): bool
    {
        return $user->can(CustomerLicensePermission::UPDATE) && $this->inCompany($user, $license);
    }

    public function revokeLicense(User $user, License $license): bool
    {
        return $user->can(CustomerLicensePermission::REVOKE) && $this->inCompany($user, $license);
    }

    public function deleteLicense(User $user, License $license): bool
    {
        return $user->can(CustomerLicensePermission::DELETE) && $this->inCompany($user, $license);
    }

    public function restoreLicense(User $user, License $license): bool
    {
        return $user->can(CustomerLicensePermission::RESTORE) && $this->inCompany($user, $license);
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
        return $user->can(CustomerDocumentPermission::VIEW) && $this->inCompany($user, $document);
    }

    public function downloadDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerDocumentPermission::DOWNLOAD) && $this->inCompany($user, $document);
    }

    public function updateDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerDocumentPermission::UPDATE) && $this->inCompany($user, $document);
    }

    public function deleteDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerDocumentPermission::DELETE) && $this->inCompany($user, $document);
    }

    public function restoreDocument(User $user, CustomerDocument $document): bool
    {
        return $user->can(CustomerDocumentPermission::RESTORE) && $this->inCompany($user, $document);
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
        return $user->can(CustomerCommunicationPermission::VIEW) && $this->inCompany($user, $item);
    }

    public function updateCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerCommunicationPermission::UPDATE) && $this->inCompany($user, $item);
    }

    public function deleteCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerCommunicationPermission::DELETE) && $this->inCompany($user, $item);
    }

    public function restoreCommunication(User $user, CustomerNote|CustomerTask|CustomerCommunication $item): bool
    {
        return $user->can(CustomerCommunicationPermission::RESTORE) && $this->inCompany($user, $item);
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
        return $user->can(CustomerAnalyticsPermission::VIEW) && $this->inCompany($user, $snapshot);
    }
}
