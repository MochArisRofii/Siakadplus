<?php

namespace App\Repositories\Subscription;

use App\Models\Subscription;
use App\Repositories\Saas\SaaSRepository;
use App\Services\CachingService;
use Carbon\Carbon;

class SubscriptionRepository extends SaaSRepository implements SubscriptionInterface {
    public function __construct(Subscription $model) {
        parent::__construct($model);
    }

    public function default()
    {
        $today_date = Carbon::now()->format('Y-m-d');
        
        return $this->defaultModel()->where('start_date','<=',$today_date)->where('end_date','>=',$today_date)->doesntHave('subscription_bill');
    }
}
