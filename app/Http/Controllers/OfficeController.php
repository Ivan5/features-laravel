<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function index(Request $request)
    {
        $offices = Office::query()
            ->with(['images','tags','user'])
            ->withCount(['reservations' => fn ($builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
            ->where('approval_status', Office::APPROVAL_APPROVED)
            ->where('hidden', false)
            ->when(request('user_id'), fn ($builder) => $builder->whereUserId(request('user_id')))
            ->when(request('visitor_id'), fn (Builder $builder) => $builder->whereRelation('reservations', 'user_id', '=', request('visitor_id')))
            ->when(request('lat') && request('lng'), fn($builder) => $builder->nearestTo(request('lat'), request('lng')), fn($builder) => $builder->orderBy('id', 'ASC'))
            ->paginate(20);

        return OfficeResource::collection($offices);
    }

    public function show(Office $office)
    {
        $office->loadCount(['reservations' => fn ($builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
            ->load(['images','tags','user']);
            
        return OfficeResource::make($office);
    }
}
