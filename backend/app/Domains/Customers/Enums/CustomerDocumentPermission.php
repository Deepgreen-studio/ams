<?php

namespace App\Domains\Customers\Enums;

final class CustomerDocumentPermission
{
    public const VIEW = 'customer-documents.view';

    public const CREATE = 'customer-documents.create';

    public const UPDATE = 'customer-documents.update';

    public const DELETE = 'customer-documents.delete';

    public const RESTORE = 'customer-documents.restore';

    public const DOWNLOAD = 'customer-documents.download';
}
