<?php

namespace App\Helpers;

class Common {
    public static function apiResponse(
        bool $success,
        $message,
        $data = null,
        $statusCode = null,
        $paginates = null,
        bool $isPagination = false
    ) {
        $statusCode = $statusCode ?? ($success ? 200 : 422);

        $response = [
            'success' => $success,
            'message' => __($message),
        ];

        if ($isPagination && $paginates instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $response = array_merge($response, [
                'data' => $data,
                'pagination' => [
                    'total' => $paginates->total(),
                    'count' => $paginates->count(),
                    'per_page' => $paginates->perPage(),
                    'current_page' => $paginates->currentPage(),
                    'total_pages' => $paginates->lastPage(),
                ]
            ]);
        } else {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }
}
