<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerMatcher;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams the identity photos of a customer to logged-in staff. The files live on
 * a private disk, so they are never reachable by a direct URL.
 */
class CustomerDocumentController extends Controller
{
    public function show(Customer $customer, string $side): Response
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);

        $path = $customer->{"document_{$side}_path"};
        $disk = Storage::disk(CustomerMatcher::DOCUMENT_DISK);

        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path);
    }
}
