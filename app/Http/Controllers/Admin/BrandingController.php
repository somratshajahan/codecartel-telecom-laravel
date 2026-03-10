<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branding;
use App\Models\BrandingSlide;
use App\Models\DepositSetting;
use App\Models\HomepageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    // Branding Page-er data show korar jonno
    public function index()
    {
        // Database theke 1st record-ti nibe, na thakle empty object pathabe
        $branding = Branding::first();
        $brandingSlides = collect();

        if (Schema::hasTable('branding_slides')) {
            $brandingSlides = BrandingSlide::query()
                ->orderBy('slot_number')
                ->get()
                ->keyBy('slot_number');
        }

        // Get settings for company name display
        $settings = HomepageSetting::first();

        return view('admin.branding', compact('branding', 'settings', 'brandingSlides'));
    }

    public function paymentGateway()
    {
        $branding = Branding::first();
        $settings = HomepageSetting::first();

        return view('admin.payment-gateway', compact('branding', 'settings'));
    }

    public function deposit()
    {
        $settings = HomepageSetting::first();

        $depositLevels = DepositSetting::rowsForDisplay();
        $depositSettingsTableExists = Schema::hasTable('deposit_settings');

        return view('admin.deposit', compact('settings', 'depositLevels', 'depositSettingsTableExists'));
    }

    public function updateDeposit(Request $request)
    {
        if (! Schema::hasTable('deposit_settings')) {
            return redirect()
                ->route('admin.deposit')
                ->with('warning', 'Deposit settings table is missing. Please run the latest migration first.');
        }

        $rules = ['deposit_levels' => ['required', 'array']];

        foreach (DepositSetting::defaultRows() as $row) {
            foreach (DepositSetting::editableColumns() as $column) {
                $rules['deposit_levels.' . $row['level'] . '.' . $column] = ['required', 'numeric'];
            }
        }

        $validated = $request->validate($rules);

        foreach (DepositSetting::defaultRows() as $row) {
            $rowData = $validated['deposit_levels'][$row['level']] ?? [];
            $payload = [
                'runtime_level' => $row['runtime_level'],
                'level_name' => $row['level_name'],
                'sort_order' => $row['sort_order'],
            ];

            foreach (DepositSetting::editableColumns() as $column) {
                $payload[$column] = round((float) ($rowData[$column] ?? 0), 2);
            }

            DepositSetting::updateOrCreate(['level' => $row['level']], $payload);
        }

        return redirect()
            ->route('admin.deposit')
            ->with('success', 'Deposit settings updated successfully!');
    }

    // Settings Update ba Create korar jonno
    public function update(Request $request)
    {
        // validation (optional but recommended)
        $request->validate([
            'brand_name' => 'nullable|string|max:255',
            'drive_balance' => 'nullable|in:on,off',
            'slides' => 'nullable|array|max:14',
            'slides.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        // Id 1 thakle update korbe, na thakle create korbe
        Branding::updateOrCreate(
            ['id' => 1],
            $this->filterExistingBrandingColumns($request->except('slides'))
        );

        $slideWarning = $this->syncBrandingSlides($request);

        if ($slideWarning !== null) {
            return back()
                ->with('warning', $slideWarning)
                ->with('success', 'Settings updated successfully!');
        }

        return back()->with('success', 'Settings updated successfully!');
    }

    protected function syncBrandingSlides(Request $request): ?string
    {
        if (! $request->hasFile('slides')) {
            return null;
        }

        if (! Schema::hasTable('branding_slides')) {
            return 'Slideshow image table is missing. Please run the latest migration first.';
        }

        foreach (range(1, 14) as $slotNumber) {
            if (! $request->hasFile("slides.{$slotNumber}")) {
                continue;
            }

            $slide = BrandingSlide::firstOrNew(['slot_number' => $slotNumber]);

            if ($slide->exists && filled($slide->image_path)) {
                Storage::disk('public')->delete($slide->image_path);
            }

            $slide->fill([
                'image_path' => $request->file("slides.{$slotNumber}")->store('branding-slides', 'public'),
                'is_active' => true,
            ]);

            $slide->save();
        }

        return null;
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
