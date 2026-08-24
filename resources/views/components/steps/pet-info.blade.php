@props(['user' => null])

<div id="step2" class="step w-full">
    <h2 class="text-center font-bold">Pet Information</h2>
    <p class="text-lg">General Information of your best friend</p>
    <div id="fastCheckinPillsSection"
         data-db-pets="{{ htmlspecialchars(json_encode(
             ($user?->pets ?? collect())->map(fn($p) => [
                 'id'       => $p->id,
                 'petName'  => $p->name,
                 'petType'  => $p->kindOfPet?->name ?? 'other',
                 'petColor' => $p->color,
                 'petBreed' => $p->race,
                 'petAge'   => $p->birth_date,
                 'petWeight'=> $p->weight,
                 'petGender'=> $p->gender?->name ?? '',
                 'petSpayed'=> $p->castrated?->name ?? '',
             ])->values()->all()
         ), ENT_QUOTES, 'UTF-8') }}">
        <p class="text-sm text-gray mb-1">Fast check-in for:</p>
        <div id="fastCheckinPillsContainer" class="pills"></div>
    </div>
    <x-forms.pet-info />
</div>
