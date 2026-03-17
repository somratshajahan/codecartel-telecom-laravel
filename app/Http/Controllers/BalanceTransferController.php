<?php
namespace App\Http\Controllers;
use App\Models\BalanceTransfer;
use App\Models\HomepageSetting;
use App\Models\ServiceModule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class BalanceTransferController extends Controller
{
    protected function normalizeReceiverUsername(?string $value): string
    {
        $normalized = trim((string) $value);

        // Remove common zero-width chars that often appear from copy/paste.
        $normalized = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $normalized) ?? $normalized;

        // Collapse multiple spaces and strip optional datalist label suffix.
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s*\(.*\)\s*$/u', '', $normalized) ?? $normalized;

        // Allow users to type @username format.
        $normalized = ltrim($normalized, '@');

        return trim((string) $normalized);
    }

    protected function resolveReceiverFromInput(string $input): ?User
    {
        $normalized = $this->normalizeReceiverUsername($input);
        $normalizedLower = Str::lower($normalized);

        // 1) Username match (preferred)
        $receiver = User::query()
            ->whereNotNull('username')
            ->whereRaw('LOWER(TRIM(username)) = ?', [$normalizedLower])
            ->first();

        if ($receiver) {
            return $receiver;
        }

        // 2) Username match without internal spaces (copy/paste tolerance)
        $normalizedCompact = preg_replace('/\s+/u', '', $normalizedLower) ?? $normalizedLower;
        $receiver = User::query()
            ->whereNotNull('username')
            ->whereRaw("REPLACE(LOWER(TRIM(username)), ' ', '') = ?", [$normalizedCompact])
            ->first();

        if ($receiver) {
            return $receiver;
        }

        // 3) Fallback: mobile exact match (many old users don't have username yet)
        $mobileDigits = preg_replace('/\D+/', '', $normalized) ?? '';
        if ($mobileDigits !== '') {
            $receiver = User::query()
                ->whereNotNull('mobile')
                ->whereRaw("REPLACE(TRIM(mobile), ' ', '') = ?", [$mobileDigits])
                ->first();

            if ($receiver) {
                return $receiver;
            }
        }

        // 4) Fallback: name exact (case-insensitive)
        return User::query()
            ->whereNotNull('name')
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedLower])
            ->first();
    }

    protected function isBalanceTransferEnabled(): bool
    {
        $settings = HomepageSetting::first();
        $isEnabledInSecurity = (bool) $settings && $settings->security_balance_transfer === 'on';

        if (! Schema::hasTable('service_modules')) {
            return $isEnabledInSecurity;
        }

        $serviceModule = ServiceModule::query()
            ->get(['title', 'status'])
            ->first(function (ServiceModule $module) {
                $normalizedTitle = Str::lower((string) preg_replace('/[^a-z0-9]/i', '', (string) $module->title));

                return in_array($normalizedTitle, ['balancetransfer', 'balancetransfers'], true);
            });

        if ($serviceModule) {
            return Str::lower((string) $serviceModule->status) === 'active';
        }

        // Backward-compatibility: some old databases may miss this module row.
        // In that case, do not hard-block transfers.
        return true;
    }

    public function index()
    {
        $user = Auth::user();
        
        // Check if balance transfer is enabled from admin service module/security settings
        if (! $this->isBalanceTransferEnabled()) {
            return redirect()->back()->withErrors(['error' => 'Balance transfer is currently disabled']);
        }

        // Get recent transfers for the user
        $sentTransfers = BalanceTransfer::with(['receiver:id,name,username'])
            ->where('sender_id', $user->id)
            ->latest()
            ->get();

        $sentTransfers = $sentTransfers
            ->unique(function (BalanceTransfer $transfer) {
                return implode('|', [
                    $transfer->sender_id,
                    $transfer->receiver_id,
                    $transfer->transfer_type,
                    (string) $transfer->amount,
                    optional($transfer->created_at)->format('Y-m-d H:i:s'),
                ]);
            })
            ->take(10)
            ->values();

        $receivedTransfers = BalanceTransfer::with(['sender:id,name,username'])
            ->where('receiver_id', $user->id)
            ->latest()
            ->get();

        $receivedTransfers = $receivedTransfers
            ->unique(function (BalanceTransfer $transfer) {
                return implode('|', [
                    $transfer->sender_id,
                    $transfer->receiver_id,
                    $transfer->transfer_type,
                    (string) $transfer->amount,
                    optional($transfer->created_at)->format('Y-m-d H:i:s'),
                ]);
            })
            ->take(10)
            ->values();

        return view('user.balance-transfer', compact('sentTransfers', 'receivedTransfers'));
    }

    public function create()
    {
        if (! $this->isBalanceTransferEnabled()) {
            return redirect()->back()->withErrors(['error' => 'Balance transfer is currently disabled']);
        }

        // We no longer need to pass users since we're using username input
        return view('balance-transfer.create');
    }

    public function store(Request $request)
    {
        $wantsJson = $request->expectsJson() || $request->ajax();

        $errorResponse = static function (string $message, int $status = 422) use ($wantsJson) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], $status);
            }

            return redirect()->back()->withInput()->withErrors(['error' => $message]);
        };

        $successResponse = static function (string $message) use ($wantsJson) {
            if ($wantsJson) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('balance.transfer.index')->with('success', $message);
        };

        if (! $this->isBalanceTransferEnabled()) {
            return $errorResponse('Balance transfer is currently disabled', 403);
        }

        $request->validate([
            'receiver_username' => 'required|string',
            'transfer_type' => 'required|in:main,bank,drive',
            'amount' => 'required|numeric|min:0.01',
            'pin' => 'required|string|size:4'
        ]);

        $sender = Auth::user();
        $receiver = $this->resolveReceiverFromInput((string) $request->receiver_username);

        if (!$receiver) {
            return $errorResponse('Receiver not found');
        }

        if ($sender->id == $receiver->id) {
            return $errorResponse('You cannot transfer balance to yourself');
        }

        if (!Hash::check($request->pin, $sender->pin)) {
            return $errorResponse('Invalid PIN');
        }

        $amount = $request->amount;

        // Check sender's balance based on transfer type
        switch ($request->transfer_type) {
            case 'main':
                if ($sender->main_bal < $amount) {
                    return $errorResponse('Insufficient main balance');
                }
                break;
            case 'bank':
                if ($sender->bank_bal < $amount) {
                    return $errorResponse('Insufficient bank balance');
                }
                break;
            case 'drive':
                if ($sender->drive_bal < $amount) {
                    return $errorResponse('Insufficient drive balance');
                }
                break;
        }

        DB::beginTransaction();

        try {
            // Deduct from sender's balance
            switch ($request->transfer_type) {
                case 'main':
                    $sender->decrement('main_bal', $amount);
                    break;
                case 'bank':
                    $sender->decrement('bank_bal', $amount);
                    break;
                case 'drive':
                    $sender->decrement('drive_bal', $amount);
                    break;
            }

            // Add to receiver's selected balance type
            switch ($request->transfer_type) {
                case 'main':
                    $receiver->increment('main_bal', $amount);
                    break;
                case 'bank':
                    $receiver->increment('bank_bal', $amount);
                    break;
                case 'drive':
                    $receiver->increment('drive_bal', $amount);
                    break;
            }

            // Create a single transfer record.
            // (Previously two rows were inserted, which caused duplicate history entries.)
            BalanceTransfer::query()->create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amount,
                'transfer_type' => $request->transfer_type
            ]);

            DB::commit();

            return $successResponse('Balance transferred successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $errorResponse('Transfer failed: ' . $e->getMessage(), 500);
        }
    }

    public function checkBalance(Request $request)
    {
        if (! $this->isBalanceTransferEnabled()) {
            return response()->json(['success' => false, 'message' => 'Balance transfer is currently disabled']);
        }

        $user = Auth::user();
        
        $request->validate([
            'type' => ['required', Rule::in(['main', 'bank', 'drive'])]
        ]);
        
        $balance = 0;
        switch ($request->type) {
            case 'main':
                $balance = $user->main_bal;
                break;
            case 'bank':
                $balance = $user->bank_bal;
                break;
            case 'drive':
                $balance = $user->drive_bal;
                break;
        }
        
        return response()->json(['success' => true, 'balance' => $balance]);
    }

    public function userSuggestions(Request $request): JsonResponse
    {
        if (! $this->isBalanceTransferEnabled()) {
            return response()->json([
                'success' => false,
                'users' => [],
                'message' => 'Balance transfer is currently disabled',
            ]);
        }

        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 1) {
            return response()->json([
                'success' => true,
                'users' => [],
            ]);
        }

        $users = User::query()
            ->select(['id', 'username', 'name', 'mobile'])
            ->where('id', '!=', Auth::id())
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('username', 'like', '%' . $query . '%')
                    ->orWhere('name', 'like', '%' . $query . '%')
                    ->orWhere('mobile', 'like', '%' . $query . '%');
            })
            ->orderBy('username')
            ->limit(8)
            ->get();

        $users = $users->map(function (User $user) {
            $identifier = trim((string) ($user->username ?: $user->mobile ?: $user->name));

            return [
                'id' => $user->id,
                'username' => $identifier,
                'name' => $user->name,
                'mobile' => $user->mobile,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }
}