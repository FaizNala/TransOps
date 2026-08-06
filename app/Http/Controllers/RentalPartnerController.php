<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\RentalPartner;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RentalPartnerController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->query('filter', []);

        $partners = RentalPartner::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return inertia('master/rental-partners', [
            'rental_partners' => $partners,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $partner = RentalPartner::create($data);

        AuditLog::create($this->audit($request, 'created', null, $partner->toArray()));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Partner {$partner->name} berhasil dibuat.",
        ]);

        return redirect()->route('rental-partners.index');
    }

    public function update(Request $request, RentalPartner $rentalPartner)
    {
        $data = $this->validated($request);

        $rentalPartner->update($data);

        AuditLog::create($this->audit($request, 'updated', null, $rentalPartner->toArray()));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Partner {$rentalPartner->name} diperbarui.",
        ]);

        return back();
    }

    public function destroy(Request $request, RentalPartner $rentalPartner)
    {
        AuditLog::create($this->audit($request, 'deleted', $rentalPartner->toArray(), null));

        $rentalPartner->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Partner {$rentalPartner->name} dihapus.",
        ]);

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function audit(Request $request, string $action, ?array $before, ?array $after): array
    {
        return [
            'user_id' => $request->user()->id,
            'action' => "rental_partner.{$action}",
            'entity_type' => 'rental_partner',
            'entity_id' => $request->route('rentalPartner')?->id,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
