<?php

namespace App\Http\Controllers;

use App\Services\CheckInService;
use App\Services\CheckInUserService;
use App\Services\CheckInPetService;
use App\Services\CheckInTransformer;
use Illuminate\Http\Request;

/**
 * Legacy CheckInController - now acts as a facade delegating to specialized services
 *
 * @deprecated This controller is being refactored. Use CheckInFormController and CheckInApiController instead.
 */
class CheckInController extends Controller
{
    protected CheckInService $checkInService;
    protected CheckInUserService $userService;
    protected CheckInPetService $petService;
    protected CheckInTransformer $transformer;

    public function __construct(
        CheckInService $checkInService,
        CheckInUserService $userService,
        CheckInPetService $petService,
        CheckInTransformer $transformer
    ) {
        $this->checkInService = $checkInService;
        $this->userService = $userService;
        $this->petService = $petService;
        $this->transformer = $transformer;
    }

    /**
     * @deprecated Use CheckInApiController::checkUser instead
     */
    public function checkUser(Request $request)
    {
        return app(CheckInApiController::class)->checkUser($request);
    }

    /**
     * @deprecated Use CheckInFormController::newForm instead
     */
    public function newForm(Request $request)
    {
        return app(CheckInFormController::class)->newForm($request);
    }

    /**
     * @deprecated Use CheckInFormController::newFormPreFilled instead
     */
    public function newFormPreFilled(Request $request)
    {
        return app(CheckInFormController::class)->newFormPreFilled($request);
    }

    /**
     * @deprecated Use CheckInFormController::viewCheckIn instead
     */
    public function viewCheckIn(Request $request)
    {
        return app(CheckInFormController::class)->viewCheckIn($request);
    }

    /**
     * @deprecated Use CheckInApiController::submitCheckIn instead
     */
    public function submitCheckIn(Request $request)
    {
        return app(CheckInApiController::class)->submitCheckIn($request);
    }

    // All private methods have been moved to appropriate services
    // CheckInUserService, CheckInPetService, and CheckInService

    /**
     * @deprecated Use CheckInFormController::editCheckIn instead
     */
    public function editCheckIn(Request $request, $checkInId)
    {
        return app(CheckInFormController::class)->editCheckIn($request, $checkInId);
    }

    /**
     * @deprecated Use CheckInApiController::autoSaveCheckIn instead
     */
    public function autoSaveCheckIn(Request $request)
    {
        return app(CheckInApiController::class)->autoSaveCheckIn($request);
    }
}
