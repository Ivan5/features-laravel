<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class OfficeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
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

    public function show(Office $office): OfficeResource
    {
        $office->loadCount(['reservations' => fn ($builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
            ->load(['images','tags','user']);
            
        return OfficeResource::make($office);
    }

    public function create(): JsonResource
    {
        if(! auth()->user()->tokenCan('office.create')){
            abort(403);
        }

        $data = validator(
            request()->all(),
            [
                'title' => ['string','required'],
                'description' => ['string','required'],
                'lat' => ['required', 'numeric'],
                'lng' => ['required', 'numeric'],
                'address_line1' => ['required', 'string'],
                'hidden' => ['bool'],
                'price_per_day' => ['required','integer','min:100'],
                'monthly_discount' => ['integer','min:0'],

                'tag' => ['array'],
                'tags.*' => ['integer', Rule::exists('tags','id')]
            ]
        )->validate();

        $data['user_id'] = auth()->id();
        $data['approval_status'] = Office::APPROVAL_PENDING;

        $office = Office::create(Arr::except($data, ['tags']));

        $office->tags()->sync($data['tags']);

        return OfficeResource::make($office);
    }
}
