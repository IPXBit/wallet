<?php

use App\Models\Point;
use App\Models\Discount;
use App\Models\Membership;
use App\Models\PackageService;
use App\Models\SubOption;
use App\Models\SubService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


function upload($avatar, $directory)
{
    // استخدام المسار النسبي لمجلد التخزين
    $avatarName = time() . '.' . $avatar->getClientOriginalExtension();
    $avatar->move(public_path('uploads/' . $directory), $avatarName);

    // إرجاع المسار النسبي للملف
    return $directory . '/' . $avatarName;
}

// if (!function_exists('upload')) {
// function upload($avatar, $directory)
// {
//         $avatarName = time() . '.' . $avatar->getClientOriginalExtension();
//         $avatar->move($directory, $avatarName);
//         return $directory.'/'.$avatarName;

// }
// }


if (!function_exists('apply_discount')) {
    function apply_discount($nights)
    {
        if ($nights < 1) {
            return 0;
        }
        $discountValue = 0;

        if ($nights >= 7 && $nights < 28) {
            $discountValue = Discount::where('type', 'weekly')->value('value');
        } elseif ($nights >= 28) {
            $discountValue = Discount::where('type', 'monthly')->value('value');
        }

        return $discountValue ?? 0;
    }
}

/////////
// if (!function_exists('checkCoupon')) {
//     function checkCoupon($couponCode, $totalAmount)
//     {
//         $coupon = App\Models\Coupon::where('discount_code', $couponCode)->first();

//         if (!$coupon) {
//             return ['status' => false, 'message' => 'coupon not exist'];
//         }

//         $currentDate = date('Y-m-d');
//         if ($currentDate < $coupon->start_date || $currentDate > $coupon->end_date) {
//             return ['status' => false, 'message' => 'date expired in coupon'];
//         }

//         if ($coupon->max_usage !== null && $coupon->max_usage <= 0) {
//             return ['status' => false, 'message' => 'max usage reached in coupon'];
//         }

//         if ($coupon->max_discount_value !== null && $totalAmount > $coupon->max_discount_value) {
//             return ['status' => false, 'message' => 'totalAmount greater than max discount value available in coupon'];
//         }

//         // Decrement max_usage in the database
//         // if ($coupon->max_usage !== null) {
//         //     $coupon->decrement('max_usage');
//         // }

//         if ($coupon->type == 'percentage') {
//             $discount = (float) $coupon->discount_percentage;
//             $priceAfterDiscount = $totalAmount - ($totalAmount * $discount);
//         } else {
//             $discount = (int) $coupon->discount;
//             $priceAfterDiscount = $totalAmount - $discount;
//         }

//         return [
//             'status' => true,
//             'discount' => $discount,
//             'price_after_discount' => $priceAfterDiscount,
//             'id' => $coupon->id,
//         ];
//     }
// }
///////////////
if (!function_exists('checkCoupon')) {
    function checkCoupon($couponCode, $totalAmount, $serviceId = null, $categoryId = null, $isPackage = false, $subServiceId = null)
    {

        $coupon = App\Models\Coupon::where('discount_code', $couponCode)->first();

        if (!$coupon) {
            return ['status' => false, 'message' => 'Coupon does not exist'];
        }

        // Check date validity
        $currentDate = date('Y-m-d');
        if ($currentDate < $coupon->start_date || $currentDate > $coupon->end_date) {
            return ['status' => false, 'message' => 'Coupon has expired'];
        }

        // Check max usage
        if ($coupon->max_usage !== null && $coupon->max_usage <= 0) {
            return ['status' => false, 'message' => 'Max usage reached for this coupon'];
        }
        // Check Package applicability
        if (!$isPackage) {
            return ['status' => false, 'message' => 'Coupon is not applicable for this package'];
        } else {

            // Check if coupon applies to the service
            if ($serviceId !== null && $coupon->service_id != $serviceId) {

                if ($categoryId !== null && $coupon->category_id != $categoryId) {

                    return ['status' => false, 'message' => 'Coupon is not applicable to this category'];
                }
            } elseif ($subServiceId !== null && $coupon->sub_service_id != $subServiceId) {

                if ($categoryId !== null && $coupon->category_id != $categoryId) {

                    return ['status' => false, 'message' => 'Coupon is not applicable to this sub service'];
                }
            }
        }
        // Check total amount against max discount value
        if ($coupon->max_discount_value !== null && $totalAmount > $coupon->max_discount_value) {
            return ['status' => false, 'message' => 'Total amount exceeds max discount value'];
        }

        // Apply discount based on type
        if ($coupon->type == 'percentage') {
            $discount = (float) $coupon->discount_percentage;
            $priceAfterDiscount = $totalAmount - ($totalAmount * $discount / 100);
        } else {
            $discount = (int) $coupon->discount;
            $priceAfterDiscount = $totalAmount - $discount;
        }

        return [
            'status' => true,
            'discount' => $discount,
            'price_after_discount' => $priceAfterDiscount,
            'id' => $coupon->id,
        ];
    }
}



if (!function_exists('calculateRiyalsFromPoints')) {
    function calculateRiyalsFromPoints($userId)
    {
        $points = Point::where('user_id', $userId)->sum('point');
        $pointsPerRiyal = 5000;
        $amountPerRiyal = 100;

        if ($points > 0) {
            $riyals = ($points / $pointsPerRiyal) * $amountPerRiyal;
            return $riyals;
        }

        return 0;
    }
}

if (!function_exists('getOrderTotalPrice')) {
    function getOrderTotalPrice($subServiceId, $subOptionIds, $couponCode)
    {
        $subServicePrice = SubService::find($subServiceId)?->price ?? 0;
        $optionPrice = SubOption::whereIn("id", $subOptionIds)->sum("price");
        $total_price = $subServicePrice + $optionPrice;
        $data = checkCoupon($couponCode, $total_price, null, null, false, $subServiceId);
        if ($data['status'] == true) {
            $discount = $data['discount'];
            $total_price -= $discount;
        }
        return $total_price;
    }
}
//////////////////location
function pointInPolygon($point, $polygon)
{
    // pointOnVertex = true;

    // Transform string coordinates into arrays with x and y values
    $point = pointStringToCoordinates($point);
    $vertices = array();
    foreach ($polygon as $vertex) {
        $vertices[] = pointStringToCoordinates($vertex);
    }

    // Check if the point sits exactly on a vertex
    if (pointOnVertex($point, $vertices) == true) {
        return true;
    }

    // Check if the point is inside the polygon or on the boundary
    $intersections = 0;

    for ($i = 1; $i < count($vertices); $i++) {
        $vertex1 = $vertices[$i - 1];
        $vertex2 = $vertices[$i];
        if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min($vertex1['x'], $vertex2['x']) and $point['x'] < max($vertex1['x'], $vertex2['x'])) { // Check if point is on an horizontal polygon boundary
            return true;
        }
        if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max($vertex1['y'], $vertex2['y']) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) {
            $xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x'];
            if ($xinters == $point['x']) { // Check if point is on the polygon boundary (other than horizontal)
                return true;
            }
            if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters) {
                $intersections++;
            }
        }
    }
    // If the number of edges we passed through is odd, then it's in the polygon.
    if ($intersections % 2 != 0) {
        return true;
    } else {
        return false;
    }
}
function pointOnVertex($point, $vertices)
{
    foreach ($vertices as $vertex) {
        if ($point == $vertex) {
            return true;
        }
    }
}
function pointStringToCoordinates($pointString)
{
    $coordinates = explode(" ", $pointString);
    return array("x" => $coordinates[0], "y" => $coordinates[1]);
}

function get_center($coords)
{
    $count_coords = count($coords);
    $xcos = 0.0;
    $ycos = 0.0;
    $zsin = 0.0;

    foreach ($coords as $lnglat) {
        $lat = $lnglat['latitude'] * pi() / 180;
        $lon = $lnglat['longitude'] * pi() / 180;

        $acos = cos($lat) * cos($lon);
        $bcos = cos($lat) * sin($lon);
        $csin = sin($lat);
        $xcos += $acos;
        $ycos += $bcos;
        $zsin += $csin;
    }

    $xcos /= $count_coords;
    $ycos /= $count_coords;
    $zsin /= $count_coords;
    $lon = atan2($ycos, $xcos);
    $sqrt = sqrt($xcos * $xcos + $ycos * $ycos);
    $lat = atan2($zsin, $sqrt);

    return number_format($lat * 180 / pi(), 6) . ',' . number_format($lon * 180 / pi(), 6);
}
if (!function_exists('checkPoints')) {

    function checkPoints($lat, $lon)
    {
        ini_set('memory_limit', -1);
        ini_set('set_time_limit', -1);
        ini_set('max_execution_time', -1);
        ini_set('post_max_size', -1);
        ini_set('upload_max_filesize', -1);

        $point = $lat . " " . $lon;
        $check = false;
        $partner_id = 0;
        $Partners = \App\Models\PartnerProfile::all();

        foreach ($Partners as $one) {
            $polygon = [];
            $boundaries = json_decode($one->boundaries, true);

            if (is_array($boundaries) && !empty($boundaries)) {
                foreach ($boundaries as $x) {
                    foreach ($x as $o) {
                        $polygon[] = $o[0] . " " . $o[1];
                    }
                }

                if (isset($boundaries[0][0][0])) {
                    $polygon[] = $boundaries[0][0][0] . " " . $boundaries[0][0][1];
                    $check = pointInPolygon($point, $polygon);

                    if ($check) {

                        $partner_id = $one->id;
                        break;
                    }
                }
            }
        }
        if ($check && $partner_id > 0) {
            $partner = \App\Models\PartnerProfile::find($partner_id);

            if ($partner) {
                return $partner->id;
            }
            return -1;
        }
        return -1;
    }
}
////////////firebase


if (!function_exists('getGoogleAccessToken')) {
    function getGoogleAccessToken()
    {
        $credentialsFilePath = base_path('firebase-cloud-messaging.json'); // تأكد من أن المسار صحيح
        $client = new \Google_Client(); // استخدم Google_Client مباشرة بدون use statement
        $client->setAuthConfig($credentialsFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();
        return $token['access_token'];
    }
}

if (!function_exists('calculateTax')) {
    function calculateTax($price, $taxPercentage = 0)
    {
        return ($price * $taxPercentage) / 100;
    }
}

// دالة لإنشاء مجموعة
if (!function_exists('makeGroup')) {
    function makeGroup(array $registrationIds, string $notificationKeyName, $accessToken, string $operation = 'create')
    {
        $url = 'https://fcm.googleapis.com/fcm/notification';
        $projectId = "9209b6225f70c869575649091cb2147557c14f39";

        if (empty($registrationIds)) return;

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'project_id: ' . $projectId,
        ];

        $payload = [
            'operation' => $operation,
            'notification_key_name' => $notificationKeyName,
            'registration_ids' => $registrationIds,
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode == 200) {
            $response = json_decode($response);
            return $response->notification_key ?? null;
        }

        return null;
    }
}


if (!function_exists('sendFirebase')) {
    function sendFirebase($tokens, $title = null, $body = null, $Url = null, $imageUrl = null)
    {
        if (empty($tokens)) {
            return false;
        }

        $apiAccessToken = getGoogleAccessToken();
        $isGroup = false;
        $key = time();

        if (is_string($tokens)) {
            $tokens = [$tokens];
        }

        $tokens = array_values(array_filter(array_unique($tokens)));

        // Notification object for FCM
        $notification = [
            'title' => $title ?: config('app.name') . ' Notification',
            'body' => $body,
        ];

        // Data object to include additional information like URL
        $data = [
            'url' => $Url,
        ];

        // If image URL is provided, include it in the notification object
        if (isset($imageUrl) && !empty($imageUrl)) {
            $notification['image'] = $imageUrl;
        } else {
            // Use a default image URL if none is provided
            $notification['image'] = 'https://kita.rstar-soft.com/storage/images/kitaimg.jpg';
        }

        if (count($tokens) === 1) {
            $token = $tokens[0];
        } else {
            if ($tokens instanceof \Illuminate\Support\Collection) {
                $tokens = $tokens->toArray();
            }
            $token = makeGroup($tokens, $key, $apiAccessToken);
            $isGroup = true;
        }

        // Payload structure for FCM
        $payload = [
            'token' => $token,
            'notification' => $notification,
            'data' => $data, // Include the additional data here
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $apiAccessToken,
            'Content-Type' => 'application/json',
        ];

        try {
            // Use GuzzleHttp Client for the request
            $client = new \GuzzleHttp\Client(['verify' => false]);

            $response = $client->post('https://fcm.googleapis.com/v1/projects/dalil-almokattam/messages:send', [
                'headers' => $headers,
                'json' => ['message' => $payload],
            ]);

            $result = json_decode($response->getBody()->getContents());

            if (isset($result->error)) {
                throw new \Exception("Notification Error: " . json_encode($result) . " Tokens: " . json_encode($tokens));
            }

            if ($isGroup) {
                makeGroup($tokens, $key, $apiAccessToken, 'remove');
            }

            return $result;
        } catch (\Exception $ex) {
            Log::error('Firebase Notification Error: ' . $ex->getMessage());
            return false;
        }
    }
}

if (!function_exists('getPartnerThatHavePoint')) {

    function getPartnerThatHavePoint($lat, $lon)
    {
        ini_set('memory_limit', -1);
        ini_set('set_time_limit', -1);
        ini_set('max_execution_time', -1);
        ini_set('post_max_size', -1);
        ini_set('upload_max_filesize', -1);

        $point = $lat . " " . $lon;
        $Partners = \App\Models\PartnerProfile::with("services:id")->select("id", "bounders")->get();
        $sub_services_ids = [];
        foreach ($Partners as $one) {
            $polygon = [];
            $boundaries = json_decode($one->bounders, true);
            if (is_array($boundaries) && !empty($boundaries)) {
                foreach ($boundaries as $x) {
                    foreach ($x as $o) {
                        $polygon[] = $o[0] . " " . $o[1];
                    }
                }

                // Close the polygon by adding the first point again at the end
                if (isset($boundaries[0][0][0])) {
                    $polygon[] = $boundaries[0][0][0] . " " . $boundaries[0][0][1];
                    $check = pointInPolygon($point, $polygon);

                    if ($check) {
                        $sub_services_ids[] = $one->services?->pluck("id")->toArray(); // Add matching partner ID to the array
                    }
                }
            }
        }

        $mergedArray = array_merge(...$sub_services_ids);

        $uniqueArray = array_unique($mergedArray);
        return $uniqueArray;
    }

    // function isServiceInUserSubscription($serviceId)
    // {
    //     $user = Auth::guard('app_users')->user();

    //     if (!$user) {
    //         return false;
    //     }

    //     $subscriptions = $user->subscription()->where('expire_date', '>', now())->get();

    //     if ($subscriptions->isEmpty()) {
    //         return false;
    //     }

    //     foreach ($subscriptions as $subscription) {
    //         $pivotData = $subscription->pivot;

    //         if ($pivotData->visit_count == $subscription->visits) {
    //             return false;
    //             return response()->json(['error' => 'Visit count limit exceeded'], 422);
    //         }

    //         $subscriptionServices = $subscription->services;

    //         foreach ($subscriptionServices as $service) {
    //             if ($service->id == $serviceId) {
    //                 return true;
    //             }
    //         }
    //     }

    //     return false;
    // } 
    function checkUserPackageSubservice($userId, $packageId, $subServiceId)
    {
        // Check if the user is subscribed to the package
        $membership = Membership::where('user_id', $userId)
            ->where('package_id', $packageId)
            ->where('paid', 1)
            ->where('expire_date', '>=', now())
            ->first();
        if (!$membership) {
            return ['status' => false, 'message' => 'User is not subscribed to the package or the subscription has expired'];
        }

        // Check if the package includes the requested subservice
        $packageSubservice = PackageService::where('package_id', $packageId)
            ->where('sub_service_id', $subServiceId)
            ->first();

        if (!$packageSubservice) {
            return ['status' => false, 'message' => 'The requested subservice is not included in the package'];
        }

        if ($membership->visit_count >= $packageSubservice->times) {
            return ['status' => false, 'message' => 'The user has reached the usage limit for this subservice'];
        }

        // If all checks pass, allow the user to proceed
        return ['status' => true, 'message' => 'The user can proceed with the subservice'];
    }
}
