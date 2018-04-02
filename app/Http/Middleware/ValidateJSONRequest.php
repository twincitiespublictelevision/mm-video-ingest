<?php

namespace App\Http\Middleware;

use Closure;

class ValidateJSONRequest {

  /**
   * Handle an incoming request. Checks to ensure that the request specified
   * the correct content type and supplied decodable content
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next) {

    // Only apply to write requests
    if ($request->isMethod('POST')) {

      // Check that the correct content type header was sent
      if (0 !== strpos($request->header('content-type'), 'application/json')) {
        return response()->json('Invalid content-type specified', 415);
      }

      // Temporarily decode the POST content
      json_decode($request->getContent());

      // Check to see if a JSON decode error has occured
      if (json_last_error() != JSON_ERROR_NONE) {
        return response()->json('Invalid JSON supplied', 400);
      }
    }

    return $next($request);
  }
}