<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    use ApiResponse;

    public function getDisclaimersPolicy()
    {
        $policy = Policy::where('type', 'disclaimers')->first();

        if (!$policy) {
            return $this->error(null, 'Disclaimers policy not found', 404);
        }

        $policy = [
            'id'      => $policy->id,
            'type'    => $policy->type,
            'content' => $policy->content,
        ];

        return $this->success($policy, 'Disclaimers policy retrieved successfully');
    }

    public function getBeachPolicy()
    {
        // Fallback method - returns disclaimers if beach policy not found
        return $this->getDisclaimersPolicy();
    }
}
