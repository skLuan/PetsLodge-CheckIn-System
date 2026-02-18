<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CheckInSummary extends Component
{
    public $checkinData;

    /**
     * Create a new component instance.
     */
    public function __construct($checkinData = [])
    {
        $this->checkinData = $checkinData;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.CheckInSummary');
    }
}
