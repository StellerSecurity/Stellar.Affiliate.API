<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

final class AffiliateRequestContext
{
    public static function isOrderPaid(Request $request): bool
    {
        $route = $request->route();

        if ($route instanceof Route) {
            if ($route->getName() === 'affiliate.events.order_paid') {
                return true;
            }

            if (trim($route->uri(), '/') === 'api/v1/affiliate/events/order-paid') {
                return true;
            }
        }

        return $request->is('api/v1/affiliate/events/order-paid')
            || $request->is('v1/affiliate/events/order-paid');
    }
}
