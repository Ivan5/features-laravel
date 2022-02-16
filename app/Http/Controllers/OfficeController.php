<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Validators\OfficeValidator;
use App\Notifications\OfficePendingApproval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OfficeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $offices = Office::query()
            ->with(['images','tags','user'])
            ->withCount(['reservations' => fn ($builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
            ->when(
                request('user_id') && auth()->user() && request('user_id') === auth()->id(),
                fn ($builder) => $builder,
                fn ($builder) => $builder->where('approval_status', Office::APPROVAL_APPROVED)
                    ->where('hidden', false)
            )
            ->when(request('user_id'), fn ($builder) => $builder->whereUserId(request('user_id')))
            ->when(request('visitor_id'), fn (Builder $builder) => $builder->whereRelation('reservations', 'user_id', '=', request('visitor_id')))
            ->when(request('lat') && request('lng'), fn ($builder) => $builder->nearestTo(request('lat'), request('lng')), fn ($builder) => $builder->orderBy('id', 'ASC'))
            ->paginate(20);

        return OfficeResource::collection($offices);
    }

    public function show(Office $office): OfficeResource
    {
        $office->loadCount(['reservations' => fn ($builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
            ->load(['images','tags','user']);

        return OfficeResource::make($office);
    }

    public function create(): JsonResource
    {
        if (! auth()->user()->tokenCan('office.create')) {
            abort(403);
        }

        $data = (new OfficeValidator())->validate($office = new Office(), request()->all());

        $data['user_id'] = auth()->id();
        $data['approval_status'] = Office::APPROVAL_PENDING;

        $office = DB::transaction(function () use ($office, $data) {
            $office->fill(Arr::except($data, ['tags']))->save();

            if (isset($attributes['tags'])) {
                $office->tags()->sync($data['tags']);
            }

            return $office;
        });

        Notification::send(User::where('is_admin', true)->get(), new OfficePendingApproval($office));

        return OfficeResource::make(
            $office->load(['images','tags','user'])
        );
    }

    public function update(Office $office): JsonResource
    {
        abort_unless(auth()->user()->tokenCan('office.update'), Response::HTTP_FORBIDDEN);

        $this->authorize('update', $office);

        $data = (new OfficeValidator())->validate($office, request()->all());

        $office->fill(Arr::except($data, ['tags']));

        if ($requiresReview = $office->isDirty(['lat','lng','price_per_day'])) {
            $office->fill(['approval_status' => Office::APPROVAL_PENDING]);
        }

        DB::transaction(function () use ($office, $data) {
            $office->save();

            if (isset($data['tags'])) {
                $office->tags()->sync($data['tags']);
            }
        });

        if ($requiresReview) {
            Notification::send(User::where('is_admin', true)->get(), new OfficePendingApproval($office));
        }

        return OfficeResource::make(
            $office->load(['images','tags','user'])
        );
    }

    public function delete(Office $office)
    {
        abort_unless(auth()->user()->tokenCan('office.delete'), Response::HTTP_FORBIDDEN);

        $this->authorize('delete', $office);

        throw_if(
            $office->reservations()->where('status', Reservation::STATUS_ACTIVE)->exists(),
            ValidationException::withMessages(['office' => 'Cannot delete this office'])
        );

        $office->images()->each(function ($image) {
            Storage::delete($image->path);

            $image->delete();
        });

        $office->delete();
    }
}
