<?php

namespace App\Services;

use App\Enums\CustomerStatus;
use App\Enums\DocumentType;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * Finds or creates the customer behind a booking, by e-mail (case-insensitive).
 * A new e-mail is a new customer even when the phone is already known. The e-mail
 * is unique among customers, so the same e-mail with another phone is the same
 * customer: the booking keeps the phone it was made with.
 */
class CustomerMatcher
{
    /** Identity photos are private: never on the public media disk. */
    public const DOCUMENT_DISK = 'local';

    public static function normalizeEmail(?string $email): string
    {
        return Str::lower(trim((string) $email));
    }

    /**
     * @param  DocumentType|string|null  $documentType  Filament can hand over the enum instead of its value.
     */
    public function forContact(string $name, string $email, string $phone, ?string $nationalId = null, DocumentType|string|null $documentType = null): Customer
    {
        $email = static::normalizeEmail($email);
        $documentType = $documentType instanceof DocumentType ? $documentType->value : $documentType;

        // The e-mail is unique among customers: whoever has it is the candidate.
        $customer = Customer::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($customer) {
            if ($customer->trashed()) {
                $customer->restore();
            }

            // Fill what the account was missing; never overwrite what is saved.
            if (blank($customer->phone) && $phone !== '') {
                $customer->forceFill(['phone' => $phone])->save();
            }

            if (blank($customer->national_id) && filled($nationalId)) {
                $customer->forceFill(['national_id' => $nationalId, 'document_type' => $documentType ?: DocumentType::Cedula->value])->save();
            }

            return $customer;
        }

        $customer = new Customer();
        $customer->forceFill([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'national_id' => filled($nationalId) ? $nationalId : null,
            'document_type' => $documentType ?: DocumentType::Cedula->value,
            // Customers do not sign in to the site; the column only has to be filled.
            'password' => Hash::make(Str::random(40)),
            'status' => CustomerStatus::Activated,
            'customer_type' => 'individual',
        ])->save();

        return $customer;
    }

    /**
     * Saves the photos of the identity document (either side, when given) on the
     * private disk and points the customer to them. A new photo replaces the old one.
     */
    public function storeDocumentPhotos(Customer $customer, ?SymfonyFile $front = null, ?SymfonyFile $back = null): void
    {
        $disk = Storage::disk(static::DOCUMENT_DISK);

        foreach (['front' => $front, 'back' => $back] as $side => $file) {
            if (! $file instanceof SymfonyFile) {
                continue;
            }

            $column = "document_{$side}_path";
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
            $path = $disk->putFileAs(
                "customer-documents/{$customer->getKey()}",
                $file,
                $side . '-' . Str::random(12) . '.' . $extension,
            );

            if ($customer->{$column}) {
                $disk->delete($customer->{$column});
            }

            $customer->forceFill([$column => $path])->save();
        }
    }

    /**
     * Gives an account to every booking that has none (made before this existed or
     * by a guest) and links it. Returns how many bookings were linked.
     */
    public function backfill(): int
    {
        $linked = 0;

        Booking::query()
            ->where(fn ($query) => $query->whereNull('customer_id')->orWhere('customer_id', '')->orWhere('customer_id', '0'))
            ->whereNotNull('customer_email')->where('customer_email', '!=', '')
            ->orderBy('id')
            ->each(function (Booking $booking) use (&$linked): void {
                $customer = $this->forContact(
                    (string) ($booking->customer_name ?: $booking->customer_email),
                    (string) $booking->customer_email,
                    (string) $booking->customer_phone,
                );

                DB::transaction(function () use ($booking, $customer): void {
                    DB::table('cr_bookings')->where('id', $booking->getKey())->update(['customer_id' => (string) $customer->getKey()]);

                    if ($booking->payment_id) {
                        DB::table('payments')->where('id', $booking->payment_id)->whereNull('customer_id')->update(['customer_id' => $customer->getKey()]);
                    }

                    DB::table('cr_invoices')->where('reference_id', $booking->getKey())->where('customer_id', 0)->update(['customer_id' => $customer->getKey()]);
                });

                $linked++;
            });

        return $linked;
    }
}
