<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branding;
use App\Models\HomepageSetting;
use Illuminate\Support\Facades\Schema;

class BrandingController extends Controller
{
    // Branding Page-er data show korar jonno
    public function index()
    {
        // Database theke 1st record-ti nibe, na thakle empty object pathabe
        $branding = Branding::first();

        // Get settings for company name display
        $settings = HomepageSetting::first();

        return view('admin.branding', compact('branding', 'settings'));
    }

    public function paymentGateway()
    {
        $branding = Branding::first();
        $settings = HomepageSetting::first();

        return view('admin.payment-gateway', compact('branding', 'settings'));
    }

    // Settings Update ba Create korar jonno
    public function update(Request $request)
    {
        // validation (optional but recommended)
        $request->validate([
            'brand_name' => 'nullable|string|max:255',
            'drive_balance' => 'nullable|in:on,off',
        ]);

        // Id 1 thakle update korbe, na thakle create korbe
        Branding::updateOrCreate(
            ['id' => 1],
            $this->filterExistingBrandingColumns($request->all())
        );

        return back()->with('success', 'Settings updated successfully!');
    }

    public function updatePaymentGateway(Request $request)
    {
        $validated = $request->validate([
            'bkash' => 'nullable|string|max:255',
            'rocket' => 'nullable|string|max:255',
            'nagad' => 'nullable|string|max:255',
            'upay' => 'nullable|string|max:255',
            'sslcommerz_store_id' => 'nullable|string|max:255',
            'sslcommerz_store_password' => 'nullable|string|max:255',
            'sslcommerz_mode' => 'nullable|in:sandbox,live',
            'amarpay_store_id' => 'nullable|string|max:255',
            'amarpay_signature_key' => 'nullable|string|max:255',
            'amarpay_mode' => 'nullable|in:sandbox,live',
        ]);

        $payload = $this->filterExistingBrandingColumns($validated);

        Branding::updateOrCreate(['id' => 1], $payload);

        $missingColumns = array_intersect($this->missingBrandingColumns($validated), [
            'sslcommerz_store_id',
            'sslcommerz_store_password',
            'sslcommerz_mode',
            'amarpay_store_id',
            'amarpay_signature_key',
            'amarpay_mode',
        ]);

        if ($missingColumns !== []) {
            return redirect()
                ->route('admin.payment.gateway')
                ->with('warning', 'Gateway credential columns are missing in the database. Manual payment numbers were saved, but SSLCommerz/AmarPay credentials need the latest migration.');
        }

        return redirect()
            ->route('admin.payment.gateway')
            ->with('success', 'Payment gateway settings updated successfully!');
    }

    protected function filterExistingBrandingColumns(array $attributes): array
    {
        if (! Schema::hasTable('brandings')) {
            return [];
        }

        $availableColumns = array_flip(Schema::getColumnListing('brandings'));

        return array_filter(
            $attributes,
            fn($key) => isset($availableColumns[$key]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    protected function missingBrandingColumns(array $attributes): array
    {
        if (! Schema::hasTable('brandings')) {
            return array_keys($attributes);
        }

        $availableColumns = array_flip(Schema::getColumnListing('brandings'));

        return array_values(array_filter(
            array_keys($attributes),
            fn($key) => ! isset($availableColumns[$key]),
        ));
    }
}
