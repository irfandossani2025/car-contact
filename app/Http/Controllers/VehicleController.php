<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VehicleController extends Controller
{
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'plate' => ['nullable', 'string', 'max:20'],
            'color' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->vehicles()->count() >= 10, 422, 'Vehicle limit reached.');

        $request->user()->vehicles()->create($request->validate($this->rules()));

        return back()->with('status', 'Car added. Download its QR sticker below.');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeOwner($request, $vehicle);
        $vehicle->update($request->validate($this->rules()));

        return back()->with('status', 'Car updated.');
    }

    public function toggle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeOwner($request, $vehicle);
        $vehicle->update(['is_active' => ! $vehicle->is_active]);

        return back()->with('status', $vehicle->is_active ? 'Alerts resumed for '.$vehicle->name.'.' : 'Alerts paused for '.$vehicle->name.'.');
    }

    public function regenerate(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeOwner($request, $vehicle);
        $vehicle->forceFill(['tag_code' => Vehicle::generateUniqueCode()])->save();

        return back()->with('status', 'New QR code created. Your old sticker no longer works — print the new one.');
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeOwner($request, $vehicle);
        $vehicle->delete();

        return back()->with('status', 'Car removed.');
    }

    public function sticker(Request $request, Vehicle $vehicle, QrCodeService $qr): Response
    {
        $this->authorizeOwner($request, $vehicle);

        $pdf = Pdf::loadView('pdf.sticker', [
            'vehicle' => $vehicle,
            'qr' => $qr->pngDataUri($vehicle->scanUrl()),
            'shortUrl' => preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')).'/t/'.$vehicle->tag_code,
        ])->setPaper('letter');

        return $pdf->download('contact-scan-'.$vehicle->tag_code.'.pdf');
    }

    private function authorizeOwner(Request $request, Vehicle $vehicle): void
    {
        abort_unless($vehicle->user_id === $request->user()->id, 404);
    }
}
