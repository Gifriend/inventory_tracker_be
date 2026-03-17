<?php
 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponse; // Ensure this import exists when using the trait

class RoleMiddleware
{
    use ApiResponse; // Use standardized API response format

    public function handle(Request $request, Closure $next, $role): Response
    {
        // Using $request->user() is more IDE / Intelephense friendly
        $user = $request->user();

        // Check whether user is not logged in OR role does not match
        if (!$user || $user->role !== $role) {
            
            // Use helper from ApiResponse trait
            return $this->errorResponse('Forbidden. Akses ditolak.', 403);
            
            // NOTE: If you do not use ApiResponse trait, use this fallback line:
            // return response()->json(['status' => 'error', 'message' => 'Forbidden. Access denied.'], 403);
        }

        return $next($request);
    }
}